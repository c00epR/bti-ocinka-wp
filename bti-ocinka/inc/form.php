<?php
/**
 * Форма заявки: REST-ендпоінт /wp-json/bti/v1/lead
 * Надсилає на email і в Telegram, а також зберігає в адмінці («Заявки»), щоб жодна не загубилась.
 */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('bti_lead', [
        'labels' => ['name' => 'Заявки', 'singular_name' => 'Заявка', 'menu_name' => 'Заявки'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-phone', 'menu_position' => 3,
        'supports' => ['title', 'editor'], 'capability_type' => 'post', 'capabilities' => ['create_posts' => 'do_not_allow'], 'map_meta_cap' => true,
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('bti/v1', '/lead', [
        'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'bti_rest_lead',
    ]);
});

function bti_rest_lead(WP_REST_Request $r) {
    if (trim((string)$r->get_param('website')) !== '') return ['ok' => true];   // бот
    $name  = sanitize_text_field(mb_substr((string)$r->get_param('name'), 0, 80));
    $phone = sanitize_text_field(mb_substr((string)$r->get_param('phone'), 0, 40));
    $page  = sanitize_text_field(mb_substr((string)$r->get_param('page'), 0, 200));
    if ($name === '' || strlen(preg_replace('/\D+/', '', $phone)) < 9) {
        return new WP_REST_Response(['ok' => false, 'error' => 'validation'], 400);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    $key = 'bti_lead_' . md5($ip);
    if (get_transient($key)) return new WP_REST_Response(['ok' => false, 'error' => 'rate'], 429);
    set_transient($key, 1, 30);

    $ok = bti_send_lead(compact('name', 'phone', 'page'));
    return new WP_REST_Response(['ok' => $ok, 'error' => $ok ? '' : 'deliver'], $ok ? 200 : 500);
}

function bti_send_lead(array $l): bool {
    $when = wp_date('d.m.Y H:i');
    $text = "Нова заявка з сайту\n\nІм'я: {$l['name']}\nТелефон: {$l['phone']}\nСторінка: {$l['page']}\nЧас: $when";

    // 1) зберегти в адмінці — завжди
    $saved = wp_insert_post(['post_type' => 'bti_lead', 'post_status' => 'private', 'post_title' => "{$l['name']} — {$l['phone']}", 'post_content' => $text]);
    $sent = false;

    // 2) Telegram
    $token = bti_opt('tg_token'); $chat = bti_opt('tg_chat');
    if ($token && $chat) {
        $res = wp_remote_post("https://api.telegram.org/bot{$token}/sendMessage", ['timeout' => 8, 'body' => ['chat_id' => $chat, 'text' => $text]]);
        $sent = !is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200;
    }

    // 3) Email
    $to = bti_opt('mail_to');
    if ($to && wp_mail($to, "Заявка з сайту: {$l['name']}, {$l['phone']}", $text)) $sent = true;

    return $sent || (bool)$saved;
}

/* Лист від імені домену — менше шансів потрапити в спам */
add_filter('wp_mail_from', fn($f) => str_starts_with($f, 'wordpress@') ? 'no-reply@' . wp_parse_url(home_url(), PHP_URL_HOST) : $f);
add_filter('wp_mail_from_name', fn($n) => $n === 'WordPress' ? bti_opt('brand') : $n);

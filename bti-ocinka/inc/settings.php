<?php
/**
 * «Налаштування → Сайт БТІ»: контакти, рейтинг, Telegram для заявок, відгуки.
 */
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_options_page('Сайт БТІ', 'Сайт БТІ', 'manage_options', 'bti-settings', 'bti_settings_page');
});

function bti_settings_fields(): array {
    return [
        'Контакти' => [
            'phone' => 'Телефон (як показувати)', 'phone_raw' => 'Телефон для посилання tel: (+380...)', 'email' => 'Email на сайті',
            'address' => 'Адреса (повна)', 'street' => 'Вулиця й офіс (для schema.org)', 'postal' => 'Індекс', 'hours' => 'Графік роботи',
            'tg_link' => 'Посилання Telegram', 'viber_link' => 'Посилання Viber', 'maps_url' => 'Посилання на Google Карти',
            'map_embed' => 'Карта (src iframe з Google Maps)', 'lat' => 'Широта', 'lng' => 'Довгота',
        ],
        'Рейтинг Google' => ['rating_value' => 'Оцінка (напр. 4.6)', 'rating_count' => 'Кількість відгуків'],
        'Головна сторінка (SEO)' => ['home_title' => 'Title головної', 'home_desc' => 'Description головної'],
        'Заявки з сайту' => ['mail_to' => 'Email, куди надходять заявки', 'tg_token' => 'Telegram: токен бота (від @BotFather)', 'tg_chat' => 'Telegram: chat_id'],
    ];
}

function bti_settings_page() {
    if (!current_user_can('manage_options')) return;
    if (isset($_POST['bti_save']) && check_admin_referer('bti_settings')) {
        $in = wp_unslash($_POST['s'] ?? []);
        $clean = [];
        foreach (bti_settings_fields() as $group) foreach ($group as $k => $_) $clean[$k] = sanitize_text_field($in[$k] ?? '');
        $clean['map_embed'] = esc_url_raw($in['map_embed'] ?? '');
        update_option('bti_settings', $clean);
        update_option('bti_reviews_text', sanitize_textarea_field(wp_unslash($_POST['reviews'] ?? '')));
        echo '<div class="notice notice-success"><p>Збережено.</p></div>';
    }
    if (isset($_POST['bti_test']) && check_admin_referer('bti_settings')) {
        $ok = bti_send_lead(['name' => 'Тест', 'phone' => '+380000000000', 'page' => 'Перевірка з адмінки']);
        echo '<div class="notice ' . ($ok ? 'notice-success' : 'notice-error') . '"><p>' . ($ok ? 'Тестову заявку надіслано — перевірте пошту/Telegram.' : 'Не вдалося надіслати. Перевірте email і налаштування Telegram.') . '</p></div>';
    }
    $saved = get_option('bti_settings', []);
    echo '<div class="wrap"><h1>Сайт БТІ — налаштування</h1><form method="post">';
    wp_nonce_field('bti_settings');
    foreach (bti_settings_fields() as $title => $fields) {
        echo '<h2>' . esc_html($title) . '</h2><table class="form-table">';
        foreach ($fields as $k => $label) {
            $val = $saved[$k] ?? (in_array($k, ['tg_token', 'tg_chat'], true) ? '' : bti_defaults()[$k] ?? '');
            $type = $k === 'tg_token' ? 'password' : 'text';
            printf('<tr><th><label for="s_%1$s">%2$s</label></th><td><input class="large-text" type="%4$s" id="s_%1$s" name="s[%1$s]" value="%3$s" autocomplete="off"></td></tr>', esc_attr($k), esc_html($label), esc_attr($val), $type);
        }
        echo '</table>';
    }
    if (bti_opt('tg_token') === '' || bti_opt('tg_chat') === '') {
        echo '<p><em>Як підключити Telegram: створіть бота в @BotFather і вставте токен; напишіть боту будь-що (або додайте в групу); відкрийте https://api.telegram.org/bot&lt;ТОКЕН&gt;/getUpdates і скопіюйте chat.id.</em></p>';
    }
    $rev = get_option('bti_reviews_text', '');
    if ($rev === '') $rev = implode("\n\n", array_map(fn($r) => $r[0] . "\n" . $r[2], bti_reviews()));
    echo '<h2>Відгуки</h2><p>Ім\'я — рядок, текст відгуку — наступний рядок, між відгуками порожній рядок.</p><textarea name="reviews" rows="14" class="large-text">' . esc_textarea($rev) . '</textarea>';
    echo '<p class="submit"><button class="button button-primary" name="bti_save" value="1">Зберегти</button> <button class="button" name="bti_test" value="1">Надіслати тестову заявку</button></p></form></div>';
}

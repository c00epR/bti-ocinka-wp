<?php
/**
 * Автоімпорт: при активації теми створює всі сторінки з data/pages.json,
 * ставить головну, сторінку блогу, ЧПУ та базові налаштування.
 * Повторний запуск безпечний: існуючі сторінки не перезаписуються.
 * Вручну: «Інструменти → Імпорт сторінок БТІ».
 */
if (!defined('ABSPATH')) exit;

add_action('after_switch_theme', function () {
    if (!get_option('bti_imported')) bti_run_import();
});

add_action('admin_menu', function () {
    add_management_page('Імпорт сторінок БТІ', 'Імпорт сторінок БТІ', 'manage_options', 'bti-import', function () {
        echo '<div class="wrap"><h1>Імпорт сторінок БТІ</h1>';
        if (isset($_POST['bti_import']) && check_admin_referer('bti_import')) {
            $r = bti_run_import(!empty($_POST['update_meta']));
            printf('<div class="notice notice-success"><p>Готово. Створено: %d, оновлено полів: %d, пропущено (вже були): %d.</p></div>', $r['created'], $r['updated'], $r['skipped']);
        }
        echo '<p>Створює відсутні сторінки послуг із вбудованого набору. Існуючі сторінки та їхні тексти не змінюються.</p><form method="post">';
        wp_nonce_field('bti_import');
        echo '<p><label><input type="checkbox" name="update_meta" value="1"> Також заповнити <b>порожні</b> поля (ціна, FAQ тощо) в існуючих сторінках</label></p>';
        echo '<p><button class="button button-primary" name="bti_import" value="1">Запустити імпорт</button></p></form></div>';
    });
});

function bti_run_import(bool $fill_empty = false): array {
    $file = BTI_DIR . '/data/pages.json';
    $res = ['created' => 0, 'updated' => 0, 'skipped' => 0];
    if (!is_readable($file)) return $res;
    $data = json_decode(file_get_contents($file), true);
    if (!$data) return $res;

    $ids = [];   // url → ID
    $blog_url = '/blog/';

    foreach ($data['pages'] as $p) {
        $is_post = $p['kind'] === 'article';
        $slug    = basename(rtrim($p['url'], '/'));
        $meta = [
            'kind' => $p['kind'], 'h1' => $p['h1'], 'title' => $p['title'], 'desc' => $p['desc'], 'lead' => $p['lead'] ?? '',
            'price' => $p['price'] ?? '', 'term' => $p['term'] ?? '',
            'when' => implode("\n", $p['when'] ?? []), 'docs' => implode("\n", $p['docs'] ?? []),
            'faq' => implode("\n\n", array_map(fn($f) => $f[0] . "\n" . $f[1], $p['faq'] ?? [])),
            'kw' => implode(', ', $p['kw'] ?? []),
        ];

        if ($is_post) {
            $existing = get_posts(['name' => $slug, 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1]);
            if ($existing) { $res['skipped']++; $id = $existing[0]->ID; }
            else {
                // Статті без тексту — чернетки з планом, щоб не публікувати порожні сторінки
                $content = "<!-- wp:paragraph --><p><strong>Чернетка.</strong> " . esc_html($p['lead'] ?? '') . "</p><!-- /wp:paragraph -->\n"
                         . "<!-- wp:list {\"ordered\":true} --><ol><li>Коротка відповідь (2–3 речення, під featured snippet)</li><li>Деталі й нюанси</li><li>Приклад із цифрами</li><li>Що робити далі + посилання на послугу</li></ol><!-- /wp:list -->";
                $id = wp_insert_post(['post_type' => 'post', 'post_status' => 'draft', 'post_title' => $p['h1'], 'post_name' => $slug, 'post_content' => $content, 'menu_order' => $p['order']]);
                $res['created']++;
            }
        } else {
            $parent = $p['parent'] ? ($ids[$p['parent']] ?? 0) : 0;
            $existing = get_page_by_path(trim($p['url'], '/'), OBJECT, 'page');
            if ($existing) { $res['skipped']++; $id = $existing->ID; }
            else {
                $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p['h1'], 'post_name' => $slug,
                    'post_parent' => $parent, 'menu_order' => $p['order'], 'post_content' => '', 'comment_status' => 'closed']);
                $res['created']++;
            }
            $ids[$p['url']] = $id;
        }
        if (!$id || is_wp_error($id)) continue;
        foreach ($meta as $k => $v) {
            $cur = get_post_meta($id, '_bti_' . $k, true);
            if ($cur === '' || $cur === false) {
                if ($v !== '' && ($fill_empty || !metadata_exists('post', $id, '_bti_' . $k))) { update_post_meta($id, '_bti_' . $k, $v); $res['updated']++; }
            }
        }
    }

    // Головна
    $front = (int)get_option('page_on_front');
    if (!$front || !get_post($front)) {
        $front = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Головна', 'post_name' => 'golovna', 'post_content' => '']);
        update_post_meta($front, '_bti_kind', 'home');
    }
    update_option('show_on_front', 'page');
    update_option('page_on_front', $front);
    if (!empty($ids[$blog_url])) update_option('page_for_posts', $ids[$blog_url]);

    // Прибрати демо-контент свіжого WordPress
    foreach ([['sample-page', 'page'], ['hello-world', 'post']] as [$s, $t]) {
        $demo = get_page_by_path($s, OBJECT, $t);
        if ($demo && in_array($demo->post_status, ['publish', 'draft'], true)) wp_trash_post($demo->ID);
    }
    // Наша сторінка конфіденційності замість стандартної чернетки
    if (!empty($ids['/privacy/'])) {
        $wp_priv = (int)get_option('wp_page_for_privacy_policy');
        if ($wp_priv && $wp_priv !== $ids['/privacy/'] && get_post_status($wp_priv) === 'draft') wp_trash_post($wp_priv);
        update_option('wp_page_for_privacy_policy', $ids['/privacy/']);
    }

    // Базові налаштування
    update_option('permalink_structure', '/blog/%postname%/');
    update_option('default_comment_status', 'closed');
    update_option('default_ping_status', 'closed');
    update_option('blogname', bti_opt('brand'));
    update_option('blogdescription', 'Оцінка нерухомості та послуги БТІ в Києві');
    update_option('timezone_string', 'Europe/Kyiv');
    update_option('date_format', 'd.m.Y');
    update_option('posts_per_page', 12);
    if ($cat = get_category(1)) wp_update_term(1, 'category', ['name' => 'Статті', 'slug' => 'statti']);

    flush_rewrite_rules();
    update_option('bti_imported', time());
    return $res;
}

<?php
/**
 * Поля сторінки: H1, SEO-title/description, лід, ціна, термін, «коли потрібна», документи, FAQ.
 * Без плагінів — звичайний метабокс під редактором.
 */
if (!defined('ABSPATH')) exit;

function bti_field_defs(): array {
    return [
        'kind'  => ['Тип сторінки', 'select', ['service' => 'Послуга', 'hub' => 'Розділ (список послуг)', 'geo' => 'Місто', 'page' => 'Інформаційна', 'article' => 'Стаття']],
        'h1'    => ['Заголовок H1', 'text', 'Якщо порожньо — береться назва сторінки'],
        'title' => ['SEO-title (вкладка браузера, Google)', 'text', 'До ~65 символів'],
        'desc'  => ['SEO-description (опис у Google)', 'textarea2', 'До ~160 символів'],
        'lead'  => ['Вступний текст під H1', 'textarea2', ''],
        'price' => ['Ціна', 'text', 'Напр.: 900 грн або від 1200 грн. Порожньо — блок ціни не показується'],
        'term'  => ['Термін', 'text', 'Напр.: 1 день'],
        'when'  => ['Коли потрібна (кожен пункт з нового рядка)', 'textarea', ''],
        'docs'  => ['Документи (кожен з нового рядка; порожньо — базовий перелік)', 'textarea', ''],
        'faq'   => ['Часті питання', 'textarea', 'Питання — рядок, відповідь — наступний рядок, між парами порожній рядок'],
        'kw'    => ['Ключові запити (для копірайтера, на сайті не показуються)', 'textarea2', ''],
    ];
}

add_action('add_meta_boxes', function () {
    foreach (['page', 'post'] as $type) {
        add_meta_box('bti_fields', 'Дані сторінки (ціна, SEO, FAQ)', 'bti_fields_box', $type, 'normal', 'high');
    }
});

function bti_fields_box(WP_Post $post) {
    wp_nonce_field('bti_fields', 'bti_fields_nonce');
    echo '<style>.bti-f{margin:0 0 14px}.bti-f label{display:block;font-weight:600;margin-bottom:4px}.bti-f input[type=text],.bti-f textarea,.bti-f select{width:100%}.bti-f small{color:#666}</style>';
    foreach (bti_field_defs() as $key => [$label, $type, $extra]) {
        if ($post->post_type === 'post' && in_array($key, ['price', 'term', 'when', 'docs'], true)) continue;
        $val = (string)get_post_meta($post->ID, '_bti_' . $key, true);
        echo '<div class="bti-f"><label for="bti_' . esc_attr($key) . '">' . esc_html($label) . '</label>';
        if ($type === 'select') {
            echo '<select id="bti_' . esc_attr($key) . '" name="bti[' . esc_attr($key) . ']">';
            foreach ($extra as $v => $l) printf('<option value="%s"%s>%s</option>', esc_attr($v), selected($val, $v, false), esc_html($l));
            echo '</select>';
        } elseif ($type === 'text') {
            printf('<input type="text" id="bti_%1$s" name="bti[%1$s]" value="%2$s">', esc_attr($key), esc_attr($val));
            if ($extra) echo '<small>' . esc_html($extra) . '</small>';
        } else {
            $rows = $type === 'textarea2' ? 2 : 6;
            printf('<textarea id="bti_%1$s" name="bti[%1$s]" rows="%3$d">%2$s</textarea>', esc_attr($key), esc_textarea($val), $rows);
            if ($extra) echo '<small>' . esc_html($extra) . '</small>';
        }
        echo '</div>';
    }
}

add_action('save_post', function ($id) {
    if (!isset($_POST['bti_fields_nonce']) || !wp_verify_nonce($_POST['bti_fields_nonce'], 'bti_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $id)) return;
    $in = wp_unslash($_POST['bti'] ?? []);
    foreach (array_keys(bti_field_defs()) as $key) {
        if (!array_key_exists($key, $in)) continue;
        $v = in_array($key, ['when', 'docs', 'faq', 'desc', 'lead', 'kw'], true) ? sanitize_textarea_field($in[$key]) : sanitize_text_field($in[$key]);
        update_post_meta($id, '_bti_' . $key, $v);
    }
});

/* Колонки «Ціна» і «Термін» у списку сторінок — щоб бачити прайс одразу */
add_filter('manage_pages_columns', function ($c) {
    $c['bti_price'] = 'Ціна'; $c['bti_term'] = 'Термін';
    return $c;
});
add_action('manage_pages_custom_column', function ($col, $id) {
    if ($col === 'bti_price') echo esc_html(get_post_meta($id, '_bti_price', true));
    if ($col === 'bti_term')  echo esc_html(get_post_meta($id, '_bti_term', true));
}, 10, 2);

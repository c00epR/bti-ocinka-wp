<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_post_type_support('page', 'excerpt');
});

/* ---------- стилі й скрипти ---------- */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('bti-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);
    wp_enqueue_style('bti-style', BTI_URI . '/assets/css/style.css', ['bti-fonts'], BTI_VER);
    wp_enqueue_style('bti-inner', BTI_URI . '/assets/css/inner.css', ['bti-style'], BTI_VER);
    wp_enqueue_script('bti-main', BTI_URI . '/assets/js/main.js', [], BTI_VER, ['in_footer' => true, 'strategy' => 'defer']);
    if (is_page() && get_post_field('post_name') === 'kalkulyator-podatkiv') {
        wp_enqueue_script('bti-calc', BTI_URI . '/assets/js/calc.js', [], BTI_VER, ['in_footer' => true, 'strategy' => 'defer']);
    }
    // блоковий редактор на фронті не використовується
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 20);

add_filter('style_loader_tag', function ($tag, $handle) {
    return $handle === 'bti-fonts' ? str_replace("rel='stylesheet'", "rel='stylesheet' crossorigin", $tag) : $tag;
}, 10, 2);

/* ---------- прибрати зайве з <head> ---------- */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rel_canonical');           // canonical виводить тема
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'wp_oembed_add_discovery_links');
add_filter('the_generator', '__return_empty_string');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('template_redirect', 'rest_output_link_header', 11);
// стилі блокового редактора (global styles, SVG-фільтри) на фронті не потрібні
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
add_filter('should_load_separate_core_block_assets', '__return_true');

/* ---------- коментарі вимкнені ---------- */
add_filter('comments_open', '__return_false', 20);
add_filter('pings_open', '__return_false', 20);
add_filter('comments_array', '__return_empty_array', 10);
add_action('admin_menu', fn() => remove_menu_page('edit-comments.php'));

/* ---------- карта сайту: без авторів і рубрик ---------- */
add_filter('wp_sitemaps_add_provider', fn($p, $name) => in_array($name, ['users'], true) ? false : $p, 10, 2);
add_filter('wp_sitemaps_taxonomies', fn($t) => []);

/* ---------- архіви авторів і вкладень → на головну / батьківську ---------- */
add_action('template_redirect', function () {
    if (is_author()) { wp_safe_redirect(home_url('/'), 301); exit; }
    if (is_attachment()) {
        $parent = wp_get_post_parent_id(get_queried_object_id());
        wp_safe_redirect($parent ? get_permalink($parent) : home_url('/'), 301); exit;
    }
});

/* ---------- в адмінці показати ієрархію сторінок зручно ---------- */
add_filter('admin_body_class', fn($c) => $c . ' bti-admin');

/* ---------- кеш LiteSpeed: скидати після кожного деплою ---------- */
// GitHub Actions при кожній заливці оновлює .ftp-deploy-sync-state.json.
// Якщо файл змінився з минулого разу — очищаємо кеш, щоб правки теми були видні одразу.
add_action('init', function () {
    $f = BTI_DIR . '/.ftp-deploy-sync-state.json';
    $stamp = (is_file($f) ? filemtime($f) : 0) . '|' . BTI_VER;
    if (get_option('bti_deploy_stamp') === $stamp) return;
    update_option('bti_deploy_stamp', $stamp, true);
    bti_purge_cache();
}, 1);

function bti_purge_cache(): void {
    do_action('litespeed_purge_all');                         // LiteSpeed Cache
    if (function_exists('wp_cache_clear_cache')) wp_cache_clear_cache();   // WP Super Cache
    if (function_exists('rocket_clean_domain')) rocket_clean_domain();     // WP Rocket
}

// Зміна налаштувань «Сайт БТІ» (телефон, відгуки…) теж має одразу з'являтись на всіх сторінках
add_action('update_option_bti_settings', 'bti_purge_cache');
add_action('update_option_bti_reviews_text', 'bti_purge_cache');

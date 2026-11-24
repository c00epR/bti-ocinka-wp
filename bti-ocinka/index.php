<?php
/** Запасний шаблон (архіви, пошук) */
if (!defined('ABSPATH')) exit;
get_header();
echo '<section class="phero"><div class="wrap"><div class="phero-grid no-aside"><div><h1>' . esc_html(wp_strip_all_tags(get_the_archive_title() ?: 'Матеріали')) . '</h1></div></div></div></section>';
if (have_posts()) {
    $items = [];
    while (have_posts()) { the_post(); $items[] = get_post(); }
    echo bti_section('Матеріали', bti_cards($items, false));
}
get_footer();

<?php
/** Сторінка блогу (/blog/) — список статей */
if (!defined('ABSPATH')) exit;
get_header();
$blog = get_post((int)get_option('page_for_posts'));
$d = $blog ? bti_data($blog) : ['h1' => 'Блог', 'lead' => '', 'price' => '', 'term' => ''];
echo $blog ? bti_hero($d, $blog) : '';

if (have_posts()) {
    $cards = '';
    while (have_posts()) { the_post();
        $x = bti_data(get_post());
        $cards .= sprintf('<a class="card card-link" href="%s"><h3>%s</h3><p>%s</p><div class="price"><span>%s</span></div></a>',
            esc_url(get_permalink()), esc_html($x['h1']), esc_html($x['desc']), esc_html(get_the_date()));
    }
    echo bti_section('Статті', '<div class="price-grid">' . $cards . '</div>' . get_the_posts_pagination(['mid_size' => 1]));
} else {
    echo bti_section('Статті', '<p class="empty-note">Статті готуються. Поки що відповімо на будь-яке питання по телефону ' . esc_html(bti_opt('phone')) . '.</p>');
}
get_footer();

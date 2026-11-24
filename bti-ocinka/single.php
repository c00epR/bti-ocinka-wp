<?php
/** Стаття блогу */
if (!defined('ABSPATH')) exit;
get_header();
the_post();
$post = get_post();
$d = bti_data($post);
echo '<article>';
echo bti_hero($d, $post);
echo '<section class="sec"><div class="wrap content"><p class="post-meta">' . esc_html(get_the_date()) . ' · ' . esc_html(bti_opt('brand')) . '</p>';
the_content();
echo '</div></section>';
if ($d['faq']) echo bti_section('Часті питання', bti_faq_html($d['faq']));
echo '</article>';

// Інші статті
$more = get_posts(['post_type' => 'post', 'numberposts' => 3, 'post__not_in' => [$post->ID]]);
if ($more) echo bti_section('Читайте також', bti_cards($more, false), 'sec-mist');
get_footer();

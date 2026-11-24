<?php
/**
 * Головна. Розмітка — у parts/home-body.html (той самий дизайн, що й статична версія).
 * Відгуки й посилання підставляються з налаштувань і сторінок.
 * Якщо в редакторі сторінки «Головна» додати текст — він виведеться окремим блоком перед заявкою.
 */
if (!defined('ABSPATH')) exit;
get_header();

$html = file_get_contents(BTI_DIR . '/parts/home-body.html');
$html = preg_replace_callback('/\{\{H\}\}(\/[^"#]*)/', function ($m) {
    $path = trim($m[1], '/');
    return $path === '' ? home_url('/') : bti_url($path);
}, $html);
$html = str_replace('{{T}}', BTI_URI . '/assets', $html);
// відгуки — з налаштувань
$html = preg_replace('#(<section id="reviews"[^>]*>\s*<div class="wrap">).*?(</div>\s*</section>)#s',
    '$1' . bti_reviews_block(3, 'Що кажуть клієнти', 'rev-h') . '<p style="margin-top:22px"><a href="' . esc_url(bti_url('vidhuky')) . '">Усі відгуки →</a></p>$2', $html, 1);
// телефон — з налаштувань
$html = str_replace(['+380675034612', '+38 (067) 503-46-12'], [bti_opt('phone_raw'), bti_opt('phone')], $html);
echo $html;

while (have_posts()) { the_post();
    if (trim(get_the_content()) !== '') {
        echo '<section class="sec"><div class="wrap content">'; the_content(); echo '</div></section>';
    }
}
get_footer();

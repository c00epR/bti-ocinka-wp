<?php
if (!defined('ABSPATH')) exit;
status_header(404);
get_header();
?>
<section class="phero"><div class="wrap"><div class="phero-grid no-aside"><div>
<h1>Сторінку не знайдено</h1>
<p class="lead">Можливо, адреса змінилась. Оберіть потрібну послугу або залиште заявку — підкажемо.</p>
<div class="hero-cta"><a class="btn btn-primary" href="<?php echo esc_url(bti_url('ocinka')); ?>">Оцінка майна</a><a class="btn btn-ghost" href="<?php echo esc_url(bti_url('bti')); ?>">Послуги БТІ</a></div>
</div></div></div></section>
<?php get_footer();

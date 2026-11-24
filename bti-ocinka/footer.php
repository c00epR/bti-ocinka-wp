<?php if (!defined('ABSPATH')) exit;
$brand = bti_opt('brand');
$ph = esc_attr(bti_opt('phone_raw')); $phone = esc_html(bti_opt('phone'));
$email = esc_html(bti_opt('email'));
?>
<section class="order" id="order" aria-labelledby="order-h"><div class="wrap"><div>
<h2 id="order-h">Залиште заявку — передзвонимо протягом 15 хвилин</h2>
<p>Уточнимо об'єкт, назвемо точну ціну і строк. Консультація безкоштовна.</p>
<address class="contacts">
<span>Телефон: <a href="tel:<?php echo $ph; ?>"><?php echo $phone; ?></a></span>
<span>Месенджери: <a href="<?php echo esc_url(bti_opt('tg_link')); ?>" rel="noopener">Telegram</a> · <a href="<?php echo esc_attr(bti_opt('viber_link')); ?>">Viber</a></span>
<span>Email: <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></span>
<span>Офіс: <?php echo esc_html(bti_opt('address') . ' · ' . bti_opt('hours')); ?></span>
</address></div>
<form class="lead-form" action="<?php echo esc_url(rest_url('bti/v1/lead')); ?>" method="post"><h3>Замовити послугу</h3>
<div class="hp" aria-hidden="true"><label>Не заповнюйте<input name="website" tabindex="-1" autocomplete="off"></label></div><input type="hidden" name="page" value="">
<div class="field"><label for="f-name">Ім'я</label><input id="f-name" name="name" autocomplete="name" placeholder="Як до вас звертатися" required></div>
<div class="field"><label for="f-phone">Телефон</label><input id="f-phone" name="phone" type="tel" autocomplete="tel" placeholder="+38 (0__) ___-__-__" required></div>
<button class="btn btn-primary" type="submit">Надіслати заявку</button><p class="form-msg" role="status" aria-live="polite"></p>
<small>Натискаючи кнопку, ви погоджуєтесь з <a href="<?php echo esc_url(bti_url('privacy')); ?>">політикою конфіденційності</a></small></form>
</div></section>
</main>
<?php
$fcol = function (string $label, array $ps): string {
    $o = '<nav aria-label="' . esc_attr($label) . '"><b>' . esc_html($label) . '</b>';
    foreach ($ps as $p) $o .= '<a href="' . esc_url(get_permalink($p)) . '">' . esc_html(bti_data($p)['h1']) . '</a>';
    return $o . '</nav>';
};
$company = [['pro-nas', 'Про нас'], ['sertyfikaty', 'Сертифікати'], ['vidhuky', 'Відгуки'], ['tsiny', 'Ціни'], ['kyivska-oblast', 'Київська область'], ['blog', 'Блог'], ['kontakty', 'Контакти']];
?>
<footer><div class="wrap footer-grid">
<div><a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><i aria-hidden="true">БТІ</i><?php echo esc_html($brand); ?></a>
<address><p style="margin-top:14px"><?php echo esc_html(bti_opt('address')); ?><br><a href="tel:<?php echo $ph; ?>"><?php echo $phone; ?></a><br><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a><br><?php echo esc_html(bti_opt('hours')); ?></p></address></div>
<?php echo $fcol('Оцінка', bti_children(bti_page('ocinka'), 10)); ?>
<?php echo $fcol('Послуги БТІ', bti_children(bti_page('bti'), 10)); ?>
<nav aria-label="Компанія"><b>Компанія</b><?php foreach ($company as [$path, $label]) echo '<a href="' . esc_url(bti_url($path)) . '">' . esc_html($label) . '</a>'; ?></nav>
</div>
<div class="wrap footer-bottom"><span>© <?php echo esc_html(wp_date('Y') . ' ' . $brand); ?> · <a href="<?php echo esc_url(bti_url('privacy')); ?>">Політика конфіденційності</a> · <a href="<?php echo esc_url(bti_opt('maps_url')); ?>" rel="noopener">Ми на Google Картах</a></span></div>
</footer>
<a class="btn btn-primary fab" href="tel:<?php echo $ph; ?>">Зателефонувати</a>
<?php wp_footer(); ?>
</body>
</html>

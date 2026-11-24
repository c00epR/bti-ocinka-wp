<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php bti_head_meta(); ?>
<meta name="theme-color" content="#12303F">
<link rel="icon" type="image/svg+xml" href="<?php echo esc_url(BTI_URI . '/assets/favicon.svg'); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url(BTI_URI . '/assets/favicon.svg'); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$brand = bti_opt('brand');
$cur   = trailingslashit(wp_make_link_relative(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))));
$col = function (string $title, string $path, array $items): string {
    $o = '<div><a class="dd-title" href="' . esc_url(bti_url($path)) . '">' . esc_html($title) . '</a>';
    foreach ($items as $p) $o .= '<a href="' . esc_url(get_permalink($p)) . '">' . esc_html(bti_data($p)['h1']) . '</a>';
    return $o . '</div>';
};
$oc_all = bti_children(bti_page('ocinka'));
$oc  = array_filter($oc_all, fn($p) => !bti_is_intent($p));
$oi  = array_filter($oc_all, fn($p) => bti_is_intent($p));
$bt  = array_slice(bti_children(bti_page('bti')), 0, 9);
$tp  = bti_children(bti_page('bti/tehpasport'));
$items = [['tsiny', 'Ціни'], ['kalkulyator-podatkiv', 'Калькулятор податків'], ['dokumenty', 'Документи'], ['vidhuky', 'Відгуки'], ['blog', 'Блог'], ['kontakty', 'Контакти']];
?>
<a class="skip" href="#main">Перейти до змісту</a>
<div class="topbar"><div class="wrap"><span><?php echo esc_html(bti_opt('address') . ' · ' . bti_opt('hours')); ?></span><span>Працюємо по всій Україні — документи відправляємо Новою поштою</span></div></div>
<header class="nav"><div class="wrap">
<a class="logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($brand); ?> — на головну"><i aria-hidden="true">БТІ</i><?php echo esc_html($brand); ?></a>
<nav class="menu" id="main-menu" aria-label="Головне меню">
<div class="dd"><button class="dd-btn" type="button" aria-expanded="false" aria-haspopup="true" aria-controls="services-menu">Послуги <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
<div class="dd-menu" id="services-menu"><?php echo $col('Оцінка майна', 'ocinka', $oc) . $col('Оцінка для…', 'ocinka', $oi) . $col('Послуги БТІ', 'bti', $bt) . $col('Техпаспорт', 'bti/tehpasport', $tp); ?></div></div>
<?php foreach ($items as [$path, $label]):
    $url = bti_url($path); $active = str_starts_with($cur, '/' . $path . '/'); ?>
<a href="<?php echo esc_url($url); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
<?php endforeach; ?>
<a class="btn btn-primary btn-m" href="#order">Замовити</a></nav>
<a class="nav-phone" href="tel:<?php echo esc_attr(bti_opt('phone_raw')); ?>"><?php echo esc_html(bti_opt('phone')); ?></a>
<a class="btn btn-primary" href="#order" style="padding:11px 20px">Замовити</a>
<button class="burger" type="button" aria-label="Відкрити меню" aria-expanded="false" aria-controls="main-menu"><span></span><span></span><span></span></button>
</div></header>
<main id="main">

<?php
if (!defined('ABSPATH')) exit;

/** Шлях сторінки у вигляді /ocinka/kvartyry/ */
function bti_path($post = null): string {
    $post = get_post($post);
    if (!$post) return '/';
    if ($post->post_type === 'page') {
        if ((int)get_option('page_on_front') === $post->ID) return '/';
        return '/' . get_page_uri($post) . '/';
    }
    return wp_make_link_relative(get_permalink($post));
}

function bti_page(string $path): ?WP_Post {
    $p = get_page_by_path(trim($path, '/'), OBJECT, 'page');
    return ($p && $p->post_status === 'publish') ? $p : null;
}

function bti_url(string $path): string {
    $p = bti_page($path);
    return $p ? get_permalink($p) : home_url($path);
}

function bti_children($parent, int $limit = -1): array {
    $parent = $parent instanceof WP_Post ? $parent->ID : (int)$parent;
    if (!$parent) return [];
    return get_posts([
        'post_type' => 'page', 'post_parent' => $parent, 'post_status' => 'publish',
        'orderby' => 'menu_order title', 'order' => 'ASC', 'numberposts' => $limit,
    ]);
}

function bti_lines(string $text): array {
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), 'strlen'));
}

/** FAQ зберігається як: питання (рядок), відповідь (рядок), порожній рядок між парами */
function bti_parse_faq(string $text): array {
    $out = [];
    foreach (preg_split('/\R\s*\R/u', trim($text)) as $block) {
        $l = preg_split('/\R/u', trim($block), 2);
        if (count($l) === 2 && trim($l[0]) !== '' && trim($l[1]) !== '') $out[] = [trim($l[0]), trim(preg_replace('/\s*\R\s*/u', ' ', $l[1]))];
    }
    return $out;
}

/** Усі дані сторінки з полів (з запасними значеннями) */
function bti_data($post = null): array {
    $post = get_post($post);
    if (!$post) return [];
    $m = fn($k) => (string)get_post_meta($post->ID, '_bti_' . $k, true);
    $h1 = $m('h1') ?: get_the_title($post);
    return [
        'id'    => $post->ID,
        'kind'  => $m('kind') ?: ($post->post_type === 'post' ? 'article' : 'page'),
        'h1'    => $h1,
        'title' => $m('title') ?: $h1 . ' — ' . bti_opt('brand'),
        'desc'  => $m('desc') ?: wp_trim_words(wp_strip_all_tags($post->post_excerpt ?: $post->post_content), 28, '…'),
        'lead'  => $m('lead'),
        'price' => $m('price'),
        'term'  => $m('term'),
        'when'  => bti_lines($m('when')),
        'docs'  => bti_lines($m('docs')),
        'faq'   => bti_parse_faq($m('faq')),
        'path'  => bti_path($post),
    ];
}

function bti_is_intent($post): bool {
    $slug = get_post($post)->post_name ?? '';
    return str_starts_with($slug, 'dlya-') || str_starts_with($slug, 'pry-') || $slug === 'terminova';
}

/* ---------- блоки розмітки ---------- */

function bti_section(string $title, string $body, string $cls = ''): string {
    static $n = 0; $n++;
    return sprintf('<section class="sec %s" aria-labelledby="sec-%d"><div class="wrap"><h2 id="sec-%d">%s</h2>%s</div></section>',
        esc_attr($cls), $n, $n, esc_html($title), $body);
}

function bti_cards(array $posts, bool $show_price = true): string {
    $out = '';
    foreach ($posts as $p) {
        $d = bti_data($p);
        $price = ($show_price && $d['price']) ? sprintf('<div class="price"><b>%s</b><span>%s</span></div>', esc_html($d['price']), esc_html($d['term'])) : '';
        $out .= sprintf('<a class="card card-link" href="%s"><h3>%s</h3><p>%s</p>%s</a>', esc_url(get_permalink($p)), esc_html($d['h1']), esc_html($d['desc']), $price);
    }
    return '<div class="price-grid">' . $out . '</div>';
}

function bti_ancestors_chain($post): array {
    $post = get_post($post);
    $chain = [$post];
    if ($post->post_type === 'page') {
        foreach (get_post_ancestors($post) as $a) array_unshift($chain, get_post($a));
    } elseif ($post->post_type === 'post' && ($blog = get_option('page_for_posts'))) {
        array_unshift($chain, get_post($blog));
    }
    return $chain;
}

function bti_crumbs($post): string {
    $items = '<li><a href="' . esc_url(home_url('/')) . '">Головна</a></li>';
    $chain = bti_ancestors_chain($post);
    foreach ($chain as $i => $c) {
        $name = esc_html(bti_data($c)['h1']);
        $items .= ($i === count($chain) - 1)
            ? '<li aria-current="page">' . $name . '</li>'
            : '<li><a href="' . esc_url(get_permalink($c)) . '">' . $name . '</a></li>';
    }
    return '<nav class="crumbs" aria-label="Навігаційний шлях"><ol>' . $items . '</ol></nav>';
}

function bti_hero(array $d, $post): string {
    $box = '';
    if ($d['price']) {
        $box = sprintf('<aside class="pricebox" aria-label="Вартість і термін"><small>Вартість</small><strong>%s</strong><small>Термін</small><b>%s</b>
<a class="btn btn-marker" href="#order">Замовити</a><p>Реєстрація у ФДМУ/ЄДЕССБ включена · оплата після отримання</p></aside>', esc_html($d['price']), esc_html($d['term']));
    }
    return sprintf('<section class="phero"><div class="wrap">%s<div class="phero-grid%s"><div><h1>%s</h1>%s
<div class="hero-cta"><a class="btn btn-primary" href="#order">Замовити</a><a class="btn btn-ghost" href="tel:%s">%s</a></div></div>%s</div></div></section>',
        bti_crumbs($post), $d['price'] ? '' : ' no-aside', esc_html($d['h1']),
        $d['lead'] ? '<p class="lead">' . esc_html($d['lead']) . '</p>' : '',
        esc_attr(bti_opt('phone_raw')), esc_html(bti_opt('phone')), $box);
}

function bti_related($post): string {
    $post = get_post($post);
    if ($post->post_type !== 'page' || !$post->post_parent) return '';
    $sib = array_filter(bti_children($post->post_parent), fn($p) => $p->ID !== $post->ID);
    $sib = array_slice($sib, 0, 6);
    if (!$sib) return '';
    $links = '';
    foreach ($sib as $p) $links .= '<a class="card-link-sm" href="' . esc_url(get_permalink($p)) . '">' . esc_html(bti_data($p)['h1']) . '</a>';
    return '<section class="sec" aria-labelledby="rel-h"><div class="wrap"><h2 id="rel-h">Суміжні послуги</h2><div class="price-grid" style="margin-top:28px">' . $links . '</div></div></section>';
}

function bti_faq_html(array $faq): string {
    $o = '';
    foreach ($faq as [$q, $a]) $o .= '<details><summary>' . esc_html($q) . '</summary><p>' . esc_html($a) . '</p></details>';
    return '<div class="faq">' . $o . '</div>';
}

function bti_reviews_block(int $limit = 3, string $heading = 'Що кажуть клієнти', string $hid = ''): string {
    $cards = '';
    foreach (array_slice(bti_reviews(), 0, $limit) as [$n, $s, $t]) {
        $cards .= sprintf('<article class="review"><span class="stars" aria-label="%s з 5">%s</span><p>%s</p><div class="who"><i aria-hidden="true">%s</i><span>%s · Google</span></div></article>',
            esc_attr($s), str_repeat('★', (int)$s), esc_html($t), esc_html(mb_substr($n, 0, 1)), esc_html($n));
    }
    $h = $heading !== '' ? '<h2' . ($hid ? ' id="' . esc_attr($hid) . '"' : '') . '>' . esc_html($heading) . '</h2>' : '<p>Усі відгуки — з нашої картки в Google, їх можна перевірити за посиланням.</p>';
    return sprintf('<div class="sec-head">%s<a class="rating" href="%s" rel="noopener"><b>%s</b><div><span class="stars" aria-hidden="true">★★★★★</span><small>%s відгуків у Google</small></div></a></div><div class="reviews">%s</div>',
        $h, esc_url(bti_opt('maps_url')), esc_html(bti_opt('rating_value')), esc_html(bti_opt('rating_count')), $cards);
}

function bti_map_block(): string {
    return sprintf('<div class="map-grid"><div class="map"><iframe src="%s" width="100%%" height="380" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="Офіс на карті: %s"></iframe></div>
<div class="map-info"><h3>Офіс у центрі Києва</h3><address><p><b>%s</b><br>Поруч — метро «Палац спорту» та «Площа Українських Героїв», 3 хвилини пішки.</p>
<p><b>Графік:</b> %s<br>Консультації в месенджерах — також у вихідні та свята.</p>
<p><b>Телефон:</b> <a href="tel:%s">%s</a><br><b>Telegram:</b> <a href="%s" rel="noopener">написати</a> · <b>Viber:</b> <a href="%s">написати</a><br><b>Email:</b> <a href="mailto:%s">%s</a></p></address>
<a class="btn btn-ghost" href="%s" rel="noopener">Відкрити в Google Картах</a></div></div>',
        esc_url(bti_opt('map_embed')), esc_attr(bti_opt('address')), esc_html(bti_opt('address')), esc_html(bti_opt('hours')),
        esc_attr(bti_opt('phone_raw')), esc_html(bti_opt('phone')), esc_url(bti_opt('tg_link')), esc_attr(bti_opt('viber_link')),
        esc_attr(bti_opt('email')), esc_html(bti_opt('email')), esc_url(bti_opt('maps_url')));
}

function bti_img(string $file): string { return esc_url(BTI_URI . '/assets/img/' . $file); }

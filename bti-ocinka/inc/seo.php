<?php
/**
 * SEO: <title>, description, canonical, Open Graph, schema.org.
 * Якщо пізніше поставите Yoast/Rank Math — тема сама вимкне свої мета-теги.
 */
if (!defined('ABSPATH')) exit;

function bti_seo_plugin_active(): bool {
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION');
}

/** Дані для поточного запиту */
function bti_current_meta(): array {
    if (is_front_page()) {
        return ['title' => bti_opt('home_title'), 'desc' => bti_opt('home_desc'), 'url' => home_url('/'), 'type' => 'website', 'kind' => 'home', 'post' => null];
    }
    if (is_home() && ($blog = get_option('page_for_posts'))) {
        $d = bti_data($blog);
        $paged = max(1, (int)get_query_var('paged'));
        return ['title' => $d['title'] . ($paged > 1 ? " — сторінка $paged" : ''), 'desc' => $d['desc'], 'url' => get_permalink($blog) . ($paged > 1 ? "page/$paged/" : ''), 'type' => 'website', 'kind' => 'hub', 'post' => get_post($blog)];
    }
    if (is_singular()) {
        $p = get_queried_object();
        $d = bti_data($p);
        return ['title' => $d['title'], 'desc' => $d['desc'], 'url' => get_permalink($p), 'type' => $p->post_type === 'post' ? 'article' : 'website', 'kind' => $d['kind'], 'post' => $p, 'data' => $d];
    }
    if (is_404()) return ['title' => 'Сторінку не знайдено — ' . bti_opt('brand'), 'desc' => '', 'url' => '', 'type' => 'website', 'kind' => '404', 'post' => null, 'noindex' => true];
    return ['title' => wp_get_document_title(), 'desc' => '', 'url' => '', 'type' => 'website', 'kind' => 'other', 'post' => null, 'noindex' => true];
}

function bti_ld(array $d): string {
    return '<script type="application/ld+json">' . wp_json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}

function bti_local_business(): array {
    $home = home_url('/');
    return [
        '@context' => 'https://schema.org', '@type' => ['LocalBusiness', 'ProfessionalService'], '@id' => $home . '#business',
        'name' => bti_opt('brand'), 'alternateName' => 'Послуги БТІ та оцінка нерухомого майна', 'url' => $home,
        'telephone' => bti_opt('phone_raw'), 'email' => bti_opt('email'),
        'image' => BTI_URI . '/assets/img/og.jpg', 'logo' => BTI_URI . '/assets/favicon.svg', 'priceRange' => '₴₴',
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => bti_opt('street'), 'addressLocality' => 'Київ', 'postalCode' => bti_opt('postal'), 'addressCountry' => 'UA'],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => (float)bti_opt('lat'), 'longitude' => (float)bti_opt('lng')],
        'hasMap' => bti_opt('maps_url'),
        'openingHoursSpecification' => [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '09:00', 'closes' => '18:00']],
        'areaServed' => [['@type' => 'City', 'name' => 'Київ'], ['@type' => 'AdministrativeArea', 'name' => 'Київська область']],
        'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => bti_opt('rating_value'), 'reviewCount' => bti_opt('rating_count'), 'bestRating' => '5'],
        'sameAs' => [bti_opt('maps_url')],
    ];
}

function bti_head_meta() {
    $m = bti_current_meta();
    $brand = bti_opt('brand');
    echo '<title>' . esc_html($m['title']) . "</title>\n";
    if (bti_seo_plugin_active()) return;

    if ($m['desc']) echo '<meta name="description" content="' . esc_attr($m['desc']) . "\">\n";
    if (!empty($m['noindex'])) {
        echo "<meta name=\"robots\" content=\"noindex,follow\">\n";
    } else {
        echo "<meta name=\"robots\" content=\"index,follow,max-image-preview:large,max-snippet:-1\">\n";
    }
    if ($m['url']) {
        echo '<link rel="canonical" href="' . esc_url($m['url']) . "\">\n";
        echo '<link rel="alternate" hreflang="uk" href="' . esc_url($m['url']) . "\">\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($m['url']) . "\">\n";
    }
    printf("<meta name=\"geo.region\" content=\"UA-30\"><meta name=\"geo.placename\" content=\"Київ\"><meta name=\"geo.position\" content=\"%s;%s\">\n", esc_attr(bti_opt('lat')), esc_attr(bti_opt('lng')));
    $img = BTI_URI . '/assets/img/og.jpg';
    if ($m['post'] && has_post_thumbnail($m['post'])) $img = get_the_post_thumbnail_url($m['post'], 'large');
    $og = ['og:type' => $m['type'], 'og:site_name' => $brand, 'og:locale' => 'uk_UA', 'og:title' => $m['title'], 'og:description' => $m['desc'], 'og:url' => $m['url'], 'og:image' => $img];
    foreach ($og as $k => $v) if ($v) echo '<meta property="' . $k . '" content="' . esc_attr($v) . "\">\n";
    echo "<meta property=\"og:image:width\" content=\"1200\"><meta property=\"og:image:height\" content=\"630\">\n";
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";

    // schema.org
    echo bti_ld(bti_local_business());
    if ($m['kind'] === 'home') {
        echo bti_ld(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $brand, 'url' => home_url('/'), 'inLanguage' => 'uk']);
        return;
    }
    if (!$m['post']) return;
    $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Головна', 'item' => home_url('/')]];
    foreach (bti_ancestors_chain($m['post']) as $i => $c) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => bti_data($c)['h1'], 'item' => get_permalink($c)];
    }
    echo bti_ld(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items]);

    $d = $m['data'] ?? bti_data($m['post']);
    if (in_array($d['kind'], ['service', 'geo', 'hub'], true) && !is_home()) {
        $s = ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $d['h1'], 'description' => $d['desc'], 'url' => $m['url'],
              'serviceType' => $d['h1'], 'provider' => ['@id' => home_url('/') . '#business'], 'areaServed' => ['Київ', 'Київська область']];
        if ($d['price'] && preg_match('/\d/', $d['price'])) {
            $s['offers'] = ['@type' => 'Offer', 'price' => preg_replace('/\D/', '', $d['price']), 'priceCurrency' => 'UAH', 'description' => $d['price']];
        }
        echo bti_ld($s);
    }
    if ($m['post']->post_type === 'post') {
        echo bti_ld(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $d['h1'], 'description' => $d['desc'], 'url' => $m['url'], 'inLanguage' => 'uk',
            'datePublished' => get_the_date('c', $m['post']), 'dateModified' => get_the_modified_date('c', $m['post']),
            'author' => ['@type' => 'Organization', 'name' => $brand], 'publisher' => ['@id' => home_url('/') . '#business'], 'image' => $img]);
    }
    if ($d['faq']) {
        echo bti_ld(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $d['faq'])]);
    }
    if ($m['post']->post_name === 'vidhuky') {
        echo bti_ld(['@context' => 'https://schema.org', '@type' => 'LocalBusiness', '@id' => home_url('/') . '#business',
            'review' => array_map(fn($r) => ['@type' => 'Review', 'author' => ['@type' => 'Person', 'name' => $r[0]], 'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r[1], 'bestRating' => '5'], 'reviewBody' => $r[2]], bti_reviews())]);
    }
}

/* Yoast/Rank Math: якщо стоять — беремо наші title/description як запасні */
add_filter('wpseo_title', fn($t) => is_singular() && get_post_meta(get_the_ID(), '_bti_title', true) ? get_post_meta(get_the_ID(), '_bti_title', true) : $t);
add_filter('wpseo_metadesc', fn($t) => is_singular() && get_post_meta(get_the_ID(), '_bti_desc', true) ? get_post_meta(get_the_ID(), '_bti_desc', true) : $t);

/* robots.txt */
add_filter('robots_txt', function ($out, $public) {
    if (!$public) return $out;
    return "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /?s=\nDisallow: /search/\n\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
}, 10, 2);

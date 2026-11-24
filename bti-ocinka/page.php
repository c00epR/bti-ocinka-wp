<?php
/**
 * Усі сторінки сайту: послуги, розділи, міста, інформаційні.
 * Набір блоків визначається полем «Тип сторінки» і адресою сторінки.
 */
if (!defined('ABSPATH')) exit;
get_header();
the_post();
$post = get_post();
$d    = bti_data($post);
$slug = $post->post_name;
$path = trim($d['path'], '/');

echo bti_hero($d, $post);

/* Довільний текст із редактора (для SEO-текстів) */
if (trim($post->post_content) !== '') {
    echo '<section class="sec"><div class="wrap content">'; the_content(); echo '</div></section>';
}

/* ---------- розділи ---------- */
if ($d['kind'] === 'hub') {
    $kids = bti_children($post);
    if ($path === 'ocinka') {
        echo bti_section('Що оцінюємо', bti_cards(array_filter($kids, fn($p) => !bti_is_intent($p))));
        echo bti_section('Для чого потрібна оцінка', bti_cards(array_filter($kids, 'bti_is_intent')), 'sec-mist');
    } elseif ($kids) {
        echo bti_section($path === 'kyivska-oblast' ? 'Міста' : 'Послуги', bti_cards($kids));
    }
}

/* ---------- послуги та міста ---------- */
if (in_array($d['kind'], ['service', 'geo'], true)) {
    if ($d['when']) {
        echo bti_section('Коли потрібна', '<ul class="checks">' . implode('', array_map(fn($w) => '<li>' . esc_html($w) . '</li>', $d['when'])) . '</ul>');
    }
    $docs = $d['docs'] ?: bti_docs_base();
    echo bti_section('Які документи потрібні', '<ul class="docs">' . implode('', array_map(fn($w) => '<li>' . esc_html($w) . '</li>', $docs)) . '</ul>', 'sec-mist');
    echo bti_section('Як це відбувається', '<ol class="steps"><li class="step"><h3>Заявка</h3><p>Телефон, Telegram, Viber або форма. Передзвонимо за 15 хвилин і назвемо точну ціну.</p></li>
<li class="step"><h3>Документи</h3><p>Надсилаєте фото документів у месенджер. Якщо потрібен виїзд — узгоджуємо час.</p></li>
<li class="step"><h3>Готовий документ</h3><p>Забираєте в офісі або отримуєте Новою поштою. Оплата після отримання.</p></li></ol>');
    if ($kids = bti_children($post)) echo bti_section('Різновиди', bti_cards($kids));
}

/* ---------- окремі сторінки ---------- */
switch ($path) {
    case 'tsiny':
        $table = function (string $root, string $cap): string {
            $rows = '';
            $walk = function ($parent) use (&$walk, &$rows) {
                foreach (bti_children($parent) as $p) {
                    $x = bti_data($p);
                    if ($x['kind'] === 'service' && $x['price']) {
                        $rows .= sprintf('<tr><td><a href="%s">%s</a></td><td>%s</td><td><b>%s</b></td></tr>', esc_url(get_permalink($p)), esc_html($x['h1']), esc_html($x['term']), esc_html($x['price']));
                    }
                    $walk($p);
                }
            };
            $walk(bti_page($root));
            return '<table class="pricetable"><caption class="vh">' . esc_html($cap) . '</caption><thead><tr><th scope="col">Послуга</th><th scope="col">Термін</th><th scope="col">Вартість</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        };
        echo bti_section('Оцінка майна', $table('ocinka', 'Ціни на оцінку майна'));
        echo bti_section('Послуги БТІ', $table('bti', 'Ціни на послуги БТІ'), 'sec-mist');
        break;

    case 'kalkulyator-podatkiv':
        echo bti_section('Розрахунок', file_get_contents(BTI_DIR . '/parts/calc-form.html'));
        echo bti_section('Як це рахується', file_get_contents(BTI_DIR . '/parts/calc-explain.html'), 'sec-mist');
        break;

    case 'dokumenty':
        $o = '';
        foreach (['ocinka', 'bti'] as $root) {
            $walk = function ($parent) use (&$walk, &$o) {
                foreach (bti_children($parent) as $p) {
                    $x = bti_data($p);
                    if ($x['kind'] === 'service') {
                        $o .= '<details><summary>' . esc_html($x['h1']) . '</summary><ul class="docs">' . implode('', array_map(fn($w) => '<li>' . esc_html($w) . '</li>', $x['docs'] ?: bti_docs_base())) . '</ul><p><a href="' . esc_url(get_permalink($p)) . '">Докладніше про послугу →</a></p></details>';
                    }
                    $walk($p);
                }
            };
            $walk(bti_page($root));
        }
        echo bti_section('По кожній послузі', $o);
        break;

    case 'kontakty':
        echo bti_section('Як нас знайти', bti_map_block());
        echo bti_section('Наш офіс', '<div class="photos"><figure><img src="' . bti_img('office.jpg') . '" alt="Офіс БТІ та оцінки майна на Великій Васильківській, 13/1" loading="lazy" width="960" height="1280"><figcaption>Офіс 405, 4-й поверх</figcaption></figure><figure><img src="' . bti_img('banner.jpg') . '" alt="Послуги БТІ та експертна оцінка майна — вивіска компанії" loading="lazy" width="1000" height="1004"><figcaption>Технічна інвентаризація, оцінка, введення в експлуатацію</figcaption></figure></div>', 'sec-mist');
        break;

    case 'sertyfikaty':
        $o = '<div class="certs">';
        foreach (bti_certs() as [$f, $t, $desc]) {
            $src = bti_img('certificates/' . $f);
            $o .= '<figure><a href="' . $src . '" target="_blank" rel="noopener"><img src="' . $src . '" alt="' . esc_attr($t) . '" loading="lazy"></a><figcaption><b>' . esc_html($t) . '</b><span>' . esc_html($desc) . '</span></figcaption></figure>';
        }
        $o .= '</div><p style="margin-top:24px;color:var(--ink-2)">Чинність сертифікатів можна перевірити в <a href="https://www.spfu.gov.ua/" rel="noopener">реєстрах Фонду державного майна України</a>.</p>';
        echo bti_section('Документи, що підтверджують кваліфікацію', $o);
        break;

    case 'vidhuky':
        echo bti_section('Відгуки з Google', bti_reviews_block(count(bti_reviews()), '') . '<p style="margin-top:24px"><a class="btn btn-primary" href="' . esc_url(bti_opt('maps_url')) . '" rel="noopener">Залишити відгук у Google</a></p>');
        break;

    case 'pro-nas':
        echo bti_section('Хто ми', '<div class="two"><div><p>Невелика команда сертифікованих оцінювачів та інженерів з технічної інвентаризації: оцінка майна для нотаріусів, банків і судів, техпаспорти, довідки та висновки БТІ, введення будинків в експлуатацію.</p><p style="margin-top:14px">Принцип простий: той, хто консультував вас по телефону, веде справу до видачі документа. Без передоплати — платите, коли документ у вас на руках.</p><ul class="checks" style="margin-top:22px;grid-template-columns:1fr"><li>Сертифікати ФДМУ та кваліфікаційні свідоцтва — <a href="' . esc_url(bti_url('sertyfikaty')) . '">дивитись</a></li><li>Рейтинг ' . esc_html(bti_opt('rating_value')) . ' у Google — <a href="' . esc_url(bti_url('vidhuky')) . '">відгуки</a></li><li>Офіс у центрі Києва — <a href="' . esc_url(bti_url('kontakty')) . '">контакти та карта</a></li></ul></div><figure><img src="' . bti_img('office.jpg') . '" alt="Офіс компанії на Великій Васильківській" loading="lazy" style="border-radius:16px"></figure></div>');
        break;
}

/* Посилання на калькулятор зі сторінок, де він доречний */
if (in_array($path, ['ocinka/dlya-prodazhu', 'ocinka/dlya-daruvannya', 'ocinka/dlya-spadshchyny', 'ocinka/dlya-notariusa', 'ocinka/kvartyry'], true)) {
    echo bti_section('Скільки податків буде у нотаріуса', '<p style="max-width:720px;color:var(--ink-2)">ПДФО, військовий збір, держмито і пенсійний збір рахуються від оціночної вартості. Порахуйте заздалегідь — щоб сума у нотаріуса не стала сюрпризом.</p><p style="margin-top:18px"><a class="btn btn-primary" href="' . esc_url(bti_url('kalkulyator-podatkiv')) . '">Відкрити калькулятор податків</a></p>', 'sec-mist');
}

if ($d['faq']) echo bti_section('Часті питання', bti_faq_html($d['faq']));
echo bti_related($post);

get_footer();

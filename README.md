# bti-ocinka.com.ua — тема WordPress

Тема сайту «БТІ та оцінка майна». Дизайн і SEO-розмітка — як у статичній версії.

## Перший запуск
1. cPanel → Softaculous → встановити WordPress на bti-ocinka.com.ua.
2. cPanel → Git Version Control → Create → URL цього репозиторію → Create.
3. Там же: Manage → Pull or Deploy → **Deploy HEAD Commit** (тема скопіюється в `wp-content/themes/bti-ocinka`).
4. WordPress → Зовнішній вигляд → Теми → **БТІ та оцінка майна → Активувати**.
   Тема сама створить усі сторінки, головну, блог, ЧПУ `/ocinka/kvartyry/`.
5. Налаштування → **Сайт БТІ**: перевірити контакти, за бажанням підключити Telegram для заявок.

## Оновлення теми
Автоматично: кожен `git push` у `main` запускає GitHub Actions (`.github/workflows/deploy.yml`), який по FTPS заливає папку `bti-ocinka/` на хостинг. Статус — вкладка **Actions** у репозиторії.

Налаштування один раз:
1. cPanel → **FTP Accounts** → створити акаунт (напр. `deploy`) з каталогом `public_html/wp-content/themes/bti-ocinka`.
2. GitHub → репозиторій → **Settings → Secrets and variables → Actions** → додати `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.
3. Вкладка **Actions** → Deploy theme → **Run workflow** (перший запуск).

Запасний варіант вручну: cPanel → Git Version Control → Update from Remote → Deploy HEAD Commit.

## Що де редагувати
- Ціни, терміни, H1, SEO-title/description, «коли потрібна», документи, FAQ — у кожній сторінці, блок «Дані сторінки» під редактором.
- Довільний SEO-текст — у самому редакторі сторінки (виводиться після першого блоку).
- Контакти, рейтинг Google, відгуки, Telegram — Налаштування → Сайт БТІ.
- Заявки з форми — розділ «Заявки» в меню адмінки (+ email і Telegram).
- Статті блогу — Записи. Імпортовані теми статей лежать у чернетках.
- Відсутні сторінки можна докинути: Інструменти → Імпорт сторінок БТІ.

## Структура
- `bti-ocinka/inc/` — логіка (поля, SEO, форма, імпорт, налаштування)
- `bti-ocinka/data/pages.json` — стартовий набір сторінок
- `bti-ocinka/parts/` — розмітка головної та калькулятора
- `bti-ocinka/assets/` — CSS, JS, зображення

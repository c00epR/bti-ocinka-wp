<?php
/**
 * БТІ та оцінка майна — тема.
 * Логіка розкладена по файлах у inc/.
 */
if (!defined('ABSPATH')) exit;

define('BTI_VER', '1.0.0');
define('BTI_DIR', get_template_directory());
define('BTI_URI', get_template_directory_uri());

require_once BTI_DIR . '/inc/config.php';
require_once BTI_DIR . '/inc/helpers.php';
require_once BTI_DIR . '/inc/setup.php';
require_once BTI_DIR . '/inc/fields.php';
require_once BTI_DIR . '/inc/seo.php';
require_once BTI_DIR . '/inc/settings.php';
require_once BTI_DIR . '/inc/form.php';
require_once BTI_DIR . '/inc/import.php';

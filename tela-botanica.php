<?php

/**
 * @link              https://github.com/telabotanica/ep-plugin
 * @since             2.0.0
 * @package           Tela_Botanica_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       Tela Botanica Plugin
 * Plugin URI:        https://github.com/telabotanica/ep-plugin
 * GitHub Plugin URI: https://github.com/telabotanica/ep-plugin
 * Description:       Newsletter management for Tela Botanica
 * Version:           2.0.0
 * Author:            Tela Botanica
 * Author URI:        https://github.com/telabotanica
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       telabotanica
 * Domain Path:       /languages
 * Requires PHP:      8.1
 */

add_filter('wp_mail_from', function () {
    return 'no-reply@tela-botanica.org';
});

add_filter('wp_mail_from_name', function () {
    return 'Tela Botanica';
});

require __DIR__ . '/newsletter/newsletter.php';

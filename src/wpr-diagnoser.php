<?php

/**
 * Plugin Name: WP Rocket - Support Diagnoser
 * Plugin URI:  https://wp-media.me/
 * Description: Helps WP Rocket's support team to diagnose issues
 * Version: x.x.x
 * Author:      WP Rocket Support Team
 * Author URI:  https://wp-rocket.me/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 **/

use \WPR\Diagnoser\WPRDiagnoser;
use \WPR\Diagnoser\QueryStrings\QueryStringFeatures;

// Exit if accessed directly.
defined('ABSPATH') || exit;

// It has 'x.x.x' because this will be replaced in the build:release process, don't touch this line or the version line in the comment at the beginning for this file.
// It is commended here, but it will be uncommented in the build:release process
// define('WPR_DIAGNOSER_VERSION', 'x.x.x');
define('WPR_DIAGNOSER_FILE', __FILE__);

include_once('inc/query-strings.php');

if (file_exists(__DIR__ . '/.a-r')) {
    // Uninstalling after 7 days
    $max_time = 3600 * 24 * 7; // Equivalent to 1 week
    $diagnoser_expiration = get_option('wpr_diagnoser_expiration_time');
    if ($diagnoser_expiration) {
        if (date_timestamp_get(date_create()) > intval($diagnoser_expiration) + $max_time) {
            add_action('admin_init', function () {
                deactivate_plugins(plugin_basename(__FILE__));
                delete_plugins([plugin_basename(__FILE__)]);
            });
            define('WPR_DIAGNOSER_AUTO_UNINSTALL', true);
        }
    } else {
        add_option('wpr_diagnoser_expiration_time', date_timestamp_get(date_create()));
    }
} else {
    delete_option('wpr_diagnoser_expiration_time');
}

/**
 * If the plugin is not being auto-uninstalled, it sets the list of querystrings allowed to be cached by WP Rocket
 */
if (!defined('WPR_DIAGNOSER_AUTO_UNINSTALL')) {
    // Filter rocket_cache_query_strings parameters
    add_filter('rocket_cache_query_strings', '\WPR\Diagnoser\QueryStrings' . '\define_cached_parameters', PHP_INT_MAX - 1);
}
/**
 * Sets the activation and deactivation hooks, so, WP Rocket config file is written consequently
 */
register_activation_hook(__FILE__, '\WPR\Diagnoser\QueryStrings' . '\flush_wp_rocket');
register_deactivation_hook(__FILE__, '\WPR\Diagnoser\QueryStrings' . '\deactivate');


/**
 * If the page visited is not an admin one and the plugin is not being auto-uninstalled, it applies the freatures of the plugin
 */
if (!is_admin() && !defined('WPR_DIAGNOSER_AUTO_UNINSTALL')) {
    include_once('inc/WPRDiagnoser.php');
    include_once('inc/QueryStringsFeatures.php');
    $wpr_query_strings_features = new QueryStringFeatures();
    $wpr_query_strings_features->init();
    $wpr_diagnoser = new WPRDiagnoser();
    $wpr_diagnoser->init();
}

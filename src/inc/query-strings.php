<?php

namespace WPR\Diagnoser\QueryStrings;

/**
 * This declares and returns the list of options that can be enabled or disabled
 */
function get_option_list()
{
    /**
     * This list contains the options that can be enabled or disabled, they have the same name as they have in WP Rocket's code base
     */
    $option_list = [
        'minify_css',
        'minify_js',
        'minify_concatenate_js',
        'remove_unused_css',
        'async_css',
        'delay_js',
        'defer_all_js',
        'lazyload',
        'lazyload_iframes',
        'lazyload_css_bg_img',
        'cdn'
    ];
    return $option_list;
}
/**
 * Declares and returns a list of other querystrings with special effects (Implementation needed for every new querystring)
 */
function get_other_query_strings()
{
    $other_query_strings = [
        'wpr_new_cache',
        'wpr_cache',
        'wpr_deactivate_all',
        'wpr_activate_all',
    ];
    return $other_query_strings;
}

/**
 * Regenerates WP Rocket config file.
 */
function flush_wp_rocket()
{

    if (!function_exists('rocket_generate_config_file')) {
        return false;
    }

    // Regenerate WP Rocket config file.
    rocket_generate_config_file();
}
/**
 * Executed when the plugin is deactivated
 */
function deactivate()
{
    // Remove all functionality added.
    remove_filter('rocket_cache_query_strings', __NAMESPACE__ . '\define_cached_parameters');
    // Make sure the query strings are removed
    add_filter('rocket_cache_query_strings', function (array $params) {
        $option_list = get_option_list();
        $other_cached_query_strings = get_other_query_strings();
        $full_list = array_merge($option_list, $other_cached_query_strings);
        foreach ($params as $key => $param) {
            if (in_array($param, $full_list)) {
                unset($params[$key]);
            }
        }

        return $params;
    }, PHP_INT_MAX);

    // Flush .htaccess rules, and regenerate WP Rocket config file.
    flush_wp_rocket();
}
/**
 * Function sent to WP Rocket filter that returns the list of querystrings allowed to be cached
 */
function define_cached_parameters(array $params)
{
    $option_list = get_option_list();
    $other_query_strings = get_other_query_strings();
    foreach ($option_list as $option) {
        $params[] = $option;
    }
    foreach ($other_query_strings as $query_string) {
        $params[] = $query_string;
    }

    return $params;
}

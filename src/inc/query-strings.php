<?php

namespace WPR\Diagnoser\QueryStrings;

/**
 * This declares and returns the list of querystrings related to WP Rocket options to diable or enable them when it's related querystrings are used
 */
function get_option_list()
{
    /**
     * The key will become querystrings prefixed with "wpr-no-" and "wpr-activate-", so, for `minifycss` the querystrings will be `wpr-no-minifycss` and `wpr-activate-minifycss`
     * 
     * The value is the actual name of the option in WP Rocket (So, it can be used to disable it or enable it), for example `minify_css`
     * 
     * Example:
     * 
     * `'minifycss' => 'minify_css'`
     * This will create a querystring called `wpr-no-minifycss` that will disable `minify_css` option in WP Rocket when the querystring is used.
     * 
     * It will also create a querystring called `wpr-activate-minifycss` that will enable `minify_css` option in WP Rocket when the querystring is used.
     *
     */
    $option_list = [
        'minifycss' => 'minify_css',
        'minifyjs' => 'minify_js',
        'combinejs' => 'minify_concatenate_js',
        'rucss' => 'remove_unused_css',
        'asynccss' => 'async_css',
        'delayjs' => 'delay_js',
        'deferjs' => 'defer_all_js',
        'llimg' => 'lazyload',
        'lliframes' => 'lazyload_iframes',
        'llcssbg' => 'lazyload_css_bg_img',
        'cdn' => 'cdn'
    ];
    return $option_list;
}
/**
 * Declares and returns a list of other querystrings with special effects (Implementation needed for every new querystring)
 */
function get_other_cache_query_strings()
{
    $no_prefix = get_no_query_string_prefix();
    $activate_prefix = get_activate_query_string_prefix();

    $other_cached_query_strings = [
        'wpr-new-cache',
        $no_prefix . 'cache',
        $activate_prefix . 'cache',
        $no_prefix . 'all',
        $activate_prefix . 'all',
    ];
    return $other_cached_query_strings;
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
        $option_list = get_all_prefixed_options();
        $option_list = array_flip($option_list);
        $other_cached_query_strings = get_other_cache_query_strings();
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
 * Declares and returns the prefix for option deactivation
 */
function get_no_query_string_prefix()
{
    return 'wpr-no-';
}
/**
 * Declares and returns the prefix for option activation
 */
function get_activate_query_string_prefix()
{
    return 'wpr-activate-';
}
/**
 * Returns an array of querystrings prefixed with the "no" prefix
 */
function get_wpr_no_prefixed_options()
{
    $query_string_prefix = get_no_query_string_prefix();
    $option_list = get_option_list();
    $prefixed_list = [];
    foreach ($option_list as $key => $option) {
        $prefixed_list[$query_string_prefix . $key] = $option;
    }
    return $prefixed_list;
}
/**
 * Returns an array of querystrings prefixed with the "activate" prefix
 */
function get_wpr_activate_prefixed_options()
{
    $query_string_prefix = get_activate_query_string_prefix();
    $option_list = get_option_list();
    $prefixed_list = [];
    foreach ($option_list as $key => $option) {
        $prefixed_list[$query_string_prefix . $key] = $option;
    }
    return $prefixed_list;
}
/**
 * Returns an array of querystrings prefixed with both "no" and "activate" prefixes
 */
function get_all_prefixed_options()
{
    $prefixed = array_merge(get_wpr_no_prefixed_options(), get_wpr_activate_prefixed_options());
    return $prefixed;
}
/**
 * Function sent to WP Rocket filter that returns the list of querystrings allowed to be cached
 */
function define_cached_parameters(array $params)
{
    $option_list = get_all_prefixed_options();
    $other_cached_query_strings = get_other_cache_query_strings();
    foreach ($option_list as $key => $option) {
        $params[] = $key;
    }
    foreach ($other_cached_query_strings as $query_string) {
        $params[] = $query_string;
    }

    return $params;
}

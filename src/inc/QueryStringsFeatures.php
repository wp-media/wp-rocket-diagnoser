<?php

namespace WPR\Diagnoser\QueryStrings;

// Standard plugin security, keep this line in place.
defined('ABSPATH') or die();

class QueryStringFeatures
{
    function __construct()
    {
    }
    public function init()
    {
        $this->disable_all_options();
        $this->enable_all_options();
        $this->disable_options();
        $this->enable_options();
    }
    private function disable_all_options()
    {
        if (!isset($_GET[get_no_query_string_prefix() . 'all'])) return;
        $option_list = get_wpr_no_prefixed_options();
        add_filter('do_rocket_generate_caching_files', '__return_false');
        foreach ($option_list as $option) {
            add_filter('get_rocket_option_' . $option, '__return_zero');
        }
    }

    private function enable_all_options()
    {
        if (!isset($_GET[get_activate_query_string_prefix() . 'all'])) return;
        $option_list = get_wpr_activate_prefixed_options();
        add_filter('do_rocket_generate_caching_files', '__return_true');
        foreach ($option_list as $option) {
            add_filter('get_rocket_option_' . $option, function () {
                return 1;
            });
        }
    }

    private function disable_options()
    {
        $option_list = get_wpr_no_prefixed_options();
        if (isset($_GET[get_no_query_string_prefix() . 'cache'])) {
            add_filter('do_rocket_generate_caching_files', '__return_false');
        }
        foreach ($option_list as $query_string => $option) {
            if (isset($_GET[$query_string])) {
                add_filter('get_rocket_option_' . $option, '__return_zero');
            }
        }
    }
    private function enable_options()
    {
        $option_list = get_wpr_activate_prefixed_options();
        if (isset($_GET[get_activate_query_string_prefix() . 'cache'])) {
            add_filter('do_rocket_generate_caching_files', '__return_true');
        }
        foreach ($option_list as $query_string => $option) {
            if (isset($_GET[$query_string])) {
                add_filter('get_rocket_option_' . $option, function () {
                    return 1;
                });
            }
        }
    }
}

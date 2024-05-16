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
        $this->deactivate_all_options();
        $this->activate_all_options();
        $this->disable_or_enable_cache();
        $this->disable_or_enable_options();
    }
    private function deactivate_all_options()
    {
        if (!isset($_GET['wpr_deactivate_all'])) return;
        $option_list = get_option_list();
        add_filter('do_rocket_generate_caching_files', '__return_false');
        foreach ($option_list as $option) {
            add_filter('get_rocket_option_' . $option, '__return_zero');
        }
    }

    private function activate_all_options()
    {
        if (!isset($_GET['wpr_activate_all'])) return;
        $option_list = get_option_list();
        add_filter('do_rocket_generate_caching_files', '__return_true');
        foreach ($option_list as $option) {
            add_filter('get_rocket_option_' . $option, function () {
                return 1;
            });
        }
    }
    private function disable_or_enable_cache()
    {
        if (isset($_GET['wpr_cache'])) {
            if ($_GET['wpr_cache'] === "1") {
                add_filter('do_rocket_generate_caching_files', '__return_true');
            } else if ($_GET['wpr_cache'] === "0") {
                add_filter('do_rocket_generate_caching_files', '__return_false');
            }
        }
    }
    private function disable_or_enable_options()
    {
        $option_list = get_option_list();
        foreach ($option_list as $option) {
            if (isset($_GET[$option])) {
                $value = $_GET[$option];
                if ($value !== "1" && $value !== "0") {
                    continue;
                }
                $value = intval($value);
                add_filter('get_rocket_option_' . $option, function () use ($value) {
                    return $value;
                });
            }
        }
    }
}

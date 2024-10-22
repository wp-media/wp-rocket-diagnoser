<?php

namespace WPR\Diagnoser\Options;

defined('ABSPATH') or die();

class WPRDiagnoserOptions
{
    private $option_pre_filter_prefix = 'pre_get_rocket_option_';
    private $option_post_filter_prefix = 'get_rocket_option_';
    private $post_meta_options = ['remove_unused_css', 'delay_js', 'defer_all_js', 'async_css', 'lazyload', 'lazyload_iframes', 'lazyload_css_bg_img', 'minify_css', 'minify_js', 'cdn'];
    /**
     * Contains the list of WP Rocket options and their related filters and options to be processed
     * 
     * Every option must be added as a new entry in the array with the following schema:
     * 
     * An string key containing the real name of the option (WP Rocket internal option name, for example for Delay JS would be: `delay_js`)
     * 
     * The value should be an array that may contain the following keys data:
     * 
     * 'name' key, whose value must be an string containing the name WP Rocket shows in the UI (human friendly name) for the option (in english)
     * 
     * 'get_rocket_option' key, whose value should be an array of strings containing the real names (WP Rocket internal name) of the related options. For example for Delay JS you can add the exclusions field `delay_js_exclusions`
     * 
     * 'filters' key, whose value must be an array of arrays. The arrays will be like ['name_of_the_filter', DEFAUL VALUE], to prevent errors, the default value must be provided, so the website doesn't break in case wrong value is provided
     * 
     * IMPORTANT:
     * You can omit the get_rocket_option and filters keys if the option doesn't have any related to them, but don't omit the name, it is useful for UIs reading this information to show the "human friendly" name
     * 
     * @example An example would be:
     * `
     *  'delay_js' => [
     *      'name' => 'Delay JavaScript Execution',
     *      'get_rocket_option' [
     *           'delay_js_exclusions', 'delay_js_exclusions_selected_exclusions'
     *      ],,
     *      'filters' => [
     *          ['rocket_delay_js_exclusions', []]
     *      ],
     *  ]
     * `
     * 
     */
    private $options = [
        'remove_unused_css' => [
            'name' => 'Remove Unused CSS',
            'get_rocket_option' => ['remove_unused_css_safelist'],
            'filters' => [
                ['rocket_rucss_safelist', []],
                ['rocket_rucss_external_exclusions', []],
                ['rocket_rucss_preserve_google_font', false],
                // This filter ⬇️ won't be very useful, since in WP Rocket it is called per style, and here it is called once, also, it can cause issues since the callback expect an argument: https://github.com/wp-media/wp-rocket/blob/8b0e79150b6b32c9cd23883a6077212e9d943fba/inc/Engine/Optimization/RUCSS/Controller/UsedCSS.php#L394
                // ['rocket_rucss_preserve_inline_style_tags', true],
                ['rocket_rucss_skip_styles_with_attr', []]
            ],
        ],
        'delay_js' => [
            'name' => 'Delay JavaScript Execution',
            'get_rocket_option' => [
                'delay_js_exclusions',
                'delay_js_exclusions_selected_exclusions'
            ],
            'filters' => [
                ['rocket_delay_js_exclusions', []]
            ],
        ],
        'defer_all_js' =>
        [
            'name' => 'Load JavaScript Deferred',
            'get_rocket_option' => ['exclude_defer_js'],
            'filters' => [
                ['rocket_exclude_defer_js', []]
            ],
        ],
        'async_css' =>
        [
            'name' => 'Load CSS Asynchronously',
            'get_rocket_option' => ['critical_css'],
        ],
        'lazyload' =>
        [
            'name' => 'Lazyload',
            'get_rocket_option' => [
                'exclude_lazyload'
            ],
            'filters' => [
                ['rocket_lazyload_threshold', 300],
                ['rocket_use_native_lazyload', false],
                ['rocket_use_native_lazyload_images', false],
                ['rocket_lazyload_background_images', true],
                ['do_rocket_lazyload', true],
                ['do_rocket_lazyload_iframes', true]
            ],
        ],

        'lazyload_iframes' =>
        [
            'name' => 'Lazyload for iframes and videos',
        ],
        'lazyload_youtube' =>
        [
            'name' => 'Replace YouTube iframe with preview image',
        ],
        'lazyload_css_bg_img' =>
        [
            'name' => 'Lazyload for CSS background images',
        ],
        'image_dimensions' => [
            'filters' => [
                ['rocket_specify_dimension_images', []],
                ['rocket_specify_dimension_skip_pictures', true],
                ['rocket_specify_image_dimensions_for_distant', false]
            ]
        ],
        'minify_css' =>
        [
            'name' => 'Minify CSS Files',
            'get_rocket_option' => [
                'exclude_css'
            ],
        ],
        'minify_js' =>
        [
            'name' => 'Minify JavaScript Files',
            'get_rocket_option' => [
                'exclude_js',
                'exclude_inline_js'
            ],
        ],
        'minify_concatenate_js' => [
            'name' => 'Combine JavaScript Files',
            'get_rocket_option' => [
                'exclude_js',
                'exclude_inline_js'
            ]
        ],
        'cdn' =>
        [
            'name' => 'CDN',
            'get_rocket_option' => [
                'cdn_reject_files'
            ],
        ],
        'cache_webp' =>
        [
            'name' => 'WebP Addon',
        ],
        'cache_ssl' => [
            'name' => 'SSL Cache'
        ]
    ];
    /**
     * Will contain the information that will be converted to JSON
     */
    private $result = [
        'options' => [],
        'post_meta_excluded_options' => []
    ];
    public function __construct()
    {
        $this->fill_result();
    }
    private function fill_result()
    {
        foreach ($this->options as $option_name => $value) {
            $this->result['options'][$option_name] = [];
            $this->result['options'][$option_name]['filters'] = [];
            $this->result['options'][$option_name]['get_rocket_option'] = [];
            $this->result['options'][$option_name]['get_rocket_option'][$option_name] = get_rocket_option($option_name);
            if (isset($value['get_rocket_option'])) {
                $this->fill_options($option_name, $value['get_rocket_option']);
            }
            if (isset($value['filters'])) {
                add_filter('rocket_buffer', function ($html) use ($option_name, $value) {
                    $this->set_filters($option_name, $value);
                    return $html;
                }, 1);
            }
            $this->fill_rocket_post_meta_options();
        }
    }
    private function set_filters(string $option_name, array $value)
    {
        if (isset($value['filters'])) {
            foreach ($value['filters'] as $filter) {
                $filter_type = gettype($filter);
                if ($filter_type === 'array' && count($filter) < 2) continue;
                $filter_name = $filter_type === 'array' ? (string) $filter[0] : (string) $filter;
                $filter_value = null;
                if ($filter_type === 'array') $filter_value = $filter[1];
                $this->result['options'][$option_name]['filters'][$filter_name] = apply_filters($filter_name, $filter_value);
            }
        }
        if (isset($value['get_rocket_option'])) {
            foreach ($value['get_rocket_option'] as $option) {
                $filter_name = $this->option_pre_filter_prefix . $option;
                $this->result['options'][$option_name]['filters'][$filter_name] = apply_filters($filter_name, null);
                $filter_name = $this->option_post_filter_prefix . $option;
                $this->result['options'][$option_name]['filters'][$filter_name] = apply_filters($filter_name, null);
            }
        }
        $filter_name = $this->option_pre_filter_prefix . $option_name;
        $this->result['options'][$option_name]['filters'][$filter_name] = apply_filters($filter_name, null);
        $filter_name = $this->option_post_filter_prefix . $option_name;
        $this->result['options'][$option_name]['filters'][$filter_name] = apply_filters($filter_name, null);
    }
    public function fill_options(string $option_name, array $option_list)
    {
        foreach ($option_list as $option) {
            $this->result['options'][$option_name]['get_rocket_option'][$option] = get_rocket_option($option);
        }
    }
    private function fill_rocket_post_meta_options()
    {
        $excluded_options = [];
        foreach ($this->options as $option_name => $value) {
            if (in_array($option_name, $this->post_meta_options, true)) {
                $is_excluded = (bool) is_rocket_post_excluded_option($option_name);
                if ($is_excluded) $excluded_options[] = $option_name;
            }
        }
        $this->result['post_meta_excluded_options'] = $excluded_options;
    }
    public function get_list()
    {
        return $this->options;
    }
    public function get_result()
    {
        return $this->result;
    }
}

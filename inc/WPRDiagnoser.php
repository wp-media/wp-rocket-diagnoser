<?php

namespace WPR\Diagnoser;

include_once('WPRDiagnoserOptions.php');
include_once('CPUInfo.php');

use WPR\Diagnoser\Options\WPRDiagnoserOptions;
use WPR\Diagnoser\Server\CPUInfo;

// use WP_Rocket\Buffer\Tests;

// Standard plugin security, keep this line in place.
defined('ABSPATH') or die();

class WPRDiagnoser
{
  private $option_pre_filter_prefix = 'pre_get_rocket_option_';
  private $option_post_filter_prefix = 'get_rocket_option_';
  private $options;
  /**
   * A list of constant names which will be checked to see if they are defined and which value they have
   */
  private $constant_names = [
    'WP_CACHE',
    'DONOTCACHEPAGE',
    'DONOTROCKETOPTIMIZE',
    'DONOTMINIFY',
    'DONOTMINIFYCSS',
    'DONOTMINIFYJS',

  ];
  /**
   * A list of filters related to Preload and RUCSS parameters
   */
  private $preload_rucss_parameters = [
    ['rocket_preload_cache_pending_jobs_cron_rows_count', 45],
    ['rocket_preload_pending_jobs_cron_interval', 60],
    ['rocket_preload_delay_between_requests', 500000],
    ['rocket_rucss_pending_jobs_cron_rows_count', 100],
    ['rocket_rucss_pending_jobs_cron_interval', 60]
  ];
  /**
   * Some WP Rocket filters
   * 
   * Provide the name of the filter as the key and the defaul value as the value in the array:
   * 
   * `
   * 'name_of_the_filter' => DEFAULT_VALUE
   * `
   */
  private $no_rocket_filters = [
    'do_rocket_generate_caching_files' => true,
    'rocket_override_donotcachepage' => false,
    'rocket_cache_ignored_parameters' => [],
    'rocket_cache_query_strings' => []
  ];
  /**
   * Some WP Rocket options not related to optimizations
   */
  private $no_rocket_options = ['version', 'cache_reject_uri', 'cache_reject_ua', 'cache_reject_cookies', 'cache_mobile', 'do_caching_mobile_files', 'manual_preload', 'preload_excluded_uri', 'cache_logged_user', 'cache_query_strings', 'cache_purge_pages', 'purge_cron_interval', 'dns_prefetch', 'preload_fonts'];
  /**
   * The statuses RUCSS and preload have (to-submit is omited later for preload)
   */
  private $preload_rucss_tasks_status = [
    'completed',
    'pending',
    'in-progress',
    'to-submit'
  ];
  function __construct()
  {
  }
  /**
   * Runs the initialization of the class
   */
  public function init()
  {
    $this->set_no_rocket_print_on_footer();
    add_action(
      'wp_footer',
      function () {
        if (!function_exists('get_rocket_option')) return;
        $this->options = new WPRDiagnoserOptions();
      }
    );
    add_filter('rocket_buffer', function ($html) {
      $options_result = $this->options->get_result();
      $json = json_encode($options_result);
      $html = $this->rocket_print_on_html($html, $json);
      return $html;
    }, PHP_INT_MAX);
  }
  /**
   * Checks all the filters and their values so they can be added to the JSON output
   */
  private function get_filters()
  {
    $result = [];
    foreach ($this->no_rocket_filters as $filter_name => $default_value) {
      $result[$filter_name] = apply_filters($filter_name, $default_value);
    }
    foreach ($this->no_rocket_options as $option_name) {
      $filter_name = $this->option_pre_filter_prefix . $option_name;
      $result[$filter_name] = apply_filters($filter_name, null);
      $filter_name = $this->option_post_filter_prefix . $option_name;
      $result[$filter_name] = apply_filters($filter_name, null);
    }
    return $result;
  }
  /**
   * Checks all the constants and their values so they can be added to the JSON output
   */
  private function get_constants()
  {
    $result = [];
    foreach ($this->constant_names as $const) {
      if (!defined($const)) {
        $result[$const] = [
          'defined' => false,
        ];
      } else {
        $result[$const] = [
          'defined' => true,
          'value' => constant($const),
        ];
      }
    }
    return $result;
  }
  /**
   * Checks all the options and their values so they can be added to the JSON output
   */
  private function get_options()
  {
    $result = [];
    foreach ($this->no_rocket_options as $option_name) {
      $option_value = get_rocket_option($option_name);
      if ($option_name === 'purge_cron_interval') {
        $unit = get_rocket_option('purge_cron_unit');
        if ($unit === 'HOUR_IN_SECONDS') {
          $unit = 'hours';
        } else if ($unit === 'DAY_IN_SECONDS') {
          $unit = 'days';
        }
        $option_value = $option_value . ' ' . $unit;
      }
      $result[$option_name] = $option_value;
    }
    return $result;
  }
  /**
   * Checks preload and rucss parameters and their values so they can be added to the JSON output
   */
  private function get_preload_rucss_parameters()
  {
    $result = [];
    foreach ($this->preload_rucss_parameters as $parameter) {
      $result[$parameter[0]] = apply_filters($parameter[0], $parameter[1]);
    }
    return $result;
  }
  /**
   * Gets the Server Load so it can be added to the JSON output
   */
  private function getServerLoad()
  {
    $result = CPUInfo::getServerLoad();
    return $result;
  }
  /**
   * Gets some information related to the server so it can be added to the JSON output
   */
  private function getServerInformation()
  {
    $result = [];
    $result['server_load'] = $this->getServerLoad();
    $result['web_server'] = $this->web_server_name();
    $php_memory_limit = ini_get('memory_limit');
    $result['php_memory_limit'] = $php_memory_limit === false ? null : $php_memory_limit;
    $result['wp_memory_limit'] = defined('WP_MEMORY_LIMIT') ? constant('WP_MEMORY_LIMIT') : 'Not set';
    $result['wp_max_memory_limit'] = defined('WP_MAX_MEMORY_LIMIT') ? constant('WP_MAX_MEMORY_LIMIT') : 'Not set';
    return $result;
  }
  /**
   * Gets the amount of tasks by status from preload and rucss
   */
  private function getPreloadAndRUCSSTasks()
  {
    $result = [];
    try {
      $result['rucss_all'] = $this->getFromTransient('all', 'rucss', 'wpr_rucss_used_css');
      $result['preload_all'] = $this->getFromTransient('all', 'preload', 'wpr_rocket_cache');
      foreach ($this->preload_rucss_tasks_status as $status) {
        $result['rucss_' . $status] = $this->getFromTransient($status, 'rucss', 'wpr_rucss_used_css');
        // Ignores `'to-submit'` for preload
        if ($status !== 'to-submit') $result['preload_' . $status] = $this->getFromTransient($status, 'preload', 'wpr_rocket_cache');
      }
    } catch (\Throwable $th) {
      return null;
    }

    return $result;
  }
  /**
   * Helps to reduce the load of the DB by getting the information of preload and rucss tasks from a transient or saving it to the transient if this doesn't exist or it is expired
   */
  private function getFromTransient($status, $option, $table)
  {
    global $wpdb;
    $prefix = 'wpr_diagnoser_';
    $transient_name = $prefix . $option . $status;
    $value = get_transient($transient_name);
    if ($value === false) {
      if ($status === 'all') {
        $value = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->prefix" . $table);
        set_transient($transient_name, $value, 30);
      } else {
        $value = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->prefix" . $table . " WHERE status = '$status'");
        set_transient($transient_name, $value, 30);
      }
    }
    return $value;
  }
  /**
   * Returns the kind of server detected by WordPress (Apache, nginx, etc)
   */
  private function web_server_name()
  {
    global $is_apache, $is_IIS, $is_iis7, $is_nginx, $is_caddy;
    if ($is_iis7) {
      return 'IIS7';
    }
    if ($is_IIS) {
      return 'IIS';
    }
    if ($is_caddy) {
      return 'Caddy';
    }
    if ($is_nginx) {
      return 'NGINX';
    }
    if ($is_apache) {
      return 'Apache';
    }
    return "Unknown";
  }
  /**
   * Prints the JSON data in the HTML related to optimizations (Only when rocket_buffer can be filtered)
   */
  private function rocket_print_on_html(string $html, string $json)
  {
    $replace = preg_replace(
      '#</title>#iU',
      '</title>' . '<script type="application/json" id="wpr-diagnoser-rocket-json-data">' . $json . '</script>',
      $html,
      1
    );
    if (null === $replace) {
      return $html;
    }
    return $replace;
  }
  /**
   * Prints the JSON data in the HTML related to WP Rocket (query strings, constants, etc.), so, it can be printed to the HTML even if rocket_buffer is not used
   */
  private function set_no_rocket_print_on_footer()
  {
    add_action('wp_footer', function () {
      if (!function_exists('json_encode')) {
        $json = '{ "error": "Server does not seem to have the ability to encode JSON" }';
      } else {
        $result = [];
        $result['wpr_plugin'] = function_exists('get_rocket_option') ? 'active' : 'not-active';
        $result['constants'] = $this->get_constants();
        $result['preload_rucss_parameters'] = $this->get_preload_rucss_parameters();
        $result['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $result['server_info'] = $this->getServerInformation();
        $result['imagify'] = !defined('IMAGIFY_VERSION') ? null : constant('IMAGIFY_VERSION');
        $result['preload_rucss_tasks'] = $this->getPreloadAndRUCSSTasks();
        if (function_exists('get_rocket_option')) {
          $result['querystrings'] = $_GET;
          $result['filters'] = $this->get_filters();
          $result['get_rocket_option'] = $this->get_options();
        };
        $json = json_encode($result);
      }
      print '<script type="application/json" id="wpr-diagnoser-no-rocket-json-data">' . $json . '</script>';
    });
  }
}

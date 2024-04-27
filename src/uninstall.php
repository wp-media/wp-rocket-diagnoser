<?php

// If uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('wpr_diagnoser_expiration_time');

if (function_exists('rocket_generate_config_file')) {
    // Regenerate WP Rocket config file.
    rocket_generate_config_file();
}

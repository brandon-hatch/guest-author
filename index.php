<?php
/*
Plugin Name: Guest Author
Plugin URI: https://wordpress.org/plugins/guest-author/
Description: The only guest author plugin you need.
Version: 2.3
Tested up to: 6.1
Author: WebFactory Ltd
Author URI: https://www.webfactoryltd.com/
License: GNU General Public License v3.0
Text Domain: guest-author
*/

if ( !defined('ABSPATH') )
    die('-1');

define('BS_GUEST_AUTHOR_VERSION', '2.2');

/**
 * Loading Guest Author class
 */
$root_dir = is_dir( plugin_dir_path(__FILE__) . 'src') ? 'src/' : '';
require plugin_dir_path(__FILE__) . $root_dir . 'guest-author.php';
require plugin_dir_path(__FILE__) . $root_dir . 'settings.php';
require plugin_dir_path(__FILE__) . $root_dir . 'functions.php';

function register_BS_Guest_Author_plugin() {
    new BS_Guest_Author();
    new BS_Guest_Author_Settings();
}

add_action('init', 'register_BS_Guest_Author_plugin');

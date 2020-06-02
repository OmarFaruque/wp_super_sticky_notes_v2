<?php
/**
 * Plugin Name: Wp Super Sticky Notes
 * Plugin URI: http://larasoftbd.net/
 * Description: Wp Super Sticky Notes. 
 * Version: 1.0.1
 * Author: larasoft
 * Author URI: https://larasoftbd.net
 * Text Domain: wp_super_sticky_notes
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 4.8
 *
 * @package     wp_super_sticky_notes
 * @category 	Core
 * @author 		LaraSoft
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define('wp_super_sticky_notesDIR', plugin_dir_path( __FILE__ ));
define('wp_super_sticky_notesURL', plugin_dir_url( __FILE__ ));

require_once(wp_super_sticky_notesDIR . 'inc/class.php');

new wp_super_sticky_notesClass;

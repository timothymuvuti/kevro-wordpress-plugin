<?php
/**
 * Plugin Name: Import Products from Kevro
 * Version: 1.0.0
 * Plugin URI: https://www.timothymuvuti.com/
 * Description: This is a custom developed plugin for importing products from Kevro
 * Author: Timothy Muvuti
 * Author URI: https://www.timothymuvuti.com/
 * Requires at least: 4.0
 * Tested up to: 5.3
 *
 * Text Domain: import-products-from-kevro
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Timothy Muvuti
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-import-products-from-kevro.php';
require_once 'includes/class-import-products-from-kevro-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-import-products-from-kevro-admin-api.php';
require_once 'includes/lib/class-import-products-from-kevro-post-type.php';
require_once 'includes/lib/class-import-products-from-kevro-taxonomy.php';

/**
 * Returns the main instance of Import_Products_from_Kevro to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Import_Products_from_Kevro
 */
function import_products_from_kevro() {
	$instance = Import_Products_from_Kevro::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Import_Products_from_Kevro_Settings::instance( $instance );
	}

	return $instance;
}

import_products_from_kevro();

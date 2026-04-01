<?php
/**
 * Plugin Name: ShopSavvy Price Comparison
 * Description: Display real-time price comparisons from thousands of retailers using Gutenberg blocks, shortcodes, and widgets.
 * Version: 1.0.0
 * Author: ShopSavvy
 * Author URI: https://shopsavvy.com
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * Text Domain: shopsavvy
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SHOPSAVVY_VERSION', '1.0.0' );
define( 'SHOPSAVVY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHOPSAVVY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SHOPSAVVY_API_BASE', 'https://api.shopsavvy.com' );

// Load plugin classes.
require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-cache.php';
require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-client.php';
require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-settings.php';
require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-shortcode.php';
require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-widget.php';
require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-rest.php';

/**
 * Initialize the plugin.
 */
function shopsavvy_init(): void {
    ShopSavvy_Settings::init();
    ShopSavvy_Shortcode::init();
    ShopSavvy_REST::init();

    add_action( 'widgets_init', 'shopsavvy_register_widgets' );
    add_action( 'init', 'shopsavvy_register_block' );
    add_action( 'wp_enqueue_scripts', 'shopsavvy_enqueue_frontend_assets' );
}
add_action( 'plugins_loaded', 'shopsavvy_init' );

/**
 * Register the sidebar widget.
 */
function shopsavvy_register_widgets(): void {
    register_widget( 'ShopSavvy_Price_Widget' );
}

/**
 * Register the Gutenberg block.
 */
function shopsavvy_register_block(): void {
    register_block_type( SHOPSAVVY_PLUGIN_DIR . 'blocks/price-comparison' );
}

/**
 * Enqueue frontend CSS.
 */
function shopsavvy_enqueue_frontend_assets(): void {
    wp_enqueue_style(
        'shopsavvy-frontend',
        SHOPSAVVY_PLUGIN_URL . 'assets/css/shopsavvy-frontend.css',
        array(),
        SHOPSAVVY_VERSION
    );
}

/**
 * Plugin activation hook.
 */
function shopsavvy_activate(): void {
    // Set default options on activation.
    if ( false === get_option( 'shopsavvy_cache_duration' ) ) {
        add_option( 'shopsavvy_cache_duration', 3600 );
    }
    if ( false === get_option( 'shopsavvy_default_style' ) ) {
        add_option( 'shopsavvy_default_style', 'table' );
    }
    if ( false === get_option( 'shopsavvy_rest_enabled' ) ) {
        add_option( 'shopsavvy_rest_enabled', '0' );
    }
}
register_activation_hook( __FILE__, 'shopsavvy_activate' );

/**
 * Plugin deactivation hook.
 */
function shopsavvy_deactivate(): void {
    // Clean up transient cache entries.
    ShopSavvy_Cache::flush_all();
}
register_deactivation_hook( __FILE__, 'shopsavvy_deactivate' );

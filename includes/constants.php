<?php

/**
 * Define plugin constants
 */

defined('OES_MAP_VERSION') || define('OES_MAP_VERSION', '1.1.0');

// Base URL to the plugin directory
defined('OES_MAP_PLUGIN_URL') || define(
    'OES_MAP_PLUGIN_URL',
    esc_url_raw(plugin_dir_url(dirname(__FILE__) . '/oes-map.php') . 'oes-map.php')
);

// Shortcode prefix for all map-related shortcodes
defined('OES_MAP_SHORTCODE_PREFIX') || define('OES_MAP_SHORTCODE_PREFIX', 'map');

// Default parameters for the [oes_map] shortcode
defined('OES_MAP_SHORTCODE_PARAMETER') || define('OES_MAP_SHORTCODE_PARAMETER', [
    'post_type' => [],
    'ids' => [],
    'categories' => [
        'nested' => true,
        'alias' => 'cat',
    ],
    'defaultzoom' => [],
    'center' => [],
    'show_legend' => [],
    'legend_text' => [],
    'name' => [
        'ignore' => true,
    ]
]);

<?php

namespace OES\MAP;

/**
 * OES Map (OES Core Module)
 *
 * @wordpress-plugin
 * Plugin Name:        OES Map (OES Core Module)
 * Plugin URI:         https://www.open-encyclopedia-system.org/
 * Description:        Display a collection of places on a map using OpenStreetMap and Leaflet. Requires OES Core to function.
 * Version:            1.2.0
 * Author:             Maren Welterlich-Strobl, Freie Universität Berlin, FUB-IT, Digitale Forschungsinfrastrukturen
 * Author URI:         https://www.fu-berlin.de/
 * Requires at least:  6.5
 * Tested up to:       6.8.2
 * Requires PHP:       8.1
 * License:            GPLv2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */


if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('OES_MAP_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('oes/plugins_loaded', function () {

    global $oes;
    if (!$oes || empty($oes->initialized)) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning is-dismissible"><p>' .
                esc_html__('The OES Core plugin is loaded but not yet initialized.', 'oes') . '</p></div>';
        });
        return;
    }

    include_once __DIR__ . '/includes/constants.php';
    include_once __DIR__ . '/includes/functions.php';
    include_once __DIR__ . '/includes/class-map.php';
    include_once __DIR__ . '/includes/class-entry.php';

    if (is_admin()) {
        include_once __DIR__ . '/includes/admin/class-tool-shortcode_map.php';
        include_once __DIR__ . '/includes/admin/class-module_page.php';
    }

    add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts');

    // Shortcodes @oesDevelopment make this into blocks
    add_shortcode('oes_map', __NAMESPACE__ . '\\html');
    add_shortcode('oes_map_legend', __NAMESPACE__ . '\\legend_html');

    // @oesDevelopment add this to documentation
    add_shortcode('oes_map_archive_switch', __NAMESPACE__ . '\\archive_switch_html');
    add_shortcode('oes_map_spinner', __NAMESPACE__ . '\\spinner_html');

    do_action('oes/map_plugin_loaded');

}, 12);

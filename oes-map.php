<?php

/**
 * Plugin Name: OES Map (OES Core Module)
 * Plugin URI: http://www.open-encyclopedia-system.org/
 * Description: Display a collection of places (post type that includes location fields) with a map. This plugin uses open street map, https://www.openstreetmap.org and leaflet, https://leafletjs.com.
 * Version: 1.0.0
 * Author: Maren Welterlich-Strobl, Freie Universität Berlin, Center für Digitale Systeme an der Universitätsbibliothek
 * Author URI: https://www.cedis.fu-berlin.de/cedis/mitarbeiter/beschaeftigte/mstrobl.html
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('OES_MAP_PLUGIN_URL', plugin_dir_url( __FILE__ ));
const OES_MAP_SHORTCODE_PARAMETER = [
    'post_type' => [],
    'ids' => [],
    'categories' => [
        'nested' => true,
        'alias' => 'cat'
    ],
    'defaultzoom' => [],
    'center' => [],
    'show_legend' => [],
    'legend_text' => [],
    'name' => [
        'ignore' => true
    ],
    'replace_archive' => [
        'ignore' => true
    ]
];
const OES_MAP_SHORTCODE_PREFIX = 'map';


add_action('oes/plugins_loaded', function () {

    /* check if OES Core Plugin is activated */
    if (!function_exists('OES')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning is-dismissible"><p>' .
                __('The OES Core Plugin is not active.', 'oes') . '</p></div>';
        });
    } else {

        /* exit early if OES Plugin was not completely initialized */
        if (!OES()->initialized) return;

        include_once(__DIR__ . '/includes/admin/functions-admin.php');
        include_once(__DIR__ . '/includes/admin/class-tool-shortcode_map.php');
        include_once(__DIR__ . '/includes/class-entry.php');
        include_once(__DIR__ . '/includes/functions.php');

        add_filter('oes/admin_menu_pages', '\OES\Map\admin_menu_pages');

        add_action('wp_enqueue_scripts', '\OES\Map\enqueue_scripts');
        add_action('oes/theme_archive_list', '\OES\Map\theme_archive_list');

        add_shortcode('oes_map', '\OES\Map\html');
        add_shortcode('oes_map_legend', '\OES\Map\legend_html');
    }
}, 12);
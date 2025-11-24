<?php

namespace OES\Map;

/**
 * Display a dynamic frontend map based on provided arguments.
 *
 * This function collects post IDs based on either a list or post type, organizes them by category,
 * instantiates map entries for each post, filters and formats them, and then returns the final HTML representation.
 *
 * @param array $args {
 *     Optional. Arguments to control the behavior and appearance of the map.
 *
 * @type array|string $ids Array or comma-separated string of post IDs to include.
 * @type string $post_type Post type to query if IDs are not provided.
 * @type array $categories Array of category configurations keyed by category index.
 * @type string $lat_field ACF field key for latitude (used per category).
 * @type string $lon_field ACF field key for longitude.
 * @type string $google_field ACF Google Map field key (fallback).
 * @type string $popup_function Callback for generating popup content.
 * @type string $popup_field ACF field key for popup content.
 * @type string $popup_text Default popup text.
 * @type string $color Marker color.
 * @type string $title Category title.
 * }
 *
 * @return string The rendered HTML of the map or empty string if in admin.
 */
function html(array $args): string
{
    if (is_admin()) {
        return '';
    }

    $class = oes_get_project_class_name('\OES\Map\Map', '\OES\Map');
    $oesMap = new $class($args);

    // Update global state with map categories and ID
    global $oes_map_categories, $oes_map_data;
    $mapId = $oesMap->map_ID;
    $oes_map_categories[$mapId] = $oesMap->categories;
    $oes_map_data = true;

    // Return the final HTML map representation
    return $oesMap->get_map_div();
}

/**
 * Get default popup text for map entry.
 *
 * @param string|int $post_id The post id.
 * @param string $text The popup text.
 * @return string Return the popup text.
 */
function popup_text($post_id, string $text = ''): string
{
    return sprintf('<a href="%s" class="oes-map-popup-link">%s</a><div class="oes-map-popup-text">%s</div>',
        get_permalink($post_id),
        oes_get_display_title($post_id),
        $text
    );
}

/**
 * Include assets if page contains map shortcode.
 */
function enqueue_scripts(): void
{
    if (has_map_shortcode()) {
        enqueue_map_scripts();
    }
}

/**
 * Include assets necessary to display map.
 */
function enqueue_map_scripts(): void
{

    // Scripts

    wp_register_script('leaflet', OES_MAP_PLUGIN_URL . 'leaflet/1.9.4/leaflet.js', [], '1.9.4', true);
    wp_enqueue_script('leaflet');

    wp_register_script('leaflet.panel-layers', OES_MAP_PLUGIN_URL . 'leaflet/leaflet-panel-layers/leaflet-panel-layers.min.js', ['leaflet'], '1.2.6');
    wp_enqueue_script('leaflet.panel-layers');

    wp_register_script('oes-map.leaflet', OES_MAP_PLUGIN_URL . 'assets/js/leaflet.min.js');
    wp_enqueue_script('oes-map.leaflet');

    wp_register_script('oes-map', OES_MAP_PLUGIN_URL . 'assets/js/map.min.js', [], false, true);
    wp_enqueue_script('oes-map');

    wp_register_script('oes-map.filter', OES_MAP_PLUGIN_URL . 'assets/js/map-filter.min.js', [], false, true);
    wp_enqueue_script('oes-map.filter');

    // Styles

    wp_register_style('leaflet', OES_MAP_PLUGIN_URL . 'leaflet/1.9.4/leaflet.css', [], '1.9.4', 'screen');
    wp_enqueue_style('leaflet');

    wp_register_style('leaflet.panel-layers', OES_MAP_PLUGIN_URL . 'leaflet/leaflet-panel-layers/leaflet-panel-layers.min.css', [], '1.2.6', 'screen');
    wp_enqueue_style('leaflet.panel-layers');

    wp_register_style('oes-map', OES_MAP_PLUGIN_URL . 'assets/css/map.css');
    wp_enqueue_style('oes-map');
}

/**
 * Determine if page include map shortcode.
 *
 * @return bool Return true if page includes shortcode.
 */
function has_map_shortcode(): bool
{
    global $post, $oes_map_data;
    return ($post && has_shortcode($post->post_content, 'oes_map')) || $oes_map_data;
}

/**
 * Generate HTML for an external legend tied to an OES map.
 *
 * This legend allows users to toggle map categories from outside the map container.
 *
 * @param array $args {
 *     Optional. Additional arguments.
 *
 *     @type string $id The map ID. Defaults to 'oes_map_1'.
 * }
 * @return string The HTML output for the external legend.
 */
function legend_html(array $args = []): string
{
    // Determine map ID
    $mapID = $args['id'] ?? 'oes_map_1';

    // Get global categories for this map
    global $oes_language, $oes_map_categories;

    $categories = $oes_map_categories[$mapID] ?? [];
    if (empty($categories)) {
        return ''; // No legend output if no categories exist
    }

    // Legend HTML containers
    $legendItems = [];
    $allIconsHtml = '';

    foreach ($categories as $key => $singleCategory) {
        $color = $singleCategory['color'] ?? '#111111';
        $iconHTML = get_category_icon($color);
        $title = $singleCategory['title'] ?? $key;

        // Append to full icon row for "All" toggle
        $allIconsHtml .= $iconHTML;

        // Individual category legend item
        $legendItems[] = sprintf(
            '<a href="javascript:void(0)" class="oes-map-external-legend-toggle" data-map-id="%s" data-category="%s">%s %s</a>',
            esc_attr($mapID),
            esc_attr($key),
            $title,
            $iconHTML
        );
    }

    // "All" button label
    $allLabel = OES()->theme_labels['archive__filter__all_button'][$oes_language] ?? __('All', 'oes-map');

    // Final HTML output
    return sprintf(
        '<div class="oes-map-legend">
            <ul class="oes-vertical-list">
                <li><a href="javascript:void(0)" class="oes-map-external-legend-toggle" data-map-id="%s" data-category="all">%s %s</a></li>
                <li>%s</li>
            </ul>
        </div>',
        esc_attr($mapID),
        esc_html($allLabel),
        $allIconsHtml,
        implode('</li><li>', $legendItems)
    );
}

/**
 * Get a category icon in a specific color.
 *
 * @param string $color The category color.
 * @return string Return the HTML representation of a category icon.
 */
function get_category_icon(string $color = '#1111111'): string
{
    return '<svg class="oes-map-legend-icon" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">' .
        '<circle cx="25" cy="25" r="20" stroke="' . $color . '" fill="' . $color . '" stroke-opacity="1" stroke-width="1"></circle>' .
        '</svg>';
}

/**
 * Generates HTML for a toggle navigation bar between a map view and a list view.
 *
 * This function is intended as a shortcode callback and builds a toggle UI
 * consisting of two buttons ("Map" and "List") to allow the user to switch views.
 *
 * Labels can be passed in for multiple languages using keys like:
 * - 'label_list' (default)
 * - 'label_list_language0', 'label_list_language1', etc. (language-specific)
 * - 'label_map' (default)
 * - 'label_list_language0', 'label_list_language1', etc. (language-specific)
 *
 * The order of the buttons can be controlled via the 'list_first' argument.
 *
 * @param array $args {
 *     Optional. Array of shortcode attributes.
 *
 *     @type string $id              The ID of the map element. Defaults to 'oes_map_1'.
 *     @type string $label_list      Default label for the list button.
 *     @type string $label_map       Default label for the map button.
 *     @type string $label_list_xx   Language-specific label for list (e.g., 'label_list_language0').
 *     @type string $label_map_xx    Language-specific label for map (e.g., 'label_map_language0').
 *     @type bool   $list_first      Whether the list button should appear before the map button.
 * }
 * @return string HTML markup for the view switch toggle.
 */
function archive_switch_html(array $args): string
{
    global $oes_language;
    $mapID = $args['id'] ?? 'oes_map_1';

    $labelList = $args['label_list_' . $oes_language] ?? ($args['label_list'] ?? __('List', 'oes-map'));
    $listToggle = sprintf(
        '<a href="javascript:void(0)" class="oes-map-list-toggle oes-map-nav-toggle wp-element-button">%s</a>',
        esc_html($labelList)
    );

    $labelMap = $args['label_map_' . $oes_language] ?? ($args['label_map'] ?? __('Map', 'oes-map'));
    $mapToggle = sprintf(
        '<a href="javascript:void(0)" class="oes-map-map-toggle oes-map-nav-toggle wp-element-button" data-map-id="%s">%s</a>',
        esc_attr($mapID),
        esc_html($labelMap)
    );

    $toggleMarkup = ($args['list_first'] ?? false)
        ? ($listToggle . '</li><li>' . $mapToggle)
        : ($mapToggle . '</li><li>' . $listToggle);

    return '<div class="oes-map-nav-container">
                <ul class="oes-map-archive-tabs-list oes-horizontal-list">
                    <li>' . $toggleMarkup . '</li>
                </ul>
            </div>';
}

/**
 * Generates HTML for a spinner that shows while filtering is processed.
 *
 * @param array $args Optional. Array of shortcode attributes.
 * @return string HTML markup for a spinner.
 */
function spinner_html(array $args): string
{
    return '<div id="oes-map-loading-spinner"><div class="spinner"></div></div>';
}
<?php

namespace OES\Map;


/**
 * Display map.
 *
 * @param array $args The map arguments.
 * @return string The map.
 */
function html(array $args): string
{
    if (is_admin()) return '';

    //@oesDevelopment: use global $oes_archive_data for archive display.
    /* get posts */
    if (empty($args['ids'] ?? false)) {
        if (empty($args['post_type'] ?? false)) return '';
        else $ids = oes_get_wp_query_posts(['post_type' => $args['post_type'], 'fields' => 'ids']);
    } else {
        if ($args['ids'] == 'this') $ids[] = get_the_ID();
        else $ids = is_array($args['ids']) ? $args['ids'] : explode(',', $args['ids']);
    }


    /* sort posts into categories */
    $categories = [];
    $layerCategories = [];
    $data = [];
    foreach ($ids as $singlePostID) {

        /* get post object */
        $singlePost = get_post($singlePostID);
        if (!$singlePost) continue;
        $singlePostType = $singlePost->post_type;

        if (empty($categories[$singlePostType] ?? '')) {

            /* category by shortcode */
            $i = 1;
            while (isset($args['cat' . $i]) || isset($args['categories'][$i])) {

                $shortcode = isset($args['cat' . $i]);
                $parameterArray = $shortcode ?
                    explode(';', $args['cat' . $i]) :
                    $args['categories'][$i];

                foreach ([
                             'lat_field',
                             'lon_field',
                             'google_field',
                             'condition_field',
                             'condition_operator',
                             'condition_value',
                             'popup_function',
                             'popup_field',
                             'popup_text',
                             'color',
                             'title'] as $position => $parameterKey) {
                    if (in_array($parameterKey, ['condition_field', 'condition_operator', 'condition_value']))
                        $categories[$singlePostType]['cat' . $i]['condition'][$parameterKey] = $parameterArray[$parameterKey] ??
                            ($parameterArray[$position] ?? '');
                    else
                        $categories[$singlePostType]['cat' . $i][$parameterKey] = $parameterArray[$parameterKey] ??
                            ($parameterArray[$position] ?? '');
                }

                $i++;
            }
        }


        /**
         * Filters the map categories.
         *
         * @param array $categories The categories.
         * @param array $args The additional parameter.
         */
        $categories = apply_filters('oes_map/categories', $categories, $args);


        /* loop through entries and collect data */
        foreach ($categories[$singlePostType] as $key => $categoryData) {

            /* get data for entry from post */
            $entry = new Entry($singlePostID, $categoryData);
            if ($entry->status !== 'invalid') {
                $data[$key]['data'][] = $entry;
                if (!isset($data[$key]['title'])) $data[$key]['title'] = $categoryData['title'] ?? 'Missing title';

                /* option layer category (can be passed by filter) */
                if (isset($categoryData['layer_category']) && !in_array($categoryData['layer_category'], $layerCategories))
                    $layerCategories[] = $categoryData['layer_category'];
            }
        }
    }

    /* add to global parameter */
    global $oes_map_categories, $oes_map_id;
    $oes_map_id++;
    $oes_map_categories['oes_map_' . $oes_map_id] = $categories;

    /* add optional layer files @oesDevelopment mae available in shortcode */
    if (isset($layerCategories)) $args['layer_files'] = get_layer_files($layerCategories);

    global $oes_map_data;
    $oes_map_data = true;
    return get_HTML_representation(array_merge([
        'map_id' => 'oes_map_' . $oes_map_id,
        'data' => $data
    ], $args));
}


/**
 * Get the map representation of posts.
 *
 * @param array $args The map arguments. Valid options are:
 *  data            :   The map data
 *  container-class :   The container class
 *  map_id          :   The map id
 *  width           :   The map width
 *  height          :   The map height
 *  legend          :   Indicates, if legend is to be included
 *  defaultzoom     :   The default zoom level. If not set, the zoomlevel is calculated to fit all markers.
 *  center          :   The map center position.
 * @return string Return the map representation.
 */
function get_HTML_representation(array $args = []): string
{
    /* prepare args */
    $mapArgs = [
        'showLegend' => (isset($args['show_legend']) && in_array($args['show_legend'], ['true', 'on', '1'])),
        'controlsCollapsed' => $args['controls_collapsed'] ?? false,
        'map_id' => $args['map_id'],
        'layer_files' => $args['layer_files'] ?? [],
        'legendLabelType' => $args['legend_text'] ?? ''
    ];

    /* check for zoom */
    if (isset($args['defaultzoom']) && is_numeric($args['defaultzoom'])) $mapArgs['defaultZoom'] = $args['defaultzoom'];
    else $mapArgs['fitBounds'] = true;

    /* check for center */
    if(isset($args['center']) && !empty($args['center'])){
        $splitCenter = explode(';', $args['center']);
        if(sizeof($splitCenter) > 1){
            $mapArgs['defaultCenter'] = [$splitCenter[0], $splitCenter[1]];
        }
    }


    /**
     * Filters the map arguments.
     *
     * @param array $mapArgs The map arguments.
     * @param array $args The additional parameter.
     */
    $mapArgs = apply_filters('oes_map/map_args', $mapArgs, $args);


    /* prepare data */
    if (isset($args['data']))
        return sprintf('<div class="%s"><div id="%s" style="width: %s; height: %s;"></div></div>',
                $args['container-class'] ?? 'oes-map-container',
                $args['map_id'] ?? 'map',
                $args['width'] ?? '100%',
                $args['height'] ?? '500px'
            ) .
            '<script>oesMap.init("' . ($args['map_id'] ?? 'map') . '",' . json_encode($args['data']) . ',' . json_encode($mapArgs) . ');</script>';
    else return '';
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
 * Replace archive list by map.
 */
function theme_archive_list(): void
{

    global $post_type;

    /* check if current post type is to be replaced by map */
    $args = \OES\Shortcode\prepare_shortcode_parameters_from_option('oes_shortcode-map-' . $post_type);

    /* replace archive by shortcode */
    global $oes_archive_displayed;
    if (!empty($args)) {
        echo html($args);
        $oes_archive_displayed = true;
    }
}


/**
 * Include assets if page contains map shortcode.
 */
function enqueue_scripts(): void
{
    if(has_map_shortcode()) enqueue_map_scripts();
}


/**
 * Include assets necessary to display map.
 */
function enqueue_map_scripts(): void
{
    $path = plugins_url(basename(__DIR__)) . '/../oes-map/assets/';

        wp_register_style('oes-map.leaflet', $path . 'leaflet/1.9.4/leaflet.css', [], '1.9.4', 'screen');
        wp_enqueue_style('oes-map.leaflet');

        wp_register_style('oes-map.leaflet.panel-layers', $path . 'leaflet/leaflet-panel-layers/leaflet-panel-layers.min.css', [], '1.2.6', 'screen');
        wp_enqueue_style('oes-map.leaflet.panel-layers');

        wp_register_script('oes-map.leaflet', $path . 'leaflet/1.9.4/leaflet.js', [], '1.9.4', true);
        wp_enqueue_script('oes-map.leaflet');

        wp_register_script('oes-map.leaflet.panel-layers', $path . 'leaflet/leaflet-panel-layers/leaflet-panel-layers.min.js', ['oes-map.leaflet'], '1.2.6');
        wp_enqueue_script('oes-map.leaflet.panel-layers');

        /* @oesDevelopment more styles, e.g. clustering link in EV */
        wp_register_script('oes-map.simplemap', $path . 'leaflet/leaflet.cedis.simplemap/leaflet.cedis.simplemap.min.js');
        wp_enqueue_script('oes-map.simplemap');
        wp_register_style('oes-map.simplemap', $path . 'leaflet/leaflet.cedis.simplemap/leaflet.cedis.simplemap.css');
        wp_enqueue_style('oes-map.simplemap');

        wp_register_script('oes-map', $path . 'js/oes-map.min.js', [], false, true);
        wp_enqueue_script('oes-map');
}


/**
 * Determine if page include map shortcode.
 *
 * @return bool Return true if page includes shortcode.
 */
function has_map_shortcode(): bool
{
    global $post;
    return $post && has_shortcode($post->post_content, 'oes_map');
}


/**
 * Get layer files (they must be stored in media archive).
 *
 * @param array $categories
 * @return array Return layer files.
 */
function get_layer_files(array $categories = []): array
{
    /* get attachments */
    $attachments = oes_get_wp_query_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => 'layer_category',
        'meta_value' => $categories,
        'meta_compare' => 'IN'
    ]);

    $files = [];
    foreach ($attachments as $attachment)
        $files[] = [
            'id' => $attachment->ID,
            'url' => wp_get_attachment_url($attachment->ID),
            'name' => $attachment->post_title
        ];

    return $files;
}


/**
 * Add an external legend for an OES map.
 *
 * @param array $args Additional arguments, optionally containing the map ID.
 * @return string
 */
function legend_html($args): string
{

    /* get map ID */
    $mapID = $args['id'] ?? 'oes_map_1';

    /* loop through categories */
    global $oes_language, $oes_map_categories;
    $filterItems = [];
    $allIconsHtml = '';
    foreach($oes_map_categories[$mapID] ?? [] as $postType => $categoryData)
        foreach($categoryData as $key => $singleCategory) {
            $iconHTML = get_category_icon($singleCategory['color'] ?? '#111111') ;
            $allIconsHtml .= $iconHTML;
            $filterItems[] = '<a href="#" id="' . $mapID . '_' . $key .
                '" onclick="oesMapExternalLegend(\'' . $mapID . '\',\'' . $key . '\');">' .
                $singleCategory['title'] . ' ' . $iconHTML .
                '</a>';
        }

    return '<div class="oes-map-legend">' .
        '<ul class="oes-vertical-list"><li>' .
        '<a href="#" id="' . $mapID . '__all" onclick="oesMapExternalLegend(\'' . $mapID . '\',\'all\');">' .
        (OES()->theme_labels['archive__filter__all_button'][$oes_language] ?? 'All ') . ' ' . $allIconsHtml .
        '</a></li>' .
        implode('</li><li>', $filterItems) .
        '</ul></div>';
}


/**
 * Get a category icon in a specific color.
 *
 * @param string $color The category color.
 * @return string Return the HTML representation of a category icon.
 */
function get_category_icon(string $color = '#1111111'): string
{
    return '<svg class="MapFilter-icon u-ml-tiny" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">' .
        '<circle cx="25" cy="25" r="20" stroke="' . $color . '" fill="' . $color . '" fill-opacity="0.8" stroke-opacity="1" stroke-width="1"></circle>' .
        '</svg>';
}
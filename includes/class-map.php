<?php

namespace OES\Map;

if (!defined('ABSPATH')) exit;

if (!class_exists('Map')) :

    /**
     * Class Map
     *
     * Represents a geolocation map with categorized data points, rendering options,
     * and optional boundary layers for display in a frontend map interface with leaflet.
     *
     * @package OES\Map
     */
    class Map
    {
        /** @var string The unique map ID. */
        public string $map_ID = '';

        /** @var array Rendering options for the map. */
        public array $options = [
            'showLegend' => false,
            'controlsCollapsed' => true,
            'controlText' => 'Legend',
            'legendLabelType' => 'Choose Type',
            'legendLabelBorders' => 'Show borders',
            'fitBounds' => false,
            'showBorders' => true,
            'defaultZoom' => 5,
            'defaultCenter' => [51.1657, 10.4515],
        ];

        /**
         * @var array Structured map data by category.
         * Format:
         * [
         *   'cat1' => [
         *     'title' => 'Category Title',
         *     'data' => [ Entry, Entry, ... ]
         *   ],
         *   ...
         * ]
         */
        public array $data = [];

        /** @var array Category configuration. */
        public array $categories = [];

        /** @var array GeoJSON layer files used for rendering borders. */
        public array $layer_files = [];

        /** @var string Fully-qualified class name for map entries. */
        public string $entry_class = '\OES\Map\Entry';

        public array $div = [
            'class' => 'oes-map-container',
            'width' => '100%',
            'height' => '500px'
        ];

        /**
         * Map constructor.
         *
         * @param array $args Configuration arguments.
         */
        public function __construct(array $args = [])
        {
            $this->set_map_id($args['map_ID'] ?? '');
            $this->set_options($args);
            $this->set_categories($args);
            $this->set_entry_class();
            $this->set_data($args);

            add_action('wp_footer', [$this, 'footer']);
        }

        /**
         * Generate a unique map ID or use the one provided.
         */
        protected function set_map_id(string $mapID = ''): void
        {
            global $oes_map_id;
            $oes_map_id = isset($oes_map_id) ? $oes_map_id + 1 : 1;
            $this->map_ID = $mapID ?: 'oes_map_' . $oes_map_id;
        }

        /**
         * Set rendering options based on provided arguments.
         */
        protected function set_options(array $args): void
        {
            $this->options['showLegend'] = in_array($args['show_legend'] ?? '', ['true', 'on', '1'], true);
            $this->options['controlsCollapsed'] = !empty($args['controls_collapsed']);

            if (isset($args['defaultzoom']) && is_numeric($args['defaultzoom'])) {
                $this->options['defaultZoom'] = (int)$args['defaultzoom'];
            } else {
                $this->options['fitBounds'] = true;
            }

            if (!empty($args['center'])) {
                $center = explode(';', $args['center']);
                if (count($center) === 2) {
                    $lat = floatval($center[0]);
                    $lon = floatval($center[1]);
                    if ($lat && $lon) {
                        $this->options['defaultCenter'] = [$lat, $lon];
                    }
                }
            }

            // Check for any other known options
            foreach ($args['options'] ?? [] as $key => $defaultValue) {
                if (array_key_exists($key, $args)) {
                    $this->options[$key] = $args[$key];
                }
            }

            // Check for div parameters
            foreach ($args['div'] ?? [] as $key => $defaultValue) {
                if (array_key_exists($key, $args)) {
                    $this->div[$key] = $args[$key];
                }
            }
        }

        /**
         * Load and assign map data based on post IDs.
         */
        public function set_data(array $args = []): void
        {
            $objects = $this->get_objects($args);

            if (empty($objects)) return;

            $layerCategories = [];

            foreach ($objects as $object) {

                foreach ($this->categories as $key => $catData) {
                    $entry = new $this->entry_class($object, $catData);

                    if ($entry->status !== 'invalid') {
                        $this->data[$key]['data'][] = $entry;

                        if (!empty($catData['layer_category']) && !in_array($catData['layer_category'], $layerCategories)) {
                            $layerCategories[] = $catData['layer_category'];
                        }

                        if (empty($this->data[$key]['title'])) {
                            $this->data[$key]['title'] = $catData['title'] ?? $key;
                        }
                    }
                }
            }

            if (!empty($layerCategories)) {
                $this->set_layer_files($layerCategories);
            }
        }

        /**
         * Retrieve posts either by explicit IDs or via WP_Query.
         */
        public function get_objects(array $args = []): array
        {
            //@oesDevelopment: get items from cache or transient

            $ids = is_array($args['ids'] ?? null) ? $args['ids'] : [];

            if (!empty($ids)) {
                return is_array($ids) ? $ids : array_map('intval', explode(',', $ids));
            }

            if (!empty($args['post_type'])) {
                return oes_get_wp_query_posts([
                    'post_type' => $args['post_type'],
                    'post_status' => 'publish',
                    'fields' => 'ids'
                ]);
            } elseif (!empty($args['taxonomy'])) {
                return get_terms([
                    'taxonomy' => $args['taxonomy'],
                    'fields' => 'ids',
                    'hide_empty' => $args['hide_empty'] ?? true,
                    'childless' => $args['childless'] ?? false
                ]);
            }

            return [];
        }

        /**
         * Determine the entry class to use based on the project name.
         */
        protected function set_entry_class(): void
        {
            $class = oes_get_project_class_name('\OES\Map\Entry');
            $this->entry_class = $class;
        }

        /**
         * Parse category settings from shortcode or structured attributes.
         */
        protected function set_categories(array $args): void
        {
            $i = 1;
            while (isset($args['cat' . $i]) || isset($args['categories'][$i])) {
                $paramArray = isset($args['cat' . $i])
                    ? explode(';', $args['cat' . $i])
                    : $args['categories'][$i];

                $catData = [];
                $keys = [
                    'lat_field', 'lon_field', 'google_field',
                    'condition_field', 'condition_operator', 'condition_value',
                    'popup_function', 'popup_field', 'popup_text',
                    'color', 'title'
                ];

                foreach ($keys as $pos => $key) {
                    $catData[$key] = $this->normalize_field_key($paramArray[$key] ?? ($paramArray[$pos] ?? ''));
                }
                $catData['cat'] = $i;

                // check for language dependent labels in title
                $catData['title'] = oes_get_translated_string($catData['title']);

                $this->categories['cat' . $i] = $catData;
                $i++;
            }

            // make sure one cat exists
            if (empty($this->categories)) {
                $this->categories['cat1'] = [];
            }
        }

        /**
         * Strips internal prefix from field keys if present.
         *
         * @param string $key field key.
         * @return string Normalized key.
         */
        protected function normalize_field_key(string $key): string {

            if(str_starts_with($key, 'field_field_')){
                return substr($key, 6);
            }
            elseif(str_starts_with($key, 'taxonomy_field_')){
                return substr($key, 9);
            }
            return $key;
        }

        /**
         * Set optional layer files based on assigned categories.
         */
        protected function set_layer_files(array $categories = []): void
        {
            $attachments = oes_get_wp_query_posts([
                'post_type' => 'attachment',
                'meta_key' => 'layer_category',
                'meta_value' => $categories,
                'meta_compare' => 'IN'
            ]);

            $this->layer_files = array_map(static function ($attachment) {
                return [
                    'id' => $attachment->ID,
                    'url' => wp_get_attachment_url($attachment->ID),
                    'name' => $attachment->post_title,
                ];
            }, $attachments);
        }

        /**
         * Get the HTML representation of the map with the provided arguments.
         *
         * @return string The HTML string for the map container.
         */
        public function get_map_div(): string
        {
            // Ensure required data exists, otherwise return an empty string
            if (empty($this->data)) {
                error_log(__('No data exists for OES map.', 'oes-map'));
                return '';
            }

            // Return the HTML for the map container
            return sprintf('<div class="%s"><div id="%s" style="width: %s; height: %s;"></div></div>',
                esc_attr($this->div['class']),
                $this->map_ID,
                esc_attr($this->div['width']),
                esc_attr($this->div['height'])
            );
        }

        /**
         * Outputs the footer map initialization script.
         *
         * If the map has associated data, this method outputs a JavaScript snippet
         * that initializes the map on the frontend using the provided map ID, data,
         * and options.
         *
         * @return void
         */
        public function footer(): void
        {
            if (empty($this->data)) {
                return; // No map data available
            }

            $mapDataJson = wp_json_encode($this->data);
            $mapArgsJson = wp_json_encode($this->options);
            $mapIdEscaped = esc_js($this->map_ID);

            // Bail if JSON encoding fails
            if (!$mapDataJson || !$mapArgsJson) {
                return;
            }

            echo <<<HTML
<script>
    (function() {
        var mapData = $mapDataJson;
        var mapOptions = $mapArgsJson;

        if (typeof oesMap !== 'undefined' && typeof oesMap.init === 'function') {
            oesMap.init('$mapIdEscaped', mapData, mapOptions);
        }
    })();
</script>
HTML;
        }
    }

endif;

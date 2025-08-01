<?php

namespace OES\Map;

if (!defined('ABSPATH')) exit;

if (!class_exists('Entry')) :

    /**
     * Class Entry
     *
     * Represents a geolocation entry used for mapping.
     * Stores coordinates, popup information, and supports conditional rendering logic.
     *
     * @package OES\Map
     */
    class Entry
    {

        /** @var string WordPress post ID representing the map entry. */
        public string $entry_ID = '';

        /** @var string ID for field values, might be post id or "taxonomy_term_ID". */
        public string $id_for_fields = '';

        /** @var string WordPress taxonomy if map entry is set from term. */
        public string $taxonomy = '';

        /** @var string Status flag for the entry; 'invalid' if coordinates are missing. */
        public string $status = 'valid';

        /** @var string ACF Google Map field key. */
        public string $google_field = '';

        /** @var string Field key for latitude. */
        public string $lat_field = '';

        /** @var string Field key for longitude. */
        public string $lon_field = '';

        /** @var float Latitude coordinate. */
        public float $lat = 0.0;

        /** @var float Longitude coordinate. */
        public float $lon = 0.0;

        /** @var string Function name used to generate popup text. */
        public string $popup_function = '\OES\Map\popup_text';

        /** @var string The text content to show in the map popup. */
        public string $popup_text = '';

        /** @var string field key used to fetch popup content. */
        public string $popup_field = '';

        /** @var string Icon color (HTML hex code). */
        public string $color = '';

        /** @var int Icon radius. */
        public int $radius = 5;

        /** @var float Icon opacity. */
        public float $fillOpacity = 1;

        /** @var array Additional, arbitrary parameters passed in. */
        public array $additional = [];

        /** @var array<string, int> Holds the count of published posts connected through specific relationship fields. */
        public array $connected_posts_count = [];

        /**
         * Entry constructor.
         *
         * @param int $entryID WordPress post ID or term ID. Defaults to current post ID if not supplied.
         * @param array $args Array of settings for the entry (field keys, popup function, etc.).
         * @param array $additional_args Additional data to be stored in the entry.
         */
        public function __construct(int $entryID = 0, array $args = [], array $additional_args = [])
        {
            $this->entry_ID = $entryID ?: get_the_ID();

            if ($args['term'] ?? false) {
                $term = get_term($this->entry_ID);
                if ($term) {
                    $this->taxonomy = $term->taxonomy;
                    $this->id_for_fields = $this->taxonomy . '_' . $this->entry_ID;
                }
            } else {
                $this->id_for_fields = $this->entry_ID;
            }

            if (!$this->check_condition($args)) {
                $this->status = 'invalid';
                return;
            }

            $this->initialize_properties($args, $additional_args);
            $this->initialize_additional_properties($args, $additional_args);
            $this->set_coordinates();

            if (empty($this->lat) || empty($this->lon)) {
                $this->status = 'invalid';
            }

            if ($this->status != 'invalid') {
                $this->set_popup_text();
            }
        }

        /**
         * Assigns provided arguments to the class properties.
         *
         * @param array $args Main configuration for the entry.
         * @param array $additional_args Any additional user-defined metadata.
         */
        protected function initialize_properties(array $args, array $additional_args): void
        {
            foreach ($args as $key => $value) {
                if (property_exists($this, $key) && gettype($value) === gettype($this->{$key})) {
                    $this->{$key} = $value;
                } else {
                    $this->additional[$key] = $value;
                }
            }

            if ($this->popup_function === 'none') {
                $this->popup_function = '\OES\Map\popup_text';
            }
        }

        /**
         * Assigns additional provided arguments to the class properties.
         *
         * @param array $args Main configuration for the entry.
         * @param array $additional_args Any additional user-defined metadata.
         */
        protected function initialize_additional_properties(array $args, array $additional_args): void
        {
            foreach ($additional_args as $key => $value) {
                $this->additional[$key] = $value;
            }
        }

        /**
         * Checks if the entry meets the condition defined in the arguments.
         *
         * @param array $args Arguments that may include a `condition` array.
         * @return bool True if the condition passes or none is defined.
         */
        protected function check_condition(array $args = []): bool
        {

            if (empty($args['condition_field']) && empty($args['condition_value'])) {
                return true;
            }

            $fieldKey = $args['condition_field'] ?? '';
            $fieldValue = $args['condition_value'] ?? null;
            $operator = $args['condition_operator'] ?? 'equal';

            if (empty($fieldKey) || $fieldKey === 'none') {
                return true;
            }

            $fieldObject = oes_get_field_object($fieldKey, $this->id_for_fields);
            if (!$fieldObject) {
                return false;
            }

            if ($fieldObject['type'] === 'select' && !empty($fieldObject['multiple'])) {
                $postFieldValue = oes_get_field($fieldKey, $this->id_for_fields);
                return in_array($fieldValue, (array)$postFieldValue, true) === ($operator !== 'notequal');
            }

            $postFieldValue = get_post_meta($this->id_for_fields, $fieldKey, true);
            return ($postFieldValue == $fieldValue) === ($operator !== 'notequal');
        }

        /**
         * Populates `$lat` and `$lon` from defined field keys or a Google Map field.
         */
        protected function set_coordinates(): void
        {
            $this->lat = $this->get_coordinate_value($this->lat_field);
            $this->lon = $this->get_coordinate_value($this->lon_field);

            if (!$this->lat || !$this->lon) {
                $this->set_coordinates_from_google_field();
            }
        }

        /**
         * Extracts a float coordinate value from a given field key.
         *
         * @param string $fieldKey field key.
         * @return float Coordinate value.
         */
        protected function get_coordinate_value(string $fieldKey): float
        {
            if (empty($fieldKey) || $fieldKey === 'none') {
                return 0.0;
            }

            $value = oes_get_field($fieldKey, $this->id_for_fields);
            return is_numeric($value) ? (float)$value : 0.0;
        }

        /**
         * Attempts to extract coordinates from an ACF Google Map field if lat/lon are not manually set.
         */
        protected function set_coordinates_from_google_field(): void
        {
            $googleFieldKey = $this->google_field;
            if (empty($googleFieldKey) || $googleFieldKey === 'none') {
                return;
            }

            $googleField = oes_get_field($googleFieldKey, $this->id_for_fields);
            if (!empty($googleField['lat']) && !empty($googleField['lng'])) {
                $this->lat = (float)$googleField['lat'];
                $this->lon = (float)$googleField['lng'];
            }
        }

        /**
         * Determines the popup text content using a function or field.
         * Falls back to a default function if none is set.
         * Applies filter `oes_map/popup_text`.
         */
        protected function set_popup_text(): void
        {
            if (empty($this->popup_function) && empty($this->popup_field)) {
                $this->popup_text = \OES\Map\popup_text($this->entry_ID, $this->popup_text);
            } elseif (function_exists($this->popup_function)) {
                $this->popup_text = call_user_func($this->popup_function, $this->entry_ID, $this->popup_text);
            } else {
                $this->popup_text = \OES\ACF\get_field_display_value($this->popup_field, $this->entry_ID);
            }

            if (has_filter('oes_map/popup_text')) {
                $this->popup_text = apply_filters('oes_map/popup_text', $this);
            }
        }

        /**
         * Generates HTML for paginated popup content, displaying one page at a time,
         * along with optional additional content and navigation controls.
         *
         * @param array $pages       Array of HTML strings, each representing one popup page.
         * @param string $additional Optional additional HTML content to append below the pages (e.g., location).
         * @return string            Complete HTML string for the popup with pagination.
         */
        protected function set_popup_pages(array $pages, string $additional = ''): string
        {
            $html = '<div class="popup-wrapper">';

            foreach ($pages as $i => $page) {
                $displayStyle = $i === 0 ? '' : 'style="display:none;"';
                $pageNumber = esc_attr($i);
                $html .= "<div class='popup-page' data-page='{$pageNumber}' {$displayStyle}>{$page}</div>";
            }

            if (!empty($additional)) {
                $html .= $additional;
            }

            $html .= $this->get_popup_pages_navigation(count($pages));
            $html .= '</div>';

            return $html;
        }

        /**
         * Builds navigation controls for paginated popup content.
         *
         * @param int $totalPages The total number of popup pages.
         * @return string         HTML for previous/next navigation with page indicators.
         */
        protected function get_popup_pages_navigation(int $totalPages = 0): string
        {
            if ($totalPages < 2) {
                return '';
            }

            $total = esc_html($totalPages);
            $iconLeft = $this->get_icon('arrow_left');
            $iconRight = $this->get_icon('arrow_right');

            return <<<HTML
<div class="navigation pagination bak-project-navigation" role="navigation" aria-label="Pagination">
    <div class="nav-links">
        <a href="javascript:void(0);" class="page-numbers prev" onclick="oesMapPageNav(this, -1)" aria-label="Previous page">
            <span class="screen-reader-text">Previous</span> {$iconLeft}
</svg>
        </a>
        <span class="page-numbers current">1 / {$total}</span>
        <a href="javascript:void(0);" class="page-numbers next" onclick="oesMapPageNav(this, 1)" aria-label="Next page">
            <span class="screen-reader-text">Next</span> {$iconRight}
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
</div>
HTML;
        }

        /**
         * Wrapper for global icon access.
         */
        public function get_icon(string $name = ''): string {
            return \OES\Icon\get($name);
        }

        /**
         * Sets the count of connected published posts for specified fields.
         *
         * @param array $fieldKeys Array of field keys to evaluate.
         * @param array $status Array of considered status.
         * @return void
         */
        public function set_connected_posts_count(array $fieldKeys = [], array $status = ['publish']): void
        {
            foreach ($fieldKeys as $fieldKey) {
                $connectedEntries = get_field($fieldKey, $this->entry_ID);

                if (empty($connectedEntries)) {
                    $this->connected_posts_count[$fieldKey] = 0;
                } else {
                    $this->connected_posts_count[$fieldKey] = count(array_filter($connectedEntries, function ($singlePost) use ($status) {
                        return in_array(get_post_status($singlePost), $status);
                    }));
                }
            }
        }
    }
endif;

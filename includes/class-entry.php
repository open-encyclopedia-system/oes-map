<?php

namespace OES\Map;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Entry')) :

    /**
     * Class OES_Map_Entry
     *
     * Defines a map entry.
     */
    class Entry
    {

        /** @var string The post id. */
        public string $entry_ID = '';

        /** @var string The validated status (latitude, longitude are set) */
        public string $status = '';

        /** @var string The field key for a Google map field. */
        public string $google_field = '';

        /** @var string The latitude field */
        public string $lat_field = '';

        /** @var string The longitude field. */
        public string $lon_field = '';

        /** @var float The latitude value. */
        public float $lat = 0;

        /** @var float The longitude value. */
        public float $lon = 0;

        /** @var string The popup function. */
        public string $popup_function = '\OES\Map\popup_text';

        /** @var string The popup text. */
        public string $popup_text = '';

        /** @var string The popup field. */
        public string $popup_field = '';

        /** @var string The icon color. Format in HTML Code. */
        public string $color = '';

        /** @var array Additional parameters. */
        public array $additional = [];


        /**
         * OES_Map_Entry constructor.
         *
         * @param array $args Additional parameters.
         */
        function __construct(int $post_id = 0, array $args = [], array $additional_args = [])
        {

            $this->entry_ID = $post_id ?: get_the_ID();

            /* check if category has field condition */
            if ($this->check_condition($args)) {

                /* set class parameters */
                foreach ($args as $key => $value) {
                    if (property_exists($this, $key) && gettype($value) === gettype($this->{$key}))
                        $this->{$key} = $value;
                    else  $this->additional[$key] = $value;
                }

                /* make sure popup function exists */
                if ($this->popup_function == 'none') $this->popup_function = '\OES\Map\popup_text';

                /* set additional parameters */
                foreach ($additional_args as $key => $value) $this->additional[$key] = $value;

                $this->set_coordinates();
                $this->set_popup_text();
            }

            /* check if lat and lon are set */
            if (empty($this->lat) || empty($this->lon)) $this->status = 'invalid';
        }


        /**
         * Check condition for post.
         *
         * @param array $args The category arguments.
         * @return bool Return true if condition is met.
         */
        function check_condition(array $args = []): bool
        {
            /* return early if no condition is set */
            if (empty($args['condition'] ?? '') ||
                (!isset($args['condition']['field']) && !isset($args['condition']['value']))) return true;

            $fieldKey = $args['condition']['field'] ?? false;
            if ($fieldKey && $fieldKey != 'none') {

                $fieldKey = oes_starts_with('field_field_', $fieldKey) ? substr($fieldKey, 6) : $fieldKey;
                $fieldValue = $args['condition']['value'] ?? false;
                $operator = $args['condition']['operator'] ?? 'equal';

                if ($fieldObject = oes_get_field_object($fieldKey, $this->entry_ID)) {
                    if ($fieldObject['type'] == 'select' &&
                        (isset($fieldObject['multiple']) && $fieldObject['multiple'])) {
                        $postFieldValue = oes_get_field($fieldKey, $this->entry_ID);
                        if ($postFieldValue && in_array($fieldValue, $postFieldValue)) return ($operator !== 'notequal');
                        else return ($operator === 'notequal');
                    } else {
                        $postFieldValue = get_post_meta($this->entry_ID, $fieldKey, true); //TODO other field types? oes_get_field?
                        if ($postFieldValue == $fieldValue) return ($operator !== 'notequal');
                        else return ($operator === 'notequal');
                    }
                }
                return false;
            }
            return true;
        }


        /**
         * Set coordinates
         */
        function set_coordinates(): void
        {
            /* latitude */
            $latitudeField = oes_starts_with($this->lat_field, 'field_field_') ?
                substr($this->lat_field, 6) :
                $this->lat_field;
            if (!empty($latitudeField) && $latitudeField !== 'none') {
                $fieldValue = oes_get_field($latitudeField, $this->entry_ID);
                if ($lat = (float)$fieldValue) $this->lat = $lat;
            }

            /* longitude */
            $longitudeField = oes_starts_with($this->lon_field, 'field_field') ?
                substr($this->lon_field, 6) :
                $this->lon_field;
            if (!empty($longitudeField) && $longitudeField !== 'none') {
                $fieldValue = oes_get_field($longitudeField, $this->entry_ID);
                if ($lon = (float)$fieldValue) $this->lon = $lon;
            }

            /* check for Google field */
            if ((empty($this->lon) || empty($this->lat)) &&
                !empty($this->google_field) && $this->google_field !== 'none') {
                $googleFieldKey = oes_starts_with($this->google_field, 'field_field_') ?
                    substr($this->google_field, 6) :
                    $this->google_field;
                $googleField = oes_get_field($googleFieldKey, $this->entry_ID);
                if (!empty($googleField['lat']) && !empty($googleField['lng'])) {
                    $this->lat = (float)$googleField['lat'];
                    $this->lon = (float)$googleField['lng'];
                }
            }
        }


        /**
         * Set popup text (might be overwritten)
         */
        function set_popup_text(): void
        {

            /* on empty call default popup function*/
            if(empty($this->popup_function) && empty($this->popup_field))
                $this->popup_text = \OES\Map\popup_text($this->entry_ID, $this->popup_text);

            /* call popup function */
           else if (function_exists($this->popup_function))
                $this->popup_text = call_user_func($this->popup_function, $this->entry_ID, $this->popup_text);

            /* popup text is field */
            else
                $this->popup_text = \OES\ACF\get_field_display_value((
                oes_starts_with($this->popup_field, 'field_field_') ?
                    substr($this->popup_field, 6) :
                    $this->popup_field
                ), $this->entry_ID);


            /**
             * Filter the popup text.
             *
             * @param string $this The OES map entry instance.
             */
            if (has_filter('oes_map/popup_text')) $this->popup_text = apply_filters('oes_map/popup_text', $this);
        }
    }
endif;

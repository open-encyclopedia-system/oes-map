<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('\OES\Admin\Tools\Shortcode')) oes_include('admin/tools/class-tool-shortcode.php');

if (!class_exists('Map_Shortcode')) :

    /**
     * Class Map_Shortcode
     *
     * Generate a map shortcode.
     */
    class Map_Shortcode extends \OES\Admin\Tools\Shortcode
    {

        /** @inheritdoc */
        public string $option_prefix = 'map';

        /** @inheritdoc */
        public string $shortcode_prefix = OES_MAP_SHORTCODE_PREFIX;

        /** @inheritdoc */
        public array $shortcode_parameters = OES_MAP_SHORTCODE_PARAMETER;

        /** @inheritdoc */
        public array $additional_submits = [
            'categories' => 'Add Category'
        ];

        /** @inheritdoc */
        function set_table_data_for_display()
        {
            global $oes;

            $optionName = 'oes_shortcode';
            $data = $this->get_current_selected_option_value();

            // prepare selects
            $postTypes = [];
            $textOptions = ['none' => '-'];
            $googleOptions = ['none' => '-'];
            $allFieldsOption = ['none' => '-'];
            foreach ($oes->post_types as $postTypeKey => $postTypeData) {
                $postTypeLabel = $postTypeData['label'] ?? $postTypeKey;
                $postTypes[$postTypeKey] = $postTypeLabel;

                $allFields = oes_get_all_object_fields($postTypeKey);
                foreach ($allFields as $fieldKey => $singleField) {
                    $fieldOption = $postTypeLabel . ' : ' .
                        (empty($singleField['label']) ? $fieldKey : $singleField['label']);
                    $allFieldsOption['field_' . $fieldKey] = $fieldOption;
                    if ($singleField['type'] == 'text') $textOptions['field_' . $fieldKey] = $fieldOption;
                    elseif ($singleField['type'] == 'google_map') $googleOptions['field_' . $fieldKey] = $fieldOption;
                }
            }

            $this->add_table_row(
                [
                    'title' => __('Name', 'oes-map'),
                    'key' => $optionName . '[name]',
                    'value' => $data['name'] ?? ''
                ],
                [
                    'subtitle' => __('The name is used to identify stored shortcode in the database. Internal use only.', 'oes-map')
                ]
            );

            $this->add_table_header(__('Data', 'oes-map'), 'inner');

            $this->add_table_row(
                [
                    'title' => __('Post IDs', 'oes-map'),
                    'key' => $optionName . '[ids]',
                    'value' => $data['ids'] ?? '',
                    'args' => [
                        'placeholder' => 'Enter IDs separated by semicolon'
                    ]
                ],
                [
                    'subtitle' =>__('To define which posts will be displayed on the map you can ' .
                        'either enter a list of valid post IDs or select post types', 'oes-map')
                ]
            );

            $this->add_table_row(
                [
                    'title' => __('Post Types', 'oes-map'),
                    'key' => $optionName . '[post_type]',
                    'value' => $data['post_type'] ?? [],
                    'type' => 'select',
                    'args' => [
                        'options' => $postTypes,
                        'multiple' => true,
                        'class' => 'oes-replace-select2',
                        'reorder' => true,
                        'hidden' => true
                    ]
                ],
                [
                    'subtitle' => __('By selecting a post type all post of this post type ' .
                        'will be considered. If neither post type nor post ids are entered the ' .
                        'current post will be considered.', 'oes-map')
                ]
            );

            $this->add_table_header(__('Defaults', 'oes-map'), 'inner');

            $this->add_table_row(
                [
                    'title' => __('Default Zoom', 'oes-map'),
                    'key' => $optionName . '[defaultzoom]',
                    'value' => $data['defaultzoom'] ?? ''
                ],
                [
                    'subtitle' => __('To set a different default zoom enter an integer bigger than zero.', 'oes-map')
                ]
            );

            $this->add_table_row(
                [
                    'title' => __('Default Center', 'oes-map'),
                    'key' => $optionName . '[center]',
                    'value' => $data['center'] ?? ''
                ],
                [
                    'subtitle' => __('The default center is "51.1657;10.4515". Set a different position ' .
                    'by entering latitude and longitude seperated by semicolon.', 'oes-map')
                ]
            );

            $this->add_table_header(__('Legend', 'oes-map'), 'inner');

            $this->add_table_row(
                [
                    'title' => __('Show Legend', 'oes-map'),
                    'key' => $optionName . '[show_legend]',
                    'value' => $data['show_legend'] ?? '',
                    'type' => 'checkbox'
                ],
                [
                    'subtitle' => __('Show the legend inside the map. Use the shortcode ' .
                        '<code>[oes_map_legend]</code> to display the legend elsewhere.', 'oes-map')
                ]
            );

            $this->add_table_row(
                [
                    'title' => __('Text Above Legend', 'oes-map'),
                    'key' => $optionName . '[legend_text]',
                    'value' => $data['legend_text'] ?? ''
                ],
                [
                    'subtitle' => __('e.g. "choose category"', 'oes-map')
                ]
            );

            $this->add_table_header(__('Categories', 'oes-map'), 'inner');

            $i = 1;
            foreach ($data['categories'] ?? [[]] as $categoryData) {

                $optionPrefix = $optionName . '[categories][' . $i . ']';

                $this->add_table_header(
                    __('Category', 'oes-map') . ' ' . $i,
                    'trigger',
                    [
                        'standalone' => true,
                        'id' => 'oes-map-category-' . $i
                    ]
                );

                $this->add_table_header(__('Data', 'oes-map'), 'inner');

                $this->add_table_row(
                    [
                        'title' => __('(1) Latitude Field', 'oes-map'),
                        'key' => $optionPrefix . '[lat_field]',
                        'value' => $categoryData['lat_field'] ?? 'none',
                        'type' => 'select',
                        'args' => [
                            'options' => $textOptions
                        ]
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(2) Longitude Field', 'oes-map'),
                        'key' => $optionPrefix . '[lon_field]',
                        'value' => $categoryData['lon_field'] ?? 'none',
                        'type' => 'select',
                        'args' => [
                            'options' => $textOptions
                        ]
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(3) or Google Map Field', 'oes-map'),
                        'key' => $optionPrefix . '[google_field]',
                        'value' => $categoryData['google_field'] ?? 'none',
                        'type' => 'select',
                        'args' => [
                            'options' => $googleOptions
                        ]
                    ]
                );

                $this->add_table_header(__('Condition', 'oes-map'), 'inner');

                $this->add_table_row(
                    [
                        'title' => __('(4) Field', 'oes-map'),
                        'key' => $optionPrefix . '[condition_field]',
                        'value' => $categoryData['condition_field'] ?? 'none',
                        'type' => 'select',
                        'args' => [
                            'options' => $allFieldsOption
                        ]
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(5) Operation', 'oes-map'),
                        'key' => $optionPrefix . '[condition_operator]',
                        'value' => $categoryData['condition_operator'] ?? 'none',
                        'type' => 'select',
                        'args' => [
                            'options' => [
                                'none' => '-',
                                'equal' => '= (equal / in array)',
                                'notequal' => '!= (not equal / not in array)'
                            ]
                        ]
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(6) Value', 'oes-map'),
                        'key' => $optionPrefix . '[condition_value]',
                        'value' => $categoryData['condition_value'] ?? ''
                    ]
                );

                $this->add_table_header(__('Popup', 'oes-map'), 'inner');

                $this->add_table_row(
                    [
                        'title' => __('(7) Function', 'oes-map'),
                        'key' => $optionPrefix . '[popup_function]',
                        'value' => $categoryData['popup_function'] ?? ''
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(8) Field', 'oes-map'),
                        'key' => $optionPrefix . '[popup_field]',
                        'value' => $categoryData['popup_field'] ?? 'none',
                        'type' => 'select',
                        'args' => [
                            'options' => $allFieldsOption
                        ]
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(9) Text', 'oes-map'),
                        'key' => $optionPrefix . '[popup_text]',
                        'value' => $categoryData['popup_text'] ?? ''
                    ]
                );

                $this->add_table_header(__('Design', 'oes-map'), 'inner');

                $this->add_table_row(
                    [
                        'title' => __('(10) Color', 'oes-map'),
                        'key' => $optionPrefix . '[color]',
                        'value' => $categoryData['color'] ?? ''
                    ]
                );

                $this->add_table_row(
                    [
                        'title' => __('(11) Title', 'oes-map'),
                        'key' => $optionPrefix . '[title]',
                        'value' => $categoryData['title'] ?? ''
                    ]
                );

                $value = '<a href="javascript:void(0);" onClick="oesRemoveElementById(\'oes-map-category-' . $i . '\');" class="button">' .
                    __('Delete Category', 'oes-map') .
                    '</a>';
                $this->add_cell($value);

                $i++;
            }
        }
    }

    // initialize
    \OES\Admin\Tools\register_tool('Map_Shortcode', 'map-shortcode');
endif;
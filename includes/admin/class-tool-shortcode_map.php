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

        public string $option_prefix = 'map';
        public array $additional_submits = [
            'categories' => 'Add Category'
        ];


        //Overwrite parent
        function set_table_data_for_display()
        {

            global $oes;

            $optionName = 'oes_shortcode';
            $optionID = 'oes_shortcode';
            $data = $this->get_current_selected_option_value();

            /* prepare replace archive */
            $postTypes = ['none' => '-'];
            $textOptions = ['none' => '-'];
            $googleOptions = ['none' => '-'];
            $allFieldsOption = ['none' => '-'];
            foreach ($oes->post_types as $postTypeKey => $postTypeData) {
                $postTypeLabel = $postTypeData['label'] ?? $postTypeKey;
                $postTypes[$postTypeKey] = $postTypeLabel;

                /* prepare fields */
                $allFields = oes_get_all_object_fields($postTypeKey);

                /* prepare html for title options */
                foreach ($allFields as $fieldKey => $singleField) {
                    $fieldOption = $postTypeLabel . ' : ' .
                        (empty($singleField['label']) ? $fieldKey : $singleField['label']);
                    $allFieldsOption['field_' . $fieldKey] = $fieldOption;
                    if ($singleField['type'] == 'text') $textOptions['field_' . $fieldKey] = $fieldOption;
                    elseif ($singleField['type'] == 'google_map') $googleOptions['field_' . $fieldKey] = $fieldOption;
                }
            }

            /* prepare selects */
            $this->table_data = [
                [
                    'rows' => [
                        [
                            'cells' => [
                                [
                                    'type' => 'th',
                                    'value' => '<strong>' . __('Name', 'oes') . '</strong><div>' .
                                        __('The name is used to identify stored shortcode in the database. Internal use only.', 'oes') .
                                        '</div>'
                                ],
                                [
                                    'class' => 'oes-table-transposed',
                                    'value' => oes_html_get_form_element('text',
                                        $optionName . '[name]',
                                        $optionID . '-name',
                                        $data['name'] ?? ''
                                    )
                                ]
                            ]
                        ],
                        [
                            'cells' => [
                                [
                                    'type' => 'th',
                                    'value' => '<strong>' . __('Replace Archive', 'oes') . '</strong>' .
                                        '<div>' .
                                        __('If you want to replace an archive by displaying the posts ' .
                                            'as map data rather than a list select a post type.', 'oes') .
                                        '</div>'
                                ],
                                [
                                    'class' => 'oes-table-transposed',
                                    'value' => oes_html_get_form_element('select',
                                        $optionName . '[replace_archive]',
                                        $optionID . '-replace_archive',
                                        $data['replace_archive'] ?? 'none',
                                        [
                                            'options' => $postTypes
                                        ]
                                    )
                                ]
                            ]
                        ],
                        [
                            'cells' => [
                                [
                                    'type' => 'th',
                                    'value' => '<strong>' . __('Data', 'oes') . '</strong>' .
                                        '<div>' .
                                        __('To define which posts will be displayed on the map you can ' .
                                            'either enter a list of valid post IDs (seperated by semicolon ";") or ' .
                                            'select post types (by selecting a post type all post of this post type ' .
                                            'will be considered). If neither post type nor post ids are entered the ' .
                                            'current post will be considered.', 'oes') .
                                        '</div>'
                                ],
                                [
                                    'class' => 'oes-table-transposed',
                                    'value' => '<div>' . __('Post Types', 'oes') . '</div>' .
                                        oes_html_get_form_element('select',
                                            $optionName . '[post_type]',
                                            $optionID . '-post_type',
                                            $data['post_type'] ?? [],
                                            [
                                                'options' => $postTypes,
                                                'multiple' => true,
                                                'class' => 'oes-replace-select2',
                                                'reorder' => true,
                                                'hidden' => true
                                            ]
                                        ) .
                                        '<div>' . __('Post IDs', 'oes') . '</div>' .
                                        oes_html_get_form_element('text',
                                            $optionName . '[ids]',
                                            $optionID . '-ids',
                                            $data['ids'] ?? ''
                                        )
                                ]
                            ]
                        ],
                        [
                            'cells' => [
                                [
                                    'type' => 'th',
                                    'value' => '<strong>' . __('Default Zoom', 'oes') . '</strong>' .
                                        '<div>' .
                                        __('To set a different default zoom enter an integer bigger than zero.', 'oes') .
                                        '</div>'
                                ],
                                [
                                    'class' => 'oes-table-transposed',
                                    'value' => oes_html_get_form_element('text',
                                        $optionName . '[defaultzoom]',
                                        $optionID . '-defaultzoom',
                                        $data['defaultzoom'] ?? ''
                                    )
                                ]
                            ]
                        ],
                        [
                            'cells' => [
                                [
                                    'type' => 'th',
                                    'value' => '<strong>' . __('Default Center', 'oes') . '</strong>' .
                                        '<div>' .
                                        __('The default center is "51.582275;10.653294". Set a different position ' .
                                            'by entering latitude and longitude seperated by semicolon.', 'oes') .
                                        '</div>'
                                ],
                                [
                                    'class' => 'oes-table-transposed',
                                    'value' => oes_html_get_form_element('text',
                                        $optionName . '[center]',
                                        $optionID . '-center',
                                        $data['center'] ?? ''
                                    )
                                ]
                            ]
                        ],
                        [
                            'cells' => [
                                [
                                    'type' => 'th',
                                    'value' => '<strong>' . __('Legend', 'oes') . '</strong>' .
                                        '<div>' .
                                        __('Show the legend inside the map. Use the shortcode ' .
                                            '<code>[oes_map_legend]</code> to display the legend elsewhere.', 'oes') .
                                        '</div>'
                                ],
                                [
                                    'class' => 'oes-table-transposed',
                                    'value' => '<div class="oes-shortcode-inner-setting">' .
                                        '<label for="' . $optionName . '-show_legend"> ' .
                                        __('Show legend', 'oes') .
                                        '</label>' .
                                        '<div>' .
                                        oes_html_get_form_element('checkbox',
                                            $optionName . '[show_legend]',
                                            $optionID . '-show_legend',
                                            $data['show_legend'] ?? false,
                                            ['class' => 'oes-shortcode-inner-settings-element']
                                        ) .
                                        '</div><br>' .
                                        '<div>' .
                                        '<label for="' . $optionName . '-legend_text"> ' .
                                        __('Text above legend (e.g. "choose category")', 'oes') .
                                        '</label>' .
                                        oes_html_get_form_element('text',
                                            $optionName . '[legend_text]',
                                            $optionID . '-legend_text',
                                            $data['legend_text'] ?? false
                                        ) .
                                        '</div>' .
                                        '</div>'
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $nr = sizeof($data['categories'] ?? []);
            for ($i = 1; $i <= max(1, $nr); $i++) {

                $this->table_data[] =
                    [
                        'class' => 'oes-shortcode-settings-category-' . $i,
                        'rows' => [
                            [
                                'cells' => [
                                    [
                                        'type' => 'th',
                                        'value' => '<strong class="oes-shortcode-settings-th-category-' . $i . '">' .
                                            __('Category', 'oes') . ' ' . $i . '</strong>' .
                                            '<div><a href="javascript:void(0);" onClick="oesConfigDeleteClosestTbody(this)" class="oes-highlighted">' .
                                            __('Delete Category', 'oes') .
                                            '</a></div>'

                                    ],
                                    [
                                        'class' => 'oes-table-transposed',
                                        'value' =>
                                            '<div class="oes-shortcode-inner-setting">' .
                                            '<div><strong>' . __('Location Data', 'oes') . '</strong></div>' .
                                            '<label for="' . $optionName . '-cat' . $i . '-lat_field">(1) ' .
                                            __('and Latitude Field', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('select',
                                                $optionName . '[categories][' . $i . '][lat_field]',
                                                $optionName . '-cat' . $i . '-lat_field',
                                                $data['categories'][$i]['lat_field'] ?? 'none',
                                                ['options' => $textOptions,
                                                    'class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-lon_field">(2) ' .
                                            __('Longitude Field', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('select',
                                                $optionName . '[categories][' . $i . '][lon_field]',
                                                $optionName . '-cat' . $i . '-lon_field',
                                                $data['categories'][$i]['lon_field'] ?? 'none',
                                                ['options' => $textOptions,
                                                    'class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-google_field">(3) ' .
                                            __('or Google Map Field', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('select',
                                                $optionName . '[categories][' . $i . '][google_field]',
                                                $optionName . '-cat' . $i . '-google_field',
                                                $data['categories'][$i]['google_field'] ?? 'none',
                                                ['options' => $googleOptions,
                                                    'class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '</div><div class="oes-shortcode-inner-setting">' .
                                            '<div><strong>' . __('Condition', 'oes') . '</strong></div>' .
                                            '<label for="' . $optionName . '-cat' . $i . '-condition_field">(4) ' .
                                            __('Field', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('select',
                                                $optionName . '[categories][' . $i . '][condition_field]',
                                                $optionName . '-cat' . $i . '-condition_field',
                                                $data['categories'][$i]['condition_field'] ?? 'none',
                                                ['options' => $allFieldsOption,
                                                    'class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-condition_operator">(5) ' .
                                            __('Operation', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('select',
                                                $optionName . '[categories][' . $i . '][condition_operator]',
                                                $optionName . '-cat' . $i . '-condition_operator',
                                                $data['categories'][$i]['condition_operator'] ?? 'none',
                                                [
                                                    'options' => [
                                                        'none' => '-',
                                                        'equal' => '= (equal / in array)',
                                                        'notequal' => '!= (not equal / not in array)'
                                                    ],
                                                    'class' => 'oes-shortcode-inner-settings-element'
                                                ]
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-condition_value' . '">(6) '
                                            . __('Value', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('text',
                                                $optionName . '[categories][' . $i . '][condition_value]',
                                                $optionName . '-cat' . $i . '-condition_value',
                                                $data['categories'][$i]['condition_value'] ?? '',
                                                ['class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '</div><div class="oes-shortcode-inner-setting">' .
                                            '<div><strong>' . __('Popup', 'oes') . '</strong></div>' .
                                            '<label for="' . $optionName . '-cat' . $i . '-popup_function">(7) ' .
                                            __('Function', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('text',
                                                $optionName . '[categories][' . $i . '][popup_function]',
                                                $optionName . '-cat' . $i . '-popup_function',
                                                $data['categories'][$i]['popup_function'] ?? '',
                                                ['class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-popup_field">(8) ' .
                                            __('or Field', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('select',
                                                $optionName . '[categories][' . $i . '][popup_field]',
                                                $optionName . '-cat' . $i . '-popup_field',
                                                $data['categories'][$i]['popup_field'] ?? '',
                                                ['options' => $allFieldsOption,
                                                    'class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-popup_text">(9) ' .
                                            __('or Text', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('text',
                                                $optionName . '[categories][' . $i . '][popup_text]',
                                                $optionName . '-cat' . $i . '-popup_text',
                                                $data['categories'][$i]['popup_text'] ?? '',
                                                ['class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '</div><div class="oes-shortcode-inner-setting">' .
                                            '<div><strong>' . __('Design', 'oes') . '</strong></div>' .
                                            '<label for="' . $optionName . '-cat' . $i . '-color">(10) ' .
                                            __('Color', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('text',
                                                $optionName . '[categories][' . $i . '][color]',
                                                $optionName . '-cat' . $i . '-color',
                                                $data['categories'][$i]['color'] ?? '',
                                                ['class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '<label for="' . $optionName . '-cat' . $i . '-title">(11) ' .
                                            __('Title', 'oes') .
                                            '</label>' .
                                            oes_html_get_form_element('text',
                                                $optionName . '[categories][' . $i . '][title]',
                                                $optionName . '-cat' . $i . '-title',
                                                $data['categories'][$i]['title'] ?? '',
                                                ['class' => 'oes-shortcode-inner-settings-element']
                                            ) .
                                            '</div>'
                                    ]
                                ]
                            ]
                        ]
                    ];
            }
        }
    }

    // initialize
    \OES\Admin\Tools\register_tool('Map_Shortcode', 'map-shortcode');
endif;
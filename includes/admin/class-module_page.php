<?php

namespace OES\Map;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('\OES\Admin\Module_Page')) oes_include('admin/pages/class-module_page.php');

if (!class_exists('Map_Module_Page')) :

    class Map_Module_Page extends \OES\Admin\Module_Page
    {
        /** @inheritdoc */
        public function set_help_tabs($screen): void
        {
            $screen->add_help_tab([
                'id' => 'oes_map_leaflet',
                'title' => 'Leaflet',
                'content' => '<p>' .
                    __('OES Map uses <a href="https://leafletjs.com/" target="_blank">https://leafletjs.com/</a>. ' .
                        'If you want to integrate maps in your website make sure you are familiar with the condition of this ' .
                        'JavaScript library (mainly: we recommend to integrate the credit banner on the map).', 'oes-map') .
                    '</p>'
            ]);

            $screen->add_help_tab([
                'id' => 'oes_map_class',
                'title' => 'Custom Class',
                'content' => '<p>' .
                    sprintf(__('For advanced users or developers with access to the project plugin, it is possible to further ' .
                        'customize the data and parameters by implementing a project-specific class. The corresponding file ' .
                        'must be named %s.', 'oes-map'),
                        '<code>class_[projectname_without_oes_and_using_underscores_instead_of_hyphens]_map_entry.php</code>') .
                    '</p>'
            ]);

            $screen->add_help_tab([
                'id' => 'oes_map_notice',
                'title' => 'Notice',
                'content' => '<p>' .
                    __('If you want to use the map shortcode outside of post content data (e.g. inside a theme template ' .
                        'file) try to avoid putting the shortcode inside a shortcode block and use plain HTML notation. ' .
                        'The shortcode block uses the wpautop function and may break up the code outside of post content.', 'oes-map') .
                    '</p>'
            ]);

            $screen->set_help_sidebar('<p><strong>See </strong></p>' .
                '<p><a href="https://leafletjs.com/">Leaflet Documentation</a></p>');
        }
    }

    new Map_Module_Page([
        'name' => 'Map',
        'schema_enabled' => false,
        'file' => (__DIR__ . '/views/view-settings-map.php')
    ]);

endif;
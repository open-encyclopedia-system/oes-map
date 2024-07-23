<?php

namespace OES\Map;


/**
 * Add module page in admin dashboard.
 *
 * @param array $pages The admin menu pages.
 * @return array Return the modified admin menu pages.
 */
function admin_menu_pages(array $pages): array {
    $pages['085_map'] = [
        'subpage' => true,
        'page_parameters' => [
            'page_title' => 'Map',
            'menu_title' => 'Map',
            'menu_slug' => 'oes_map',
            'position' => 91,
            'parent_slug' => 'oes_settings'
        ],
        'view_file_name_full_path' => (__DIR__ . '/views/view-settings-map.php')
    ];
    return $pages;
}




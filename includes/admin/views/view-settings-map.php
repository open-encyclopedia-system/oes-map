<?php
$tabs = ['shortcode' => __('Shortcode', 'oes'), 'shortcode_editor' => __('Editor', 'oes')];
?>
<div class="oes-page-header-wrapper">
    <div class="oes-page-header">
        <h1><?php _e('OES Map Settings', 'oes'); ?></h1>
    </div>
    <nav class="oes-tabs-wrapper hide-if-no-js tab-count-<?php echo sizeof($tabs); ?>" aria-label="Secondary menu"><?php

        foreach ($tabs as $tab => $label) printf('<a href="%s" class="oes-tab %s">%s</a>',
            admin_url('admin.php?page=oes_map&tab=' . $tab),
            ((($_GET['tab'] ?? 'shortcode') == $tab) ? 'active' : ''),
            $label
        );
        ?>
    </nav>
</div>
<div class="oes-page-body"><?php

    if (isset($_GET['tab']) && $_GET['tab'] == 'shortcode_editor'):
        \OES\Admin\Tools\display('map-shortcode');
    else:?>
    <p>
        <?php _e('OES Map uses <a href="https://leafletjs.com/" target="_blank">https://leafletjs.com/</a>. ' .
            'If you want to integrate maps in your website make sure you are familiar with the condition of this ' .
            'JavaScript library (mainly: we recommend to integrate the credit banner on the map).', 'oes');?>
    </p>
        <div class="oes-configuration-header">
            <h2><?php _e('Stored Shortcodes', 'oes'); ?></h2>
        </div>
        <?php
        \OES\Shortcode\display_stored_shortcodes(OES_MAP_SHORTCODE_PREFIX, OES_MAP_SHORTCODE_PARAMETER);
        ?>
        <p>
            <a class="button"
               href="<?php echo admin_url('admin.php?page=oes_map&tab=shortcode_editor&selected=new'); ?>"><?php
                echo __('Create New Shortcode', 'oes'); ?></a>
        </p>
        <div class="oes-configuration-header">
            <h2><?php _e('Shortcode Documentation', 'oes'); ?></h2>
        </div>
        <div>
            <p><?php
                _e('To display data as a map you can add a shortcode to a page. The shortcode ' .
                    'includes parameters that determine which data will be used to display the map.',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('The shortcode will look something like this (everything in curved brackets depends on the ' .
                    'project data model and can be configured):', 'oes');
                ?>
            </p>
            <code>
                [oes_map post_type="{post type key}" cat1="{latitude field;longitude field;google Field;color;condition
                field;condition value;condition operator}" defaultzoom="{zoom}"
                center="{latitude;longitude}" legend="{show;type}"]
            </code>
            <p><?php
                _e('The shortcode is always defined within square brackets <code>[]</code>, starts with ' .
                    '<code>oes_map</code> and is followed by optional parameters that form a pair of ' .
                    'key and value noted as <code>{key}="{value}"</code>.',
                    'oes');
                ?>
            </p>
        </div>
        <div>
            <p><strong><?php _e('Post Type', 'oes'); ?></strong></p>
            <p><?php
                _e('The post type define the OES post type that is to be displayed as a map. To display data ' .
                    'as map the post type needs to have fields storing location data, e.g. a Google map field or ' .
                    'two fields storing longitude and latitude. The post type is ' .
                    'marked by <code>post_type</code>. The value of an object ' .
                    'is the post type key of the considered OES post type.', 'oes');
                ?>
            </p>
        </div>
        <div>
            <p><strong><?php _e('IDs', 'oes'); ?></strong></p>
            <p><?php
                _e('Alternatively, a list of post IDs can be specified. The values are separated by a semicolon. ' .
                    'If no post type and no IDs are defined only the ' .
                    'currently displayed post is considered for map data.', 'oes');
                ?>
            </p>
        </div>
        <div>
            <p><strong><?php _e('Categories', 'oes'); ?></strong></p>
            <p><?php
                _e('Categories determine the group and color in which a map pin is displayed. ' .
                    'The first category is defined by ' .
                    '<code>cat1</code>, the value of <code>cat1</code> consists of several parameters separated by a ' .
                    'semicolon following a strict sequence. Further categories follow this notation and are defined by ' .
                    '<code>cat2</code>, <code>cat3</code>, and so on.',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('The first parameter of a category is the (1) “longitude field” defining the longitude of a map ' .
                    'pin. The second parameter is the (2) “latitude field” defining the latitude of a map pin. ' .
                    'Valid values are field keys of the considered post type, empty or "none".',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('The third parameter is the (3) “Google map field” (only for ACF Pro). This field can be used ' .
                    'instead of the two first parameter (latitude and longitude). Valid values are field keys of ' .
                    'fields with field type "Google Map" of the considered post type, empty or "none".',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('If the category depends on a specific field value you can use the fourth ((4) "condition field") ' .
                    'and sixth ((6) "condition value") to define the category. Valid values for the "condition field" ' .
                    'are field keys of the considered post type. The "condition value" defines the condition to be met. ' .
                    'The default condition operator is "equal" (===), if you want to use a different operator you ' .
                    'can use the fifth parameter (5) "condition operation". Valid values are "equal" and "notequal".',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('The pin popup can be represented by a static string, a field value or a valid function. ' .
                    'The popup can be modified by a custom function defined in the seventh parameter (7) "function". ' .
                    'To display a field value as popup you can use the eight parameter (8) "field". It the ninth ' .
                    'parameter (9) "text" is defined a static string for all pins is displayed. ' .
                    'The default is the post name with a link to the single view of the post.', 'oes');
                ?></p>
            <p><?php
                _e('The tenth parameter is the (10) “color” defining the color of the map pin. Valid values are ' .
                    'hex color values. Default is #111111 (black).',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('If you are displaying the legend you can set a category title with the eleventh parameter ' .
                    '(11) "title".',
                    'oes');
                ?>
            </p>
            <p><?php
                _e('Examples:', 'oes');
                ?>
            </p>
            <code>cat1="field_demo_place__longitude;field_demo_place__latitude;"</code><br>
            <code>cat2=";;field_demo_place__google_map;;;;;;Static Info"</code><br>
            <code>cat3=";;field_demo_place__google_map;field_demo_place__country;equal;Germany;;;#123456;In Germany"</code><br>
            <code>cat4=";;field_demo_place__google_map;field_demo_place__country;notequal;Germany;;;#654321;Not in Germany"</code>
        </div>
        <div>
            <p><strong><?php _e('Further options', 'oes'); ?></strong></p>
            <p><?php
                _e('<code>defaultzoom</code>: To set a different default zoom enter an integer bigger than zero.',
                    'oes');
                ?></p>
            <p><?php
                _e('<code>center</code>: The default center is "51.582275;10.653294". Set a different ' .
                    'position by entering latitude and longitude seperated by semicolon.', 'oes');
                ?></p>
            <p><?php
                _e('<code>show_legend</code>: If set to "true" or "1" the legend is shown inside the map. '.
                    'Use another shortcode [oes_map_legend] to display the legend elsewhere.', 'oes');
                ?></p>
            <p><?php
                _e('<code>legend_text</code>: Set the text above the legend (e.g. "choose category").',
                    'oes');
                ?></p>
            <p><?php
                _e('<code>width</code>: Set the map width (for css % or px). Default is 100%.', 'oes');
                ?></p>
            <p><?php
                _e('<code>height</code>: Set the map height (for css % or px). Default is 500px.', 'oes');
                ?></p>
        </div>
    <?php
    endif; ?>
</div>
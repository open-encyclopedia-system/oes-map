/* TODO while map is hidden by default, we have to invalidate size on show, and fit bounds */
jQuery('.oes-ev-map-single .oes-panel-bootstrap').on('shown.bs.collapse', function (e) {
    oesMap.map.invalidateSize(true);
    oesMap.map.fitBounds(oesMap.allMarkers);
});

function oesMapExternalLegend(map_ID, category) {
    for (let key in window.oesMap.layerGroups) {

        let layer = window.oesMap.layerGroups[key];
        if (category === 'all') {
            window.oesMap.maps[map_ID].addLayer(layer);
            jQuery('#' + key).removeClass('oes-legend-item-inactive');
        } else if (layer.oes_id === map_ID + '_' + category) {
            jQuery('#' + map_ID + '_' + category).toggleClass('oes-legend-item-inactive');
            if (window.oesMap.maps[map_ID].hasLayer(layer)) {
                window.oesMap.maps[map_ID].removeLayer(layer);
            } else {
                window.oesMap.maps[map_ID].addLayer(layer);
            }
        }
    }
}
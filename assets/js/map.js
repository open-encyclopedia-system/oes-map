/**
 * Initialize the map after the page is fully loaded.
 * Calls oesMapShowMap with default parameters to ensure the map is ready.
 */
window.addEventListener('load', function () {
    oesMapShowMap(false, true);
});

/**
 * Handles all relevant click interactions on the document:
 * - Map toggle
 * - List toggle
 * - Archive/filter elements
 * - External legend toggle
 */
document.addEventListener('click', function (event) {
    const filterEl = event.target.closest(
        '.oes-archive-filter, .oes-filter-abc, .oes-active-filter-item'
    );

    if (filterEl) {

        const mapID = filterEl.dataset.mapId || 'oes_map_1';
        const mapWrapper = document.getElementById(mapID);
        const container = mapWrapper?.closest('.oes-map-container');

        if (container && window.getComputedStyle(container).display !== 'none') {

            const spinner = document.getElementById('oes-map-loading-spinner');
            if (spinner) {
                spinner.style.display = 'flex';
            }

            oesMapShowMap(mapID, false);
        }
        return;
    }

    const el = event.target;

    if (el.matches('.oes-map-map-toggle')) {
        const mapID = el.dataset.mapId || 'oes_map_1';
        oesMapShowMap(mapID, true);
        return;
    }

    if (el.matches('.oes-map-list-toggle')) {
        oesMapShowList();
        return;
    }

    if (el.matches('.oes-map-external-legend-toggle')) {
        const mapID = el.dataset.mapId;
        const category = el.dataset.category;
        el.classList.toggle('selected');
        oesMapExternalLegend(mapID, category);
        return;
    }
});

/**
 * Hook triggered by range slider filters.
 * Ensures the map is updated if the container is visible.
 */
document.addEventListener('oes-range-filter-processed', function () {
    document.querySelectorAll('.oes-range-slider').forEach(function (el) {
        const mapID = el.dataset.mapId || 'oes_map_1';
        const mapWrapper = document.getElementById(mapID);
        const container = mapWrapper?.closest('.oes-map-container');

        if (container && window.getComputedStyle(container).display !== 'none') {
            oesMapShowMap(mapID, false);
        }
    });
});

/**
 * Updates the visible markers on a Leaflet map based on a list of valid entry IDs.
 *
 * @param {string} map_ID - The ID of the map instance (e.g., "oes_map_1").
 * @param {Array<string>} valid_IDs - An array of entry IDs that should remain visible.
 */
function oesMapModifyLayers(map_ID, valid_IDs) {
    map_ID = map_ID || 'oes_map_1';
    const map = oesMap.maps[map_ID];
    const bounds = L.latLngBounds([]);

    oesMap.allMarkers[map_ID].forEach(markerGroup => {
        markerGroup.eachLayer(marker => {
            const shouldBeVisible = valid_IDs.includes(marker.entryID);
            const isCurrentlyVisible = map.hasLayer(marker);

            if (shouldBeVisible && !isCurrentlyVisible) {
                map.addLayer(marker);
            } else if (!shouldBeVisible && isCurrentlyVisible) {
                map.removeLayer(marker);
            }

            if (map.hasLayer(marker)) {
                bounds.extend(marker.getLatLng());
            }
        });
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [10, 10] });
    }
}

/**
 * Toggles visibility of map layers based on external legend interaction.
 *
 * @param {string} map_ID - The ID of the map instance (e.g., "oes_map_1").
 * @param {string} category - The category to toggle (e.g., "cat1", or "all" to show all).
 */
function oesMapExternalLegend(map_ID, category) {
    const map = oesMap.maps[map_ID];
    const layerGroups = oesMap.layerGroups;

    for (let key in layerGroups) {
        const layer = layerGroups[key];
        const isCategoryMatch = key === `${map_ID}_${category}`;
        const isMapMatch = key.startsWith(`${map_ID}_`);

        if (category === 'all') {
            if (isMapMatch) {
                map.addLayer(layer);
                jQuery('#' + key).removeClass('oes-map-legend-item-inactive');
            }
        } else if (isCategoryMatch) {
            const selector = '#' + key;
            const isActive = map.hasLayer(layer);

            jQuery(selector).toggleClass('oes-map-legend-item-inactive');

            if (isActive) {
                map.removeLayer(layer);
            } else {
                map.addLayer(layer);
            }
        }
    }
}

/**
 * Switches the view to show the map, filters markers, and triggers a custom event.
 *
 * @param {string|boolean} map_ID - ID of the map instance or false to use default.
 * @param {boolean} switch_tab - Whether to switch UI tabs (map/list).
 */
function oesMapShowMap(map_ID, switch_tab = true) {
    map_ID = map_ID || 'oes_map_1';

    // Allow external plugin to provide visible marker IDs
    const externalFnName = 'oesMapGetValidIDS_' + map_ID;
    if (typeof window[externalFnName] === 'function') {
        let valid_IDs = [];
        valid_IDs = window[externalFnName]();
        oesMapModifyLayers(map_ID, valid_IDs);
    }

    const mapToggle = jQuery('.oes-map-map-toggle');
    const listToggle = jQuery('.oes-map-list-toggle');
    if (mapToggle.length || listToggle.length) {

        if (switch_tab) {
            mapToggle.addClass('active');
            listToggle.removeClass('active');
        }

        jQuery('.oes-map-container').show();
        jQuery('.oes-archive-wrapper').css('opacity', '0');
        jQuery('.wp-block-oes-archive-loop').css('height', '0');
    }

    const spinner = document.getElementById('oes-map-loading-spinner');
    if (spinner) {
        spinner.style.display = 'none';
    }

    document.dispatchEvent(new CustomEvent('oes-map-map-displayed'));
}

/**
 * Switches the view to list view (hides the map).
 */
function oesMapShowList() {
    jQuery('.oes-map-map-toggle').removeClass('active');
    jQuery('.oes-map-list-toggle').addClass('active');

    jQuery('.oes-map-container').hide();
    jQuery('.oes-archive-wrapper').css('opacity', '1');
    jQuery('.wp-block-oes-archive-loop').css('height', '100%');

    document.dispatchEvent(new CustomEvent('oes-map-list-displayed'));
}


/**
 * Navigates between paginated popup content elements within a Leaflet map marker popup.
 */
function oesMapPageNav(button, direction) {
    const wrapper = button.closest('.popup-wrapper');
    const pages = wrapper.querySelectorAll('.popup-page');
    const pageIndicator = wrapper.querySelector('.page-numbers.current');

    let currentIndex = Array.from(pages).findIndex(p => p.style.display !== 'none');
    const newIndex = currentIndex + direction;

    if (newIndex >= 0 && newIndex < pages.length) {

        // Hide current and show new page
        pages[currentIndex].style.display = 'none';
        pages[newIndex].style.display = '';

        // Update page indicator text (1-based index)
        if (pageIndicator) {
            pageIndicator.textContent = `${newIndex + 1} / ${pages.length}`;
        }
    }
}

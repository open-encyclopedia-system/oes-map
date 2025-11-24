(function ($) {

    // Prevents redundant add-layer calls
    let ignored_add_layer = false;

    /**
     * Global OES Map Object to store maps, layers, settings, and markers
     */
    window.oesMap = {
        maps: {},         // Leaflet map instances
        settings: {},     // Custom settings per map
        allMarkers: {},   // Marker groups for each map
        maplayers: {},    // GeoJSON border layers
        layerGroups: {}   // LayerGroups for legend overlays
    };

    /**
     * Initialize the map with provided data and options.
     *
     * @param {string} elementName - The HTML element ID for the map container.
     * @param {Array} data - Marker groups and their metadata.
     * @param {Object} options - Configuration options for map appearance and behavior.
     */
    oesMap.init = function (elementName, data, options) {
        const defaults = {
            showLegend: false,
            controlsCollapsed: true,
            controlText: 'Legend',
            legendLabelType: 'Choose Type',
            legendLabelBorders: 'Show borders',
            fitBounds: false,
            showBorders: true,
            defaultZoom: 5,
            maxZoom: 18,
            defaultCenter: [51.1657, 10.4515],
            map_id: elementName,
            layer_files: [] // GeoJSON files for borders
        };

        const validated_options = $.extend({}, defaults, options || {});

        initializeMap(elementName, validated_options);
        addMarkersToMap(elementName, data, validated_options);
        addMapLayersAndControls(elementName, validated_options, data);
    };

    /**
     * Initializes the Leaflet map with tile layer and default settings.
     *
     * @param {string} elementName - The map container ID.
     * @param {Object} options - Validated configuration options.
     */
    function initializeMap(elementName, options) {
        const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Daten von <a href="http://www.openstreetmap.org/">OpenStreetMap</a> - Veröffentlicht unter <a href="http://opendatacommons.org/licenses/odbl/">ODbL</a>'
        });

        oesMap.maps[elementName] = L.map(elementName, {
            center: oesMap.settings[elementName]?.center || options.defaultCenter,
            zoom: oesMap.settings[elementName]?.zoom || options.defaultZoom,
            layers: [tileLayer],
            maxZoom: oesMap.settings[elementName]?.maxzoom || options.maxZoom,
            scrollWheelZoom: false
        });
    }

    /**
     * Adds marker groups to the map and optionally adds them to the legend.
     *
     * @param {string} elementName - Map identifier.
     * @param {Array} data - Array of marker group objects.
     * @param {Object} options - Validated configuration options.
     */
    function addMarkersToMap(elementName, data, options) {
        let bounds = L.latLngBounds([]);
        const allMarkers = [];
        let controlLayers = null;

        if (options.showLegend) {
            controlLayers = new L.control.layers(null, null, {collapsed: options.controlsCollapsed})
        }

        $.each(data, function (i, row) {
            const groupTitle = row.title,
                markersData = row.data;

            if (markersData.length > 0) {
                const layerGroup = L.layerGroup();
                const color = markersData[0]?.color || '#000000';

                markersData.forEach(marker => {
                    const markerLayer = L.circleMarker([marker.lat, marker.lon], {
                        radius: marker.radius || 5,
                        fillColor: marker.color || color,
                        color: marker.color || color,
                        weight: marker.weight || 1,
                        opacity: marker.opacity || 1,
                        fillOpacity: marker.fillOpacity || 1
                    }).bindPopup(marker.popup_text);

                    markerLayer.entryID = marker.entry_ID;
                    
                    markerLayer.addTo(layerGroup);
                    bounds.extend(markerLayer.getLatLng());
                });

                // Store and add the group to the map
                layerGroup.addTo(oesMap.maps[elementName]);
                oesMap.layerGroups[`${elementName}_${i}`] = layerGroup;
                allMarkers.push(layerGroup);

                // Optionally add to legend
                if (options.showLegend) {
                    const iconHTML = `<svg width="12" height="12" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 5px;"><circle cx="5" cy="5" r="4" stroke="${color}" fill="${color}" /></svg>`;
                    controlLayers.addOverlay(layerGroup, `<span class="oes-map-group-title">${iconHTML}${groupTitle}</span>`);
                }
            }
        });

        oesMap.allMarkers[elementName] = allMarkers;

        // Fit map to marker bounds
        if (options.fitBounds && bounds.isValid()) {
            oesMap.maps[elementName].fitBounds(bounds, {padding: [10, 10]});
        }


        if (options.showLegend) {
            oesMap.maps[elementName].addControl(controlLayers);
        }
    }

    /**
     * Adds overlays like legends, border layers (GeoJSON), and filter toggles.
     *
     * @param {string} elementName - Map ID.
     * @param {Object} options - Validated configuration options.
     * @param {Array} data - Marker data (used for legends).
     */
    function addMapLayersAndControls(elementName, options, data) {
        const map = oesMap.maps[elementName];
        const container = `#${options.map_id}`;

        // Legend toggle button
        if (options.showLegend) {
            const $legendToggle = $(`
                <div id="oes-map-legend" class="leaflet-control-layers-expanded leaflet-control">
                    ${options.controlText} <i class="fa fa-caret-down"></i>
                </div>`);

            $legendToggle.insertBefore(`${container} .leaflet-control-layers`);
            $(`${container} .leaflet-control-layers`).hide();

            $legendToggle.on('click', function () {
                $(`${container} .leaflet-control-layers`).toggle();
            });

            // Optional help text above overlays
            $('<div class="oes-map-help">' + options.legendLabelType + '</div>')
                .insertBefore(`${container} .leaflet-control-layers-overlays`);
        }

        // GeoJSON border layer toggles
        if (options.showBorders && options.layer_files.length > 0) {
            $('<div id="oes-overlay-filter-header">' + options.legendLabelBorders + '</div>')
                .insertAfter(`${container} .leaflet-control-layers-overlays`);

            options.layer_files.forEach(function (layerFile) {
                $.getJSON(layerFile.url).done(function (geojsonData) {
                    const geoJsonLayer = L.geoJSON(geojsonData, {
                        style: {color: 'orange', weight: 1, opacity: 1, fillOpacity: 0}
                    }).addTo(map);

                    oesMap.maplayers[elementName] = oesMap.maplayers[elementName] || [];
                    oesMap.maplayers[elementName].push(geoJsonLayer);

                    const checkboxId = `oes-map-layer-${elementName}_${layerFile.id}`;
                    $(`<div><label><input type="checkbox" id="${checkboxId}" checked> ${layerFile.name}</label></div>`)
                        .insertAfter(`${container} #oes-overlay-filter-header`);

                    $(`#${checkboxId}`).change(function () {
                        if (this.checked) {
                            map.addLayer(geoJsonLayer);
                        } else {
                            map.removeLayer(geoJsonLayer);
                        }
                    });
                });
            });
        }
    }

})(jQuery);

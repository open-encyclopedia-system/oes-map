(function ($) {

    /*
    * Settings is an array with first key 'elementName' and contains:
    * center
    * zoom
    * clusteringEnabled
    * isClustered
    */
    let ignored_add_layer = false;
    window.oesMap = {
        maps: [],
        settings: [],
        //MarkerClusterGroups: [],
        allMarkers: [],
        //ClusterGroupsActiveState: [],
        maplayers: {},  //enthällt layers as objects with id and geojson
        layerGroups: {}
    };


    /**
     * Initialize map.
     *
     * @param elementName Selector for the map.
     * @param data The map data.
     * @param options The map options.
     */
    oesMap.init = function (elementName, data, options) {
        /* VALIDATE PARAMETERS ---------------------------------------------------------------------------------------*/

        /* prepare default options */
        const defaults = {
            showLegend: false,
            controlsCollapsed: true,
            controlText: 'Legend',
            legendLabelType: 'Choose Type',
            legendLabelBorders: 'Show borders',
            fitBounds: false,
            showBorders: true,
            defaultZoom: 5,
            defaultCenter: [51.582275, 10.653294]

        };
        const validated_options = $.extend({}, defaults, options || {});


        /* clear the marker cluster groups, necessary cause with changing filter or facet, map will be reloaded */

        oesMap.markers = [];
        oesMap.settings[elementName] = [];

        //console.log(oesMap);
        /* prepare title (credits) */
        const tile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Daten von <a href="http://www.openstreetmap.org/">OpenStreetMap</a> - Veröffentlicht unter <a href="http://opendatacommons.org/licenses/odbl/">ODbL</a>'
        });

        /* create leaflet map */
        oesMap.maps[elementName] = L.map(elementName, {
            center: oesMap.settings[elementName]['center'] === undefined ? validated_options.defaultCenter : oesMap.settings[elementName]['center'],
            zoom: oesMap.settings[elementName]['zoom'] === undefined ? validated_options.defaultZoom : oesMap.settings[elementName]['zoom'],
            layers: [tile],
            maxZoom: 18,
            scrollWheelZoom: false
        });

        /* prepare map layers */
        oesMap.maplayers[elementName] = [];

        /* validate controls: no matter what was set in the options, if the screenwidth < 768 px, we do not want to
        show the controls initially, because it conserves almost the whole map. */
        if (screen.availWidth < 768) validated_options.showLegend = false;

        /* prepare control layer */
        const control_layers = new L.control.layers(null, null, {collapsed: validated_options.controlsCollapsed});


        /* PREPARE DATA ----------------------------------------------------------------------------------------------*/

        /* collect all markers from all layer to update fitBounds */
        let allMarkers = [];
        allMarkers[elementName] = [];
        $.each(data, function (i, row) {

            const group_title = row.title, markers_data = row.data;
            let markers = [];
            //create a layergroup
            var layerGroup = L.layerGroup();
            layerGroup.oes_id = elementName + '_' + i;
            console.log(layerGroup.oes_id);

            if (markers_data.length > 0) {

                /* prepare filter */
                let post_filter = '';
                for (let key in markers_data) {
                    post_filter = post_filter + ' oes-post-filter-' + markers_data[key]['post_id'];
                }

                /* Process markers per group */
                let marker_icon_class, marker_color;
                markers_data.forEach(function (marker_data, index) {
                    if (marker_data.color !== undefined) {
                        marker_color = marker_data.color;
                    }
                    circleMarker = L.circleMarker([marker_data.lat, marker_data.lon], {
                        radius: 5,
                        fillColor: marker_data.color,
                        color: marker_data.color,
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 1
                    });

                    circleMarker.bindPopup(marker_data.popup_text);
                    //add the marker to the layerGroup
                    circleMarker.addTo(layerGroup);
                    markers.push(circleMarker);

                });

                //const svg = '<svg width="25" height="33" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg"><path d="M28.205 3.217H6.777c-2.367 0-4.286 1.87-4.286 4.179v19.847c0 2.308 1.919 4.179 4.286 4.179h5.357l5.337 13.58 5.377-13.58h5.357c2.366 0 4.285-1.87 4.285-4.179V7.396c0-2.308-1.919-4.179-4.285-4.179" fill="' + marker_color + '"></path><g opacity=".15" transform="matrix(1.0714 0 0 -1.0714 -233.22 146.783)"><path d="M244 134h-20c-2.209 0-4-1.746-4-3.9v-18.525c0-2.154 1.791-3.9 4-3.9h5L233.982 95 239 107.675h5c2.209 0 4 1.746 4 3.9V130.1c0 2.154-1.791 3.9-4 3.9m0-1c1.654 0 3-1.301 3-2.9v-18.525c0-1.599-1.346-2.9-3-2.9h-5.68l-.25-.632-4.084-10.318-4.055 10.316-.249.634H224c-1.654 0-3 1.301-3 2.9V130.1c0 1.599 1.346 2.9 3 2.9h20" fill="#231f20"></path></g></svg>';
                //const svg = '<path class="leaflet-interactive" stroke="' + marker_color + '" stroke-opacity="1" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" fill="#191970" fill-opacity="0.5" fill-rule="evenodd" d="M0 0"></path>';
                const iconHTML = '<svg class="MapFilter-icon u-ml-tiny" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg"><circle cx="25" cy="25" r="20" stroke="' + marker_color +'" fill="' + marker_color + '" fill-opacity="1" stroke-opacity="1" stroke-width="1"></circle></svg>';
                //add the layergroup to the map
                layerGroup.addTo(oesMap.maps[elementName]);
                //add the layerGroup to the layerGroups
                oesMap.layerGroups[elementName + '_' + i] = layerGroup;

                if (validated_options.showLegend === true) {
                    control_layers.addOverlay(layerGroup, '<div class="oes-map-group-title">' + iconHTML + group_title + '</div>');
                    control_layers.addTo(oesMap.maps[elementName]);
                }

                if (validated_options.controlsCollapsed === true) {
                  $('#' + validated_options.map_id + ' .leaflet-control-layers').hide(1);
                }
                allMarkers[elementName] = allMarkers[elementName].concat(markers);
            }
        });

        /* prepare icon popup */
        oesMap.allMarkers[elementName] = allMarkers[elementName];


        /* Add legend */
        //TODO id eindeutig!, nur an entsprechender Karte einfügen
        $('<div id="oes-map-legend" class="leaflet-control-layers-expanded leaflet-control">' + validated_options.controlText + '&nbsp;&nbsp;<i class="fa fa-caret-down"></i></div>').insertBefore('#' + validated_options.map_id +' div.leaflet-control-layers');
        $('<div class="oes-map-help">' + validated_options.legendLabelType + '</div>').insertBefore('#' + validated_options.map_id + ' div.leaflet-control-layers-overlays');

        /* Add map layers (borderfiles) */
        if (validated_options.showBorders !== false && validated_options.layer_files.length > 0) {
            $('<div id="oes-overlay-filter-header">' + validated_options.legendLabelBorders + '</div>').insertAfter('#' + validated_options.map_id + ' div.leaflet-control-layers-overlays');
            validated_options.layer_files.forEach(function (layer_file) {
                const geoJsonData = $.getJSON(layer_file.url);
                const id = layer_file.id;
                const exteriorStyle = {
                    "color": "orange",
                    "weight": 1,
                    "fillOpacity": 0,
                    "opacity": 1
                };
                geoJsonData.then(function (data) {
                    var geoJson = L.geoJSON(data, {style: exteriorStyle}).addTo(oesMap.maps[elementName]);
                    checked = '';

                   oesMap.maplayers[elementName][id] = oesMap.maplayers[id] || [];
                   oesMap.maplayers[elementName][id].push(geoJson);

                    $('<div><label><input type="checkbox" id="oes-map-layer-' +elementName + '_' + id + '" checked>' + layer_file.name  + ' </label</div>').insertAfter('#' + validated_options.map_id + ' div#oes-overlay-filter-header');

                    //geoJson.addTo(oesMap.map);
                    $('#oes-map-layer-' + elementName + '_' + id ).change(function () {
                        if ($(this).is(':checked')) {
                            oesMap.maplayers[elementName][id][0].addTo(oesMap.maps[elementName]);
                        } else {
                            oesMap.maplayers[elementName][id][0].removeFrom(oesMap.maps[elementName]);
                        }
                    });
                })
            } );

        }

        if (validated_options.fitBounds !== false) {
            var bounds = L.latLngBounds(allMarkers[elementName]);
            //TODO: Fi Bounds nur wenn es Marker gibt
            //esMap.maps[elementName].fitBounds(bounds, {padding: [10, 10]})
        }

        console.log('mapid: ' + validated_options.map_id);
        /* add legend toggle */
        $('#' + validated_options.map_id + ' #oes-map-legend').click(function () {
            $('#' + validated_options.map_id +' .leaflet-control-layers').toggle('1000');
        });

        /* Overlay functionalities */
        oesMap.maps[elementName].on('overlayadd', function (e) {
            let test = e.name.split('<div class="oes-map-group-title">');
            const title = test[1].replace('</span>', '').replace('</div>', '');
        });

        oesMap.maps[elementName].on('overlayremove', function (e) {
            let test = e.name.split('<div class="oes-map-group-title">');
            const title = test[1].replace('</span>', '').replace('</div>', '');
        });

        /* after moving, save current center in oesMap.settings */
        oesMap.maps[elementName].on('moveend', function (e) {
            oesMap.settings[elementName]['center'] = oesMap.maps[elementName].getCenter();
        });

    };

})(jQuery);
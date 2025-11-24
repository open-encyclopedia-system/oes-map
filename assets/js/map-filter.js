(function () {
    function safeInitMap(elementName, data, options) {
        if (!window.oesMap || typeof oesMap.init !== 'function') {
            console.error("oesMap is not available.");
            return;
        }

        // Destroy any existing Leaflet map instance for this element
        if (oesMap.maps[elementName]) {
            oesMap.maps[elementName].remove();
            delete oesMap.maps[elementName];
        }

        oesMap.init(elementName, data, options);
    }

    function filterMapData(originalData, allowedIds, filterGroups) {

        // allowedIds → array of numbers/strings
        const isEmpty = Object.values(filterGroups).every(arr => Array.isArray(arr) && arr.length === 0);
        if (isEmpty &&(!Array.isArray(allowedIds) || allowedIds.length === 0)) {
            return originalData; // nothing to filter → return all
        }

        const allowedSet = new Set(allowedIds);
        const filteredData = {};

        for (const category in originalData) {
            if (!originalData.hasOwnProperty(category)) continue;

            const categoryObj = originalData[category];
            if (!categoryObj.data) continue;

            const filteredEntries = categoryObj.data
                .map(entry => {
                    if (!Array.isArray(entry.projects)) return entry;

                    // Keep only projects with id in allowedSet
                    const allowedProjects = entry.projects.filter(project => allowedSet.has(project.id));

                    // Also clean popup_text based on allowed project IDs
                    const cleanedPopup = window.oesFilterPopupText(entry.popup_text, allowedSet);

                    // Return new entry with filtered projects and cleaned popup_text
                    return {
                        ...entry,
                        projects: allowedProjects,
                        popup_text: cleanedPopup
                    };
                })
                // Then keep only entries that still have projects
                .filter(entry => Array.isArray(entry.projects) && entry.projects.length > 0);


            // Only keep category if it still has entries
            if (filteredEntries.length > 0) {
                filteredData[category] = {
                    ...categoryObj,
                    data: filteredEntries
                };
            }
        }

        let markerCount = 0;
        for (const catKey in filteredData) {
            if (filteredData[catKey]?.data) {
                markerCount += filteredData[catKey].data.length;
            }
        }

        const countEl = document.querySelector('.oes-archive-count-number');
        if (countEl) {
            countEl.textContent = markerCount;
        }

        return filteredData;
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', function () {
        safeInitMap(mapID, mapData, mapOptions);
    });

    // Re-init when filters are applied
    document.addEventListener('oes-filter-processed', function (e) {
        const filterGroups = e.detail.currentFilterPostIDs || {};
        const allAllowedIds = [...(e.detail.filteredIDs || [])];

        const newMapData = filterMapData(mapData, allAllowedIds, filterGroups);
        safeInitMap(mapID, newMapData, mapOptions);
    });
})();

window.oesFilterPopupText = function defaultFilterPopupText(popupText, allowedSet){
    return popupText;
}

const maps = new Map();

async function initMap(mapElement) {
  const mapSettings = JSON.parse(mapElement.dataset.mapSettings);
  const { Map } = await google.maps.importLibrary("maps");
  const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

  const map = new Map(mapElement, {
    zoom: mapSettings.zoom,
    center: mapSettings.center,
    mapId: mapSettings.mapId,
  });

  if (mapSettings.locations) {
    mapSettings.locations.forEach((location) => {
      new AdvancedMarkerElement({
        map: map,
        position: { lat: location.lat, lng: location.lng },
        title: location.name,
        
      });
    });
  }

  maps.set(mapSettings.mapId, map);
  return map;
}

function initAllMaps() {
  document.querySelectorAll(".gme-map").forEach((mapElement) => {
    initMap(mapElement);
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initAllMaps);
} else {
  initAllMaps();
}

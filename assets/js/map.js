const maps = new Map();

async function initMap(mapElement) {
  const mapSettings = JSON.parse(mapElement.dataset.mapSettings);
  const { Map } = await google.maps.importLibrary("maps");
  const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

  const mapOptions = {
    zoom: mapSettings.zoom,
    center: mapSettings.center,
    mapId: mapSettings.mapId,
    backgroundColor: mapSettings.backgroundColor,
    disableDefaultUI: mapSettings.disableDefaultUI,
    fullscreenControl: mapSettings.fullscreenControl,
    clickableIcons: mapSettings.clickableIcons,
    colorScheme: mapSettings.colorScheme,
    disableDoubleClickZoom: mapSettings.disableDoubleClickZoom,
    gestureHandling: mapSettings.gestureHandling,
    keyboardShortcuts: mapSettings.keyboardShortcuts,
    maxZoom: mapSettings.maxZoom,
    minZoom: mapSettings.minZoom,
    rotateControl: mapSettings.rotateControl,
    scaleControl: mapSettings.scaleControl,
    scrollwheel: mapSettings.scrollwheel,
    zoomControl: mapSettings.zoomControl,
    streetView: mapSettings.streetView,
    streetViewControl: mapSettings.streetViewControl,
    // Add new configuration options
    renderingType: mapSettings.renderingType,
    mapTypeId: mapSettings.mapTypeId,
    tilt: mapSettings.tilt,
    tiltInteractionEnabled: mapSettings.tiltInteractionEnabled,
  };

  // Add restriction if enabled
  if (mapSettings.restriction) {
    mapOptions.restriction = mapSettings.restriction;
  }

  const map = new Map(mapElement, mapOptions);

  // Add markers if locations exist
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

// Initialize all maps when DOM is ready
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

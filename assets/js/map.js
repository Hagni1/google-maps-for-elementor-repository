const maps = new Map();

async function initMap(mapElement) {
  try {
    const placeholder = mapElement.querySelector('.gme-map-placeholder');
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
    
    // Remove placeholder after map is loaded
    if (placeholder) {
      placeholder.style.display = 'none';
    }
    
    return map;
  } catch (error) {
    console.error('Error initializing map:', error);
    const notice = mapElement.querySelector('.gme-map-notice');
    if (notice) {
      notice.textContent = 'Error loading map. Please try again later.';
    }
  }
}

// Initialize all maps when DOM is ready
function initAllMaps() {
  document.querySelectorAll(".gme-map").forEach((mapElement) => {
    initMap(mapElement);
  });
}

// Ensure maps initialize both on DOM ready and when Elementor frontend is initialized
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initAllMaps);
} else {
  initAllMaps();
}

// Handle Elementor frontend initialization
if (window.elementorFrontend) {
  elementorFrontend.hooks.addAction('frontend/element_ready/google_maps_widget.default', function($scope) {
    const mapElement = $scope[0].querySelector('.gme-map');
    if (mapElement) {
      initMap(mapElement);
    }
  });
}

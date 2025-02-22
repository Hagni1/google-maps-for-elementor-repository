const maps = new Map();

async function geocodeAddress(address, apiKey) {
  try {
    // Try Google Maps Geocoding first (for frontend)
    if (window.google && google.maps && google.maps.Geocoder) {
      const geocoder = new google.maps.Geocoder();
      const response = await geocoder.geocode({ address });
      if (response.results && response.results[0]) {
        const { location } = response.results[0].geometry;
        return { lat: location.lat(), lng: location.lng() };
      }
    }

    // Fallback to Nominatim API (for Elementor editor)
    const response = await axios.get(
      "https://nominatim.openstreetmap.org/search",
      {
        params: {
          q: address,
          format: "json",
          limit: 1,
        },
        headers: {
          "User-Agent": "GoogleMapsElementor/1.0",
        },
      }
    );

    if (response.data && response.data[0]) {
      return {
        lat: parseFloat(response.data[0].lat),
        lng: parseFloat(response.data[0].lon),
      };
    }

    throw new Error("Location not found");
  } catch (error) {
    console.error("Geocoding error:", error);
    return null;
  }
}

async function initMap(mapElement) {
  try {
    const placeholder = mapElement.querySelector(".gme-map-placeholder");
    const mapSettings = JSON.parse(mapElement.dataset.mapSettings);

    // Process locations that have addresses but no coordinates
    let addressLocationsCount = 0;
    let totalLat = 0;
    let totalLng = 0;

    if (mapSettings.locations) {
      for (const location of mapSettings.locations) {
        if (location.position_type === "address" && location.address) {
          const coords = await geocodeAddress(
            location.address,
            window.gmeApiKey
          );
          if (coords) {
            location.lat = coords.lat;
            location.lng = coords.lng;
            totalLat += coords.lat;
            totalLng += coords.lng;
            addressLocationsCount++;
          }
        } else if (
          location.position_type === "coordinates" &&
          location.lat &&
          location.lng
        ) {
          totalLat += parseFloat(location.lat);
          totalLng += parseFloat(location.lng);
          addressLocationsCount++;
        }
      }

      // Recalculate center if we have any valid locations
      if (addressLocationsCount > 0) {
        mapSettings.center = {
          lat: totalLat / addressLocationsCount,
          lng: totalLng / addressLocationsCount,
        };
      }
    }

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
        if (location.lat && location.lng) {
          new AdvancedMarkerElement({
            map: map,
            position: { lat: location.lat, lng: location.lng },
            title: location.name,
          });
        }
      });
    }

    maps.set(mapSettings.mapId, map);

    // Remove placeholder after map is loaded
    if (placeholder) {
      placeholder.style.display = "none";
    }

    return map;
  } catch (error) {
    console.error("Error initializing map:", error);
    const notice = mapElement.querySelector(".gme-map-notice");
    if (notice) {
      notice.textContent = "Error loading map. Please try again later.";
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
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/google_maps_widget.default",
    function ($scope) {
      const mapElement = $scope[0].querySelector(".gme-map");
      if (mapElement) {
        initMap(mapElement);
      }
    }
  );
}

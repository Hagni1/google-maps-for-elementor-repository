document.addEventListener("DOMContentLoaded", function () {
  if (typeof google !== "undefined") {
    // Initialize Google Map if element with id 'gme-map' exists
    const mapElement = document.getElementById("gme-map");
    if (mapElement) {
      // Retrieve center, zoom, and locations from dataset
      const center_lat = parseFloat(mapElement.dataset.centerLat) || -34.397;
      const center_lng = parseFloat(mapElement.dataset.centerLng) || 150.644;
      const zoom = parseInt(mapElement.dataset.zoom) || 8;

      const map = new google.maps.Map(mapElement, {
        zoom: zoom,
        center: { lat: center_lat, lng: center_lng },
      });

      // Add markers if locations data is provided
      if (mapElement.dataset.locations) {
        try {
          const locations = JSON.parse(mapElement.dataset.locations);
          locations.forEach(function (location) {
            if (location.lat && location.lng) {
              new google.maps.Marker({
                position: {
                  lat: parseFloat(location.lat),
                  lng: parseFloat(location.lng),
                },
                map: map,
                title: location.location_name || "",
              });
            }
          });
        } catch (error) {
          console.error("Invalid locations data:", error);
        }
      }
    } else {
      console.log("No map element found.");
    }
  } else {
    console.error("Google Maps API is not loaded.");
  }
});

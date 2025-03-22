/**
 * Google Maps API Key Handler
 *
 * Handles the Google Maps API key and related functionality
 */

(function () {
  // Store API key in a variable that can be accessed by other scripts
  if (typeof window.agmfeApiKey !== "undefined") {
    // Make API key accessible to other scripts
    window.googleMapsApiKey = window.agmfeApiKey;
  }
})();

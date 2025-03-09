/**
 * Advanced Google Maps For Elementor Admin JS
 * Handles API key verification
 */
jQuery(document).ready(function ($) {
  const $verifyButton = $("#gme_verify_api_key");
  const $apiKeyField = $("#gme_api_key");
  const $resultsDiv = $("#gme_api_validation_results");

  $verifyButton.on("click", function (e) {
    e.preventDefault();
    const apiKey = $apiKeyField.val().trim();

    if (!apiKey) {
      $resultsDiv.html(
        '<div class="notice notice-error inline"><p>' +
          "Please enter an API key to verify." +
          "</p></div>"
      );
      return;
    }

    // Show verifying message
    $resultsDiv.html(
      '<div class="notice notice-info inline"><p>' +
        gmeAdmin.verifying +
        "</p></div>"
    );
    $verifyButton.prop("disabled", true);

    // Make AJAX request to verify API key
    $.ajax({
      url: gmeAdmin.ajaxurl,
      type: "POST",
      data: {
        action: "gme_verify_api_key",
        api_key: apiKey,
        nonce: gmeAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          const result = response.data;
          $resultsDiv.html(
            '<div class="notice notice-success inline"><p>' +
              gmeAdmin.success +
              "</p>" +
              '<ul class="gme-verification-results">' +
              "<li>✅ " +
              result.maps_api.message +
              "</li>" +
              "<li>✅ " +
              result.geocoding_api.message +
              "</li>" +
              "</ul></div>"
          );
        } else {
          const result = response.data;
          let errorHtml =
            '<div class="notice notice-error inline"><p>API validation failed:</p><ul class="gme-verification-results">';

          if (result && result.maps_api) {
            let statusIcon = result.maps_api.status === "success" ? "✅" : "❌";
            errorHtml +=
              "<li>" +
              statusIcon +
              " " +
              gmeAdmin.mapsApiError +
              " " +
              result.maps_api.message +
              "</li>";
          }

          if (result && result.geocoding_api) {
            let statusIcon =
              result.geocoding_api.status === "success" ? "✅" : "❌";
            errorHtml +=
              "<li>" +
              statusIcon +
              " " +
              gmeAdmin.geocodingApiError +
              " " +
              result.geocoding_api.message +
              "</li>";
          }

          errorHtml += "</ul></div>";
          $resultsDiv.html(errorHtml);
        }
      },
      error: function () {
        $resultsDiv.html(
          '<div class="notice notice-error inline"><p>Error during API verification. Please try again.</p></div>'
        );
      },
      complete: function () {
        $verifyButton.prop("disabled", false);
      },
    });
  });
});

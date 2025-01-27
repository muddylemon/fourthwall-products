/**
 * Fourthwall Products Admin Settings
 */
(function ($) {
  "use strict";

  const FourthwallAdmin = {
    /**
     * Initialize admin functionality
     */
    init: function () {
      this.bindEvents();
      this.initTooltips();
    },

    /**
     * Bind event handlers
     */
    bindEvents: function () {
      $("#fourthwall-test-connection").on("click", this.testConnection);
      $("#fourthwall-clear-cache").on("click", this.clearCache);
      $("#fourthwall_storefront_token").on("change", this.handleTokenChange);
    },

    /**
     * Initialize tooltips
     */
    initTooltips: function () {
      $(".fourthwall-tooltip").tooltip({
        position: { my: "left+10 center", at: "right center" },
      });
    },

    /**
     * Test API connection
     */
    testConnection: function (e) {
      e.preventDefault();

      const $button = $(this);
      const $spinner = $button.next(".spinner");
      const $result = $button.siblings(".fourthwall-test-result");

      // Disable button and show spinner
      $button.prop("disabled", true);
      $spinner.addClass("is-active");
      $result.html("");

      // Make AJAX request
      $.ajax({
        url: fourthwallAdmin.ajax_url,
        type: "POST",
        data: {
          action: "fourthwall_test_connection",
          nonce: fourthwallAdmin.nonce,
        },
        success: function (response) {
          if (response.success) {
            $result.html(
              '<span class="fourthwall-success">' +
                fourthwallAdmin.strings.testSuccess +
                "</span>"
            );
          } else {
            $result.html(
              '<span class="fourthwall-error">' +
                fourthwallAdmin.strings.testError +
                " " +
                response.data +
                "</span>"
            );
          }
        },
        error: function (xhr, status, error) {
          $result.html(
            '<span class="fourthwall-error">' +
              fourthwallAdmin.strings.testError +
              " " +
              error +
              "</span>"
          );
        },
        complete: function () {
          // Re-enable button and hide spinner
          $button.prop("disabled", false);
          $spinner.removeClass("is-active");

          // Hide result after 5 seconds
          setTimeout(function () {
            $result.fadeOut("slow", function () {
              $(this).html("").show();
            });
          }, 5000);
        },
      });
    },

    /**
     * Clear cache
     */
    clearCache: function (e) {
      e.preventDefault();

      const $button = $(this);
      const $spinner = $button.next(".spinner");
      const $result = $button.siblings(".fourthwall-cache-result");

      // Confirm action
      if (!confirm("Are you sure you want to clear the cache?")) {
        return;
      }

      // Disable button and show spinner
      $button.prop("disabled", true);
      $spinner.addClass("is-active");
      $result.html("");

      // Make AJAX request
      $.ajax({
        url: fourthwallAdmin.ajax_url,
        type: "POST",
        data: {
          action: "fourthwall_clear_cache",
          nonce: fourthwallAdmin.nonce,
        },
        success: function (response) {
          if (response.success) {
            $result.html(
              '<span class="fourthwall-success">' + response.data + "</span>"
            );
          } else {
            $result.html(
              '<span class="fourthwall-error">' + response.data + "</span>"
            );
          }
        },
        error: function (xhr, status, error) {
          $result.html(
            '<span class="fourthwall-error">' + "Error: " + error + "</span>"
          );
        },
        complete: function () {
          // Re-enable button and hide spinner
          $button.prop("disabled", false);
          $spinner.removeClass("is-active");

          // Hide result after 5 seconds
          setTimeout(function () {
            $result.fadeOut("slow", function () {
              $(this).html("").show();
            });
          }, 5000);
        },
      });
    },

    /**
     * Handle token input change
     */
    handleTokenChange: function () {
      const $input = $(this);
      const token = $input.val().trim();

      // Basic token format validation
      if (token && !token.match(/^ptkn_[a-zA-Z0-9]+$/)) {
        $input.addClass("fourthwall-invalid");
        $input.next(".fourthwall-token-error").remove(); // Remove any existing error
        $input.after(
          '<span class="fourthwall-token-error">' +
            'Invalid token format. Token should start with "ptkn_"' +
            "</span>"
        );
      } else {
        $input.removeClass("fourthwall-invalid");
        $input.next(".fourthwall-token-error").remove();
      }
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    FourthwallAdmin.init();
  });
})(jQuery);

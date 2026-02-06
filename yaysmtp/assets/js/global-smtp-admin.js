(function($) {
  $(document).ready(function() {
    $("body").on(
      "click",
      ".yaysmtp-import-settings-notice .close-btn",
      function() {
        $(".yaysmtp-import-settings-notice").remove();
        $.ajax({
          url: yaySmtpWpGlobalData.YAY_ADMIN_AJAX,
          type: "POST",
          data: {
            action: "yaysmtp_close_popup_import_smtp_settings",
            nonce: yaySmtpWpGlobalData.ajaxNonce
          },
          success: function(result) {}
        });
      }
    );
  });
})(window.jQuery);


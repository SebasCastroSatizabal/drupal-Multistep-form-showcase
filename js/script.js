(function ($, Drupal) {
  //Variable to store the selected dial code by the user.
  let PhoneCountry = "us";

  /**
   * Callback function to update the progress bar classes.
   * This function is called from the backend using InvokeCommand
   **/
  $.fn.updateProgressBar = function (page) {
    const current_page_item = $(".progressbar li").eq(page - 1);
    current_page_item.addClass("active");
    current_page_item.prevAll().addClass("active");
    current_page_item.nextAll().removeClass("active");
  };

  //Init Phone input with Intl-tel-input plugin
  Drupal.behaviors.initPhoneInput = {
    attach: function (context, settings) {
      $("#form-phone", context)
        .once("initPhoneInput")
        .each(function () {
          const phoneInput = window.intlTelInput(this, {
            utilsScript:
              "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            initialCountry: PhoneCountry,
          });

          //Set the phone number in the hidden input on submit
          $(this)
            .closest("form")
            .find("input[type=submit]")
            .on("mousedown", function (e) {
              //Prevent default to stop the form submit
              e.preventDefault();
              const hiddenInput = $("#form-full-phone");

              PhoneCountry = phoneInput.getSelectedCountryData().iso2;
              if (phoneInput.isValidNumber()) {
                hiddenInput.val(phoneInput.getNumber());
              } else {
                hiddenInput.val("");
              }

              //Re submit
              $(this).click();
            });
        });
    },
  };
})(jQuery, Drupal);

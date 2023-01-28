(function ($, Drupal) {
  $.fn.updateProgressBar = function (page) {
    const current_page_item = $(".progressbar li").eq(page - 1);
    current_page_item.addClass("active");
    current_page_item.prevAll().addClass("active");
    current_page_item.nextAll().removeClass("active");
  };
})(jQuery, Drupal);

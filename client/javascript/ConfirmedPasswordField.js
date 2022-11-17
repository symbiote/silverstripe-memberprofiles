/* extracted from silverstripe/framework */

(function ($) {
  $(document).on('click', '.confirmedpassword .showOnClick a', function () {
    var $container = $('.showOnClickContainer', $(this).parent());

    $container.toggle('fast', function() {
      $container.toggleClass("d-none").find('input[type="hidden"]').val($container.hasClass("d-none")?0:1);
    });

    return false;
  });
})(jQuery);

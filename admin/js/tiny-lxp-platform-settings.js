(function ($) {
  $(document).ready(function () {
    window.tiny_lxp_platform_changed = false;
    window.onbeforeunload = function (e) {
      if (window.tiny_lxp_platform_changed) {
        return '';
      } else {
        return;
      }
    };
    $(document).on('change', 'input[type="text"], input[type="checkbox"]', function () {
      window.tiny_lxp_platform_changed = true;
    });
    $(document).on('submit', 'form', function () {
      window.tiny_lxp_platform_changed = false;
    });

  });
})(jQuery);

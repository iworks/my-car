/* global jQuery, document */
jQuery(document).ready(function ($) {
  if ("undefined" !== typeof $.fn.select2) {
    $("select.iworks-select2").select2();
  }
});

/**
 * @file
 * JS for Sticky Menu.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.dczThemeStickyMenu = {
    processed: false,

    attach: function (context, settings) {
      // Process only once.
      if (this.processed === true) {
        return;
      }
      this.processed = true;

      // Get elements.
      var navbar = $('#block-mainnavigation');
      var page = $('main');

      // Initialize variables.
      var navbarFixedHeight = 0;
      var navbarHeight = 0;
      var navbarOffset = 0;

      // Define calculation function.
      var stickyMenu = function () {

        // Skip for small screens.
        if ($(window).width() <= 768) {
          return;
        }
        // Initialize empty padding.
        var pagePadding = '';
        // Get current fixed state.
        var navbarIsFixed = navbar.hasClass('fixed');

        // Get current scroll position.
        var top = $(window).scrollTop();

        // Count current navbar values.
        if (navbar.length) {
          // Update navbar height.
          navbarHeight = navbar.outerHeight(true);
          // Update navbar offset when not fixed.
          if (!navbarFixedHeight) {
            navbarOffset = navbar.offset().top;
          }
          // Toggle fixed header.
          if (top >= navbarOffset) {
            // Enable header fixed and update height.
            if (!navbarIsFixed) {
              navbar.addClass('fixed');
            }
            navbarFixedHeight = navbar.outerHeight(true);
            // Set page top padding.
            pagePadding = navbarHeight;
          }
          else {
            // Disable fixed header.
            if (navbarIsFixed) {
              navbar.removeClass('fixed');
            }
            navbarFixedHeight = 0;
          }
        }

        // Add page top padding when needed.
        page.css('padding-top', pagePadding);
      };

      // Process calculation on load, resize and scroll.
      $(document).ready(stickyMenu);
      $(window).on('resize scroll', stickyMenu);

    }
  };

})(jQuery, Drupal);

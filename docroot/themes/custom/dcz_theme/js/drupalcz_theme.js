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

/**
 * @file
 * JS for mobile Menu Switch.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.dczMenuSwitch = {
    attach: function (context, settings) {

      $('.menu-switch').click(function(){
        $(this).toggleClass('open');
        $('.main-navigation').toggleClass('open');
      });

    }
  };

})(jQuery);

/**
 * @file
 * JS for Full Height.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.tabs = {
    attach: function (context, settings) {

      $('.paragraph--type--dcz-para-tabs').each(function(){
        var tab = $(this);
        var tabpane = $('.tab-pane', tab);
        var tablink = $('.tablink', tab);

        // When any link is clicked.
        tablink.click(function(event){
          // Remove active class from all links.
          tablink.removeClass('active');
          //Set clicked link class to active.
          $(this).addClass('active');
          // Set variable currentTab to value of data-tab-id attribute of clicked link.
          var currentTab = $(this).attr('data-tab-id');
          // Hide all tab panes in current tab group.
          tabpane.removeClass('active');
          // Show tab pane with id equal to variable currentTab.
          $('#' + currentTab).addClass('active');
          // Prevent link action.
          event.preventDefault();
        });
      })

    }
  };

})(jQuery, Drupal);

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

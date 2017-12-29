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

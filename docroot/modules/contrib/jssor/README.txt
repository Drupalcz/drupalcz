## Installation

First you create new folder either in the sites/all/modules folder or just
directly in the modules folder at the drupal root.
The good news is that you can move the folder event after it's already enable.
No more need to rebuild the registry. You can thanks the clever autoloading
capability of Drupal 8.

## Requirements

Download the jssor slider, rename the folder as 'jssor-slider' and place it
under your libraries folder. So you structure should look like this:
libraries/jssor-slider/js/jssor.slider.min.js

http://www.jssor.com/download-jssor-slider-from-github.html

## Why jssor slider

Light Weight: minimum 17KB code snippet
High Performance: smooth animation with low cpu usage
Flexible: tons of options and api interfaces
Design: easy to adapt skins and look and feel

## Plugin Features

- Simple, easy to use interface – perfect for individual users.
- Create Responsive slideshows in seconds
- Unrestricted support for Image slides (supports caption, link, description text)
- Full width slideshow support
- Fast – only the minimum JavaScript/CSS is included on your page
- Touch + drag navigation
- 390+ caption effects/transitions
- 360+ slideshow effects/transitions: http://www.jssor.com/development/tool-slideshow-transition-viewer.html
- 18+ arrow skins
- 16+ bullet skins
- Auto detect vertical/horizontal drag
- Multiple sliders in one page
- Cross browser support

## Browser Compatibility

- IE6+
- Chrome 3+
- Firefox 2+
- Safari 3.1+
- Opera 10+
- Mobile browsers( iOS, Android, Windows, Windows Surface and Mac are all supported)

USAGE
-----
## Enable the jssor_example module.

This will give you a new content type and a few pre-configured views.

## How to create a new view slider.

1) Create a view
2) View Settings - Show Content of type [whatever type you want].
3) Create the view and click the 'block' option
4) In the Block Settings area, Display Format = JSSOR Slider of type Fields, don't use a pager
5) Save & Edit
6) Add fields such as title, image and maybe a new field on that content-type called 'teaser-phrase'
7) Order the fields so the image is last and suppress display of the title and teaser phrase **
8) Set the JSSOR Settings
9) save it

ROADMAP
-------
1) Add drush support to download library.
2) Add more options
3) Add more theming
4) Add field formatter
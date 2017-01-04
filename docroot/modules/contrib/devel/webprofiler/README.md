!! README.md is a work in progress !!

#Dependencies:

- d3.js: Webprofiler module requires D3 library 3.x (not 4.x) to render data.
  Download https://github.com/d3/d3 into /libraries/d3/d3.min.js
  
- highlight.js: Webprofiler module requires highlight 9.7.x library to syntax highlight collected queries.
  Download http://highlightjs.org into /libraries/highlight
  
If you use composer to manage dependencies and composer/installers plugin you could add those lines to composer.json:

```
"d3/d3": "3.5.*",
"components/highlightjs": "9.7.*"
```

to require section.

```
"libraries/{$name}": ["type:drupal-library"],
```

to installer-paths section.

```
{
      "type": "package",
      "package": {
        "name": "d3/d3",
        "version": "v3.5.17",
        "type": "drupal-library",
        "source": {
          "url": "https://github.com/d3/d3",
          "type": "git",
          "reference": "v3.5.17"
        }
      }
    },
    {
      "type": "package",
      "package": {
        "name": "components/highlightjs",
        "version": "9.7.0",
        "type": "drupal-library",
        "source": {
          "url": "https://github.com/components/highlightjs",
          "type": "git",
          "reference": "9.7.0"
        }
      }
    }
```

to repositories section.

#IDE link:

Every class name discovered while profiling (controller class, event class) are linked to an url for directly open in
an IDE, you can configure the url of those link based on the IDE you are using:

- Sublime text (2 and 3): see https://github.com/dhoulb/subl for Mac OS X
- Textmate: should be supported by default, use txmt://open?url=file://@file&line=@line as link
- PhpStorm 8+: use phpstorm://open?file=@file&line=@line as link

#Timeline:

Now it is possible to also collect the time needed to instantiate every single service used in a request, to make it 
work you need to add these two lines to settings.php (or, event better, to settings.local.php):

```
$class_loader->addPsr4('Drupal\\webprofiler\\', [ __DIR__ . '/../../modules/contrib/devel/webprofiler/src']);
$settings['container_base_class'] = '\Drupal\webprofiler\DependencyInjection\TraceableContainer';
```

Check if the path from the Webprofiler module in your settings.php file matches the location of the installed Webprofiler module in your project.

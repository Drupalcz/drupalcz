# Migrate Manifest

This project provides tools for running Drupal 8 migrations using the manifest format. 

## Usage

This command allows you to specify a list of migrations and their config in
a YAML file. An example of a simple migration may look like this:

````
- d6_action_settings
- d6_aggregator_feed
````

You can also provide configuration to a migration for both source and the
destination. An example as such:

````
- d6_file:
  source:
    conf_path: sites/assets
  destination:
    source_base_path: destination/base/path
    destination_path_property: uri
- d6_action_settings
````

# Author

- Author:: Mike Ryan
- Author:: James Gilliland (<jgilliland@apqc.org>)

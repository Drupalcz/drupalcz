# Drupal.cz community website

## Build status

Branch | Build status | Dev site | HTTP Basic auth
------------ | ------------- | ------------- | -------------
[Master](https://github.com/Drupalcz/drupalcz/tree/master) | [![Build Status](https://travis-ci.org/Drupalcz/drupalcz.svg?branch=master)](https://travis-ci.org/Drupalcz/drupalcz) | http://stage.drupal.cz | `drupal` / `cz`
[Develop](https://github.com/Drupalcz/drupalcz/tree/develop) | [![Build Status](https://travis-ci.org/Drupalcz/drupalcz.svg?branch=develop)](https://travis-ci.org/Drupalcz/drupalcz) | http://dev.drupal.cz | `drupal` / `cz`

## Contents
* blt/ - Settings for Acquia BLT.
* config/ - Exported Drupal configutation.
* console/ - Cache folder for Drupal Console. ()
* docroot/ - Website root directory.
* drush/ - Drush aliases and other rutiens.
* hooks/ - Acquia cloud hooks. (See https://docs.acquia.com/cloud/manage/cloud-hooks )
* patches/ - Our custom patches. (But we prefer linking drupal.org.)
* reports/ - PHP Unit results.
* tests/ - Collection of tests for Travis CI and local development.
* .gitignore - Gitignore - be sure to check your own local gitignore so you don't commit your IDE's tmp files.
* .travis.yml - Travis CI test suite configuration.

## Requirements
* You need PHP ^7.1
* Install composer: https://getcomposer.org/doc/00-intro.md
* Install Drush version 8: http://docs.drush.org/en/master/install/
* We are using Acquia BLT which has it's own set of requirements.
  * Check out https://github.com/acquia/blt
  * Not all are needed for basic tasks.
  * Try `blt vm` if you don't want to alter your system.
  * See more at https://github.com/acquia/blt

## Getting the site up and running.
* Get your copy of the code:
  * Fork this repository. ( https://help.github.com/articles/fork-a-repo/ )
  * Clone your repository.
  * `git clone git@github.com:[YOUR-NAME]/drupalcz.git drupalcz`
  * `cd drupalcz`
* Prepare your database and fill the credentials into your new local config.
  * `cp docroot/sites/default/settings/default.local.settings.php docroot/sites/default/settings/local.settings.php`
  * edit this config: `docroot/sites/default/settings/local.settings.php`
* Install the site (it will use the Drupal.cz distribution).
  * `composer install`
  * If this is first time you are setting up BLT, run `composer run-script blt-alias`
  * `blt setup:git-hooks` (Learn more about BLT: https://blt.readthedocs.io/)
  * `cd docroot`
  * `drush si dcz`
  * Import configuration:
  * `drush cim -y`
  * Login to new site:
  * `drush uli`
  
If you want to have some default content for your development, 

  * add this row to local.settings.php 
     
      ` $config['content_directory'] = '../content';`
  
  * Run commands
       
        drush cr
        drush dcdi
        drush cr
        
  ### Poznámka pro české vývojáře:
  Na stránce Články (http://dcz.localhost/clanky) se nezobrazují články, 
  protože nefunguje korektně import views reference fieldu (bug v modulu a v Drupal core,
  co čeká na vyřešení). Ručně je třeba na stránce http://dcz.localhost/node/46/edit 
  jít do paragraphs Layout, kliknout na Edit, kliknout Edit u paragrafu typu 
  View reference a do položky View reference vepsat slovo "Articles".
  
  To samé na stránce Komunity (http://dcz.localhost/node/171/edit) opravit 
  views referenci "User directory".
  
  Než se vyřeší problém v Travis, je třeba nastavit homepage.
  Na stránce http://dcz.localhost/admin/config/system/site-information nastavte Front page 
  na hodnotu /drupal-cz.

  
* Optional: Migrate data from D6 Drupal.cz
  * Get the database snapshot: https://github.com/Drupalcz/drupalcz_db
  * Import it into new database separarate from D8 version.
  * See docroot/sites/default/default.settings.local.php for info how to connect second DB.
  * Enable module with migration definitions:
  * `drush en dcz_migrate -y`
  * See which migrations are available:
  * `drush migrate-status`
  * Run the migration:
  * `drush migrate-import --group=dcz6 -vvv`
  * Check results:
  * `drush migrate-status`

## Contributing
* We are using GitFlow(https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow/) branching strategy
* Learn how to manage config: https://www.youtube.com/watch?v=WsMuQFO8yGU
* You need to create ```feature/NAME``` branch for each issue
* Commit your changes. ( http://chris.beams.io/posts/git-commit/ )
* Test your work by running:
  * `blt validate`
  * `blt tests`
* After you finish work on issue, create pull request against ```develop``` branch.
* Create pull request. https://help.github.com/articles/creating-a-pull-request/

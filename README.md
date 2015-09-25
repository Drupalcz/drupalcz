# Drupal.cz community website

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Drupalcz/drupalcz?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge) [![Build Status](https://travis-ci.org/Drupalcz/drupalcz.svg?branch=master)](https://travis-ci.org/Drupalcz/drupalcz)

## Requirements
* Install composer: https://getcomposer.org/doc/00-intro.md
* Install Drush version 8: http://docs.drush.org/en/master/install/

## Getting the site up and running.
* Get your copy of the code:
  * Fork this repository. ( https://help.github.com/articles/fork-a-repo/ )
  * Clone your repository.
  * `git clone git@github.com:[YOUR-NAME]/drupalcz.git drupalcz`
  * `cd drupalcz`
* Prepare your database and fill the credentials into your new local config.
  * `cp docroot/settings/default.settings.local.php docroot/settings/settings.local.php`
  * edit this config: `docroot/settings/settings.local.php`
* Install the site using the Drupal.cz installation profile.
  * `drush si dcz`

## Contributing
* We are using GitFlow(https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow/) branching strategy
  * you need to create ```feature/NAME``` branch for each issue
  * after you finish work on issue, create pull request against ```develop``` branch 
* Commit your changes. ( http://chris.beams.io/posts/git-commit/ )
* Create pull request. https://help.github.com/articles/creating-a-pull-request/

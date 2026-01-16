# DDEV Migration Summary

## Files Created

1. **`.ddev/config.yaml`** - Main DDEV configuration file
   - Configures Drupal 9, PHP 7.4, Apache, MariaDB 10.4
   - Disables DDEV's automatic settings management (we use custom structure)
   - Includes post-start hook to create local settings file

2. **`docroot/sites/default/settings/ddev.settings.php`** - DDEV-specific settings
   - Database connection configuration (db/db/db on host 'db')
   - Development-friendly settings (caching disabled, update access enabled)
   - Config split configuration for development
   - Private files path and other Drupal settings

3. **`DDEV_MIGRATION.md`** - Migration guide
   - Step-by-step instructions for migrating from Lando to DDEV
   - Command reference table
   - Database and files migration instructions
   - Troubleshooting tips

## Files Modified

1. **`docroot/sites/default/settings/includes.settings.php`**
   - Added DDEV environment detection using `IS_DDEV_PROJECT` environment variable
   - Loads `settings/ddev.settings.php` when running in DDEV

2. **`.gitignore`**
   - Added DDEV-specific ignore patterns

3. **`README.md`**
   - Added DDEV setup instructions
   - Added reference to migration guide

## How It Works

### Settings Loading Chain:
1. Drupal loads `docroot/sites/default/settings.php`
2. Which includes `docroot/sites/default/settings/includes.settings.php`
3. `includes.settings.php` checks for environment:
   - If `IS_DDEV_PROJECT=true` → loads `settings/ddev.settings.php`
   - If `LANDO_APP_NAME` exists → loads `settings/lando.settings.php`
   - If `AH_SITE_ENVIRONMENT` exists → loads Acquia Cloud settings
4. Finally loads `settings/local.settings.php` for any local overrides

### Database Configuration:
- **Host**: `db` (DDEV's database container)
- **Database**: `db`
- **Username**: `db`
- **Password**: `db`
- **Port**: `3306`

This setup ensures that:
- Both Lando and DDEV can work simultaneously (different environment detection)
- No code changes needed when switching between environments
- Settings are organized and maintainable
- DDEV doesn't interfere with the custom settings structure

## Quick Start

```bash
# Start DDEV
ddev start

# Check status
ddev drush status

# Get login link
ddev drush uli

# Access site at:
# http://drupalcz.ddev.site
```

## Note on settings.ddev.php

DDEV normally auto-generates a `settings.ddev.php` file in `docroot/sites/default/`.
This project uses a custom settings structure, so we:
- Set `disable_settings_management: true` in `.ddev/config.yaml`
- Use `docroot/sites/default/settings/ddev.settings.php` instead
- This allows better organization and multi-environment support


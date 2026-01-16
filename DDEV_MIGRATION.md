# Migrating from Lando to DDEV

This document explains how to migrate from Lando to DDEV for local development.

## Prerequisites

1. Install DDEV: https://ddev.readthedocs.io/en/stable/#installation
2. Stop and remove your Lando environment (optional, but recommended to free resources):
   ```
   lando stop
   lando destroy
   ```

## Migration Steps

### 1. Start DDEV

```bash
ddev start
```

This will:
- Download required Docker images
- Create containers for web server, database, and other services
- Run the `scripts/create-settings.sh` hook to create local settings file

### 2. Install Drupal

If you're setting up from scratch:

```bash
ddev drush si minimal --existing-config
```

### 3. Import Content (Optional)

If you want default content for development:

```bash
ddev drush dcdi --force update
ddev drush cr
```

### 4. Get Login Link

```bash
ddev drush uli
```

### 5. Access Your Site

Your site will be available at:
- http://drupalcz.ddev.site
- https://drupalcz.ddev.site (if mkcert is installed)

## Useful DDEV Commands

| Lando Command | DDEV Equivalent |
|---------------|-----------------|
| `lando start` | `ddev start` |
| `lando stop` | `ddev stop` |
| `lando poweroff` | `ddev poweroff` |
| `lando drush <command>` | `ddev drush <command>` |
| `lando composer <command>` | `ddev composer <command>` |
| `lando ssh` | `ddev ssh` |
| `lando db-import <file>` | `ddev import-db --src=<file>` |
| `lando db-export` | `ddev export-db` |
| `lando logs` | `ddev logs` |

## Database Migration

If you have an existing database in Lando that you want to migrate:

1. Export from Lando:
   ```bash
   lando db-export database.sql.gz
   ```

2. Stop Lando and start DDEV:
   ```bash
   lando stop
   ddev start
   ```

3. Import into DDEV:
   ```bash
   ddev import-db --src=database.sql.gz
   ddev drush cr
   ```

## Files Migration

If you have user-uploaded files:

1. Your files in `docroot/sites/default/files/` will remain in place
2. Private files are now expected at `/var/www/html/private` inside the container

## Frontend Development

The original Lando setup included Node.js for theme development. For DDEV, you have two options:

### Option 1: Use DDEV's Node service (Recommended)

Add to `.ddev/config.yaml`:
```yaml
web_extra_daemons:
  - name: "node"
    command: "/var/www/html/docroot/themes/custom/dcz_theme && npm install && npm run watch"
    directory: /var/www/html/docroot/themes/custom/dcz_theme
```

### Option 2: Use Node.js on your host machine

Install Node.js locally and run:
```bash
cd docroot/themes/custom/dcz_theme
npm install
npm run watch
```

## Differences from Lando

- **Database host**: Changed from `database` to `db`
- **Database credentials**: All are `db` (name, username, password)
- **Project URL**: Changed from `*.lndo.site` to `*.ddev.site`
- **Mailhog**: Available at http://drupalcz.ddev.site:8025
- **PHP version**: 7.4 (same as Lando)
- **Web server**: Apache (same as Lando)

## Custom Settings Structure

This project uses a custom settings file structure instead of DDEV's default `settings.ddev.php`:

1. **Main settings**: `docroot/sites/default/settings.php` includes `docroot/sites/default/settings/includes.settings.php`
2. **DDEV detection**: `includes.settings.php` detects DDEV via the `IS_DDEV_PROJECT` environment variable
3. **DDEV settings**: When DDEV is detected, it loads `docroot/sites/default/settings/ddev.settings.php`
4. **Local overrides**: Finally, it loads `docroot/sites/default/settings/local.settings.php` if it exists

This structure allows the same codebase to work with Lando, DDEV, Acquia Cloud, and other environments without modification.

## Troubleshooting

### Clear cache if you have issues
```bash
ddev drush cr
```

### Restart DDEV
```bash
ddev restart
```

### Check DDEV status
```bash
ddev describe
```

### View logs
```bash
ddev logs
```

## Additional Resources

- DDEV Documentation: https://ddev.readthedocs.io/
- DDEV Drupal Quickstart: https://ddev.readthedocs.io/en/stable/users/quickstart/#drupal


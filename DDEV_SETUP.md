# DDEV Setup

## Quick Start

```bash
# Start DDEV
ddev start

# Install dependencies
ddev composer install

# Install Drupal (minimal profile)
ddev drush site:install minimal --existing-config --yes

# Or import existing database
ddev import-db --file=path/to/database.sql.gz

# Get login link
ddev drush uli

# Access site
# http://drupalcz.ddev.site
```

## Database Connection
- Host: `db`
- Database: `db`
- User: `db`
- Password: `db`


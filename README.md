# Drupal.cz

Komunitní web [Drupal.cz](https://www.drupal.cz). Projekt vyžaduje Drupal 10.6, PHP 8.2 a Drush 13. Lokální vývoj probíhá v DDEV.

Repozitář: https://github.com/Drupalcz/drupalcz

## Prostředí

| Větev | Web | HTTP basic auth |
| --- | --- | --- |
| [master](https://github.com/Drupalcz/drupalcz/tree/master) | http://stage.drupal.cz | `drupal` / `cz` |
| [develop](https://github.com/Drupalcz/drupalcz/tree/develop) | http://dev.drupal.cz | `drupal` / `cz` |

Produkce je na https://www.drupal.cz.

## Lokální vývoj (DDEV)

Nainstalujte [DDEV](https://ddev.readthedocs.io/en/stable/). V `.ddev/config.yaml` je PHP 8.2, MariaDB 10.11 a Node.js 22. Verzi PHP nezvyšujte, dokud se nezvýší i na hostingu.

### Nová instalace

```bash
ddev start
ddev composer install
ddev drush site:install minimal --existing-config --yes
ddev drush uli
```

Web je na http://drupalcz.ddev.site.

Při `ddev start` se vytvoří `docroot/sites/default/settings/local.settings.php`, pokud ještě neexistuje. Instalace používá profil `minimal` a exportovanou konfiguraci z `config/`.

### Import existující databáze

```bash
ddev import-db --file=cesta/k/databazi.sql.gz
ddev drush uli
```

### Databáze

- Host: `db`
- Databáze: `db`
- Uživatel: `db`
- Heslo: `db`

## Frontend

Téma je `docroot/themes/custom/dcz_theme`. Závislosti stačí nainstalovat jednou:

```bash
ddev composer run init-theme
```

Kompilace:

```bash
ddev gulp css       # style.css
ddev gulp css-min   # style.min.css
ddev gulp           # kompilace, sledování změn a BrowserSync
```

BrowserSync běží na https://drupalcz.ddev.site:3000.

## Konfigurace a obsah

Exportovaná konfigurace je v `config/`. Patche se zadávají v `composer.json` (`extra.patches`). Soubory, které nejdou odkázat na drupal.org, patří do `patches/`.

Výchozí obsah je v `content/`. Import:

```bash
ddev drush dcdi
```

## Adresáře

- `config/` — exportovaná konfigurace
- `content/` — výchozí obsah
- `docroot/` — document root
- `drush/` — konfigurace Drushe
- `hooks/` — [Acquia Cloud hooks](https://docs.acquia.com/acquia-cloud-platform/develop-apps/drupal-apps/cloud-hooks)
- `patches/` — lokální patche
- `scripts/` — pomocné skripty
- `tests/` — testy
- `.ddev/` — konfigurace DDEV
- `.github/` — GitHub Actions

## Přispívání

Používáme větve `feature/NAZEV`. Pull request míří do `develop`.

# Orbis Persons

WordPress plugin for Orbis that adds persons, with personal details such as birth date and gender, built on top of [Orbis Contacts](https://github.com/pronamic/wp-orbis-contacts).

## Requirements

- PHP 8.3+
- WordPress 7.1+
- [Orbis Contacts](https://github.com/pronamic/wp-orbis-contacts)
- Composer
- Node.js / npm

## Installation

```sh
composer install
npm install
```

## Development

This plugin follows the [SolveBeam WordPress Plugin Boilerplate](https://github.com/solvebeam/solvebeam-wordpress-plugin-boilerplate) conventions.

### Local environment (wp-env)

```sh
npx wp-env start
npx wp-env stop
```

The `.wp-env.json` maps the plugin twice into the WordPress environment:

| Mount path | Source | Purpose |
|---|---|---|
| `wp-content/plugins/orbis-persons-dev` | `./` | Live development (including dev files) |
| `wp-content/plugins/orbis-persons` | `./build/orbis-persons/` | Built distribution version |

### Build

```sh
composer run build
```

### Deploy

```sh
vendor/bin/dep deploy
```

### Translations

```sh
composer run make-pot
```

### Linting & analysis

```sh
composer run phpcs
composer run phpstan
composer run rector
composer run qa
```

## Data model

| Type | Key |
|---|---|
| Post type | `orbis_person` (supports `orbis_contact`) |
| Taxonomies | `orbis_gender`, `orbis_person_category` |
| Post meta | `_orbis_title`, `_orbis_organization`, `_orbis_department`, `_orbis_email`, `_orbis_phone_number`, `_orbis_mobile_number`, `_orbis_address`, `_orbis_postcode`, `_orbis_city`, `_orbis_country`, `_orbis_birth_date_string`, `_orbis_birth_date`, `_orbis_birth_date_timestamp`, `_orbis_iban`, `_orbis_twitter`, `_orbis_facebook`, `_orbis_linkedin` |

## Orbis core

The `orbis_person` post type is still registered by [Orbis](https://github.com/pronamic/wp-orbis). This plugin registers it again on `init` priority 20 (with the `persons` slug, `orbis_contact` support and the Orbis Contacts menu) and replaces the Orbis core contact information meta box. The post type, taxonomy and meta keys are the same, so existing persons keep working without migration.

## License

GPL-2.0-or-later

# American Music Migration (upgrade7_module)

## Overview
This module migrates the **American Music Project (AMP)** content from a
Drupal 7 site into Drupal 10. It is built on Drupal core's Migrate API and
provides a complete set of migration definitions (`migrate_plus.migration.*`)
covering content types, media, and taxonomies specific to the American Music
Project's library/archive site.

### Content types
| Content type | Machine name |
|---|---|
| AMP Collection | `amp_collection` |
| Basic page | `page` |
| Article | `article` |
| Composers | `composers` |
| Exhibit Component | `exhibit_component` |
| Exhibits | `exhibits` |
| Performers | `performers` |
| Songs | `songs` |

### Taxonomies
| Vocabulary | Machine name |
|---|---|
| Genres | `genres` |
| Publishers | `publishers` |

## Requirements
- Drupal 10, 11
- Drupal core Migrate, Migrate Drupal
- Drupal contributed module for upgrading legacy Drupal 6 or 7 sites to newer version of Drupal
```bash
docker-compose exec -T drupal with-contenv bash -lc 'composer require drupal/migrate_upgrade:^4'
docker-compose exec -T drupal with-contenv bash -lc 'drush -y en migrate_upgrade'

docker-compose exec -T drupal with-contenv bash -lc 'composer require drupal/colorbox:^2.2'
docker-compose exec -T drupal with-contenv bash -lc 'drush en -y colorbox'
```
- PHP 8.1 or later (per Drupal 10 requirements)

## Installation and module enable
### 1. Install the module
Install via Composer:
```bash
composer require drupal/upgrade7_module
```

### 2. Enable the module
Via Drush:
```bash
drush en -y upgrade7_module
```
Via the Drupal admin UI:
1. Go to **Extend** (`/admin/modules`).
2. Locate the custom module **upgrade7_module**.
3. Check the box next to it and click **Install**.

### 3. Confirm the module is enabled
```bash
drush pml --type=module --status=enabled | grep upgrade7_module
```
You should see the module listed with an `Enabled` status.

## Inspect available migrations
```bash
drush migrate:status --group=migrate_drupal_7
```
## Run selective imports
```bash
drush migrate:import <migration_id>
```
### List of migrations needed to run in the order:
1. taxonomy
```bash
drush mim upgrade_d7_taxonomy_vocabulary -vvv 
drush mim upgrade_d7_taxonomy_term_publishers -vvv
drush mim upgrade_d7_taxonomy_term_genres -vvv
```
2. node type
```bash
drush mim upgrade_d7_node_type 
```
3. field
```bash
drush mim upgrade_d7_filter_format
drush mim upgrade_d7_image_styles
drush mim upgrade_d7_field 
drush mim upgrade_d7_field_instance
drush mim upgrade_d7_field_formatter_settings
drush mim upgrade_d7_field_widget_settings
```
4. file
```bash
drush mim upgrade_d7_file
```
5. contents
```bash
drush mim upgrade_d7_node_complete_amp_collection
drush mim upgrade_d7_node_complete_article
drush mim upgrade_d7_node_complete_page
drush mim upgrade_d7_node_complete_composers
drush mim upgrade_d7_node_complete_exhibit_component
drush mim upgrade_d7_node_complete_exhibits
drush mim upgrade_d7_node_complete_songs
```


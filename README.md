## Introduction
This module contains plugins to import data from a Fedora 3 Islandora instance
into an Islandora CLAW instance. It also contains a feature as a submodule
that contains some example migrations.  The example migrations are based on forms from vanilla Islandora 7.x solution
packs, and are meant to work with the fields defined in `islandora_demo`.  If you customized your MODS forms, then you
will also need to customize the example migration and `islandora_demo`.

Currently, the following content models can be migrated over with full functionality:

- Collection
- Basic Image
- Large Image
- Audio
- Video
- PDF
- Binary

If you want some sample Basic Image objects with metadata made from stock forms, check out this zip file that you can
use with islandora_zip_batch_importer. All the images were obtained from [Pexels](https://www.pexels.com/) and are
free to use for personal or business purposes, with the original photographers attributed in the MODS. 

## Installation

Download this module, its feature, and its dependencies with composer

```
composer require islandora/migrate_7x_claw
```

Install the module and example migrations at the same time using drush

```
drush en islandora_migrate_7x_claw_feature
```
 
## Configuration

By default, the migrations are configured to work with an `islandora_vagrant` instance running on the same host as a
`claw-playbook` instance, which is convienent for development and testing. But for your Islandora 7.x instance, the
following config will need to be set the same way on the source plugin of each migration (other than
`islandora_7x_tags`):  

- `solr_base_url` should point to your Islandora 7.x Solr instance (i.e. `http://example.org:8080/solr`)
- `fedora_base_url` should point to your Fedora 3 instance (i.e. `http://example.org:8080/fedora`)
- The `username` and `password` for your Fedora 3 instance in the block 
   ```
    plugin: basic
    username: fedoraAdmin
    password: fedoraAdmin
   ```
- `q` is used to define a Solr query that selects which objects get migrated.  From a fresh clone, the 
migrations are configured to look for `islandora:sp_basic_image_collection` and all its children with the following query:
  ```
    RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/islandora:sp_basic_image_collection" OR PID:"islandora:sp_basic_image_collection" 
  ```
You can easily import a collection of your own by changing the PID in the above query, or you can provide your own
query to migrate over objects in other ways (such as per content model, in order by date created, etc...).  If you can write a Solr select query for it, you can migrate it into CLAW.  Omitting `q` from configuration will default to `*:*`
for the Solr query.  

Once you've updated the configuration, you need to re-import the feature to load your changes.  You can do this with `drush`:
```
drush -y fim islandora_migrate_7x_claw_feature
```

You can also use the UI to import the feature if you go to `admin/config/development/features` and click on the `Changed` link next to "Migrate 7x Claw Feature".

![Changed Link](docs/images/feature_click_changed.png)

From there, you can select all changes and clicking "Import Changes"

![Import Changes](docs/images/feature_import_changes.png)

## Running the migrations

You can quickly run all migrations using `drush`:
```
drush -y mim --group islandora_7x
```

If you want to go through the UI, you can visit `admin/structure/migrate/manage/islandora_7x/migrations` to see a list of all the migrations.

![List of Migrations](docs/images/migrations.jpg)

You will see 8 migrations. _The "7.x Tags Migration from CSV" needs to be run first_. Clicking **Execute** on
"7.x Tags Migration from CSV" migration displays a page like

![Migration Execute](docs/images/migrate2.jpg)

The operations you can run for a migration are 
* **Import** - import un-migrated objects (check the "Update" checkbox to re-run previously migrated objects)
* **Rollback** - delete all the objects (if any) previously imported
* **Stop** - stop a long running import.
* **Reset** - reset an import that might have failed.

If you select "Import", and then click "Execute", it will run the migration. It should process 5 items.
  
Then you can run the "Islandora Media" migration, which
depends on the remaining migrations.  Running it effectively runs the rest of the group.  After it's done,
you should be able to navigate to the home page of your CLAW instance and see your content brought over from
Islandora 7.x!

SHOW MIGRATED CONTENT ON HOMEPAGE
![Migration](docs/images/migrate1.jpg)

If you click on any node you should see all its metadata, which has been extracted from its MODS and Solr documents.

Clicking on the Media tab will reveal all of the datastreams migrated over from 7.x, which you can now manage through CLAW.

## How this migration works
You provide a query, as `q` in the source plugin configuration, that defines which objects get migrated.  The PIDS of those
objects are used to construct the URLs for either an object's Solr doc, it's FOXML file, or a specific datastream (such as
MODS).  Migrations choose which url they want via the 'url_type' configuraiton.  Those urls are fetched via http and the
migrations select what data to extract by either providing Solr field names or XPaths, which are then aligned with fields
in Drupal 8.

All datastreams are migrated over as-is, regardless of what data is extracted by the migrations and applied as fields.

Collection hierarchy is preserved so long as all the collections are in the `q` query results.

Subject, geographic, and person/corporate agents from MODS all get transformed into taxonomy terms, and content
is tagged with these terms.

## Introduction
This module contains plugins and some example migrations to import data from a Fedora 3 Islandora instance
into an Islandora CLAW instance.

This is a base setup, it requires configuration changes for your setup.

## Installation and configuration
To use this migration:

1. from within your Drupal 8 base directory (e.g. `/var/www/html/drupal`), run `composer require islandora/migrate_7x_claw`
1. edit the .yaml files as indicated in the next section (it is important you edit these files before installing the module)

## Editing the .yaml configuration files

You will need to edit the 3 `migrate_plus.migration.islandora_basic_image*` files in the `migrate_7x_claw/modules/islandora_migrate_7x_claw_feature/config/install` directory.

At a minimum you'll need to set:
1. `solr_base_url: http://10.0.2.2:9080/solr` to your Solr instance
1. `fedora_base_url: &fedora_base_url http://10.0.2.2:9080/fedora` to your Fedora.
1. The `username` and `password` in the block 
   ```
    authentication:
    plugin: basic
    username: fedoraAdmin
    password: fedoraAdmin
   ```

You may also need (or want) to alter the content model field name in Solr.
`content_model_field: RELS_EXT_hasModel_uri_ms`
and the content model to migrate.
`content_model: islandora:sp_basic_image`

These changes need to be made in all 3 migration configuration files.

Now you can install the `migrate_7x_claw` and `islandora_migrate_7x_claw_feature` modules, either using `drush` or via Drupal's "Admin > Extend" user interface.

If you have installed the `migrate_ui` module (installed by default on the CLAW Playbook) you can review the process in the `Admin -> Structure -> Migrations`.

You can then see.
![List of Migrations](docs/images/migrations.jpg)

If you click **List Migrations** you will see 3 migrations.

![Migration](docs/images/migrate1.jpg)

## Example usage

The _Basic Image Objects OBJ Media_ migration requires the other two be completed first, if you try to run this one it 
will run the other two first.

Clicking **Execute** on the _Basic Image Objects_ displays a page like.

![Migration Execute](docs/images/migrate2.jpg)

The operations you can run are 
* **Import** - import the objects
* **Rollback** - delete all the objects (if any) previously imported
* **Stop** - stop a long running import.
* **Reset** - reset an import that might have failed.

With _Import_ selected press **Execute**.

When complete, you should see something like below (your number will be different).

![Migration result](docs/images/migrate_result1.jpg)

## How this migration works

1. The migration searches Solr for all of the content models specified.
1. Each 7.x object is migrated to a new node in Drupal 8.
1. Then it creates a file for the OBJ datastream of each of these objects and a file for each of the object's other datastreams.
1. Lastly it creates a media object that links the files to the node.

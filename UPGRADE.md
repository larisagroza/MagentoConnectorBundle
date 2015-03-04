# Upgrade 1.1.* to 1.2.*

## Prepare your upgrade

If your project uses Magento connector v1.1.20 or prior, you should first update it to the latest 1.1 version and upgrade your database schema as explained in "UPGRADE 1.1.20 to 1.1.21" section

Now, you have to remove the export profiles that does not exist anymore in v1.2. If you upgrade the Magento connector before doing so, you will be unable to delete them, as Akeneo will refuse to remove unknown profiles.

The only jobs you can keep are `magento_category_export` and `magento_product_export`. Take a look at the [user guide](./Resources/doc/userguide.md) to understand how exports work in Magento connector 1.2.

## Go for it!

Start by removing the following lines from the composer.json of your project:

    akeneo/connector-mapping-bundle:v1.0.0-BETA3@dev
    akeneo/delta-export-bundle:v1.0.0-BETA3@dev

and those two from your `app/AppKernel.php` file:

    $bundles[] = new Pim\Bundle\DeltaExportBundle\PimDeltaExportBundle();
    $bundles[] = new Pim\Bundle\ConnectorMappingBundle\PimConnectorMappingBundle();

Then you can change the version of the `magento-connector-bundle` in the composer.json file to `1.2.*@stable`, and run:

    php composer.phar update akeneo/magento-connector-bundle

You can of course remove the `akeneo/magento-connector-bundle` part if you want to do a full update of the PIM application.

Now, apply the script `Resources/sql/migration_1.1_to_1.2.sql` to your Akeneo database, then run:

    php app/console doctrine:schema:update --force
    php app/console cache:clear --env=prod

## Get back to work

You can now recreate missing jobs (see the [user guide](./Resources/doc/userguide.md) for more information). Don't forget to use the exact same mapping than you defined before the upgrade, and used with your other jobs, or the exports will not work.

# UPGRADE 1.1.20 to 1.1.21

To upgrade from v1.1.20 to v1.1.21 you will need to update your database schema. Apply the following steps to upgrade:

 1. Apply the script `Resources/sql/mapping_migration.pre_schema.sql` to your PIM application database
 2. Run the `php app/console doctrine:schema:update --force` command
 3. Apply the script `Resources/sql/mapping_migration.post_schema.sql` to your PIM application database

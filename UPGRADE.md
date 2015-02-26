# UPGRADE 1.1.20 to 1.1.21

To upgrade from v1.1.20 to v1.1.21 you will need to update your database schema. Apply the following steps to upgrade:

 1. Apply the script `Resources/sql/mapping_migration.pre_schema.sql` to your PIM application database
 2. Run the `php app/console doctrine:schema:update --force` command
 3. Apply the script `Resources/sql/mapping_migration.post_schema.sql` to your PIM application database

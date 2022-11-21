<?php

namespace venveo\bigcommerce\migrations;

use craft\db\Migration;
use craft\db\Table as CraftTable;
use craft\helpers\MigrationHelper;
use venveo\bigcommerce\db\Table;
use venveo\bigcommerce\elements\Product as ProductElement;
use ReflectionClass;
use yii\base\NotSupportedException;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    /**
     * Creates the tables for Craft Commerce
     */
    public function createTables(): void
    {
        $this->archiveTableIfExists(Table::PRODUCTS);
        $this->createTable(Table::PRODUCTS, [
            'id' => $this->integer()->notNull(),
            'bcId' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY(id)',
        ]);

        $this->archiveTableIfExists(Table::PRODUCTDATA);
        $this->createTable(Table::PRODUCTDATA, [
            'bcId' => $this->string(),
            'title' => $this->text(),
            'sku' => $this->string(),
            'bodyHtml' => $this->text(),
            'createdAt' => $this->dateTime(),
            'handle' => $this->string(),
            'images' => $this->text(),
            'options' => $this->text(),
            'productType' => $this->string(),
            'bcStatus' => $this->string(),
            'updatedAt' => $this->string(),
            'variants' => $this->text(),
            'vendor' => $this->string(),
            'metaFields' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->string(),
            'PRIMARY KEY(bcId)',
        ]);
    }

    /**
     * @return void
     */
    public function createIndexes(): void
    {
        $this->createIndex(null, Table::PRODUCTDATA, ['bcId'], true);
    }

    /**
     * @return void
     */
    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, Table::PRODUCTS, ['bcId'], Table::PRODUCTDATA, ['bcId'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::PRODUCTS, ['id'], CraftTable::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();

        $this->delete(CraftTable::FIELDLAYOUTS, ['type' => [ProductElement::class]]);

        return true;
    }

    /**
     * Drop the tables
     */
    public function dropTables(): void
    {
        $tables = $this->_getAllTableNames();
        foreach ($tables as $table) {
            $this->dropTableIfExists($table);
        }
    }


    /**
     * Removes the foreign keys.
     */
    public function dropForeignKeys(): void
    {
        $tables = $this->_getAllTableNames();

        foreach ($tables as $table) {
            $this->_dropForeignKeyToAndFromTable($table);
        }
    }

    /**
     * @param $tableName
     * @throws NotSupportedException
     */
    private function _dropForeignKeyToAndFromTable($tableName): void
    {
        if ($this->_tableExists($tableName)) {
            $this->dropAllForeignKeysToTable($tableName);
            MigrationHelper::dropAllForeignKeysOnTable($tableName, $this);
        }
    }

    /**
     * Returns if the table exists.
     *
     * @param string $tableName
     * @return bool If the table exists.
     * @throws NotSupportedException
     */
    private function _tableExists(string $tableName): bool
    {
        $schema = $this->db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);

        return (bool)$table;
    }


    /**
     * @return string[]
     */
    private function _getAllTableNames(): array
    {
        $class = new ReflectionClass(Table::class);
        return $class->getConstants();
    }
}

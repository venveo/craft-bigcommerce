<?php

namespace venveo\bigcommerce\migrations;

use craft\db\Migration;
use venveo\bigcommerce\db\Table;

/**
 * m221121_133943_adjust_properties_for_bc migration.
 */
class m221121_133943_adjust_properties_for_bc extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropColumn(Table::PRODUCTDATA, 'tags');
        $this->dropColumn(Table::PRODUCTDATA, 'templateSuffix');
        $this->dropColumn(Table::PRODUCTDATA, 'publishedScope');
        $this->dropColumn(Table::PRODUCTDATA, 'publishedAt');
        $this->addColumn(Table::PRODUCTDATA, 'sku', $this->string()->after('title'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221121_133943_adjust_properties_for_bc cannot be reverted.\n";
        return false;
    }
}

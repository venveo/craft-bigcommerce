<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use venveo\bigcommerce\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Product record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 *
 * @property int $id
 * @property int $bcId
 *
 */
class Product extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::PRODUCTS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getData(): ActiveQueryInterface
    {
        return $this->hasOne(ProductData::class, ['bcId' => 'id']);
    }
}

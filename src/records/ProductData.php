<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\records;

use craft\db\ActiveRecord;
use venveo\bigcommerce\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Product Data record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 *
 * @property int $bcId
 * @property string $title
 * @property string $bodyHtml
 * @property string $createdAt
 * @property string $handle
 * @property array $images
 * @property string $options
 * @property string $productType
 * @property string $publishedAt
 * @property string $publishedScope
 * @property string $bcStatus
 * @property string $tags
 * @property string $templateSuffix
 * @property string $updatedAt
 * @property array $variants
 * @property string $vendor
 * @property string $dateUpdated
 *
 */
class ProductData extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::PRODUCTDATA;
    }

    public function getData(): ActiveQueryInterface
    {
        return $this->hasOne(Product::class, ['id' => 'bcId']);
    }
}

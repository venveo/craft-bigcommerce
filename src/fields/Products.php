<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\fields;

use Craft;
use craft\fields\BaseRelationField;
use venveo\bigcommerce\elements\Product;

/**
 * Class BigCommerce Product Field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 *
 * @property-read array $contentGqlType
 */
class Products extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('bigcommerce', 'BigCommerce Products');
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('bigcommerce', 'Add a product');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Product::class;
    }
}

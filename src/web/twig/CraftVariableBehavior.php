<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\web\twig;

use Craft;
use venveo\bigcommerce\elements\db\ProductQuery;
use venveo\bigcommerce\elements\Product;
use venveo\bigcommerce\Plugin;
use yii\base\Behavior;

/**
 * Class CraftVariableBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class CraftVariableBehavior extends Behavior
{
    /**
     * @var Plugin
     */
    public Plugin $bigcommerce;

    public function init(): void
    {
        parent::init();

        $this->bigcommerce = Plugin::getInstance();
    }

    /**
     * Returns a new ProductQuery instance.
     *
     * @param array $criteria
     * @return ProductQuery
     */
    public function bigcommerceProducts(array $criteria = []): ProductQuery
    {
        $query = Product::find();
        Craft::configure($query, $criteria);
        return $query;
    }
}

<?php

namespace venveo\bigcommerce\jobs;

use Craft;
use craft\queue\BaseJob;
use venveo\bigcommerce\elements\Product;
use venveo\bigcommerce\Plugin;

/**
 * Updates the metadata for a Shopify product.
 */
class UpdateProductMetadata extends BaseJob
{
    public int $bcProductId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $api = Plugin::getInstance()->getApi();
        $product = Product::find()->bcId($this->bcProductId)->one();
        if ($product) {
            Plugin::getInstance()->products->syncProductByBcId($this->bcProductId);
//            Craft::$app->elements->saveElement($product);
            sleep(1); // Avoid rate limiting
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return null;
    }
}

<?php

namespace venveo\bigcommerce\services;

use BigCommerce\ApiV3\ResourceModels\Catalog\Product\Product as BigCommerceProduct;
use BigCommerce\ApiV3\ResourceModels\Metafield;
use Craft;
use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use venveo\bigcommerce\elements\Product as ProductElement;
use venveo\bigcommerce\events\BigCommerceProductSyncEvent;
use venveo\bigcommerce\Plugin;
use venveo\bigcommerce\records\ProductData as ProductDataRecord;

/**
 * BigCommerce Products service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 *
 *
 * @property-read void $products
 */
class Products extends Component
{
    /**
     * @event BigCommerceProductSyncEvent Event triggered just before BigCommerce product data is saved to a product element.
     *
     * ---
     *
     * ```php
     * use venveo\bigcommerce\events\BigCommerceProductSyncEvent;
     * use venveo\bigcommerce\services\Products;
     * use yii\base\Event;
     *
     * Event::on(
     *     Products::class,
     *     Products::EVENT_BEFORE_SYNCHRONIZE_PRODUCT,
     *     function(BigCommerceProductSyncEvent $event) {
     *         // Cancel the sync if a flag is set via a BigCommerce metafield:
     *         if ($event->metafields['do_not_sync'] ?? false) {
     *             $event->isValid = false;
     *         }
     *     }
     * );
     * ```
     */
    public const EVENT_BEFORE_SYNCHRONIZE_PRODUCT = 'beforeSynchronizeProduct';

    /**
     * @return void
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function syncAllProducts(): void
    {
        $api = Plugin::getInstance()->getApi();
        $products = $api->getAllProducts();

        foreach ($products as $product) {
            $metafields = $api->getMetafieldsByProductId($product->id);
            try {
                $variants = $api->getVariantsByProductId($product->id);
                $options = $api->getOptionsByProductId($product->id);
            } catch (\Throwable $throwable) {
                $variants = [];
                $options = [];
            }
            $this->createOrUpdateProduct($product, $metafields, $variants, $options);
        }

        // Remove any products that are no longer in BigCommerce just in case.
        $bcIds = ArrayHelper::getColumn($products, 'id');
        $deletableProductElements = ProductElement::find()->bcId(['not', $bcIds])->all();

        foreach ($deletableProductElements as $element) {
            Craft::$app->elements->deleteElement($element);
        }
    }

    /**
     * @return void
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function syncProductByBcId($id): void
    {
        $api = Plugin::getInstance()->getApi();

        $product = $api->getProductByBcId($id);
        $metafields = $api->getMetafieldsByProductId($id);
        $variants = $api->getVariantsByProductId($id);
        $options = $api->getOptionsByProductId($id);
        $this->createOrUpdateProduct($product, $metafields, $variants, $options);
    }

    /**
     * This takes the bigcommerce data from the REST API and creates or updates a product element.
     *
     * @param BigCommerceProduct $product
     * @param Metafield[] $metafields
     * @return bool Whether or not the synchronization succeeded.
     */
    public function createOrUpdateProduct(BigCommerceProduct $product, array $metafields = [], $variants = [], $options = []): bool
    {
        // Expand any JSON-like properties:
//        $metafields = MetafieldsHelper::unpack($metafields);
        $handle = $product->custom_url->url ?? null;
        if ($handle) {
            $handle = StringHelper::slugify($handle);
        }

        // Build our attribute set from the BigCommerce product data:
        $attributes = [
            'bcId' => $product->id,
            'title' => $product->name,
            'sku' => $product->sku,
            'bodyHtml' => $product->description, // Rename field
            'createdAt' => $product->date_created,
            'handle' => $handle, // ??
            'images' => [], // ??
            'options' => $options, // ??
            'productType' => $product->type,
            'bcStatus' => $product->is_visible ? ProductElement::BC_STATUS_ACTIVE : ProductElement::BC_STATUS_DRAFT,
            'updatedAt' => $product->date_modified,
            'variants' => $variants,
            'vendor' => null, // ??
            'metaFields' => $metafields,
            // This one is unusual, because we’re merging two different BigCommerce API resources:
//            'metaFields' => $metafields,
        ];

        // Find the product data or create one
        $productDataRecord = ProductDataRecord::find()->where(['bcId' => $product->id])->one() ?: new ProductDataRecord();

        // Set attributes and save:
        $productDataRecord->setAttributes($attributes, false);
        $productDataRecord->save();

        // Find the product element or create one
        /** @var ProductElement|null $productElement */
        $productElement = ProductElement::find()
            ->bcId($product->id)
            ->status(null)
            ->one();

        if ($productElement === null) {
            /** @var ProductElement $productElement */
            $productElement = new ProductElement();
        }

        // Set attributes on the element to emulate it having been loaded with JOINed data:
        $productElement->setAttributes($attributes, false);

        $event = new BigCommerceProductSyncEvent([
            'element' => $productElement,
            'source' => $product,
            'metafields' => $metafields,
        ]);
        $this->trigger(self::EVENT_BEFORE_SYNCHRONIZE_PRODUCT, $event);

        if (!$event->isValid) {
            Craft::warning("Synchronization of BigCommerce product ID #{$product->id} was stopped by a plugin.", 'bigcommerce');

            return false;
        }

        if (!Craft::$app->getElements()->saveElement($productElement)) {
            Craft::error("Failed to synchronize BigCommerce product ID #{$product->id}.", 'bigcommerce');

            return false;
        }

        return true;
    }

    /**
     * Deletes a product element by the BigCommerce ID.
     *
     * @param $id
     * @return void
     */
    public function deleteProductByBcId($id): void
    {
        if ($id) {
            if ($product = ProductElement::find()->bcId($id)->one()) {
                // We hard delete because it will have been hard deleted in BigCommerce
                Craft::$app->getElements()->deleteElement($product, true);
            }
            if ($productData = ProductDataRecord::find()->where(['bcId' => $id])->one()) {
                $productData->delete();
            }
        }
    }

    /**
     * Gets a Product element ID from a bigcommerce ID.
     *
     * @param $id
     * @return int
     */
    public function getProductIdByBcId($id): int
    {
        return ProductElement::find()->bcId($id)->one()->id;
    }

    public function getLiveProductDetailsByProductId($id) {
        $data = \venveo\bigcommerce\api\operations\Products::getProductInformationById($id)->getBody()->getContents();
        return Json::decodeIfJson($data);
    }
}

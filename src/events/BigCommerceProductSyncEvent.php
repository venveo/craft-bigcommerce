<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\events;

use craft\events\CancelableEvent;
use venveo\bigcommerce\elements\Product as ProductElement;
//use Shopify\Rest\Admin2022_10\Product as ShopifyProduct;

/**
 * Event triggered just before a synchronized product element is going to be saved.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class BigCommerceProductSyncEvent extends CancelableEvent
{
    /**
     * @var ProductElement Craft product element being synchronized.
     */
    public ProductElement $element;

//    /**
//     * @var ShopifyProduct Source Shopify API resource.
//     */
//    public ShopifyProduct $source;

    /**
     * @var array List of Shopify metafields for the product.
     */
    public array $metafields;
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use venveo\bigcommerce\elements\Product;
use venveo\bigcommerce\helpers\Product as ProductHelper;
use venveo\bigcommerce\Plugin;
use yii\web\Response;

/**
 * The ProductsController handles listing and showing BigCommerce products elements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ProductsController extends \craft\web\Controller
{
    /**
     * Displays the product index page.
     *
     * @return Response
     */
    public function actionProductIndex(): Response
    {
        $newProductUrl = '';
        $storeHash = Plugin::getInstance()->settings->getStoreHash(true);
        if ($storeHash) {
            $baseUrl = 'store-' . $storeHash . '.mybigcommerce.com';
            $newProductUrl = UrlHelper::url('https://' . $baseUrl . '/manage/products/add');
        }

        return $this->renderTemplate('bigcommerce/products/_index', compact('newProductUrl'));
    }

    /**
     * Syncs all products
     *
     * @return Response
     */
    public function actionSync(): Response
    {
        $this->requireAdmin(false);
        Plugin::getInstance()->getProducts()->syncAllProducts();
        return $this->asSuccess(Craft::t('bigcommerce','Products successfully synced'));
    }

    /**
     * Renders the card HTML.
     *
     * @return string
     */
    public function actionRenderCardHtml(): string
    {
        $id = (int)Craft::$app->request->getParam('id');
        /** @var Product $product */
        $product = Product::find()->id($id)->status(null)->one();
        return ProductHelper::renderCardHtml($product);
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\helpers\App;
use venveo\bigcommerce\Plugin;
use craft\web\assets\admintable\AdminTableAsset;
use craft\web\Controller;
use yii\web\ConflictHttpException;
use yii\web\Response as YiiResponse;

/**
 * The WebhooksController to manage the BigCommerce webhooks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class WebhooksController extends Controller
{
    /**
     * Edit page for the webhook management
     *
     * @return YiiResponse
     */
    public function actionEdit(): YiiResponse
    {
        $view = $this->getView();
        $view->registerAssetBundle(AdminTableAsset::class);

        if (!$session = Plugin::getInstance()->getApi()->getSession()) {
            throw new ConflictHttpException('No BigCommerce API session found, check credentials in settings.');
        }

        $webhooks = collect(Webhook::all($session));

        // If we don't have all webhooks needed for the current environment show the create button

        $containsAllWebhooks = (
            $webhooks->contains(function($item) {
                return str_contains($item->address, Craft::$app->getRequest()->getHostName()) && $item->topic === 'products/create';
            }) &&
            $webhooks->contains(function($item) {
                return str_contains($item->address, Craft::$app->getRequest()->getHostName()) && $item->topic === 'products/delete';
            }) &&
            $webhooks->contains(function($item) {
                return str_contains($item->address, Craft::$app->getRequest()->getHostName()) && $item->topic === 'products/update';
            })
        );


        return $this->renderTemplate('bigcommerce/webhooks/index', compact('webhooks', 'containsAllWebhooks'));
    }

    /**
     * Creates the webhooks for the current environment.
     *
     * @return YiiResponse
     */
    public function actionCreate(): YiiResponse
    {
        $this->requirePostRequest();

        $view = $this->getView();
        $view->registerAssetBundle(AdminTableAsset::class);

        $pluginSettings = Plugin::getInstance()->getSettings();

        if (!$session = Plugin::getInstance()->getApi()->getSession()) {
            throw new ConflictHttpException('No BigCommerce API session found, check credentials in settings.');
        }

        $responseCreate = Registry::register(
            path: 'bigcommerce/webhook/handle',
            topic: Topics::PRODUCTS_CREATE,
            shop: App::parseEnv($pluginSettings->hostName),
            accessToken: App::parseEnv($pluginSettings->accessToken)
        );
        $responseUpdate = Registry::register(
            path: 'bigcommerce/webhook/handle',
            topic: Topics::PRODUCTS_UPDATE,
            shop: App::parseEnv($pluginSettings->hostName),
            accessToken: App::parseEnv($pluginSettings->accessToken)
        );
        $responseDelete = Registry::register(
            path: 'bigcommerce/webhook/handle',
            topic: Topics::PRODUCTS_DELETE,
            shop: App::parseEnv($pluginSettings->hostName),
            accessToken: App::parseEnv($pluginSettings->accessToken)
        );

        if (!$responseCreate->isSuccess() || !$responseUpdate->isSuccess() || !$responseDelete->isSuccess()) {
            Craft::error('Could not register webhooks with BigCommerce API.', __METHOD__);
        }

        $this->setSuccessFlash(Craft::t('app', 'Webhooks registered.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Deletes a webhook from the BigCommerce API.
     *
     * @return YiiResponse
     */
    public function actionDelete(): YiiResponse
    {
        $this->requireAcceptsJson();
        $id = Craft::$app->getRequest()->getBodyParam('id');

        if ($session = Plugin::getInstance()->getApi()->getSession()) {
            Webhook::delete($session, $id);
            return $this->asSuccess(Craft::t('bigcommerce', 'Webhook deleted'));
        }

        return $this->asSuccess(Craft::t('bigcommerce', 'Webhook could not be deleted'));
    }
}

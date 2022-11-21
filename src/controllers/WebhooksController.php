<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\assets\admintable\AdminTableAsset;
use craft\web\Controller;
use venveo\bigcommerce\Plugin;
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

        if (!$client = Plugin::getInstance()->getApi()->getClient()) {
            throw new ConflictHttpException('No BigCommerce API client found, check credentials in settings.');
        }
        $containsAllWebhooks = [];

        $webhooks = collect(Json::decode($client->getRestClient()->get('hooks')->getBody())['data'] ?? []);

        // If we don't have all webhooks needed for the current environment show the create button

//        $containsAllWebhooks = (
//            $webhooks->contains(function($item) {
//                return str_contains($item->address, Craft::$app->getRequest()->getHostName()) && $item->topic === 'products/create';
//            }) &&
//            $webhooks->contains(function($item) {
//                return str_contains($item->address, Craft::$app->getRequest()->getHostName()) && $item->topic === 'products/delete';
//            }) &&
//            $webhooks->contains(function($item) {
//                return str_contains($item->address, Craft::$app->getRequest()->getHostName()) && $item->topic === 'products/update';
//            })
//        );


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

        if (!$client = Plugin::getInstance()->getApi()->getClient()) {
            throw new ConflictHttpException('No BigCommerce API client found, check credentials in settings.');
        }

        $destination = $pluginSettings->getWebhookUrl();

        $responseDelete = $client->getRestClient()->post('hooks', [
            'json' => [
                'scope' => 'store/product/deleted',
                'destination' => $destination,
                'is_active' => true,
                'events_history_enabled' => true
            ]
        ]);

        $responseCreate = $client->getRestClient()->post('hooks', [
            'json' => [
                'scope' => 'store/product/created',
                'destination' => $destination,
                'is_active' => true,
                'events_history_enabled' => true
            ]
        ]);

        $responseUpdate = $client->getRestClient()->post('hooks', [
            'json' => [
                'scope' => 'store/product/updated',
                'destination' => $destination,
                'is_active' => true,
                'events_history_enabled' => true
            ]
        ]);

//        if (!$responseCreate->isSuccess() || !$responseUpdate->isSuccess() || !$responseDelete->isSuccess()) {
//            Craft::error('Could not register webhooks with BigCommerce API.', __METHOD__);
//        }

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

        if ($client = Plugin::getInstance()->getApi()->getClient()) {
            $client->getRestClient()->delete('hooks/' . $id);
            return $this->asSuccess(Craft::t('bigcommerce', 'Webhook deleted'));
        }

        return $this->asSuccess(Craft::t('bigcommerce', 'Webhook could not be deleted'));
    }
}

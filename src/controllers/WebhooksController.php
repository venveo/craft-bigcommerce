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
use venveo\bigcommerce\base\SdkClientTrait;
use venveo\bigcommerce\handlers\Product as ProductHandler;
use venveo\bigcommerce\Plugin;
use yii\web\Response as YiiResponse;

/**
 * The WebhooksController to manage the BigCommerce webhooks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class WebhooksController extends Controller
{
    use SdkClientTrait;
    /**
     * Edit page for the webhook management
     *
     * @return YiiResponse
     */
    public function actionEdit(): YiiResponse
    {
        $settings = Plugin::getInstance()->settings;
        $this->requireAdmin(false);
        $view = $this->getView();
        $view->registerAssetBundle(AdminTableAsset::class);
        $webhooks = collect(Json::decode($this->getClient()->getRestClient()->get('hooks')->getBody())['data'] ?? []);

        // If we don't have all webhooks needed for the current environment show the create button

        $containsAllWebhooks = (
            $webhooks->contains($this->validateWebhook('store/product/deleted', $settings->webhookSecret)) &&
            $webhooks->contains($this->validateWebhook('store/product/created', $settings->webhookSecret)) &&
            $webhooks->contains($this->validateWebhook('store/product/updated', $settings->webhookSecret))
        );

        return $this->renderTemplate('bigcommerce/webhooks/_index', compact('webhooks', 'containsAllWebhooks', 'settings'));
    }

    /**
     * Creates the webhooks for the current environment.
     *
     * @return YiiResponse
     */
    public function actionCreate(): YiiResponse
    {
        $this->requireAdmin(false);
        $this->requirePostRequest();
        $baseUrlOverride = $this->request->getBodyParam('baseUrlOverride');

        $view = $this->getView();
        $view->registerAssetBundle(AdminTableAsset::class);

        $pluginSettings = Plugin::getInstance()->getSettings();
        $destination = $pluginSettings->getWebhookUrl($baseUrlOverride);

        $webhookData = [
            'json' => [
                'destination' => $destination,
                'is_active' => true,
                'events_history_enabled' => true,
                'headers' => [
                    'x-secret' => $pluginSettings->webhookSecret
                ]
            ]
        ];
        $created = false;
        try {
            $webhookData['json']['scope'] = ProductHandler::PRODUCT_CREATE;
            $this->getClient()->getRestClient()->post('hooks', $webhookData);
            $webhookData['json']['scope'] = ProductHandler::PRODUCT_UPDATE;
            $this->getClient()->getRestClient()->post('hooks', $webhookData);
            $webhookData['json']['scope'] = ProductHandler::PRODUCT_DELETE;
            $this->getClient()->getRestClient()->post('hooks', $webhookData);
            $created = true;
        } catch (\Exception $exception) {
            Craft::error($exception->getMessage(), __METHOD__);
            Craft::error($exception->getTraceAsString(), __METHOD__);
        }

        if ($created) {
            $this->setSuccessFlash(Craft::t('bigcommerce', 'Webhooks registered.'));
        } else {
            $this->setFailFlash(Craft::t('bigcommerce', 'One or more webhooks could not be created'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Deletes a webhook from the BigCommerce API.
     *
     * @return YiiResponse
     */
    public function actionDelete(): YiiResponse
    {
        $this->requireAdmin(false);
        $this->requireAcceptsJson();
        $id = Craft::$app->getRequest()->getBodyParam('id');

        if ($client = Plugin::getInstance()->getApi()->getClient()) {
            $client->getRestClient()->delete('hooks/' . $id);
            return $this->asSuccess(Craft::t('bigcommerce', 'Webhook deleted'));
        }

        return $this->asSuccess(Craft::t('bigcommerce', 'Webhook could not be deleted'));
    }

    /**
     * @return \Closure
     */
    protected function validateWebhook(string $scope, string $secret): \Closure
    {
        return static function ($item) use ($scope, $secret) {
            return $item['is_active'] &&
                str_contains($item['destination'], Craft::$app->getRequest()->getHostName()) &&
                isset($item['headers']['x-secret']) && $item['headers']['x-secret'] === $secret &&
                $item['scope'] === $scope;
        };
    }
}

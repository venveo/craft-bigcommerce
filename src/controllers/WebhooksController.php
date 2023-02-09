<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\web\assets\admintable\AdminTableAsset;
use craft\web\Controller;
use venveo\bigcommerce\base\SdkClientTrait;
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
        $this->requireAdmin(false);
        $settings = Plugin::getInstance()->settings;
        $view = $this->getView();
        $view->registerAssetBundle(AdminTableAsset::class);
        $webhooksService = Plugin::getInstance()->webhooks;
        $webhooks = $webhooksService->getAllWebhooks();

        // If we don't have all webhooks needed for the current environment show the create button

        $containsAllWebhooks = (
            $webhooks->contains($webhooksService::createWebhookValidator('store/product/deleted',
                $settings->getWebhookSecret(true))) &&
            $webhooks->contains($webhooksService::createWebhookValidator('store/product/created',
                $settings->getWebhookSecret(true))) &&
            $webhooks->contains($webhooksService::createWebhookValidator('store/product/updated',
                $settings->getWebhookSecret(true)))
        );

        return $this->renderTemplate('bigcommerce/webhooks/_index',
            compact('webhooks', 'containsAllWebhooks', 'settings'));
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

        $webhooksService = Plugin::getInstance()->webhooks;
        $created = $webhooksService->createRequiredWebhooks($baseUrlOverride);

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
        $webhooksService = Plugin::getInstance()->webhooks;
        if ($webhooksService->deleteWebhookById($id)) {
            return $this->asSuccess(Craft::t('bigcommerce', 'Webhook deleted'));
        }
        return $this->asSuccess(Craft::t('bigcommerce', 'Webhook could not be deleted'));
    }
}

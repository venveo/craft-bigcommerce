<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use venveo\bigcommerce\base\SdkClientTrait;
use venveo\bigcommerce\handlers\Product;
use venveo\bigcommerce\Plugin;
use yii\web\Response as YiiResponse;

/**
 * The WebhookController handles the BigCommerce webhook request.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class WebhookHandlerController extends Controller
{
    use SdkClientTrait;

    public $defaultAction = 'handle';
    public $enableCsrfValidation = false;
    public array|bool|int $allowAnonymous = ['handle'];

    /**
     * Handles the webhooks from BigCommerce for all topics
     *
     * @return YiiResponse
     */
    public function actionHandle(): YiiResponse
    {
        $pluginSettings = Plugin::getInstance()->getSettings();
        $request = Craft::$app->getRequest();
        $headers = $request->headers->toArray();
        $body = Json::decode($request->getRawBody());
        // NOTE: For some reason BigCommerce stores the custom headers as an array
        if (!isset($headers['x-secret'][0]) || $headers['x-secret'][0] !== $pluginSettings->webhookSecret) {
            Craft::warning('Received webhook with missing or incorrect secret', __METHOD__);
            $this->response->setStatusCode(200);
            return $this->asRaw('OK');
        }
        try {
            $handler = new Product();
            $handler->handle($body['scope'], $body['store_id'], $body['data']);
        } catch (\Exception $error) {
            Craft::error($error->getMessage());
        }

        $this->response->setStatusCode(200);
        return $this->asRaw('OK');
    }
}

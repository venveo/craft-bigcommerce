<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\helpers\Json;
use venveo\bigcommerce\handlers\Product;
use venveo\bigcommerce\Plugin;
use craft\web\Controller;
use yii\web\Response as YiiResponse;

/**
 * The WebhookController handles the BigCommerce webhook request.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class WebhookController extends Controller
{
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
        $request = Craft::$app->getRequest();
        // TODO: Validate request integrity
        $client = Plugin::getInstance()->getApi()->getClient();

        $headers = $request->headers->toArray();
        $body = Json::decode($request->getRawBody());
        $handler = new Product();
        $handler->handle($body['scope'], $body['store_id'], $body['data']);
//
//        try {
//            $response = Registry::process($request->headers->toArray(), $request->getRawBody());
//
//            if (!$response->isSuccess()) {
//                Craft::error("Webhook handler failed with message:" . $response->getErrorMessage());
//            }
//        } catch (\Exception $error) {
//            Craft::error($error->getMessage());
//        }

        $this->response->setStatusCode(200);
        return $this->asRaw('OK');
    }
}

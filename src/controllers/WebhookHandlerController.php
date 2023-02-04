<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use Craft;
use craft\helpers\Json;
use venveo\bigcommerce\base\SdkClientTrait;
use venveo\bigcommerce\handlers\Product;
use craft\web\Controller;
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
        $request = Craft::$app->getRequest();

        $headers = $request->headers->toArray();
        $body = Json::decode($request->getRawBody());

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
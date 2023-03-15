<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\console\controllers;

use craft\console\Controller;
use venveo\bigcommerce\Plugin;
use yii\console\ExitCode;

class WebhooksController extends Controller
{
    public function actionRefresh(): int
    {
        $service = Plugin::getInstance()->webhooks;
        $webhooks = $service->getAllWebhooks();
        // If we don't have all webhooks needed for the current environment show the create button
        $disabledWebhooks = $webhooks->where('is_enabled', '=', false);
        $disabledWebhooks->each(function ($webhook) use ($service) {
            $service->deleteWebhookById($webhook['id']);
            $this->stdout('Deleted inactive webhook: '. $webhook['id']);
        });
        $service->createRequiredWebhooks();
        return ExitCode::OK;
    }
}

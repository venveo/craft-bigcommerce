<?php

namespace venveo\bigcommerce\services;

use craft\base\Component;
use craft\errors\SiteNotFoundException;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\GuzzleException;
use venveo\bigcommerce\base\SdkClientTrait;
use venveo\bigcommerce\handlers\Product as ProductHandler;
use venveo\bigcommerce\Plugin;


class Webhooks extends Component
{
    use SdkClientTrait;

    /**
     * @param array $webhookData
     * @param string $expectedScope
     * @param string $expectedSecret
     * @return bool
     * @throws SiteNotFoundException
     */
    public static function validateWebhook(array $webhookData, string $expectedScope, string $expectedSecret): bool
    {
        $siteUrl = \Craft::$app->sites->getPrimarySite()->getBaseUrl();
        $hostName = \Craft::$app->getRequest()->getHostName();
        return $webhookData['is_active'] &&
            (str_contains($webhookData['destination'], $siteUrl) || str_contains($webhookData['destination'],
                    $hostName)) &&
            isset($webhookData['headers']['x-secret']) && $webhookData['headers']['x-secret'] === $expectedSecret &&
            $webhookData['scope'] === $expectedScope;
    }

    public static function createWebhookValidator($expectedScope, $expectedSecret): \Closure
    {
        return static function ($item) use ($expectedScope, $expectedSecret) {
            return static::validateWebhook($item, $expectedScope, $expectedSecret);
        };
    }

    public function getAllWebhooks(): \Illuminate\Support\Collection
    {
        return collect(Json::decode($this->getClient()->getRestClient()->get('hooks')->getBody())['data'] ?? []);
    }


    /**
     * @param string|null $baseUrlOverride
     * @return string
     */
    public function getWebhookUrl(?string $baseUrlOverride): string
    {
        $path = 'bigcommerce/webhook/handle';
        if ($baseUrlOverride && $baseUrl = App::parseEnv($baseUrlOverride)) {
            return UrlHelper::url($baseUrl . '/' . $path);
        }
        return UrlHelper::actionUrl($path);
    }

    public function createRequiredWebhooks(?string $baseUrlOverride): bool
    {
        $destination = $this->getWebhookUrl($baseUrlOverride);
        $secret = Plugin::getInstance()->settings->getWebhookSecret(true);
        $webhookData = [
            'json' => [
                'destination' => $destination,
                'is_active' => true,
                'events_history_enabled' => true,
                'headers' => [
                    'x-secret' => $secret
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
            \Craft::error($exception->getMessage(), __METHOD__);
            \Craft::error($exception->getTraceAsString(), __METHOD__);
        }
        return $created;
    }

    public function deleteWebhookById(int $id): bool
    {
        try {
            $client = $this->getV2Client()->getRestClient();
            $client->delete('hooks/' . $id);
            return true;
        } catch (GuzzleException $e) {
            \Craft::error('Failed to delete webhook', $e->getMessage());
            return false;
        }
    }
}

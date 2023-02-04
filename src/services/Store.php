<?php

namespace venveo\bigcommerce\services;

use craft\base\Component;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use venveo\bigcommerce\Plugin;
use yii\base\InvalidConfigException;

/**
 * BigCommerce Store service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Store extends Component
{
    /**
     * Creates a URL to the external BigCommerce store
     *
     * @param string $path
     * @param array $params
     * @param string|int|null $channelId - optional channel ID override
     * @return string
     *@throws InvalidConfigException when no hostname is set up.
     */
    public function getUrl(string $path = '', array $params = [], string|int $channelId = null): string
    {
        $settings = Plugin::getInstance()->getSettings();
        $storeHash = App::parseEnv($settings->storeHash);
        $channelId = $channelId ?? $settings->getDefaultChannelId();
        $host = 'store-'.$storeHash.'-'.$channelId.'.mybigcommerce.com';

        if (!$host) {
            throw new InvalidConfigException('BigCommerce URLs cannot be generated without a hostname configured.');
        }

        return UrlHelper::url("https://{$host}/{$path}", $params);
    }
}

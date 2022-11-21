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
     * @throws InvalidConfigException when no hostname is set up.
     * @return string
     */
    public function getUrl(string $path = '', array $params = []): string
    {
        $settings = Plugin::getInstance()->getSettings();
        $storeHash = App::parseEnv($settings->storeHash);
        $host = 'store-'.$storeHash.'.mybigcommerce.com';

        if (!$host) {
            throw new InvalidConfigException('BigCommerce URLs cannot be generated without a hostname configured.');
        }

        return UrlHelper::url("https://{$host}/{$path}", $params);
    }
}

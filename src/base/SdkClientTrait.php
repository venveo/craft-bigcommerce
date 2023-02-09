<?php
namespace venveo\bigcommerce\base;

use BigCommerce\ApiV2\V2ApiClient;
use BigCommerce\ApiV3\Client;
use craft\helpers\App;
use venveo\bigcommerce\Plugin;

trait SdkClientTrait {
    /**
     * @var Client|null
     */
    private ?Client $_client = null;
    private ?V2ApiClient $_v2Client = null;
    /**
     * Returns or sets up a Rest API client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->_client === null) {
            $pluginSettings = Plugin::getInstance()->getSettings();
            $apiClientId = $pluginSettings->getClientId(true);
//            $apiSecretKey = $pluginSettings->getClientSecret(true);
            $storeHash = $pluginSettings->getStoreHash(true);
            $accessToken = $pluginSettings->getAccessToken(true);

            $this->_client = new \BigCommerce\ApiV3\Client($storeHash, $apiClientId, $accessToken);
        }

        return $this->_client;
    }

    public function getV2Client() {
        if ($this->_v2Client === null) {
            $pluginSettings = Plugin::getInstance()->getSettings();
            $apiClientId = $pluginSettings->getClientId(true);
//            $apiSecretKey = $pluginSettings->getClientSecret(true);
            $storeHash = $pluginSettings->getStoreHash(true);
            $accessToken = $pluginSettings->getAccessToken(true);

            $this->_v2Client = new \BigCommerce\ApiV2\V2ApiClient($storeHash, $apiClientId, $accessToken);
        }
        return $this->_v2Client;
    }
}
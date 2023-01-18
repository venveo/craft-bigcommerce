<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use venveo\bigcommerce\elements\Product;

/**
 * BigCommerce Settings model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Settings extends Model
{
    public string $clientId = '';
    public string $clientSecret = '';
    public string $accessToken = '';
    public string $storeHash = '';
    public string $webhookBaseUrl = '';
    public string $uriFormat = '';
    public string $template = '';
    public string $defaultChannel = '';
    private mixed $_productFieldLayout;

    public function rules(): array
    {
        return [
            [['clientId', 'clientSecret', 'accessToken', 'storeHash'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'clientId' => Craft::t('bigcommerce', 'BigCommerce API Client ID'),
            'clientSecret' => Craft::t('bigcommerce', 'BigCommerce API Secret Key'),
            'accessToken' => Craft::t('bigcommerce', 'BigCommerce Access Token'),
            'storeHash' => Craft::t('bigcommerce', 'BigCommerce Store ID'),
            'webhookBaseUrl' => Craft::t('bigcommerce', 'Webhook Base URL'),
            'uriFormat' => Craft::t('bigcommerce', 'Product URI format'),
            'template' => Craft::t('bigcommerce', 'Product Template'),
        ];
    }

    /**
     * @return \craft\models\FieldLayout|mixed
     */
    public function getProductFieldLayout()
    {
        if (!isset($this->_productFieldLayout)) {
            $this->_productFieldLayout = Craft::$app->fields->getLayoutByType(Product::class);
        }

        return $this->_productFieldLayout;
    }

    /**
     * @param mixed $fieldLayout
     * @return void
     */
    public function setProductFieldLayout(mixed $fieldLayout): void
    {
        $this->_productFieldLayout = $fieldLayout;
    }

    /**
     * @return string
     */
    public function getWebhookUrl(): string
    {
        $path = 'bigcommerce/webhook/handle';
        if ($this->webhookBaseUrl && $baseUrl = App::parseEnv($this->webhookBaseUrl)) {
            $baseUrl = UrlHelper::url($baseUrl . '/' . $path);
            return $baseUrl;
        }
        return UrlHelper::actionUrl($path);
    }
}

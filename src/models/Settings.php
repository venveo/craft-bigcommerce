<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use venveo\bigcommerce\elements\Product;

/**
 * Shopify Settings model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Settings extends Model
{
    public string $apiKey = '';
    public string $apiSecretKey = '';
    public string $accessToken = '';
    public string $hostName = '';
    public string $uriFormat = '';
    public string $template = '';
    private mixed $_productFieldLayout;

    public function rules(): array
    {
        return [
            [['apiSecretKey', 'apiKey', 'accessToken', 'hostName'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => Craft::t('bigcommerce', 'BigCommerce API Key'),
            'apiSecretKey' => Craft::t('bigcommerce', 'BigCommerce API Secret Key'),
            'accessToken' => Craft::t('bigcommerce', 'BigCommerce Access Token'),
            'hostName' => Craft::t('bigcommerce', 'BigCommerce Host Name'),
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
        return UrlHelper::actionUrl('bigcommerce/webhook/handle');
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
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
    /** @deprecated */
    public string $webhookBaseUrl = '';
    public string $uriFormat = '';
    public string $template = '';
    public string $defaultChannel = '';
    public string $webhookSecret = '';
    private mixed $_productFieldLayout;

    public function rules(): array
    {
        return [
            [['clientId', 'clientSecret', 'accessToken', 'storeHash', 'defaultChannel', 'webhookSecret'], 'required'],
            [['webhookSecret'], 'string', 'min' => 16],
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
            'uriFormat' => Craft::t('bigcommerce', 'Product URI format'),
            'template' => Craft::t('bigcommerce', 'Product Template'),
            'defaultChannel' => Craft::t('bigcommerce', 'Default Channel'),
            'webhookSecret' => Craft::t('bigcommerce', 'Webhook Secret'),
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
    public function getWebhookUrl($baseUrlOverride): string
    {
        $path = 'bigcommerce/webhook/handle';
        if ($baseUrlOverride && $baseUrl = App::parseEnv($baseUrlOverride)) {
            $baseUrl = UrlHelper::url($baseUrl . '/' . $path);
            return $baseUrl;
        }
        return UrlHelper::actionUrl($path);
    }

    public function getDefaultChannelId(): int {
        return (int)App::parseEnv($this->defaultChannel);
    }

    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['clientId', 'clientSecret', 'accessToken', 'storeHash', 'webhookSecret', 'defaultChannel'],
            ],
        ];
    }
}

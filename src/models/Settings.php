<?php

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
 * @author Venveo <development@venveo.com>
 * @since 3.0
 */
class Settings extends Model
{
    private ?string $_clientId = null;
    private ?string $_clientSecret = null;
    private ?string $_accessToken = null;
    private ?string $_storeHash = null;
    private int|string|null $_defaultChannel = null;
    private ?string $_webhookSecret = null;

    public string $uriFormat = '';
    public string $template = '';
    private mixed $_productFieldLayout;

    public function rules(): array
    {
        return [
            [['clientId', 'clientSecret', 'accessToken', 'storeHash', 'defaultChannel', 'webhookSecret'], 'required'],
            [['defaultChannel'], 'integer'],
            [['webhookSecret'], 'string', 'min' => 16],
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


    public function getClientId(bool $parse = false): string
    {
        return ($parse ? App::parseEnv($this->_clientId) : $this->_clientId) ?? '';
    }

    public function setClientId(?string $clientId): void
    {
        $this->_clientId = $clientId;
    }


    public function getClientSecret(bool $parse = false): string
    {
        return ($parse ? App::parseEnv($this->_clientSecret) : $this->_clientSecret);
    }

    public function setClientSecret(?string $clientSecret): void
    {
        $this->_clientSecret = $clientSecret;
    }


    public function getAccessToken(bool $parse = false): string
    {
        return ($parse ? App::parseEnv($this->_accessToken) : $this->_accessToken);
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->_accessToken = $accessToken;
    }


    public function getStoreHash(bool $parse = false): string
    {
        return ($parse ? App::parseEnv($this->_storeHash) : $this->_storeHash);
    }

    public function setStoreHash(?string $storeHash): void
    {
        $this->_storeHash = $storeHash;
    }


    public function getDefaultChannel(bool $parse = false): int|string
    {
        return ($parse ? (int)App::parseEnv($this->_defaultChannel) : $this->_defaultChannel);
    }

    public function setDefaultChannel(int|string|null $id): void
    {
        $this->_defaultChannel = $id;
    }

    public function getWebhookSecret(bool $parse = false): string
    {
        return ($parse ? App::parseEnv($this->_webhookSecret) : $this->_webhookSecret);
    }

    public function setWebhookSecret(?string $webhookSecret): void
    {
        $this->_webhookSecret = $webhookSecret;
    }


    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'clientId';
        $attributes[] = 'clientSecret';
        $attributes[] = 'accessToken';
        $attributes[] = 'storeHash';
        $attributes[] = 'defaultChannel';
        $attributes[] = 'webhookSecret';
        return $attributes;
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
            'defaultChannel' => Craft::t('bigcommerce', 'Default Channel'),
            'webhookSecret' => Craft::t('bigcommerce', 'Webhook Secret'),
            'template' => Craft::t('bigcommerce', 'Product Template'),
        ];
    }

    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'clientId' => fn() => $this->getClientId(),
                    'clientSecret' => fn() => $this->getClientSecret(),
                    'accessToken' => fn() => $this->getAccessToken(),
                    'storeHash' => fn() => $this->getStoreHash(),
                    'webhookSecret' => fn() => $this->getWebhookSecret(),
                    'defaultChannel' => fn() => $this->getDefaultChannel(),
                ],
            ],
        ];
    }
}

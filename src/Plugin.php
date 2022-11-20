<?php
/**
 * BigCommerce plugin for Craft CMS 4.x
 *
 * BigCommerce for Craft CMS
 *
 * @link      https://craftcms.com
 * @copyright Copyright (c) 2022 Pixel & Tonic, Inc
 */

namespace venveo\bigcommerce;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Utilities;
use venveo\bigcommerce\elements\Product;
use venveo\bigcommerce\fields\Products as ProductsField;
use venveo\bigcommerce\handlers\Product as ProductHandler;
use venveo\bigcommerce\models\Settings;
use venveo\bigcommerce\services\Api;
use venveo\bigcommerce\services\Products;
use venveo\bigcommerce\utilities\Sync;
use venveo\bigcommerce\web\twig\CraftVariableBehavior;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 *
 * @author    Pixel & Tonic, Inc
 * @package   BigCommerce
 * @since     1.0
 *
 * @property-read null|array $cpNavItem
 * @property Settings $settings
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    /**
     * @var string
     */
    public string $schemaVersion = '4.0.0';

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public bool $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public static function config(): array
    {
        return [
            'components' => [
                'api' => ['class' => Api::class],
                'products' => ['class' => Products::class],
//                'store' => ['class' => Store::class],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('bigcommerce/settings'));
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $request = Craft::$app->getRequest();

        $this->_registerElementTypes();
        $this->_registerUtilityTypes();
        $this->_registerFieldTypes();
        $this->_registerVariables();

        if (!$request->getIsConsoleRequest()) {
            if ($request->getIsCpRequest()) {
                $this->_registerCpRoutes();
            } else {
                $this->_registerSiteRoutes();
            }
        }

        // Globally register bigcommerce webhooks registry event handlers
//        Registry::addHandler(Topics::PRODUCTS_CREATE, new ProductHandler());
//        Registry::addHandler(Topics::PRODUCTS_DELETE, new ProductHandler());
//        Registry::addHandler(Topics::PRODUCTS_UPDATE, new ProductHandler());
    }

    /**
     * Returns the API service
     *
     * @return Api The API service
     * @throws InvalidConfigException
     * @since 3.0
     */
    public function getApi(): Api
    {
        return $this->get('api');
    }

    /**
     * Returns the ProductData service
     *
     * @return Products The Products service
     * @throws InvalidConfigException
     * @since 3.0
     */
    public function getProducts(): Products
    {
        return $this->get('products');
    }

    /**
     * Returns the API service
     *
     * @return Store The Store service
     * @throws InvalidConfigException
     * @since 3.0
     */
    public function getStore(): Store
    {
        return $this->get('store');
    }

    /**
     * Registers the utilities.
     *
     * @since 3.0
     */
    private function _registerUtilityTypes(): void
    {
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Sync::class;
            }
        );
    }

    /**
     * Register the element types supplied by BigCommerce
     *
     * @since 3.0
     */
    private function _registerElementTypes(): void
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, static function(RegisterComponentTypesEvent $e) {
            $e->types[] = Product::class;
        });
    }

    /**
     * Register BigCommerce’s fields
     *
     * @since 3.0
     */
    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, static function(RegisterComponentTypesEvent $event) {
            $event->types[] = ProductsField::class;
        });
    }

    /**
     * Register BigCommerce twig variables to the main craft variable
     *
     * @since 3.0
     */
    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, static function(Event $event) {
            $variable = $event->sender;
            $variable->attachBehavior('bigcommerce', CraftVariableBehavior::class);
        });
    }

    /**
     * Register the CP routes
     *
     * @since 3.0
     */
    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $session = Plugin::getInstance()->getApi()->getSession();
            $event->rules['bigcommerce'] = ['template' => 'bigcommerce/_index', 'variables' => ['hasSession' => (bool)$session]];

            $event->rules['bigcommerce/products'] = 'bigcommerce/products/product-index';
            $event->rules['bigcommerce/sync-products'] = 'bigcommerce/products/sync';
            $event->rules['bigcommerce/products/<elementId:\d+>'] = 'elements/edit';
            $event->rules['bigcommerce/settings'] = 'bigcommerce/settings';
            $event->rules['bigcommerce/webhooks'] = 'bigcommerce/webhooks/edit';
        });
    }

    /**
     * Registers the Site routes.
     *
     * @since 3.0
     */
    private function _registerSiteRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['bigcommerce/webhook/handle'] = 'bigcommerce/webhook/handle';
        });
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $ret = parent::getCpNavItem();
        $ret['label'] = Craft::t('bigcommerce', 'BigCommerce');

        $session = Plugin::getInstance()->getApi()->getSession();

        if ($session) {
            $ret['subnav']['products'] = [
                'label' => Craft::t('bigcommerce', 'Products'),
                'url' => 'bigcommerce/products',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $ret['subnav']['settings'] = [
                'label' => Craft::t('bigcommerce', 'Settings'),
                'url' => 'bigcommerce/settings',
            ];
        }

        if ($session) {
            if (Craft::$app->getUser()->getIsAdmin()) {
                $ret['subnav']['webhooks'] = [
                    'label' => Craft::t('bigcommerce', 'Webhooks'),
                    'url' => 'bigcommerce/webhooks',
                ];
            }
        }


        return $ret;
    }
}

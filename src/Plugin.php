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
use craft\console\Controller;
use craft\console\controllers\ResaveController;
use craft\events\ConfigEvent;
use craft\events\DefineConsoleActionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Utilities;
use venveo\bigcommerce\elements\Product;
use venveo\bigcommerce\fields\Products as ProductsField;
use venveo\bigcommerce\models\Settings;
use venveo\bigcommerce\services\Addresses;
use venveo\bigcommerce\services\Api;
use venveo\bigcommerce\services\Cart;
use venveo\bigcommerce\services\Customers;
use venveo\bigcommerce\services\Products;
use venveo\bigcommerce\services\Store;
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
    public const PC_PATH_PRODUCT_FIELD_LAYOUTS = 'bigcommerce.productFieldLayout';

    /**
     * @var string
     */
    public string $schemaVersion = '4.0.5'; // For some reason the 2.2+ version of the plugin was at 4.0 schema version

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
                'store' => ['class' => Store::class],
                'customers' => ['class' => Customers::class],
                'cart' => ['class' => Cart::class],
                'addresses' => ['class' => Addresses::class],
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
        $this->_registerResaveCommands();

        if (!$request->getIsConsoleRequest()) {
            if ($request->getIsCpRequest()) {
                $this->_registerCpRoutes();
            } else {
                $this->_registerSiteRoutes();
            }
        }

        Craft::$app->getProjectConfig()->onUpdate(self::PC_PATH_PRODUCT_FIELD_LAYOUTS, function(ConfigEvent $event) {
            $data = $event->newValue;
            $fieldsService = Craft::$app->getFields();

            if (empty($data) || empty($config = reset($data))) {
                $fieldsService->deleteLayoutsByType(Product::class);
                return;
            }

            // Make sure fields are processed
            ProjectConfigHelper::ensureAllFieldsProcessed();

            // Save the field layout
            $layout = FieldLayout::createFromConfig($config);
            $layout->id = $fieldsService->getLayoutByType(Product::class)->id;
            $layout->type = Product::class;
            $layout->uid = key($data);
            $fieldsService->saveLayout($layout, false);

            // Invalidate product caches
            Craft::$app->getElements()->invalidateCachesForElementType(Product::class);
        });
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

    public function getAddresses(): Addresses
    {
        return $this->get('addresses');
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

    public function _registerResaveCommands(): void
    {
        Event::on(ResaveController::class, Controller::EVENT_DEFINE_ACTIONS, static function(DefineConsoleActionsEvent $e) {
            $e->actions['bigcommerce-products'] = [
                'action' => function(): int {
                    /** @var ResaveController $controller */
                    $controller = Craft::$app->controller;
                    return $controller->resaveElements(Product::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves BigCommerce products.',
            ];
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
            $isConnected = true;
            $event->rules['bigcommerce'] = ['template' => 'bigcommerce/_index', 'variables' => ['hasSession' => (bool)$isConnected]];

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
            $event->rules['bigcommerce/customers/register'] = 'bigcommerce/customers/register';
            $event->rules['bigcommerce/customers/login'] = 'bigcommerce/customers/login';
            $event->rules['bigcommerce/customers/save'] = 'bigcommerce/customers/save';
        });
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $ret = parent::getCpNavItem();
        $ret['label'] = Craft::t('bigcommerce', 'BigCommerce');
        $isConnected = true;
//        $isConnected = Plugin::getInstance()->getApi()->getClient()->catalog()->summary()->get();

        if ($isConnected) {
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

        if ($isConnected) {
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

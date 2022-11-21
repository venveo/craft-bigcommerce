<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\elements;

use Craft;
use craft\base\Element;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use venveo\bigcommerce\elements\conditions\products\ProductCondition;
use venveo\bigcommerce\elements\db\ProductQuery;
use venveo\bigcommerce\helpers\Product as ProductHelper;
use venveo\bigcommerce\Plugin;
use venveo\bigcommerce\records\Product as ProductRecord;
use venveo\bigcommerce\web\assets\bigcommercecp\BigCommerceCpAsset;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use Exception;
use yii\base\InvalidConfigException;
use yii\helpers\Html as HtmlHelper;
use yii\web\Response;

/**
 * Product element.
 * @property array $tags
 * @property array $options
 *
 */
class Product extends Element
{
    // BigCommerce Product Statuses
    // -------------------------------------------------------------------------

    /**
     * Craft Statuses
     * @since 3.0
     */
    public const STATUS_LIVE = 'live';
    public const STATUS_BC_DRAFT = 'bcDraft';
    public const STATUS_BC_ARCHIVED = 'bcArchived';

    /**
     * BigCommerce Statuses
     * @since 3.0
     */
    public const BC_STATUS_ACTIVE = 'active';
    public const BC_STATUS_DRAFT = 'draft';
    public const BC_STATUS_ARCHIVED = 'archived';

    /**
     * @var string
     */
    public ?string $bodyHtml = null;

    /**
     * @var ?DateTime
     */
    public ?DateTime $createdAt = null;

    /**
     * @var string
     */
    public ?string $handle = null;

    /**
     * @var array
     */
    private array $_images;

    /**
     * @var array
     */
    private array $_options = [];

    /**
     * @var array
     */
    private array $_metaFields = [];

    /**
     * @var string
     */
    public ?string $productType = null;

    /**
     * @var ?DateTime
     */
    public ?DateTime $publishedAt = null;

    /**
     * @var string
     */
    public ?string $publishedScope = null;

    /**
     * The product ID in the BigCommerce store
     *
     * @var int|null
     */
    public ?int $bcId = null;

    /**
     * @var string
     */
    public string $bcStatus = 'active';

    /**
     * @var array
     */
    private array $_tags = [];

    /**
     * @var string
     */
    public ?string $templateSuffix = null;

    /**
     * @var ?DateTime
     */
    public ?DateTime $updatedAt = null;

    /**
     * @var array
     */
    private array $_variants;

    /**
     * @var string
     */
    public ?string $vendor = null;

    /**
     * @inheritdoc
     */
    public static function searchableAttributes(): array
    {
        return array_merge(parent::searchableAttributes(), [
            'bodyHtml',
            'handle',
            'vendor',
            'productType',
            'tags',
            'options',
            'metaFields',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_LIVE => Craft::t('bigcommerce', 'Live'),
            self::STATUS_BC_DRAFT => ['label' => Craft::t('bigcommerce', 'Draft in BigCommerce'), 'color' => 'orange'],
            self::STATUS_BC_ARCHIVED => ['label' => Craft::t('bigcommerce', 'Archived in BigCommerce'), 'color' => 'red'],
            self::STATUS_DISABLED => Craft::t('app', 'Disabled'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        $status = parent::getStatus();

        if ($status === self::STATUS_ENABLED) {
            return match ($this->bcStatus) {
                self::BC_STATUS_DRAFT => self::STATUS_BC_DRAFT,
                self::BC_STATUS_ARCHIVED => self::STATUS_BC_ARCHIVED,
                default => self::STATUS_LIVE,
            };
        }

        return $status;
    }

    /**
     * @param array|string $tags
     * @return void
     */
    public function setTags(array|string $tags): void
    {
        if (is_string($tags)) {
            $tags = StringHelper::split($tags);
        }

        $this->tags = $tags;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->_tags ?? [];
    }

    /**
     * @param string|array $value
     * @return void
     */
    public function setImages(string|array $value): void
    {
        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        $this->_images = $value;
    }

    /**
     * @return array
     */
    public function getImages(): array
    {
        return $this->_images ?? [];
    }

    /**
     * @param string|array $value
     * @return void
     */
    public function setOptions(string|array $value): void
    {
        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        $this->_options = $value;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options ?? [];
    }

    /**
     * @param string|array $value
     * @return void
     */
    public function setMetaFields(string|array $value): void
    {
        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        $this->_metaFields = $value;
    }

    /**
     * @return array
     */
    public function getMetaFields(): array
    {
        return $this->_metaFields ?? [];
    }

    /**
     * @param string|array $value
     * @return void
     */
    public function setVariants(string|array $value): void
    {
        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        $this->_variants = $value;
    }

    /**
     * @return array
     */
    public function getVariants(): array
    {
        return $this->_variants ?? [];
    }

    /**
     * Gets the cheapest variant.
     *
     * @return array
     */
    public function getCheapestVariant(): array
    {
        return collect($this->getVariants())->sortBy('price')->first();
    }

    /**
     * Gets the first variant which is BigCommerce's default variant.
     *
     * @return array
     */
    public function getDefaultVariant(): array
    {
        return collect($this->getVariants())->first();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Product');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'BigCommerce product');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'BigCommerce Products');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'BigCommerce products');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'bigcommerce-products';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getBigCommerceStatusHtml(): string
    {
        $color = match ($this->bcStatus) {
            'active' => 'green',
            'archived' => 'red',
            default => 'orange', // takes care of draft
        };
        return "<span class='status $color'></span>" . StringHelper::titleize($this->bcStatus);
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('bigcommerce/products');
    }

    /**
     *
     * @return string|null
     */
    public function getBigCommerceEditUrl(): ?string
    {
        return Plugin::getInstance()->getStore()->getUrl("manage/products/edit/{$this->bcId}");
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        return Plugin::getInstance()->getSettings()->uriFormat;
    }

    /**
     * @inheritdoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('bigcommerce', 'Products'),
                'url' => 'bigcommerce/products',
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    /**
     * @inerhitdoc
     */
    public function previewTargets(): array
    {
        if ($uriFormat = Plugin::getInstance()->getSettings()->uriFormat) {
            return [[
                'urlFormat' => $uriFormat,
            ]];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    protected function route(): array|string|null
    {
        if (!$this->previewing && $this->getStatus() != self::STATUS_LIVE) {
            return null;
        }

        $settings = Plugin::getInstance()->getSettings();

        if ($settings->uriFormat) {
            return [
                'templates/render', [
                    'template' => $settings->template,
                    'variables' => [
                        'product' => $this,
                    ],
                ],
            ];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        if (!isset($this->title) || trim($this->title) === '') {
            return Craft::t('bigcommerce', 'Untitled product');
        }

        return null;
    }

    /**
     * @inheritdoc
     * @return ProductQuery The newly created [[ProductQuery]] instance.
     */
    public static function find(): ProductQuery
    {
        return new ProductQuery(static::class);
    }

    /**
     * @inheritdoc
     * @return ProductCondition
     * @throws InvalidConfigException
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ProductCondition::class, [static::class]);
    }

    /**
     * @inheritDoc
     */
    protected function metaFieldsHtml(bool $static): string
    {
        $fields[] = parent::metaFieldsHtml($static);
        return implode("\n", $fields);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->fields->getLayoutByType(Product::class);
    }

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(bool $static): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        Craft::$app->getView()->registerAssetBundle(BigCommerceCpAsset::class);
        $productCard = ProductHelper::renderCardHtml($this);
        return $productCard . parent::getSidebarHtml($static);
    }

    /**
     * @inerhitdoc
     */
    public static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('bigcommerce', 'All products'),
                'criteria' => [
                ],
                'defaultSort' => ['id', 'desc'],
            ],
        ];
    }

    /**
     * @param bool $isNew
     * @return void
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = ProductRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid product ID: ' . $this->id);
            }
        } else {
            $record = new ProductRecord();
            $record->id = $this->id;
        }

        $record->bcId = $this->bcId;

        // We want to always have the same date as the element table, based on the logic for updating these in the element service i.e re-saving
        $record->dateUpdated = $this->dateUpdated;
        $record->dateCreated = $this->dateCreated;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @return array
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'bcId' => Craft::t('bigcommerce', 'BigCommerce ID'),
            'createdAt' => Craft::t('bigcommerce', 'Created At'),
            'handle' => Craft::t('bigcommerce', 'Handle'),
            // TODO: Support images
            // 'images' => Craft::t('bigcommerce', 'Images'),
            'options' => Craft::t('bigcommerce', 'Options'),
            'productType' => Craft::t('bigcommerce', 'Product Type'),
            'publishedAt' => Craft::t('bigcommerce', 'Published At'),
            'publishedScope' => Craft::t('bigcommerce', 'Published Scope'),
            'bcStatus' => Craft::t('bigcommerce', 'BigCommerce Status'),
            'tags' => Craft::t('bigcommerce', 'Tags'),
            'updatedAt' => Craft::t('bigcommerce', 'Updated At'),
            'variants' => Craft::t('bigcommerce', 'Variants'),
            'vendor' => Craft::t('bigcommerce', 'Vendor'),
            'bigcommerceEdit' => Craft::t('bigcommerce', 'BigCommerce Edit'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'bcId',
            'bcStatus',
            'handle',
            'productType',
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $sortOptions = parent::defineSortOptions();

        $sortOptions['title'] = [
            'label' => Craft::t('app', 'Title'),
            'orderBy' => 'bigcommerce_productdata.title',
            'defaultDir' => SORT_DESC,
        ];

        $sortOptions['bcId'] = [
            'label' => Craft::t('bigcommerce', 'BigCommerce ID'),
            'orderBy' => 'bigcommerce_productdata.bcId',
            'defaultDir' => SORT_DESC,
        ];

        $sortOptions['bcStatus'] = [
            'label' => Craft::t('bigcommerce', 'BigCommerce Status'),
            'orderBy' => 'bigcommerce_productdata.bcStatus',
            'defaultDir' => SORT_DESC,
        ];

        return $sortOptions;
    }

    /**
     * @param string $attribute
     * @return string
     * @throws InvalidConfigException
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'bigcommerceEdit':
                return HtmlHelper::a('', $this->getBigCommerceEditUrl(), ['target' => '_blank', 'data' => ['icon' => 'external']]);
            case 'bcStatus':
                return $this->getBigCommerceStatusHtml();
            case 'bcId':
                return $this->$attribute;
            case 'options':
                return collect($this->getOptions())->map(function ($option) {
                    return HtmlHelper::tag('span', $option['name'], [
                        'title' => $option['name'] . ' option values: ' . collect($option['values'])->join(', '),
                    ]);
                })->join(',&nbsp;');
            case 'tags':
                return collect($this->getTags())->map(function ($tag) {
                    return HtmlHelper::tag('div', $tag, [
                        'style' => 'margin-bottom: 2px;',
                        'class' => 'token',
                    ]);
                })->join('&nbsp;');
            case 'variants':
                return collect($this->getVariants())->pluck('title')->map(fn($title) => StringHelper::toTitleCase($title))->join(',&nbsp;');
            default:
            {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        $path = sprintf('bigcommerce/products/%s', $this->getCanonicalId());
        return UrlHelper::cpUrl($path);
    }

    /**
     * @return string
     */
    public function getBigCommerceUrl(array $params = []): string
    {
        return Plugin::getInstance()->getStore()->getUrl("products/{$this->handle}", $params);
    }

    /**
     * @return string
     * @see self::getLink()
     * @see self::getBigCommerceUrl()
     */
    public function getBigCommerceLink(?string $text = null, array $attributes = []): string
    {
        $link = HtmlHelper::a($text ?: $this->title, $this->getBigCommerceUrl(), $attributes);
        return Template::raw($link);
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        // We normally cant delete bigcommerce elements, but we can if we are in a draft state.
        if ($this->getIsDraft()) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function hasRevisions(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        $labels['bcId'] = Craft::t('bigcommerce', 'BigCommerce ID');
        $labels['bodyHtml'] = Craft::t('bigcommerce', 'Body HTML');
        $labels['createdAt'] = Craft::t('bigcommerce', 'Created at');
        $labels['handle'] = Craft::t('bigcommerce', 'Handle');
        $labels['images'] = Craft::t('bigcommerce', 'Images');
        $labels['options'] = Craft::t('bigcommerce', 'Options');
        $labels['productType'] = Craft::t('bigcommerce', 'Product Type');
        $labels['publishedAt'] = Craft::t('bigcommerce', 'Published at');
        $labels['publishedScope'] = Craft::t('bigcommerce', 'Published Scope');
        $labels['tags'] = Craft::t('bigcommerce', 'Tags');
        $labels['bcStatus'] = Craft::t('bigcommerce', 'Status');
        $labels['templateSuffix'] = Craft::t('bigcommerce', 'Template Suffix');
        $labels['updatedAt'] = Craft::t('bigcommerce', 'Updated at');
        $labels['variants'] = Craft::t('bigcommerce', 'Variants');
        $labels['vendor'] = Craft::t('bigcommerce', 'Vendor');
        $labels['metaFields'] = Craft::t('bigcommerce', 'Meta Fields');

        return $labels;
    }
}

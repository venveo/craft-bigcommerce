<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\elements\db;

use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use venveo\bigcommerce\elements\Product;

/**
 * ProductQuery represents a SELECT SQL statement for entries in a way that is independent of DBMS.
 *
 * @method Product[]|array all($db = null)
 * @method Product|array|null one($db = null)
 * @method Product|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ProductQuery extends ElementQuery
{
    /**
     * @var mixed The BigCommerce product ID(s) that the resulting products must have.
     */
    public mixed $bcId = null;

    public mixed $bcStatus = null;
    public mixed $handle = null;
    public mixed $productType = null;
    public mixed $publishedScope = null;
    public mixed $tags = null;
    public mixed $vendor = null;
    public mixed $images = null;
    public mixed $options = null;

    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = ['bigcommerce_productdata.bcId' => SORT_ASC];

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = 'live';
        }

        parent::__construct($elementType, $config);
    }

    /**
     * Narrows the query results based on the BigCommerce product type
     */
    public function productType(mixed $value): self
    {
        $this->productType = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the BigCommerce status
     */
    public function bcStatus(mixed $value): self
    {
        $this->bcStatus = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the BigCommerce product handle
     */
    public function handle(mixed $value): self
    {
        $this->handle = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the BigCommerce product vendor
     */
    public function vendor(mixed $value): self
    {
        $this->vendor = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the BigCommerce product tags
     */
    public function tags(mixed $value): self
    {
        $this->tags = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the BigCommerce product ID
     */
    public function bcId(mixed $value): ProductQuery
    {
        $this->bcId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'live'` _(default)_ | that are live (enabled in Craft, with an Active bigcommerce Status).
     * | `'bcDraft'` | that are enabled with a Draft bigcommerce Status.
     * | `'bcArchived'` | that are enabled, with an Archived bigcommerce Status.
     * | `'disabled'` | that are disabled in Craft (Regardless of BigCommerce Status).
     * | `['live', 'bcDraft']` | that are live or bigcommerce draft.
     *
     * ---
     *
     * ```twig
     * {# Fetch disabled {elements} #}
     * {% set {elements-var} = {twig-method}
     *   .status('disabled')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch disabled {elements}
     * ${elements-var} = {element-class}::find()
     *     ->status('disabled')
     *     ->all();
     * ```
     */
    public function status(array|string|null $value): ProductQuery
    {
        parent::status($value);
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        $res = match ($status) {
            strtolower(Product::STATUS_LIVE) => [
                'elements.enabled' => true,
                'elements_sites.enabled' => true,
                'bigcommerce_productdata.bcStatus' => 'active',
            ],
            strtolower(Product::STATUS_BC_DRAFT) => [
                'elements.enabled' => true,
                'elements_sites.enabled' => true,
                'bigcommerce_productdata.bcStatus' => 'draft',
            ],
            strtolower(Product::STATUS_BC_ARCHIVED) => [
                'elements.enabled' => true,
                'elements_sites.enabled' => true,
                'bigcommerce_productdata.bcStatus' => 'archived',
            ],
            default => parent::statusCondition($status),
        };

        return $res;
    }

    /**
     * @inheritdoc
     * @throws QueryAbortedException
     */
    protected function beforePrepare(): bool
    {
        if ($this->bcId === []) {
            return false;
        }

        $productTable = 'bigcommerce_products';
        $productDataTable = 'bigcommerce_productdata';

        // join standard product element table that only contains the bcId
        $this->joinElementTable($productTable);

        $productDataJoinTable = [$productDataTable => "{{%$productDataTable}}"];
        $this->query->innerJoin($productDataJoinTable, "[[$productDataTable.bcId]] = [[$productTable.bcId]]");
        $this->subQuery->innerJoin($productDataJoinTable, "[[$productDataTable.bcId]] = [[$productTable.bcId]]");

        $this->query->select([
            'bigcommerce_products.bcId',
            'bigcommerce_productdata.bcStatus',
            'bigcommerce_productdata.handle',
            'bigcommerce_productdata.productType',
            'bigcommerce_productdata.bodyHtml',
            'bigcommerce_productdata.createdAt',
            'bigcommerce_productdata.publishedAt',
            'bigcommerce_productdata.publishedScope',
            'bigcommerce_productdata.tags',
            'bigcommerce_productdata.templateSuffix',
            'bigcommerce_productdata.updatedAt',
            'bigcommerce_productdata.vendor',
            'bigcommerce_productdata.metaFields',
            'bigcommerce_productdata.images',
            'bigcommerce_productdata.options',
            'bigcommerce_productdata.variants',
        ]);

        if (isset($this->bcId)) {
            $this->subQuery->andWhere(['bigcommerce_productdata.bcId' => $this->bcId]);
        }

        if (isset($this->productType)) {
            $this->subQuery->andWhere(['bigcommerce_productdata.productType' => $this->productType]);
        }

        if (isset($this->bcStatus)) {
            $this->subQuery->andWhere(Db::parseParam('bigcommerce_productdata.bcStatus', $this->bcStatus));
        }

        if (isset($this->handle)) {
            $this->subQuery->andWhere(Db::parseParam('bigcommerce_productdata.handle', $this->handle));
        }

        if (isset($this->vendor)) {
            $this->subQuery->andWhere(Db::parseParam('bigcommerce_productdata.vendor', $this->vendor));
        }

        if (isset($this->tags)) {
            $this->subQuery->andWhere(Db::parseParam('bigcommerce_productdata.tags', $this->tags));
        }

        return parent::beforePrepare();
    }
}

<?php

namespace venveo\bigcommerce\elements\conditions\products;

use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\StringHelper;
use venveo\bigcommerce\elements\db\ProductQuery;
use venveo\bigcommerce\elements\Product;

class BigCommerceStatusConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return \Craft::t('bigcommerce', 'BigCommerce Status');
    }

    /**
     * @inheritDoc
     */
    protected function options(): array
    {
        return [
            ['value' => Product::BC_STATUS_ACTIVE, 'label' => StringHelper::titleize(Product::BC_STATUS_ACTIVE)],
            ['value' => Product::BC_STATUS_DRAFT, 'label' => StringHelper::titleize(Product::BC_STATUS_DRAFT)],
            ['value' => Product::BC_STATUS_ARCHIVED, 'label' => StringHelper::titleize(Product::BC_STATUS_ARCHIVED)],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['bcStatus'];
    }

    /**
     * @inheritDoc
     * @param Product $element
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->bcStatus);
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var ProductQuery $query */
        $query->bcStatus($this->paramValue());
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\helpers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use venveo\bigcommerce\elements\Product as ProductElement;
use venveo\bigcommerce\records\ProductData;

/**
 * BigCommerce Product Helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Product
{
    /**
     * @return string
     */
    public static function renderCardHtml(ProductElement $product): string
    {
        $formatter = Craft::$app->getFormatter();

        $title = Html::tag('h3', $product->title, [
            'class' => 'pec-title',
        ]);

        $subTitle = Html::tag('p', $product->productType, [
            'class' => 'pec-subtitle',
        ]);
        $externalLink = Html::tag('div', '&nbsp;', [
            'class' => 'pec-external-icon',
            'data' => [
                'icon' => 'external',
            ],
        ]);
        $cardHeader = Html::a($title . $subTitle . $externalLink, $product->getBigCommerceEditUrl(), [
            'style' => '',
            'class' => 'pec-header',
            'target' => '_blank',
            'title' => Craft::t('bigcommerce', 'Open in BigCommerce'),
        ]);

        $hr = Html::tag('hr', '', [
            'class' => '',
        ]);

        $meta = [];

        $meta[Craft::t('bigcommerce', 'Handle')] = $product->handle;
        $meta[Craft::t('bigcommerce', 'Status')] = $product->getBigCommerceStatusHtml();

        // Options
        if (count($product->getOptions()) > 0) {
            $meta[Craft::t('bigcommerce', 'Options')] = collect($product->options)
                ->map(function($option) {
                    return Html::tag('span', $option['name'], [
                        'title' => Craft::t('bigcommerce', '{name} option values: {values}', [
                            'name' => $option['name'],
                            'values' => implode(', ', $option['values']),
                        ]),
                    ]);
                })
                ->join(', ');
        }

        // Variants
        if (count($product->getVariants()) > 0) {
            $meta[Craft::t('bigcommerce', 'Variants')] = collect($product->getVariants())
                ->pluck('sku')
                ->join(', ');
        }

        $meta[Craft::t('bigcommerce', 'BigCommerce ID')] = Html::tag('code', (string)$product->bcId);

        $meta[Craft::t('bigcommerce', 'Created at')] = $formatter->asDatetime($product->createdAt, Formatter::FORMAT_WIDTH_SHORT);
        $meta[Craft::t('bigcommerce', 'Updated at')] = $formatter->asDatetime($product->updatedAt, Formatter::FORMAT_WIDTH_SHORT);

        $metadataHtml = Cp::metadataHtml($meta);

        $spinner = Html::tag('div', '', [
            'class' => 'spinner',
            'hx' => [
                'indicator',
            ],
        ]);

        // This is the date updated in the database which represents the last time it was updated from a BigCommerce webhook or sync.
        /** @var ProductData $productData */
        $productData = ProductData::find()->where(['bcId' => $product->bcId])->one();
        $dateUpdated = DateTimeHelper::toDateTime($productData->dateUpdated);
        $now = new \DateTime();
        $diff = $now->diff($dateUpdated);
        $duration = DateTimeHelper::humanDuration($diff, false);
        $footer = Html::tag('div', 'Updated ' . $duration . ' ago.' . $spinner, [
            'class' => 'pec-footer',
        ]);

        return Html::tag('div', $cardHeader . $hr . $metadataHtml . $footer, [
            'class' => 'meta proxy-element-card',
            'id' => 'pec-' . $product->id,
            'hx' => [
                'get' => UrlHelper::actionUrl('bigcommerce/products/render-card-html', [
                    'id' => $product->id,
                ]),
                'swap' => 'outerHTML',
                'trigger' => 'every 15s',
            ],
        ]);
    }
}

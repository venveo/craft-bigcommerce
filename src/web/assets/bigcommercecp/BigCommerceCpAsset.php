<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\web\assets\bigcommercecp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\htmx\HtmxAsset;
use craft\web\View;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the Control Panel
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class BigCommerceCpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
            JqueryAsset::class,
            HtmxAsset::class,
        ];

        $this->css[] = 'css/bigcommercecp.css';
        $this->js[] = 'bigcommercecp.js';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('bigcommerce', [
            ]);
        }
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\utilities;

use Craft;
use craft\base\Utility;

/**
 * Sync class offers the BigCommerce Sync utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Sync extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'BigCommerce Sync');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'bigcommerce-sync';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@vendor') . '/craftcms/bigcommerce/src/icon-mask.svg';
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        return $view->renderTemplate('bigcommerce/utilities/_sync.twig');
    }
}

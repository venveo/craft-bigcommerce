<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use venveo\bigcommerce\Plugin;
use yii\console\ExitCode;

/**
 * Allows you to sync BigCommerce data
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SyncController extends Controller
{
    public $defaultAction = 'products';

    public function actionAll()
    {
        $this->_syncProducts();
    }

    /**
     * Reset Commerce data.
     */
    public function actionProducts(): int
    {
        $this->_syncProducts();
        return ExitCode::OK;
    }

    private function _syncProducts(): void
    {
        $this->stdout('Syncing BigCommerce products…' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        Plugin::getInstance()->getProducts()->syncAllProducts();
        $this->stdout('Finished' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
    }
}

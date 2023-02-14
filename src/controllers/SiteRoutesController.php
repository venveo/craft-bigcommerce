<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use BigCommerce\ApiV3\ResourceModels\Channel\Channel;
use BigCommerce\ApiV3\ResourceModels\Channel\ChannelSite;
use craft\web\Controller;
use venveo\bigcommerce\Plugin;
use yii\web\Response;

/**
 * The SettingsController handles modifying and saving the general settings.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SiteRoutesController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAdmin(false);
        return parent::beforeAction($action);
    }

    /**
     * Display a form to allow an administrator to update plugin settings.
     *
     * @return Response
     */
    public function actionIndex(int $channelId)
    {
        $channel = Plugin::getInstance()->api->getClient()->channel($channelId)->get()->getChannel();
        $site = Plugin::getInstance()->api->getClient()->channel($channelId)->site()->get()->getSite();
        $routesService = Plugin::getInstance()->api->getClient()->site($site->id)->routes();

        $routes = $routesService->getAll();

        return $this->renderTemplate('bigcommerce/channels/site/routes/_index.twig',
            ['site' => $site, 'channel' => $channel, 'routes' => $routes]);
    }
}

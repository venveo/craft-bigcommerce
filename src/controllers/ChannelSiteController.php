<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use BigCommerce\ApiV3\ResourceModels\Channel\Channel;
use BigCommerce\ApiV3\ResourceModels\Channel\ChannelSite;
use Craft;
use craft\web\Controller;
use venveo\bigcommerce\Plugin;
use yii\web\Response;

/**
 * The SettingsController handles modifying and saving the general settings.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ChannelSiteController extends Controller
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
        return $this->renderTemplate('bigcommerce/channels/site/_index.twig',
            ['site' => $site, 'channel' => $channel]);
    }


    public function actionEdit(int $channelId = null, Channel $channel = null)
    {
        $client = Plugin::getInstance()->api->getClient();
        if ($channel) {
            $channel = $channel->jsonSerialize();
        }
        $statusOptions = [
            'active' => 'Active',
            'prelaunch' => 'Prelaunch',
            'inactive' => 'Inactive',
            'connected' => 'Connected',
            'disconnected' => 'Disconnected',
            'archived' => 'Archived'
        ];
        $site = null;
        if ($channelId) {
            $channelRequest = $client->channel($channelId);
            $channel = $channelRequest->get()->getChannel()->jsonSerialize();
            try {
                $site = $channelRequest->site()->get()->getSite()->jsonSerialize();
            } catch (\Exception $exception) {
                $site = [];
            }
        }
        return $this->renderTemplate('bigcommerce/channels/_edit.twig', [
            'statusOptions' => $statusOptions,
            'channel' => $channel,
            'site' => $site
        ]);
    }

    public function actionSave()
    {
        $channelId = $this->request->getRequiredBodyParam('channelId');
        $url = $this->request->getRequiredBodyParam('url');
        $client = Plugin::getInstance()->api->getClient();

        $channelRequest = $client->channel($channelId);
        $channel = $channelRequest->get()->getChannel();
        $isNew = false;
        try {
            $site = $channelRequest->site()->get()->getSite();
        } catch (\Exception $exception) {
            $site = new ChannelSite();
            $site->channel_id = $channel->id;
            $isNew = true;
        }
        $site->url = $url;
        if ($isNew) {
            $site = $client->channel($channelId)->site()->create($site)->getSite();
        } else {
            $site = $client->channel($channelId)->site()->update($site)->getSite();
        }
        return $this->asSuccess('Site saved.');
    }
}

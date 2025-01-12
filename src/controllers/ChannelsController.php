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
class ChannelsController extends Controller
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
    public function actionIndex()
    {
        $connected = false;
        try {
            $channels = Plugin::getInstance()->api->getClient()->channels()->getAll()->getChannels();
            $connected = true;
        } catch (\Exception $exception) {
            $channels = [];
        }
        return $this->renderTemplate('bigcommerce/channels/_index',
            ['channels' => $channels, 'connected' => $connected]);
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
        $channelName = $this->request->getRequiredBodyParam('name');
        $channelId = $this->request->getBodyParam('id');
        $status = $this->request->getBodyParam('status');
        if ($channelId) {
            $channel = Plugin::getInstance()->api->getClient()->channel($channelId)->get()->getChannel();
        } else {
            $channel = new Channel();
            $channel->type = 'storefront';
            $channel->platform = 'custom';
            $channel->is_visible = true;
            $channel->is_listable_from_ui = true;
        }
        $channel->name = $channelName;
        $channel->status = $status;

        try {
            Plugin::getInstance()->api->getClient()->channels()->create($channel);
        } catch (\Exception $exception) {
            Craft::error($exception->getMessage());
            Craft::error($exception->getTraceAsString());
            return $this->asFailure('Failed to save channel. Please check your plan limits.', [],
                ['channel' => $channel]);
        }
        return $this->asSuccess('Channel saved');
    }
}

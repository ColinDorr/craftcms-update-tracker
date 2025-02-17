<?php

declare(strict_types=1);

namespace colindorr\craftcmsupdatetracker;

use Craft;
use craft\base\Plugin;
use craft\base\Model;
use craft\web\Application;
use colindorr\craftcmsupdatetracker\models\Settings;

use colindorr\craftcmsupdatetracker\services\UpdateNotificationServices;
use colindorr\craftcmsupdatetracker\services\TrackUpdatesService;

/**
 * update-tracker plugin
 *
 * @method static TrackUpdates getInstance()
 * @method Settings getSettings()
 */
class UpdateTracker extends Plugin
{
    public static $plugin_handle = "update-tracker";
    public $hasCpSettings = true;
    public $schemaVersion = '1.0.0';
    
    public function init(): void
    {
        parent::init();

        Craft::$app->on(Application::EVENT_INIT, function () {
            UpdateNotificationServices::checkForUpdatesAndSendEmail();
        });

        if (Craft::$app->request->getIsConsoleRequest()) {
            // Set the correct namespace for console commands
            $this->controllerNamespace = 'colindorr\craftcmsupdatetracker\console';
        }
    }

    public function iconPath(): ?string
    {
        return __DIR__ . '/icon.svg';
    }
    
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(self::$plugin_handle . "/_settings.twig", [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }
}

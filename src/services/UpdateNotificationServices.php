<?php

namespace colindorr\craftcmsupdatetracker\services;

use Craft;
use DateTime;
use DateInterval;
use craft\mail\Message;
use craft\base\Component;
use colindorr\craftcmsupdatetracker\helpers\VersionHelper;
use colindorr\craftcmsupdatetracker\models\Settings;
use colindorr\craftcmsupdatetracker\helpers\SettingsHelpers;
use colindorr\craftcmsupdatetracker\services\TrackUpdatesService;

class UpdateNotificationServices extends Component
{
    public static string $email = "";
    public static string $version_type = "";
    public static string $day_of_week = "";
    public static string $frequency = "";
    public static int $next_planned_email_timestamp = 0;
    public static string $plugin_handle = "update-tracker";

    public static array $available_update = [];
    public static array $up_to_date = [];

    // Initialize public variables
    public static function start(): void
    {
        $pluginSettings = Craft::$app->plugins->getPlugin(self::$plugin_handle)->settings;

        self::$email = $pluginSettings->email ?? '';
        self::$version_type = $pluginSettings->version_type ?? 'Minor';
        self::$day_of_week = $pluginSettings->day_of_week ?? '';
        self::$frequency = $pluginSettings->frequency ?? '';
        self::$next_planned_email_timestamp = $pluginSettings->next_planned_email_timestamp ?? 0;

        // Get update info (you would fetch this from your actual update check)
        $updateInfo = TrackUpdatesService::getUpdateInfo();
        self::$available_update = $updateInfo['available_update'] ?? [];
        self::$up_to_date = $updateInfo['up_to_date'] ?? [];
    }

    // Check for updates and send emails
    public static function checkForUpdatesAndSendEmail( bool $forced = false): array
    {
        $pluginSettings = Craft::$app->plugins->getPlugin(self::$plugin_handle)->settings;
        self::start();

        $invalid_checks = [];

        if (!self::checkAllowedUpdateType() && !$forced) {
            $invalid_checks[] = "Blocked by selected Major|Minor|Patch type";
        }

        if (!self::checkAllowedDayOfWeek() && !$forced) {
            $invalid_checks[] = "Blocked by selected Day";
        }

        if (!self::checkAllowedFrequency() && !$forced) {
            $invalid_checks[] = "Blocked by selected ferquency";
        }

        if (count($invalid_checks) === 0 || $forced ){
            self::updateLastEmailSentTimestamp();
            EmailServices::sendEmail();
            return ["Mail was send"];
        } else {
            return $invalid_checks;
        }
    }

    private static function checkAllowedUpdateType(): bool
    {
        $types = [
            "Critical" => 0,
            "Major" => 1,
            "Minor" => 2,
            "Patch" => 3,
            "No update" => 4
        ];

        $updateType = self::determineUpdateType(self::$available_update);

        if ($updateType && isset($types[$updateType]) && $types[$updateType] <= $types[self::$version_type]) {
            return true;
        }

        return false;
    }

    private static function checkAllowedDayOfWeek(): bool
    {
        $currentDay = date('l');
        return self::$day_of_week === $currentDay || self::$frequency === "Daily";
    }

    private static function getGetDates(): array
    {
        // Add logic to check frequency if needed
        $currentDay = new DateTime('today 00:01'); // Base time
        $currentDayTimestamp = $currentDay->getTimestamp();

        // Define intervals using DateInterval for better clarity and reusability
        $intervals = [
            'Daily' => (clone $currentDay)->modify('+1 day')->getTimestamp(),
            'Weekly' => (clone $currentDay)->modify('+1 week')->getTimestamp(),
            'Bi-Weekly' => (clone $currentDay)->modify('+2 weeks')->getTimestamp(),
            'Monthly' => (clone $currentDay)->modify('+1 month')->getTimestamp(),
        ];

        return [
            "current" => $currentDayTimestamp,
            "selected_frequency" => $intervals[self::$frequency]
        ];
    }

    private static function checkAllowedFrequency(): bool
    {
        $dates = self::getGetDates();
        $current = $dates["current"];
        return $current >= self::$next_planned_email_timestamp;
    }

    // Determine the type of update (Minor, Major, or Critical)
    private static function determineUpdateType(array $available_update): ?string
    {
        if (count($available_update) === 0) {
            return null;
        }
        // Ensure the available_update array has elements before trying to access them
        if (isset($available_update[0])) {
            if (preg_match('/\[(.*?)\]/', $available_update[0], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // Update the last email sent date
    private static function updateLastEmailSentTimestamp(): void
    {
        $dates = self::getGetDates();
        $current = $dates["current"];
        $selected_frequency = $dates["selected_frequency"];

        $pluginSettings = Craft::$app->plugins->getPlugin(self::$plugin_handle)->settings;
        $pluginSettings["next_planned_email_timestamp"] = $selected_frequency;
        Craft::$app->plugins->savePluginSettings(Craft::$app->plugins->getPlugin(self::$plugin_handle), $pluginSettings->toArray());
    }
}

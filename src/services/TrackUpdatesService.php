<?php

declare(strict_types=1);

namespace colindorr\craftcmsupdatetracker\services;

use Craft;
use craft\helpers\App;
use colindorr\craftcmsupdatetracker\helpers\VersionHelper;
use Symfony\Component\Yaml\Yaml;

class TrackUpdatesService
{
    public static function getCraftUpdateInfo(): array
    {
        $craftVersion = Craft::$app->getVersion();
        $updateInfo = Craft::$app->getUpdates()->getUpdates();
        $latestCraftVersion = ! empty($updateInfo->cms->releases) ? $updateInfo->cms->releases[0]->version : $craftVersion;
        $craftLicenseKey = VersionHelper::getCraftLicenseKey();
        $is_abandoned = isset($updateInfo->cms->abandoned) ? $updateInfo->cms->abandoned : null;
        $is_expired = isset($updateInfo->cms->status) ? ($updateInfo->cms->status === "eligible" ? false : null) : null;
        $containsCritical = isset($updateInfo->cms->releases) && 
            array_reduce($updateInfo->cms->releases, function($carry, $release) {
                return $carry || (isset($release->critical) && $release->critical === true);
            }, false);

        return [(object) [
            'type' => 'craft',
            'handle' => 'craftcms',
            'name' => 'Craft CMS',
            'version' => $craftVersion,
            'update_available' => $latestCraftVersion !== $craftVersion,
            'update_version' => $latestCraftVersion !== $craftVersion ? $latestCraftVersion : null,
            'update_type' => VersionHelper::compareVersions($latestCraftVersion, $craftVersion),
            'license_key' => $craftLicenseKey,
            'is_expired' => $is_expired,
            'is_critical' => $containsCritical,
            'is_abandoned' => $is_abandoned,
        ]];
    }

    public static function getPluginUpdateInfo(): array
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();
        $updateInfo = Craft::$app->getUpdates()->getUpdates();

        // Load plugins from project.yaml
        $projectYamlFilePath = Craft::getAlias('@config/project/project.yaml');
        $projectYamlPlugins = [];
        if (file_exists($projectYamlFilePath)) {
            $parsedData = Yaml::parseFile($projectYamlFilePath);
            $projectYamlPlugins = $parsedData['plugins'] ?? [];
        }

        // Iterate through all plugins and add plugin objects to the $versions array
        $plugins_updates = [];
        // dd($plugins);
        foreach ($plugins as $handle => $plugin) {
            $plugin_handle = $plugin->id; // craft 3 - updated to use $plugin_data
            $plugin_data = $updateInfo->plugins[$plugin_handle] ?? null; // Retrieve plugin data

            // Check if plugin data exists, then proceed
            if ($plugin_data) {
                $latestPluginVersion = isset($plugin_data->releases[0]->version)
                    ? $plugin_data->releases[0]->version
                    : $plugin_data->version;
                
                // Retrieve plugin status and abandonment status
                $is_abandoned = $plugin_data->abandoned ?? null;
                $is_expired = $plugin_data->status === "eligible" ? false : null;
                
                // Check if there are any critical releases
                $containsCritical = isset($plugin_data->releases) && 
                    array_reduce($plugin_data->releases, function($carry, $release) {
                        return $carry || (isset($release->critical) && $release->critical === true);
                    }, false);

                // Get plugin license key from project YAML (handling Craft 5)
                $pluginLicenseKey = $projectYamlPlugins[$plugin_handle]['licenseKey'] ?? null;
                if ($pluginLicenseKey && $pluginLicenseKey[0] === "$") {
                    $pluginLicenseKey = App::env(ltrim($pluginLicenseKey, '$')) ?? null;
                }

                // Add plugin update information to the list
                $plugins_updates[] = (object) [
                    'type' => 'plugin',
                    'handle' => $plugin_handle,
                    'name' => $plugin_data->name, // updated to $plugin_data
                    'version' => $plugin_data->version, // updated to $plugin_data
                    'update_available' => $latestPluginVersion !== $plugin_data->version, // compare the version
                    'update_version' => $latestPluginVersion !== $plugin_data->version ? $latestPluginVersion : null,
                    'update_type' => VersionHelper::compareVersions($latestPluginVersion, $plugin_data->version),
                    'license_key' => $pluginLicenseKey,
                    'is_expired' => $is_expired,
                    'is_critical' => $containsCritical,
                    'is_abandoned' => $is_abandoned,
                ];
            } else {
                // If plugin data doesn't exist, log or handle the absence of the plugin
                \Craft::error("Plugin '$plugin_handle' not found in update info.", __METHOD__);
            }

        }
        return $plugins_updates;
    }

    public static function getUpdateInfo(): array
    {
        $craft_updates = self::getCraftUpdateInfo();
        $plugin_updates = self::getPluginUpdateInfo();
        $updates = array_merge($craft_updates, $plugin_updates);

        $available_update = [];
        $up_to_date = [];

        if (! empty($updates)) {
            foreach ($updates as $item) {
                if ($item->update_available) {
                    $available_update[] = VersionHelper::getItemDescription($item);
                } else {
                    $up_to_date[] = VersionHelper::getItemDescription($item);
                }
            }
        }

        return [
            "available_update" => VersionHelper::orderUpdateResults($available_update),
            "up_to_date" => VersionHelper::orderUpdateResults($up_to_date),
        ];
    }
}

<?php

namespace Craft;

use Exception;
use GuzzleHttp\Client;

class HvrcraftService extends BaseApplicationComponent
{

	/**
	 * This method fetches a cleaned up array of installed plugin data.
	 * @return array
	 */
	public function getInstalledPlugins()
	{
		$plugins = craft()->plugins->getPlugins();
		$cleaned = [];
		foreach($plugins as $handle => &$plugin) {
			$cleaned[$handle] = [
				'name' => $plugin->getName(),
				'description' => $plugin->getDescription(),
				'version' => $plugin->getVersion(),
				'developer' => $plugin->getDeveloper(),
				'changelogUrl' => $plugin->getReleaseFeedUrl()
			];
		}
		return $cleaned;
	}

	/**
	 * This method fetches a cleaned up array of available updates. It only returns
	 * the latest update available for the CMS and each installed plugin.
	 * @return array
	 */
	public function getAvailableUpdates()
	{
		// Boolean "true" parameter forces updates service to refresh the cache.
		$updates = craft()->updates->getUpdates(true);
		$cleaned = [
			'cms' => [
				'version' => craft()->getVersion(),
				'edition' => craft()->getEdition()
			]
		];
		// Check for a CMS update.
		if ( isset($updates->app->releases[0]) ) {
			$latestCmsUpdate = $updates->app->releases[0];
			$cleaned['cms']['update'] = [
				'version' => $latestCmsUpdate->version,
				'releaseDate' => $latestCmsUpdate->date->format('Y-m-d'),
				'critical' => $latestCmsUpdate->critical
			];
		} else {
			$cleaned['cms']['update'] = null;
		}
		// Clean up any plugin updates.
		if ( isset($updates->plugins) ) {
			foreach($updates->plugins as $index => $updateInfo) {
				$latestPluginUpdate = $updateInfo->releases[0] ?? null;
				if ( $latestPluginUpdate ) {
					$cleaned[$updateInfo->displayName] = [
						'version' => $latestPluginUpdate->version,
						'releaseDate' => $latestPluginUpdate->date ? $latestPluginUpdate->date->format('Y-m-d') : '',
						'critical' => $latestPluginUpdate->critical
					];
				}
			}
		}
		return $cleaned;
	}

	/**
	 * This method returns a cleaned up array of installed plugin data along with
	 * any available update data.
	 * @return array
	 */
	public function getPluginsAndUpdates()
	{
		$plugins = $this->getInstalledPlugins();
		$updates = $this->getAvailableUpdates();
		$formatted['cms'] = isset($updates['cms']) ? $updates['cms'] : null;
		$formatted['plugins'] = $plugins;
		foreach($formatted['plugins'] as $handle => &$plugin) {
			$name = $plugin['name'];
			$plugin['craft'] = 2;
			$plugin['update'] = isset($updates[$name]) ? $updates[$name] : null;
		}
		return $formatted;
	}

	/**
	 * This method sends a wakeup call to Hvrcraft telling it to fetch the updated
	 * plugin/update data from this site.
	 */
	public function wakeupHvrcraft()
	{
		$settings = craft()->plugins->getPlugin('hvrcraft')->getSettings();
		$siteKey = $settings['siteKey'];
		$baseUrl = getenv('CRAFTENV_HVRCRAFT_BASE_URL') ?: 'https://www.hvrcraft.com/';
		$client = new Client(['base_uri' => $baseUrl]);
		try {
			$response = $client->request('GET', 'api/wake-up', [
				'query' => ['key' => $siteKey]
			]);
			$body = (string)$response->getBody();
		} catch (Exception $e) {
			HvrcraftPlugin::log($e->getMessage(), LogLevel::Error);
		}
	}

}

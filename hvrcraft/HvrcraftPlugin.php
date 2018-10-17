<?php

namespace Craft;

class HvrcraftPlugin extends BasePlugin
{

	public function getName()
	{
		 return Craft::t('hvrcraft');
	}

	public function getVersion()
	{
		return '2.0.0-beta.4';
	}

	public function getSchemaVersion()
	{
		return '2.0.0';
	}

	public function getDescription()
	{
		return Craft::t('This plugin allows the site to relay plugin and update data to hvrcraft.com.');
	}

	public function getDeveloper()
	{
		return 'Masuga Design';
	}

	public function getDeveloperUrl()
	{
		return 'https://gomasuga.com';
	}

	public function getReleaseFeedUrl()
	{
		return 'https://www.hvrcraft.com/craft-2-plugin-releases';
	}

	public function defineSettings()
	{
		return array(
			'siteKey' => array(AttributeType::String, 'default' => '')
		);
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('hvrcraft/_settings', array(
			'settings' => $this->getSettings()
		));
	}

	public function hasCpSection()
	{
		return false;
	}

	public function registerSiteRoutes()
	{
		return array(
			'synchronize-hvrcraft' => array('action' => 'hvrcraft/communication/synchronize'),
		);
	}

	public function init()
	{
		parent::init();
		// Call home after an update is made to the site.
		craft()->on('updates.onEndUpdate', function(Event $event) {
			craft()->hvrcraft->wakeupHvrcraft();
		});
		craft()->hvrcraft->getAvailableUpdates();
	}

}

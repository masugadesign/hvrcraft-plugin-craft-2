<?php

namespace Craft;

class Hvrcraft_CommunicationController extends BaseController
{

	/**
	 * Do not require an authenticated user for this controller.
	 * @var boolean
	 */
	protected $allowAnonymous = true;

	/**
	 * This controller action method responds to requests from hvrcraft.com with
	 * plugin and update data formated as JSON.
	 */
	public function actionSynchronize()
	{
		$settings = craft()->plugins->getPlugin('hvrcraft')->getSettings();
		$siteKey = craft()->request->getParam('key');
		// On fail, throw a 404 so bots/hackers can't scan for a hvrcraft plugin installation.
		if ( ! $siteKey || $siteKey !== $settings['siteKey'] ) {
			throw new HttpException(404);
		}
		// Fetch the installed plugin and update data.
		$responseContent = craft()->hvrcraft->getPluginsAndUpdates();
		$this->returnJson($responseContent);
	}

}

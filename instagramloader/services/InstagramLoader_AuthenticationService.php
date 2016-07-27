<?php

namespace Craft;

use Larabros\Elogram\Client;

class InstagramLoader_AuthenticationService extends BaseApplicationComponent
{
	private $plugin;
	private $client;

	function __construct()
	{
		require_once craft()->path->getPluginsPath() . 'instagramloader/vendor/autoload.php';

		$this->plugin = craft()->plugins->getPlugin('instagramLoader');

		$settings = $this->plugin->getSettings();

		if (!$clientId = $settings->clientId)
		{
			Craft::log('No Client Id provided in settings', LogLevel::Error);

			return;
		}

		if (!$clientSecret = $settings->clientSecret)
		{
			Craft::log('No Client Secret provided in settings', LogLevel::Error);

			return;
		}

		$redirectUri = craft()->getSiteUrl() . 'actions/instagramLoader/authentication/authenticate';

		if (!$this->client = new Client($clientId, $clientSecret, null, $redirectUri))
		{
			Craft::log('Failed to instantiate connection', LogLevel::Error);

			return;
		}
	}

	public function authenticate()
	{
		// If we failed to make the connection, don't go on
		if (!$this->client)
		{
			return false;
		}

		// If we don't have an authorization code then get one
		if (!isset($_GET['code'])) {
			// Direct the user to the login URL, using the public_content scope
			header('Location: ' . $this->client->getLoginUrl(array( 'scope' => 'public_content' )));

			exit;
		// If we do
		} else {
			// Get the access token
			$token = $this->client->getAccessToken($_GET['code']);
			// Save it to the settings
			craft()->plugins->savePluginSettings($this->plugin,
				array( 'accessToken' => json_encode($token) )
			);
		}
	}
}

?>
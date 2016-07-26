<?php

namespace Craft;

class InstagramLoader_AuthenticationController extends BaseController
{
	public function actionAuthenticate() {
		craft()->instagramLoader_authentication->authenticate();

		// After the service has run and returns, direct the user back to the settings page.
		header('Location: ' . craft()->getSiteUrl() . 'admin/settings/plugins/instagramloader');

		exit;
	}
}
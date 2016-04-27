<?php

namespace Craft;

class InstagramLoader_EntriesController extends BaseController
{
	protected $allowAnonymous = true;
	
	public function actionSyncWithRemote() {
		craft()->instagramLoader_entries->syncWithRemote();

		$this->renderTemplate('instagramLoader/empty');
	}
}
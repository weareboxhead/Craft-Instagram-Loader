<?php

namespace Craft;

use Larabros\Elogram\Client;

class InstagramLoader_EntriesService extends BaseApplicationComponent
{
	private $callLimit = 20; // Instagram Sandbox limit
	private $sectionId;
	private $entryTypeId;
	private $instagramUserIds;
	private $client;

	function __construct()
	{
		require_once craft()->path->getPluginsPath() . 'instagramloader/vendor/autoload.php';

		$settings = craft()->plugins->getPlugin('instagramLoader')->getSettings();

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

		if (!$accessToken = $settings->accessToken)
		{
			Craft::log('No access token provided', LogLevel::Error);

			return;
		}

		if (!$this->sectionId = $settings->sectionId)
		{
			Craft::log('No Section Id provided in settings', LogLevel::Error);

			return;
		}

		if (!$this->entryTypeId = $settings->entryTypeId)
		{
			Craft::log('No Entry Type Id provided in settings', LogLevel::Error);

			return;
		}

		if (!$this->client = new Client($clientId, $clientSecret, $accessToken))
		{
			Craft::log('Failed to instantiate connection', LogLevel::Error);

			return;
		}

		$this->instagramUserIds = $settings->instagramUserIds;
	}

	private function getRemoteData($userId)
	{
		// Call for remote instagrams
		$instagrams = $this->client->users()->getMedia();

		// Get the data
		$instagrams = $instagrams->get();

		$data = array(
			'ids'			=>	array(),
			'instagrams'	=>	array(),
		);

		// For each Instagram
		foreach ($instagrams as $instagram) {
			// Get the id
			$instagramId = $instagram['id'];

			// Add this id to our array
			$data['ids'][]						= $instagramId;
			// Add this instagram to our array, using the id as the key
			$data['instagrams'][$instagramId] 	= $instagram;
		}

		return $data;
	}

	private function getLocalData($userId)
	{
		// Create a Craft Element Criteria Model
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		// Restrict the parameters to the correct channel
		$criteria->sectionId 		= $this->sectionId;
		// Restrict the parameters to the correct entry type
		$criteria->type 			= $this->entryTypeId;
		// Restrict the parameters to this user
		$criteria->instagramUserId 	= $userId;
		// Include closed entries
		$criteria->status 			= [null];
		// Match the Instagram call limit as we don't need any more than this for the comparison
		$criteria->limit 			= $this->callLimit;

		$data = array(
			'ids'			=>	array(),
			'instagrams'	=>	array(),
		);

		// For each Instagram, add it to our data object with its id as the key
		foreach ($criteria as $instagram) {
			$instagramId = $instagram->instagramId;

			// Add this id to our array
			$data['ids'][]						= $instagramId;
			// Add this instagram to our array, using the id as the key
			$data['instagrams'][$instagramId] 	= $instagram;
		}

		return $data;
	}

	private function getOrientation($width, $height)
	{
		$orientation = '';

		// If this is non-square, set the orientation field
		if ($width !== $height) {
			if ($width > $height) {
				$orientation = 'landscape';
			} else {
				$orientation = 'portrait';
			}
		}

		return $orientation;
	}

	private function saveEntry($entry)
	{
		$success = craft()->entries->saveEntry($entry);

		// If the attempt failed
		if (!$success)
		{
			Craft::log('Couldn’t save entry ' . $entry->getContent()->id, LogLevel::Warning);
		}
	}

	private function parseContent($instagram, $caption, $userId)
	{
		$image 		= $instagram['images']['standard_resolution'];
		$width 		= $image['width'];
		$height 	= $image['height'];

		// The standard content
		$content = array(
			'instagramId'			=>	$instagram['id'],
			'instagramUserId'		=>	$userId,
			'instagramFileUrl'		=>	$image['url'],
			'instagramPageUrl'		=>	$instagram['link'],
			'instagramCaption'		=>	$caption,
			'instagramWidth'		=>	$width,
			'instagramHeight'		=>	$height,
			'instagramOrientation'	=>	$this->getOrientation($width, $height),
			'instagramCategories'	=>	craft()->instagramLoader_categories->parseCategories($instagram['tags']),
		);

		return $content;
	}

	private function truncateTitle($title)
	{
		return craft()->instagramLoader_string->truncate($title);
	}

	private function createEntry($instagram, $userId)
	{
		// Format the caption
		$caption = $instagram['caption']['text'];

		// Create a new instance of the Craft Entry Model
		$entry = new EntryModel();
		// Set the section id
		$entry->sectionId 	= $this->sectionId;
		// Set the entry type
		$entry->typeId 		= $this->entryTypeId;
		// Set the author as super admin
		$entry->authorId 	= 1;
		// Set the publish date as post date
		$entry->postDate 	= $instagram['created_time'];
		// Set the title
		$entry->getContent()->title = $this->truncateTitle($caption);
		// Set the other content
		$entry->setContentFromPost($this->parseContent($instagram, $caption, $userId));

		// Save the entry!
		$this->saveEntry($entry);
	}

	// Anything we like can be updated in here
	private function updateEntry($localEntry, $instagram)
	{
		// Set up an empty array for our updating content
		$content = array();

		// Get the remote caption
		$remoteCaption 	= $instagram['caption']['text'];
		// Get the local caption
		$localCaption 	= $localEntry->instagramCaption;

		// If they have changed
		if ($remoteCaption !== $localCaption) {
			// Add this to our updating content array
			$content['instagramCaption'] = $remoteCaption;

			// Also update the title
			$localEntry->getContent()->title = $this->truncateTitle($remoteCaption);
		}

		// If we have no updating content, don't update the entry
		if (!count($content))
		{
			return true;
		}

		// Set the other content
		$localEntry->setContentFromPost($content);

		// Save the entry!
		$this->saveEntry($localEntry);
	}

	public function syncWithRemote()
	{
		// If we failed to make the connection, don't go on
		if (!$this->client)
		{
			return false;
		}

		// For each id in the settings field
		foreach (explode(',', $this->instagramUserIds) as $userId)
		{
			// Remove any white space
			$userId = trim($userId);

			// If there are no user ids, continue
			if (!strlen($userId))
			{
				Craft::log('No user ids specified', LogLevel::Warning);

				return false;
			}


			// Get remote data
			$remoteData = $this->getRemoteData($userId);

			if (!$remoteData)
			{
				Craft::log('Failed to get remote data for user id: ' . $userId, LogLevel::Error);

				continue;
			}

			// Get local data
			$localData 	= $this->getLocalData($userId);

			if (!$localData)
			{
				Craft::log('Failed to get local data for user id: ' . $userId, LogLevel::Error);

				continue;
			}

			// Determine which entries we are missing by id
			$missingIds 	= 	array_diff($remoteData['ids'], $localData['ids']);

			// Determine which entries need updating (all active entries which we aren't about to create)
			$updatingIds 	=	array_diff($remoteData['ids'], $missingIds);

			// For each missing id
			foreach ($missingIds as $id)
			{
				// Create this entry
			    $this->createEntry($remoteData['instagrams'][$id], $userId);
			}

			// For each updating track
			foreach ($updatingIds as $id) {
				$this->updateEntry($localData['instagrams'][$id], $remoteData['instagrams'][$id]);
			}
		}
	}
}
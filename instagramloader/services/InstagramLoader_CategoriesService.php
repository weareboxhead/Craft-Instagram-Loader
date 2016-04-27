<?php

namespace Craft;

class InstagramLoader_CategoriesService extends BaseApplicationComponent
{
	private $categories = array();

	function __construct()
	{
		// Get our category group id
		$categoryGroupId = craft()->plugins->getPlugin('instagramLoader')->getSettings()->categoryGroupId;

		// If there is no category group specified, don't do this
		if (!$categoryGroupId)
		{
			return;
		}

		// Create a Craft Element Criteria Model
		$criteria = craft()->elements->getCriteria(ElementType::Category);
		// Restrict the parameters to the correct category group
		$criteria->groupId = $categoryGroupId;

		// For each category
		foreach ($criteria as $category) {
			// Add its slug and id to our array
			$this->categories[$category->slug] = $category->id;
		}
	}	

	public function parseCategories($tags)
	{
		$categoryIds = array();

		foreach ($tags as $tag) {
			// If it matches one of the handles, add the id to the array and continue
			foreach ($this->categories as $slug => $id) {
				if ($tag === $slug)
				{
					$categoryIds[] = $id;
				}
			}
		}

		return $categoryIds;
	}
}

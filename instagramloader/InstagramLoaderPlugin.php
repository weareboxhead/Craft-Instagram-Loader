<?php

namespace Craft;

class InstagramLoaderPlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('Instagram Loader');
	}

	function getVersion()
	{
		return '0.1';
	}

	function getDeveloper()
	{
		return 'Boxhead';
	}

	function getDeveloperUrl()
	{
		return 'http://boxhead.io';
	}

	function onAfterInstall()
	{
		// Create the Instagram Field Group
		Craft::log('Creating the Instagram Field Group.');

		$group = new FieldGroupModel();
		$group->name = 'Instagram';

		if (craft()->fields->saveGroup($group))
		{
			Craft::log('Instagram field group created successfully.');

			$groupId = $group->id;
		}
		else
		{
			Craft::log('Could not save the Instagram field group.', LogLevel::Error);

			return false;
		}

		// Create the Basic Fields
		Craft::log('Creating the basic Instagram Fields.');

		$basicFields = array(
			'instagramId'			=>	'Instagram Id',
			'instagramUserId'		=>	'Instagram User Id',
			'instagramFileUrl'		=>	'Instagram File URL',
			'instagramPageUrl'		=>	'Instagram Page URL',
			'instagramCaption'		=>	'Instagram Caption',
			'instagramWidth'		=>	'Instagram Width',
			'instagramHeight'		=>	'Instagram Height',
			'instagramOrientation'	=>	'Instagram Orientation',
		);

		$instagramEntryLayoutIds = array();

		foreach($basicFields as $handle => $name) {
			Craft::log('Creating the ' . $name . ' field.');

			$field = new FieldModel();
			$field->groupId	  		= $groupId;
			$field->name	 		= $name;
			$field->handle	   		= $handle;
			$field->translatable 	= true;
			$field->type		 	= 'PlainText';

			if (craft()->fields->saveField($field))
			{
				Craft::log($name . ' field created successfully.');

				$instagramEntryLayoutIds[] = $field->id;
			}
			else
			{
				Craft::log('Could not save the ' . $name . ' field.', LogLevel::Error);

				return false;
			}
		}

		// Create the Instagram category group
		Craft::log('Creating the Instagram category group.');

		$categoryGroup = new CategoryGroupModel();

		$categoryGroup->name 	= 'Instagram';
		$categoryGroup->handle 	= 'instagram';
		$categoryGroup->hasUrls = false;

		if (craft()->categories->saveGroup($categoryGroup))
		{
			Craft::log('Instagram category group created successfully.');
		}
		else
		{
			Craft::log('Could not create the Instagram category group.', LogLevel::Error);

			return false;
		}

		// Create the Instagram categories field
		Craft::log('Creating the Instagram categories field.');

		$categoriesField = new FieldModel();

		$categoriesField->groupId		= $groupId;
		$categoriesField->name			= 'Instagram Categories';
		$categoriesField->handle		= 'instagramCategories';
		$categoriesField->translatable	= true;
		$categoriesField->type			= 'Categories';
		$categoriesField->settings		= array( 'source' => 'group:' . $categoryGroup->id );

		if (craft()->fields->saveField($categoriesField))
		{
			Craft::log('Instagram categories field created successfully.');

			$instagramEntryLayoutIds[] = $categoriesField->id;
		}
		else
		{
			Craft::log('Could not save the Instagram categories field.', LogLevel::Error);

			return false;
		}

		// Create the Instagram Field Layout
		Craft::log('Creating the Instagram Field Layout.');

		if ($instagramEntryLayout = craft()->fields->assembleLayout(array('Instagram' => $instagramEntryLayoutIds), array()))
		{
			Craft::log('Instagram Field Layout created successfully.');
		}
		else
		{
			Craft::log('Could not create the Instagram Field Layout', LogLevel::Error);

			return false;
		}	

		// Set the layout type to an Entry
		$instagramEntryLayout->type = ElementType::Entry;

		// Create the Instagram Channel
		Craft::log('Creating the Instagram Channel.');

		$instagramChannelSection = new SectionModel();

		$instagramChannelSection->name 				= 'Instagram';
		$instagramChannelSection->handle 			= 'instagram';
		$instagramChannelSection->type 				= SectionType::Channel;
		$instagramChannelSection->hasUrls 			= false;
		$instagramChannelSection->enableVersioning 	= false;

		$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();

		$locales[$primaryLocaleId] = new SectionLocaleModel(array(
			'locale' => $primaryLocaleId,
		));

		$instagramChannelSection->setLocales($locales);

		if (craft()->sections->saveSection($instagramChannelSection))
		{
			Craft::log('Instagram Channel created successfully.');
		}
		else
		{
			Craft::log('Could not create the Instagram Channel.', LogLevel::Error);

			return false;
		}

		// Get the array of entry types for our new section
		$instagramEntryTypes = $instagramChannelSection->getEntryTypes();
		// There will only be one so get that
		$instagramEntryType = $instagramEntryTypes[0];

		$instagramEntryType->hasTitleField 	= true;
		$instagramEntryType->titleLabel 	= 'Title';
		$instagramEntryType->setFieldLayout($instagramEntryLayout);

		if (craft()->sections->saveEntryType($instagramEntryType))
		{
			Craft::log('Instagram Channel Entry Type saved successfully.');
		}
		else
		{
			Craft::log('Could not create the Instagram Channel Entry Type.', LogLevel::Error);

			return false;
		}

		// Save the settings based on the section and entry type we just created
		craft()->plugins->savePluginSettings($this,
			array(
				'sectionId'	 		=> $instagramChannelSection->id,
				'entryTypeId'   	=> $instagramEntryType->id,
				'categoryGroupId'   => $categoryGroup->id,
			)
		);
	}

	protected function defineSettings()
	{
		return array(
			'clientId'			=> array(AttributeType::String, 'default' => ''),
			'sectionId'			=> array(AttributeType::String, 'default' => ''),
			'entryTypeId'		=> array(AttributeType::String, 'default' => ''),
			'categoryGroupId'	=> array(AttributeType::String, 'default' => ''),
			'instagramUserIds'	=> array(AttributeType::String, 'default' => ''),
		);
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('instagramloader/settings', array(
			'settings' => $this->getSettings()
		));
	}
}
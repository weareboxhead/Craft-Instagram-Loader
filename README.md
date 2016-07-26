# Instagram Loader - Plugin for Craft CMS

Retrieves Instagram content from a given list of Instagram accounts and saves the content as individual entries.

More specifically, Instagram Loader retrieves content from a given list of Instagram accounts, saves new content as individual entries and updates the captions for existing entries.

The Section, Fields and Category Group are automatically created on installation.

## Usage

* Download and extract the plugin files
* Copy `instagramloader/` to your site's `/craft/plugins/` directory
* Install the plugin
* Fill in the fields in the plugin's settings
* Load `http://[yourdomain]/actions/instagramLoader/entries/syncWithRemote`

## Categories

When saving new entries, Instagram Loader will check if any categories in the Category Group match the post's tags.

Any which are found will be saved as categories for the entry.

## Instagram Restrictions

Due to recent restrictions imposed by Instagram:

* The API calls are limited to only the most recent 20 posts
* The User Ids must each be either the owner of the account with the Instagram Client (from which the Client Id and Access Tokens are generated) is created, or be an authorized member of that account's Sandbox
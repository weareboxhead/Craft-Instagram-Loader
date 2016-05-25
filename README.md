# Instagram Loader - Plugin for Craft CMS

Retrieves Instagram content from a given list of Instagram accounts and saves the content as individual entries.

More specifically, Instagram Loader retrieves content from a given list of Instagram accounts, saves new content as individual entries and updates the captions for existing entries.

The Section, Fields and Category Group are automatically created on installation.

## Usage

* Download and extract the plugin files
* Copy `instagramloader/` to your site's `/craft/plugins/` directory
* Install the plugin
* Fill in the fields in the plugin's [settings](#settings)
* Load `http://[yourdomain]/actions/instagramLoader/entries/syncWithRemote`

## <a name="settings"></a>Settings

### Client Id

This is the Client Id of the Client created in [https://instagram.com/developer/](https://www.instagram.com/developer/).

### Access Token

See [https://instagram.com/developer/authentication/](https://www.instagram.com/developer/authentication/) for instructions on how to obtain a valid access token. The required scope for this plugin is "public_content"

### Section Id, Entry Type Id, Category Group Id

These are the ids of the Section, Entry Type and Category Group used.

Automatically populated on plugin install.

### Instagram User Ids

A comma separated list of Instagram account ids for which to retrieve content.

## Categories

When saving new entries, Instagram Loader will check if any categories in the Category Group match the post's tags.

Any which are found will be saved as categories for the entry.

## Instagram Restrictions

Due to recent restrictions imposed by Instagram:

* The API calls are limited to only the most recent 20 posts
* The User Ids must each be either the owner of the account with the Instagram Client (from which the Client Id and Access Tokens are generated) is created, or be an authorized member of that account's Sandbox
# Brandfolder Drupal Integration
-------------------------------------------------------------------------------
![CircleCI](https://img.shields.io/circleci/build/github/brandfolder/integration-drupal?token=94d3945b6680f1ca5e786886ac94757e49c5136b)

This module allows you to manage images using the Brandfolder
(https://brandfolder.com) Digital Asset Management system and use those images
natively on your Drupal site. It integrates with Drupal's Media and Media
Library modules, and also provides low-level file and image integration to
support Drupal image styles while serving images from the Brandfolder CDN.

If you have any feedback or feature requests, please add them to the issue 
queue!


# Dependencies
-------------------------------------------------------------------------------
- An active Brandfolder account (see https://brandfolder.com) with "Smart CDN"
  functionality enabled.
- The Brandfolder PHP SDK/library (should be automatically installed by Composer).

# Installation
-------------------------------------------------------------------------------
Install with composer to ensure that the required SDK and other dependencies
are added. Enable the module via Drush or the modules admin page on your site.

# Initial Setup
-------------------------------------------------------------------------------
1. Log in to your Brandfolder account as an administrator
2. Navigate to "My Profile" > "Integrations"
3. In the "API Keys" section, copy the key for the Brandfolder organization you
   wish to integrate.
4. Visit /admin/config/media/brandfolder/settings on your Drupal site
5. Enter the API key* and save the settings form.

*You can use the same admin key for all three fields, or, preferably, add
separate API keys for Brandfolder users with collaborator and guest roles.

# Common Use Case Overview
-------------------------------------------------------------------------------
- Create a new Media type with `Brandfolder Image` as the source.
- Configure the media type to restrict it to certain Brandfolder collections, sections, or labels as desired.
- Customize the display of the new media type. By default, the module will configure it to display the auto-generated image field using a standard image formatter, but you can display the image using any compatible option (e.g. Responsive Image).
- Create or edit a media reference field to allow your new media type, and/or edit a rich text input mode so that the WYSIWYG "insert media" dialog will include a tab for your new media type.
- Edit content with the aforementioned media or rich text field
- When selecting the new media type (or immediately if it is the only eligible type for your field), you will see a custom Brandfolder image browser in place of the standard media library browser.
- Browse eligible Brandfolder assets, searching and filtering as desired. Note: filters will appear automatically based on the allowed collections, etc. you configure for your media type. If there is only one allowed collection, for instance, then no collection filters will appear.
- Select an asset, which will display a list of all eligible attachments for that asset. Select an attachment (or multiple attachments from one or more assets, if your field allows more than one media item) then click the "Insert Selected" button to proceed.
- The Brandfolder module will automatically generate a media entity for the selected attachment(s), if they do not already exist, and will enable the Media module to use them in the same way it uses other image media entities.

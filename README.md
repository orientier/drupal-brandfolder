# INTRODUCTION 
![CircleCI](https://img.shields.io/circleci/build/github/brandfolder/integration-drupal?token=94d3945b6680f1ca5e786886ac94757e49c5136b)

This module allows you to interact with the Brandfolder
(https://brandfolder.com) Digital Asset Management system using the
Brandfolder PHP SDK. It is currently geared toward developers, but we plan
to build it into a full integration between Drupal and Brandfolder, similar
to the Drupal 7 version of this module.
If you have any feature requests, please add them to the issue queue!


# REQUIREMENTS
 - An active Brandfolder account (see https://brandfolder.com).
 - The [Brandfolder PHP SDK/library](https://packagist.org/packages/brandfolder/brandfolder-sdk-php)
   (this should be installed automatically by Composer).

# INSTALLATION
```
drush en brandfolder -y
```

# CONFIGURATION
1. Log in to your Brandfolder account as an administrator
2. Navigate to "My Profile" > "Integrations"
3. In the "API Keys" section, copy the key for the Brandfolder organization you
wish to integrate
4. Visit /admin/config/media/brandfolder on your Drupal site
5. Enter the API key and save the settings form

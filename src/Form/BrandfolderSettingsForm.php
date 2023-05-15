<?php

namespace Drupal\brandfolder\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define the administrative form used to configure the Brandfolder integration.
 */
class BrandfolderSettingsForm extends ConfigFormBase {

  /**
   * @var $password_field_preservation_token
   *  A string to help preserve existing values for password-style form fields
   *  without exposing sensitive data and still allowing users to clear
   *  previous entries without providing new data.
   */
  protected $password_field_preservation_token = 'bf_api_key_exists_for_this_field_bf_api_key_exists_for_this_field_bf_api_key_exists_for_this_field_bf_api_key_exists_for_this_field';

  /**
   * @var array machine names and human-readable names for the various types of
   *  Brandfolder API keys we collect.
   */
  protected $api_key_types;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'brandfolder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brandfolder_settings_form';
  }

  /**
   * BrandfolderSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->api_key_types = [
      'admin'        => $this->t('Admin'),
      'collaborator' => $this->t('Collaborator'),
      'guest'        => $this->t('Guest'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('brandfolder.settings');
    $api_keys = [];
    $messenger = $this->messenger();
    $brandfolder_id = $config->get('brandfolder_id');
    $bf = brandfolder_api();

    if ($config->get('verbose_log_mode')) {
      $bf->enableVerboseLogging();
    }

    $preview_collection_id = $config->get('preview_collection_id');

    $none_option_array = ['none' => $this->t('< None >')];

    $brandfolders_list = $collections_list = [];
    if ($bf) {
      try {
        $brandfolders_list = $bf->getBrandfolders();
      } catch (\Exception $e) {
        $messenger->addMessage($this->t('Please fill in all the requested API keys. You will then be able to select a Brandfolder.'));
      }
      if ($brandfolder_id) {
        try {
          $collections_list = $bf->getCollectionsInBrandfolder();
        } catch (\Exception $e) {
          $messenger->addMessage($this->t('Could not find collections for the selected Brandfolder.'));
        }
      }
    }
    $brandfolders_list = array_merge($none_option_array, $brandfolders_list);
    $collections_list = array_merge($none_option_array, $collections_list);

    /************************************
     * Credentials
     ************************************/
    $form['credentials'] = [
      '#type'  => 'details',
      '#title' => $this->t('Brandfolder Credentials'),
    ];

    foreach ($this->api_key_types as $api_key_type => $api_key_type_label) {
      $api_key = $config->get("api_keys.$api_key_type");

      $form['credentials']["brandfolder_api_key_$api_key_type"] = [
        '#type'        => 'password',
        '#title'       => $this->t('Brandfolder API key: @label', ['@label' => $api_key_type_label]),
        '#description' => $this->t('An API key for a Brandfolder user who has the "@label" role for the Brandfolder you wish to integrate with your Drupal site. This can be found in Brandfolder under "My Profile > Integrations > API Keys."', ['@label' => $api_key_type_label]),
        '#maxlength'   => 255,
        '#size'        => 64,
      ];
      if (!empty($api_key)) {
        $form['credentials']["brandfolder_api_key_$api_key_type"]['#attributes']['value'] = $this->password_field_preservation_token;
        $api_keys[$api_key_type] = $api_key;
      }
    }
    // Open the fieldset if any of the three API keys have yet to be provided.
    // Collapse it if we have all three.
    $form['credentials']['#open'] = (count($api_keys) < 3);


    /************************************
     * Basic Configuration
     ************************************/
    $form['basic'] = [
      '#type'  => 'details',
      '#title' => $this->t('Basic Configuration Options'),
      '#open'  => empty($brandfolder_id),
    ];

    $form['basic']['brandfolder_brandfolder_id'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Choose a Brandfolder'),
      '#options'       => $brandfolders_list,
      '#default_value' => $brandfolder_id ?? 'none',
      '#description'   => $this->t('The Brandfolder to integrate with this Drupal site.'),
    ];

    $form['basic']['brandfolder_preview_collection'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Collection to Preview'),
      '#options'       => $collections_list,
      '#default_value' => $preview_collection_id ?? 'none',
      '#description'   => $this->t('Choose a collection from which to display sample images. This can help confirm that the integration is successful.'),
    ];

    if (isset($bf) && $brandfolder_id) {
      /************************************
       * Metadata Sync
       ************************************/
      $form['metadata'] = [
        '#type'  => 'details',
        '#title' => $this->t('Metadata Synchronization'),
      ];

      $form['metadata']['metadata_sync_mode'] = [
        '#type'          => 'radios',
        '#title'         => $this->t('Sync Mode'),
        '#options'       => [
          'empties_only' => $this->t('Only update Drupal fields that are empty (default).'),
          'indiscriminate_bf_overwrite' => $this->t('Always update Drupal fields when Brandfolder data changes, regardless of whether Drupal fields are empty, have been changed in Drupal, etc. (feature currently in development)'),
          'update_non_overridden_fields' => $this->t('When Brandfolder data changes, update all corresponding Drupal fields except those that have been changed (in Drupal) since the last sync (feature currently in development).'),
        ],
        'indiscriminate_bf_overwrite' => ['#disabled' => TRUE],
        'update_non_overridden_fields' => ['#disabled' => TRUE],
        '#default_value' => $config->get('metadata_sync_mode') ?? 'empties_only',
        '#description'   => $this->t('Some metadata pertaining to Brandfolder assets can be mapped to corresponding fields/attributes in Drupal. Choose how you want this module to manage that relationship.'),
      ];

      $custom_field_options = $none_option_array;
      if ($custom_field_ids_and_names = $bf->listCustomFields(NULL, FALSE, TRUE)) {
        $custom_field_options = array_merge($custom_field_options, $custom_field_ids_and_names);
      }

      $existing_value = $config->get('alt_text_custom_field');
      if (empty($existing_value)) {
        $existing_value = 'none';
      }

      $form['metadata']['alt_text_custom_field'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Alt-Text Custom Field'),
        '#options'       => $custom_field_options,
        '#default_value' => $existing_value,
        '#description'   => $this->t('You can use a custom field in Brandfolder to store alt-text for assets, and Drupal will pull text from that field for use with Brandfolder-sourced images, where applicable. To enable this functionality, select the Brandfolder field you plan to use to store alt-text values.'),
      ];

      /************************************
       * Sample Images
       ************************************/
      // Display some images from the selected Brandfolder/collection if
      // applicable.
      if ($preview_collection_id) {
        $assets = $bf->listAssets([], $preview_collection_id);
      }
      else {
        $assets = $bf->listAssets();
      }
      if ($assets) {
        $thumbnails = array_map(function ($asset) {
          return '<img src="' . $asset->attributes->thumbnail_url . '">';
        }, $assets->data);

        $form['sample_pics'] = [
          '#type'   => 'markup',
          '#prefix' => '<h2>Sample Images</h2>',
          '#markup' => '<div class="brandfolder-sample-images">' . implode(' ', $thumbnails) . '</div>',
          '#weight' => 999,
        ];
      }
    }

    /************************************
     * Advanced
     ************************************/
    $form['advanced'] = [
      '#type'  => 'details',
      '#title' => $this->t('Advanced Settings'),
    ];

    $form['advanced']['verbose_log_mode'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Detailed logging'),
      '#default_value' => $config->get('verbose_log_mode'),
      '#description'   => $this->t('Enable this setting to create log entries for all Brandfolder API queries, incoming webhooks, etc. This can be useful for troubleshooting, but should probably only be enabled for short periods lest it overwhelm your logs.'),
    ];

    $form['#attached']['library'][] = 'brandfolder/brandfolder-admin';

    if ($config->get('verbose_log_mode')) {
      foreach ($bf->getLogData() as $log_entry) {
        $this->logger('brandfolder')->debug($log_entry);
      }
      $bf->clearLogData();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check to see if all API keys are valid.
    // @todo: Additionally, validate that each key is associated with the correct user role. There appears to be no straightforward way to do this via the API, but we can deduce it by attempting operations that are only allowed for certain roles.
    $a_valid_api_key_exists = FALSE;
    $config = $this->config('brandfolder.settings');

    foreach ($this->api_key_types as $api_key_type => $api_key_type_label) {
      $form_field_identifier = "brandfolder_api_key_$api_key_type";
      $api_key = $form_state->getValue($form_field_identifier);
      // If a value already existed, and the user did not overwrite it, make
      // sure we preserve the existing value. This is how we achieve the best
      // UX while using password fields.
      if ($api_key == $this->password_field_preservation_token) {
        $api_key = $config->get("api_keys.$api_key_type");
        $form_state->setValue($form_field_identifier, $api_key);
      }
      if (!empty($api_key)) {
        $bf = brandfolder_api(NULL, $api_key);
        if ($config->get('verbose_log_mode')) {
          $bf->enableVerboseLogging();
        }
        $api_success = FALSE;
        try {
          $brandfolders = $bf->getBrandfolders();
          // Note that the getBrandfolders request will return a 200 response even
          // if the API key is invalid, and the brandfolders array will simply be
          // empty. This is a quirk of the Brandfolder API.
          if (!empty($brandfolders)) {
            $api_success = TRUE;
            $a_valid_api_key_exists = TRUE;
          }
        } catch (\Exception $e) {
          $api_success = FALSE;
        }
        if (!$api_success) {
          $message = $this->t('Could not connect to Brandfolder using the @key_type API key. Make sure the key is correct and is linked to a Brandfolder user who has permission to access at least one Brandfolder.', ['@key_type' => $api_key_type]);
          $form_state->setErrorByName($form_field_identifier, $message);
        }
        if ($config->get('verbose_log_mode')) {
          foreach ($bf->getLogData() as $log_entry) {
            $this->logger('brandfolder')->debug($log_entry);
          }
          $bf->clearLogData();
        }
      }
    }

    if (!$a_valid_api_key_exists) {
      // If no valid API key is specified, clear out any existing Brandfolder
      // and Collection choices.
      $form_state->setValue('brandfolder_brandfolder_id', 'none');
      $form_state->setValue('brandfolder_preview_collection', 'none');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('brandfolder.settings');

    foreach ($this->api_key_types as $api_key_type => $api_key_type_label) {
      $config->set("api_keys.$api_key_type", $form_state->getValue("brandfolder_api_key_$api_key_type"));
    }
    $old_brandfolder = $config->get('brandfolder_id');
    $specified_brandfolder = $form_state->getValue('brandfolder_brandfolder_id');
    if ($specified_brandfolder == 'none') {
      $specified_brandfolder = NULL;
    }
    $config->set('brandfolder_id', $specified_brandfolder);
    // If the Brandfolder selection is being changed, reset the collection,
    // which is dependent on the Brandfolder. Otherwise, use the value
    // specified by the form.
    $specified_collection = $form_state->getValue('brandfolder_preview_collection');
    if ($specified_brandfolder != $old_brandfolder || $specified_collection == 'none') {
      $collection = NULL;
    }
    else {
      $collection = $specified_collection;
    }
    $config->set('preview_collection_id', $collection);

    $alt_text_custom_field = $form_state->getValue('alt_text_custom_field');
    if ($alt_text_custom_field == 'none') {
      $alt_text_custom_field = NULL;
    }
    $config->set('alt_text_custom_field', $alt_text_custom_field);

    $config->set('verbose_log_mode', $form_state->getValue('verbose_log_mode'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}

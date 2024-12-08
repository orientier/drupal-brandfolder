<?php

namespace Drupal\brandfolder\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the administrative form used to configure the Brandfolder integration.
 */
class BrandfolderSettingsForm extends ConfigFormBase {

  /**
   * @var array machine names and human-readable names for the various types of
   *  Brandfolder API keys we collect.
   */
  protected array $api_key_types;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'brandfolder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'brandfolder_settings_form';
  }

  /**
   * The KeyRepository service.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected KeyRepository $key_repository;

  /**
   * BrandfolderSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   * @param \Drupal\key\KeyRepository $key_repository
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, KeyRepository $key_repository) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->key_repository = $key_repository;
    $this->api_key_types = [
      'admin'        => $this->t('Admin'),
      'collaborator' => $this->t('Collaborator'),
      'guest'        => $this->t('Guest'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): BrandfolderSettingsForm|static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('brandfolder.settings');
    $api_key_count = 0;
    $messenger = $this->messenger();
    $brandfolder_id = $config->get('brandfolder_id');
    $bf = brandfolder_api('admin');

    if ($bf && $config->get('verbose_log_mode')) {
      $bf->enableVerboseLogging();
    }

    $preview_collection_id = $config->get('preview_collection_id');

    $none_option_array = ['none' => $this->t('< None >')];

    $brandfolders_list = $collections_list = [];
    if ($bf) {
      $brandfolders_list = $bf->listAllBrandfolderNames();
      if (!$brandfolders_list) {
        $messenger->addMessage($this->t('Please fill in all the requested API keys. You will then be able to select a Brandfolder.'));
      }
      if ($brandfolder_id) {
        $collections_list = $bf->listAllCollectionNamesInBrandfolder();
        if (!$collections_list) {
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
      $config_name = "api_key_ids.$api_key_type";
      $api_key_id = $config->get($config_name);
      if (!empty($api_key_id)) {
        $api_key_count++;
      }

      $form['credentials']["api_key_ids_$api_key_type"] = [
        '#type'        => 'key_select',
        '#title'       => $this->t('Brandfolder API key: @label', ['@label' => $api_key_type_label]),
        '#description' => $this->t('The value of this key should be an API key for a Brandfolder user who has the "@label" role for the Organization you wish to integrate with your Drupal site. This can be found in Brandfolder under "My Profile > Integrations > API Keys."', ['@label' => $api_key_type_label]),
        '#default_value' => $api_key_id,
        '#required' => TRUE,
      ];
    }
    // Open the fieldset if any of the three API keys have yet to be provided.
    // Collapse it if we have all three.
    $form['credentials']['#open'] = ($api_key_count < count($this->api_key_types));


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
        'indiscriminate_bf_overwrite' => $this->t('Always update Drupal fields when Brandfolder data changes, regardless of whether Drupal fields are empty, have been changed in Drupal, etc. (feature not currently available)'),
        'update_non_overridden_fields' => $this->t('When Brandfolder data changes, update all corresponding Drupal fields except those that have been changed (in Drupal) since the last sync (feature not currently available).'),
      ],
      'indiscriminate_bf_overwrite' => ['#disabled' => TRUE],
      'update_non_overridden_fields' => ['#disabled' => TRUE],
      '#default_value' => $config->get('metadata_sync_mode') ?? 'empties_only',
      '#description'   => $this->t('Some metadata pertaining to Brandfolder assets can be mapped to corresponding fields/attributes in Drupal. Choose how you want this module to manage that relationship.'),
    ];


    /************************************
     * Brandfolder Browser
     ************************************/
    $form['bf_browser'] = [
      '#type'  => 'details',
      '#title' => $this->t('Brandfolder Browser'),
      '#description' => $this->t('Settings related to the custom Brandfolder Browser interface via which BF items are selected for use in Drupal. You may need to clear caches before seeing changes take effect.'),
    ];

    $form['bf_browser']['customize_entity_browser_modal_pages'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Customize Entity Browser modal pages for Brandfolder Browsers'),
      '#default_value' => $config->get('customize_entity_browser_modal_pages') ?? TRUE,
      '#description' => $this->t('If enabled, we will modify the structure/content of the page within which entity browsers are rendered in a modal/iframe, when an entity browser includes a Brandfolder Browser widget. This is recommended to maximize the (already limited) screen real estate available to the BF browser, but you can disable if needed.'),
    ];

    $form['bf_browser']['disable_system_messages_on_browser_pages'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Disable system messages on Brandfolder Browser pages'),
      '#default_value' => $config->get('disable_system_messages_on_browser_pages') ?? TRUE,
      '#description' => $this->t('If enabled, Drupal system messages will not be shown on Brandfolder Browser pages. This is recommended to maximize the (already limited) screen real estate available to the BF browser and improve layout, but you can opt out, e.g. if your browser post-selection context needs this messaging. Note: this will only take effect if you elect to customize page output in the first place (see above).'),
    ];

    $form['bf_browser']['media_library_bf_browser_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Browser Height for Media Library'),
      '#description' => $this->t('An explicit height for the Brandfolder browser in pixels, when rendered in a Media Library context. If this setting is empty, the browser will do its best to occupy the available space in an optimal way. If that is not working well for you, you can experiment with this setting.'),
      // @todo: Let people enter values in %, vh, etc.
      '#default_value' => $config->get('media_library_bf_browser_height'),
      '#min' => 300,
      '#max' => 10000,
    ];


    /************************************
     * Image Optimization
     ************************************/
    $form['image_optimization'] = [
      '#type'  => 'details',
      '#title' => $this->t('Image Optimization'),
      '#description' => $this->t('These settings can help reduce image file size. These are not applicable to SVG images.'),
    ];

    $form['image_optimization']['io_format_auto'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Automatically calculate image format'),
      '#default_value' => $config->get('io_format_auto') ?? TRUE,
      '#description' => $this->t('If enabled, the CDN will calculate the best image format to deliver to each client/browser based on a variety of factors. This tends to yield the most optimized results. The "quality" parameter will still be respected if provided (see below).'),
    ];

    $form['image_optimization']['io_format_auto_force'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use auto-format even when a specific format is requested'),
      '#default_value' => $config->get('io_format_auto_force') ?? TRUE,
      '#description' => $this->t('Use the auto-format option (see above) regardless of whether a particular image is being requested in a specific format. E.g. a Drupal image style effect might specify conversion to PNG or WebP, but this setting will override that and deliver the best format for each client.'),
      '#states' => [
        'disabled' => [
          ':input[name="io_format_auto"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['image_optimization']['io_auto_webp'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Automatically use WebP format for images if supported'),
      '#default_value' => (bool) $config->get('io_auto_webp'),
      '#description' => $this->t('This will deliver a WebP version of an image if the user\'s browser supports that format. This is not relevant if using the auto-format option above (dynamic WebP delivery is included in that mode). If you find that the auto-format option is not suitable for you, then you may wish to experiment with this WebP option.'),
      '#states' => [
        'disabled' => [
          ':input[name="io_format_auto"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['image_optimization']['io_quality'] = [
      '#type'          => 'number',
      '#min'           => 1,
      '#max'           => 100,
      '#title'         => $this->t('Quality to use for all lossy/compressed images'),
      '#default_value' => $config->get('io_quality') ?? '',
      '#description' => $this->t('Choose a value between 1 and 100 (default). A lower value will result in smaller image file sizes (and faster image load time) but also less detail/fidelity. You can experiment to find something that reduces image sizes without too much obvious degradation.'),
    ];


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

    if ($bf && $brandfolder_id) {
      /**********************************
       * Custom Fields/Alt Text.
       **********************************/
      $custom_field_options = $none_option_array;
      if ($custom_field_ids_and_names = $bf->listCustomFields(NULL, FALSE, TRUE)) {
        $custom_field_options = array_merge($custom_field_options, $custom_field_ids_and_names);
      }

      $current_alt_text_custom_field_key_id = $config->get('alt_text_custom_field');
      $current_alt_text_custom_field_key_name = NULL;
      if (empty($current_alt_text_custom_field_key_id) || !array_key_exists($current_alt_text_custom_field_key_id, $custom_field_options)) {
        $current_alt_text_custom_field_key_id = 'none';
      }
      else {
        $current_alt_text_custom_field_key_name = $custom_field_options[$current_alt_text_custom_field_key_id];
      }

      $form['metadata']['alt_text_custom_field'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Alt-Text Custom Field'),
        '#options'       => $custom_field_options,
        '#default_value' => $current_alt_text_custom_field_key_id,
        '#description'   => $this->t('You can use a custom field in Brandfolder to store alt-text for assets, and Drupal will pull text from that field for use with Brandfolder-sourced images, where applicable. To enable this functionality, select the Brandfolder field you plan to use to store alt-text values.'),
      ];


      /************************************
       * Sample Images
       ************************************/
      // Display some images from the selected Brandfolder/collection if
      // applicable.

      $form['sample_image_width'] = [
        '#type'          => 'number',
        '#min'           => 16,
        '#max'           => 1920,
        '#title'         => $this->t('Sample image width (in pixels)'),
        '#default_value' => $config->get('sample_image_width') ?? 400,
        '#description'   => $this->t('Optionally adjust the width of the sample images below, for testing. The default is 400px.'),
      ];

      $sample_image_extensions = [
        'jpg',
        'jpeg',
        'png',
      ];

      $params = [
        'fields'  => 'cdn_url',
        'sort_by' => 'updated_at',
        'order'   => 'desc',
        'search'  => '(approved:true) AND (expired:false) AND (unpublished:false) AND (extension:(' . implode(' OR ', $sample_image_extensions) . '))',
        'include' => 'custom_fields',
      ];
      if ($preview_collection_id) {
        $assets = $bf->listAssets($params, $preview_collection_id);
      }
      else {
        $assets = $bf->listAssets($params);
      }
      if ($assets) {
        $sample_image_width = $config->get('sample_image_width') ?? 400;
        $cdn_url_param_string = "width=$sample_image_width";
        // Apply image optimization settings to the sample images so users can
        // do some basic testing.
        if ($config->get('io_format_auto')) {
          $cdn_url_param_string .= '&format=auto';
        }
        elseif ($config->get('io_auto_webp')) {
          $cdn_url_param_string .= '&auto=webp';
        }
        if ($config->get('io_quality')) {
          $cdn_url_param_string .= '&quality=' . $config->get('io_quality');
        }
        $thumbnails = array_map(function ($asset) use ($current_alt_text_custom_field_key_name, $cdn_url_param_string) {
          $output = '';
          $url = $asset->attributes->cdn_url;
          if ($url) {
            // Strip any query string from the URL.
            $url = preg_replace('/^([^?]+)\?.*$/', '$1', $url);
            $url .= '?' . $cdn_url_param_string;
            $alt_text = 'Brandfolder image for illustrative purposes only';
            if ($current_alt_text_custom_field_key_name && isset($asset->custom_field_values[$current_alt_text_custom_field_key_name])) {
              $alt_text = $asset->custom_field_values[$current_alt_text_custom_field_key_name];
            }
            $output .= "<div class=\"brandfolder-sample-image-wrapper\"><img src=\"$url\" alt=\"$alt_text\" /></div>";
          }

          return $output;
        }, $assets->data);

        $form['sample_pics'] = [
          '#type'   => 'markup',
          '#prefix' => '<h2>Sample Images</h2>',
          '#markup' => '<p class="sample-images-intro">Showing approved, published, non-expired assets from the selected Brandfolder (and collection, if applicable) with the following filetypes/extensions: <em>' . implode(', ', $sample_image_extensions) . '</em></p>'
              . '<div class="brandfolder-sample-images">' . implode(' ', $thumbnails) . '</div>',
          '#weight' => 999,
        ];
      }
    }

    if ($bf && $config->get('verbose_log_mode')) {
      foreach ($bf->getLogData() as $log_entry) {
        $this->logger('brandfolder')->debug($log_entry);
      }
      $bf->clearLogData();
    }

    $form['#attached']['library'][] = 'brandfolder/brandfolder-admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Check to see if all API keys are valid.
    // @todo: Additionally, validate that each key is associated with the correct user role. There appears to be no straightforward way to do this via the API, but we can deduce it by attempting operations that are only allowed for certain roles.
    $a_valid_api_key_exists = FALSE;
    $config = $this->config('brandfolder.settings');

    foreach ($this->api_key_types as $api_key_type => $api_key_type_label) {
      $field_name = "api_key_ids_$api_key_type";
      $api_key_id = $form_state->getValue($field_name);
      if (!empty($api_key_id)) {
        if ($key_entity = $this->key_repository->getKey($api_key_id)) {
          $api_key = $key_entity->getKeyValue();
          $bf = brandfolder_api(NULL, $api_key);
          if ($config->get('verbose_log_mode')) {
            $bf->enableVerboseLogging();
          }
          $brandfolders = $bf->listAllBrandfolderNames();
          // Note that the getBrandfolders request will return a 200 response
          // even if the API key is invalid, and the brandfolders array will
          // simply be empty. This is a quirk of the Brandfolder API.
          if (!empty($brandfolders)) {
            $a_valid_api_key_exists = TRUE;
          }
          else {
            $message = $this->t('Could not connect to Brandfolder using the @key_type API key. Make sure the key is correct and is linked to a Brandfolder user who has permission to access at least one Brandfolder.', ['@key_type' => $api_key_type]);
            $form_state->setErrorByName($field_name, $message);
          }
          if ($config->get('verbose_log_mode')) {
            foreach ($bf->getLogData() as $log_entry) {
              $this->logger('brandfolder')->debug($log_entry);
            }
            $bf->clearLogData();
          }
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('brandfolder.settings');

    foreach ($this->api_key_types as $api_key_type => $api_key_type_label) {
      $config_name = "api_key_ids.$api_key_type";
      $field_name = "api_key_ids_$api_key_type";
      $config->set($config_name, $form_state->getValue($field_name));
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

    $config->set('metadata_sync_mode', $form_state->getValue('metadata_sync_mode'));
    $config->set('customize_entity_browser_modal_pages', $form_state->getValue('customize_entity_browser_modal_pages'));
    $config->set('disable_system_messages_on_browser_pages', $form_state->getValue('disable_system_messages_on_browser_pages'));
    $config->set('media_library_bf_browser_height', $form_state->getValue('media_library_bf_browser_height'));
    $config->set('io_format_auto', $form_state->getValue('io_format_auto'));
    $config->set('io_format_auto_force', $form_state->getValue('io_format_auto_force'));
    $config->set('io_auto_webp', $form_state->getValue('io_auto_webp'));
    $config->set('io_quality', $form_state->getValue('io_quality'));
    $config->set('sample_image_width', $form_state->getValue('sample_image_width'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}

<?php

namespace Drupal\brandfolder\Plugin\media\Source;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

/**
 * Allows Brandfolder image attachments to be used by Drupal's Media system.
 *
 * @todo: generic BrandfolderAsset or BrandfolderAttachment class and various default type-based classes (image, video, etc.)
 *
 * @MediaSource(
 *   id = "brandfolder_image",
 *   label = @Translation("Brandfolder Image"),
 *   description = @Translation("Allows Brandfolder attachments to be used by Drupal's Media system. Automatically creates an image field linked to a Brandfolder attachment, such that the image will be updated whenever the attachment changes in Brandfolder."),
 *   allowed_field_types = {"string"}
 * )
 */
class BrandfolderImage extends MediaSourceBase {

  /**
   * Brandfolder API.
   *
   * @var \Brandfolder\Brandfolder
   *   BF SDK.
   */
  protected $brandfolderClient;

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Statically cached metadata information for the given attachments.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   *
   * @todo: Remove if unused.
   */
  protected $time;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *
   * @todo: Remove if unused.
   */
  protected $moduleHandler;

  /**
   * The inviolable source field name.
   *
   * @var string
   */
  protected $source_field_name;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, AccountProxyInterface $account_proxy, UrlGeneratorInterface $url_generator, LoggerChannelFactoryInterface $logger, CacheBackendInterface $cache, TimeInterface $time, ModuleHandlerInterface $module_handler) {
    $this->source_field_name = 'field_brandfolder_attachment_id';

    // Customize some aspects of the plugin definition.
    // @see \Drupal\media\MediaSourceBase
    // Our media thumbnails are the same as the primary image for each media
    // item, i.e. the Brandfolder attachment served via a BF CDN URL.
    // See comment about this in BrandfolderImage::getMetadata() under the
    // "thumbnail_uri" case.
    $plugin_definition['thumbnail_width_metadata_attribute'] = 'width';
    $plugin_definition['thumbnail_height_metadata_attribute'] = 'height';
    $plugin_definition['thumbnail_alt_metadata_attribute'] = 'alt_text';

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

    // @todo: DI
    $this->brandfolderClient = brandfolder_api();
    $this->accountProxy = $account_proxy;
    $this->urlGenerator = $url_generator;
    $this->logger = $logger;
    $this->cache = $cache;
    $this->time = $time;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('logger.factory'),
      $container->get('cache.data'),
      $container->get('datetime.time'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'name' => $this->t('Name'),
      'description' => $this->t('Asset description'),
      'mime_type' => $this->t('MIME type'),
      'filename' => $this->t('File name'),
      'file_extension' => $this->t('File extension'),
      'thumbnail_url' => $this->t('Thumbnail url'),
      'filesize' => $this->t('File size'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'asset_creation_date'=> 'Brandfolder asset upload date (format: "Y-m-d")',
      'asset_creation_datetime_minutes'=> 'Brandfolder asset upload date/time with minutes (format: "Y-m-d\TH:i)',
      'asset_creation_datetime_seconds'=> 'Brandfolder asset upload date/time with seconds (format: "Y-m-d\TH:i:s)',
      'asset_creation_datetime_milliseconds'=> 'Brandfolder asset upload date/time with milliseconds (format: "Y-m-d\TH:i:s.v)',
      'asset_updated_date'=> 'Brandfolder asset updated date (format: "Y-m-d)',
      'asset_updated_datetime_minutes'=> 'Brandfolder asset updated date/time with minutes (Y-m-d\TH:i)',
      'asset_updated_datetime_seconds'=> 'Brandfolder asset updated date/time with seconds (Y-m-d\TH:i:s)',
      'asset_updated_datetime_milliseconds'=> 'Brandfolder asset updated date/time with milliseconds (Y-m-d\TH:i:s.v)',
      'bf_position' => $this->t('Brandfolder attachment position'),
      //      'tags' => $this->t('Tags'),
    ];

    $forcefully_updated_fields = $this->getForcefullyUpdatedMetadataAttributes();
    array_walk($fields, function(&$field_label, $field_name) use ($forcefully_updated_fields) {
      if (in_array($field_name, $forcefully_updated_fields)) {
        $field_label .= $this->t(' (will always be updated whenever Brandfolder data changes).');
      }
    });

    $config = $this->configFactory->get('brandfolder.settings');
    $alt_text_custom_field_id = $config->get('alt_text_custom_field');
    if (!empty($alt_text_custom_field_id)) {
      $fields['alt_text'] = $this->t('Alt-Text (from Brandfolder custom field)');
      // @todo: Allow admins to specify BF custom fields other than alt-text?
    }

    return $fields;
  }

  /**
   * Provide a list of metadata fields that should always be updated with fresh
   * Brandfolder data even if the target/mapped media entity field has a value
   * in Drupal.
   *
   * @return string[]
   *
   * @todo: Consider making these configurable per field per media type...
   */
  public function getForcefullyUpdatedMetadataAttributes() {
    // @todo: Consider expanding this list. Obviously something like filemime ought to be updated if it changes in Brandfolder. However, we'd need to do more than just update this media metadata - we'd need to update the relevant Drupal file and figure out any usage/validation/etc. implications.

    return [
//      'name',
//      'description',
//      'mime_type',
//      'filename',
//      'file_extension',
//      'thumbnail_url',
//      'filesize',
//      'width',
//      'height',
//      'asset_creation_date',
//      'asset_creation_datetime_minutes',
//      'asset_creation_datetime_seconds',
//      'asset_creation_datetime_milliseconds',
      'asset_updated_date',
      'asset_updated_datetime_minutes',
      'asset_updated_datetime_seconds',
      'asset_updated_datetime_milliseconds',
      'bf_position',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => $this->source_field_name,
      'source_field_label' => $this->t('Brandfolder Attachment ID'),
      'brandfolder' => [
        'bf_entity_criteria' => [],
      ],
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $merged_config = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
    $merged_config['source_field'] = $this->source_field_name;

    $this->configuration = $merged_config;
  }

  /**
   * Limit the source field options to our preferred field (if it already
   * exists).
   *
   * @return string[]
   *   A list of source field options for the media type form.
   */
  protected function getSourceFieldOptions() {
    // If the field already exists, populate the options list with it.
    // @todo: Efficiency, etc.
    $options = [];
    foreach ($this->entityFieldManager->getFieldStorageDefinitions('media') as $field_name => $field) {
      $allowed_type = in_array($field->getType(), $this->pluginDefinition['allowed_field_types'], TRUE);
      if ($field_name == $this->configuration['source_field'] && $allowed_type && !$field->isBaseField()) {
        $options[$field_name] = $field->getLabel();
        break;
      }
    }

    return $options;
  }

  /**
   * Determine the name of the source field.
   *
   * @return string
   *   The source field name. Always use our explicit field machine name.
   */
  protected function getSourceFieldName(): string {
    return $this->source_field_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function createSourceFieldStorage() {
    // Note: we override this method to ensure that our source field is locked
    // (we don't want users editing it directly). It should always be derived
    // from a Brandfolder browser or sync/etc. operation.
    return $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => $this->getSourceFieldName(),
        'type' => 'string',
        'locked' => TRUE,
      ]);
  }

  /**
   * {@inheritdoc}
   *
   * Note: We override the parent method because we do not want to use the
   * media source plugin name/label as the source field label.
   */
  public function createSourceField(MediaTypeInterface $type) {
    $storage = $this->getSourceFieldStorage() ?: $this->createSourceFieldStorage();
    $field = $this->entityTypeManager
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $type->id(),
        'label' => $this->configuration['source_field_label'] ?? $this->pluginDefinition['label'],
        'required' => TRUE,
      ]);

    // @todo: Disable the field on the default form display. This can't be done here, because the field isn't saved yet.
//    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
//    $display_repository = \Drupal::service('entity_display.repository');
//
//    try {
//      $display_repository->getFormDisplay('media', $type->id())
//        ->removeComponent($storage->getName())
//        ->save();
//    }
//    catch (\Exception $e) {
//      $this->logger->error('Error when disabling Brandfolder Attachment ID field on form display. @msg', ['@msg' => $e->getMessage()]);
//    }

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    // Set the view display so only our special bf_image field is displayed
    // initially. This is important because, by default, only the source field
    // would be shown, which in our case is a textual attachment ID, so this
    // is a much better out-of-the-box experience.
    // Admins can obviously tweak as desired.
    // @todo: A nice feature might be to allow admins to select another media type from which to copy any applicable settings. Useful when you're converting an existing site to use Brandfolder and have numerous media view modes with various image formatters.
    $display->setComponent('bf_image', ['type' => 'image', 'label' => 'visually_hidden']);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $bf_attachment_id = $this->getSourceFieldValue($media);

    // @todo: Consider caching metadata and/or API response data.
//    if (isset($this->metadata[$bf_attachment_id][$attribute_name])) {
//      return $this->metadata[$bf_attachment_id][$attribute_name];
//    }

    // Do we want to support the attachment ID as metadata? It could be useful,
    // but I can also see it leading to confusion with our (often hidden)
    // source field.
    // @see ::getMetadataAttributes()
    //    if ($attribute_name == 'bf_attachment_id') {
    //      return $bf_attachment_id;
    //    }

    $api_params = [
      'include' => 'asset',
      //      'fields' => 'metadata,extension,version_count,tag_names'
      'fields' => 'extension'
    ];
    $attachment = $this->brandfolderClient->fetchAttachment($bf_attachment_id, $api_params);
    // In the edge case that we are unable to fetch the attachment from BF,
    // we can return generic values for certain attributes, but are generally
    // incapable of proceeding.
    if (!$attachment) {
      if (in_array($attribute_name, ['name', 'default_name'])) {

        return "Brandfolder attachment $bf_attachment_id";
      }
      else {

        return FALSE;
      }
    }

    $asset_dependent_attributes = [
      'name',
      'description',
      'alt_text',
      'default_name',
      //      'tags', ??
    ];

    $datetime_attributes_and_formats = [
      'asset_creation_date' => 'Y-m-d',
      'asset_creation_datetime_minutes'=> 'Y-m-d\TH:i',
      'asset_creation_datetime_seconds'=> 'Y-m-d\TH:i:s',
      'asset_creation_datetime_milliseconds'=> 'Y-m-d\TH:i:s.v',
      'asset_updated_date'=> 'Y-m-d',
      'asset_updated_datetime_minutes'=> 'Y-m-d\TH:i',
      'asset_updated_datetime_seconds'=> 'Y-m-d\TH:i:s',
      'asset_updated_datetime_milliseconds'=> 'Y-m-d\TH:i:s.v',
    ];
    $datetime_attribute_names = array_keys($datetime_attributes_and_formats);
    $asset_dependent_attributes = array_merge($asset_dependent_attributes, $datetime_attribute_names);

    $custom_field_attributes = [
      'alt_text'
    ];

    $asset = FALSE;
    if (in_array($attribute_name, $asset_dependent_attributes)) {
      $asset_id = $attachment->data->relationships->asset->data->id ?? FALSE;
      if ($asset_id) {
        $api_params = [
          'fields' => 'created_at,updated_at',
        ];
        if (in_array($attribute_name, $custom_field_attributes)) {
          $api_params['include'] = 'custom_fields';
        }
        $asset = $this->brandfolderClient->fetchAsset($asset_id, $api_params);
      }
      if (!$asset) {

        return FALSE;
      }
    }

    // Consolidated handling for any date/time attributes.
    if (isset($datetime_attributes_and_formats[$attribute_name])) {
      try {
        $format = $datetime_attributes_and_formats[$attribute_name];
        $datetime_string = (strpos($attribute_name, 'asset_creation') === 0) ? $asset->data->attributes->created_at : $asset->data->attributes->updated_at;
        $date = new \DateTime($datetime_string);
        $formatted_output = $date->format($format);
      }
      catch (\Exception $e) {
        $formatted_output = FALSE;
      }

      return $formatted_output;
    }

    switch ($attribute_name) {
      case 'thumbnail_uri':
        // Note that the Media module expects there to be a Drupal file entity
        // for the thumbnail. The Drupal media thumbnail for Brandfolder
        // attachments will correspond to the BF CDN URL rather than the
        // "thumbnail_url" value returned by the API. This is somewhat
        // unintuitive but is actually desirable because (a) many Drupal users
        // and modules use the media thumbnail as a quick way to get from an
        // entity reference field to an actual image, and (b) the thumbnail_url
        // for an attachment in Brandfolder
        // (e.g. "https://thumbs.brandfolder.com/yadayadda") is not
        // something that can be changed without changing the attachment
        // source file (unlike assets, for which users can customize the
        // thumbnail in Brandfolder).
        if ($fid = brandfolder_map_attachment_to_file($bf_attachment_id)) {
          if ($file = File::load($fid)) {
            $uri = $file->getFileUri();
            if (!empty($uri)) {

              return $uri;
            }
          }
        }
        break;

      case 'name':
        return "{$asset->data->attributes->name} - {$attachment->data->attributes->filename}";

      case 'filesize':
        return $attachment->data->attributes->size;

      case 'width':
        return $attachment->data->attributes->width;

      case 'height':
        return $attachment->data->attributes->height;

      case 'filename':
        return $attachment->data->attributes->filename;

      case 'mime_type':
        return $attachment->data->attributes->mimetype;

      case 'file_extension':
        return $attachment->data->attributes->extension;

      case 'bf_position':
        // Positions as used internally by Brandfolder and returned via API
        // start at zero. However, CDN URLs provided in the Brandfolder web app
        // use positions starting at one. Therefore, we iIncrement the internal
        // position index here to match non-technical users' expectations.
        return $attachment->data->attributes->position + 1;

      case 'description':
        return $asset->data->attributes->description;

      case 'default_name':
        return ($asset ? "{$asset->data->attributes->name} - {$attachment->data->attributes->filename}" : $attachment->data->attributes->filename);

      case 'alt_text':
        $alt_text = FALSE;
        // See if alt text exists for the media entity's image field. This may
        // be manually entered or pulled from BF (depending on metadata sync
        // settings). If it exists, use it.
        $imageItem = $media->get('bf_image');
        $image_field_data = ($imageItem && $imageItem->count() > 0) ? $imageItem->first()->getValue() : FALSE;
        if ($image_field_data && !empty($image_field_data['alt'])) {
          $alt_text = $image_field_data['alt'];
        }
        // Otherwise, look into fetching from a BF custom field.
        // @todo: This really shouldn't be necessary if webhooks are active and metadata is syncing, right? Should we keep this for greater redundancy/robustness? Downside is longer execution time.
        else {
          // @todo: Show messaging encouraging admins to create and specify this custom field as needed, etc.
          $config = $this->configFactory->get('brandfolder.settings');
          $alt_text_custom_field_id = $config->get('alt_text_custom_field');
          if (!empty($alt_text_custom_field_id)) {
            // Look up the current name associated with the given custom field
            // key ID.
            if ($custom_field_keys = $this->brandfolderClient->listCustomFields(NULL, FALSE, TRUE)) {
              if (isset($custom_field_keys[$alt_text_custom_field_id])) {
                $custom_field_name = $custom_field_keys[$alt_text_custom_field_id];
                if (!empty($asset->data->custom_field_values[$custom_field_name])) {
                  $alt_text = $asset->data->custom_field_values[$custom_field_name];
                }
              }
            }
          }
        }
        return $alt_text;

      //        default:
      //          return isset($this->metadata[$bf_attachment_id][$name]) ? $this->metadata[$bf_attachment_id][$name] : FALSE;
    }

    return FALSE;
  }

  /**
   * Keep track of explicit user-driven and/or Drupal-initiated changes to
   * media entity fields where such changes overwrite data that had been pulled
   * in from Brandfolder. We need to do this in order to know whether future
   * Brandfolder data updates should be propagated to the relevant Drupal
   * entity fields. If those fields have been modified on the Drupal side, we
   * want to leave that data untouched. Otherwise, we want to update them using
   * the latest BF data.
   *
   * @param \Drupal\media\MediaInterface $media
   *
   * @todo
   */
  public function trackMetadataOverrides(MediaInterface $media) {
    $bf_attachment_id = $this->getSourceFieldValue($media);
    $all_metadata = $this->getMetadataAttributes();
    $metadata_attributes = array_keys($all_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    if ($this->brandfolderClient) {
      $test_result = $this->brandfolderClient->listAssets(['per' => 1]);
      $bf_connection_error = $test_result === FALSE;
    }
    else {
      $bf_connection_error = TRUE;
    }

    if ($bf_connection_error) {
      if ($this->accountProxy->hasPermission('administer brandfolder settings')) {
        // @todo: Cause this to appear on AJAX media type config form update.
        $this->messenger()
          ->addError($this->t('Could not connect to Brandfolder. Make sure the connection is <a href=":url">configured</a> properly.', [
            ':url' => $this->urlGenerator->generateFromRoute('brandfolder.brandfolder_settings_form'),
          ]));
      }
      else {
        $this->messenger()
          ->addError($this->t('Something went wrong with the Brandfolder connection. Please contact the site administrator.'));
      }

      return FALSE;
    }

    $form = parent::buildConfigurationForm($form, $form_state);

    // If our preferred source field already exists, do not allow users to
    // request the creation of a new source field.
    if (count($form['source_field']['#options']) > 0) {
      unset($form['source_field']['#empty_option']);
      // @todo: Update field description accordingly, disable field, etc.
    }

    $gatekeeper = \Drupal::getContainer()
      ->get(BrandfolderGatekeeper::class);
    $gatekeeper->loadFromMediaSource($this);
    $gatekeeper->buildConfigForm($form);

    return $form;
  }

  /**
   * Creates the image field storage definition.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved field storage definition.
   */
  public function createImageFieldStorage() {
    return $this->entityTypeManager->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => 'bf_image',
        'type' => 'image',
        'cardinality' => 1,
        'locked' => TRUE,
        'field_settings' => [
          'uri_scheme' => 'bf',
          'target_type' => 'file'
        ],
      ]);
  }

  /**
   * Creates the image field definition.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type.
   *
   * @return \Drupal\field\FieldConfigInterface
   *   The unsaved field definition. The field storage definition, if new,
   *   should also be unsaved.
   */
  public function createImageField(MediaTypeInterface $type) {
    return $this->entityTypeManager->getStorage('field_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => 'bf_image',
        'bundle' => $type->id(),
        'label' => 'Brandfolder Image',
        'translatable' => FALSE,
        'field_type' => 'image',
        'settings' => [
          'file_extensions' => '',
          'alt_field_required' => 0,
        ],
      ]);
  }

}

<?php

namespace Drupal\brandfolder\Plugin\media\Source;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   */
  protected $time;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
      'created' => $this->t('Asset upload date/time'),
      'updated' => $this->t('Asset last updated date/time'),
      'bf_position' => $this->t('Brandfolder attachment position'),
      'tags' => $this->t('Tags'),
      'alt_text' => $this->t('Alt-Text'),
      // @todo: Allow admins to specify BF custom fields other than alt-text?
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'field_brandfolder_attachment_id',
      'source_field_label' => 'Brandfolder Attachment ID',
      'brandfolder' => [
        'bf_entity_criteria' => [],
      ],
    ];
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
   *
   * @todo: Collision testing.
   */
  protected function getSourceFieldName() {

    return $this->configuration['source_field'];
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
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $bf_attachment_id = $this->getSourceFieldValue($media);

    // Do we want to support the attachment ID as metadata? It could be useful,
    // but I can also see it leading to confusion with our (often hidden)
    // source field.
    // @see ::getMetadataAttributes()
//    if ($attribute_name == 'bf_attachment_id') {
//      return $bf_attachment_id;
//    }

    $api_params = [
      'include' => 'asset',
//      'fields' => 'metadata,thumbnail_url,view_thumbnail_retina,extension,version_count,tag_names'
      'fields' => 'thumbnail_url,extension'
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

//    $all_fields = [
//      'name' => $this->t('Name'),
//      'description' => $this->t('Asset description'),
//      'mime_type' => $this->t('MIME type'),
//      'filename' => $this->t('File name'),
//      'file_extension' => $this->t('File extension'),
//      'thumbnail_url' => $this->t('Thumbnail url'),
//      'filesize' => $this->t('File size'),
//      'width' => $this->t('Width'),
//      'height' => $this->t('Height'),
//      'created' => $this->t('Asset upload date/time'),
//      'updated' => $this->t('Asset last-updated date/time'),
//      'bf_position' => $this->t('Brandfolder attachment position'),
//      'tags' => $this->t('Tags'),
//      'alt_text' => $this->t('Alt-Text (if available)'),
//      // @todo: Allow admins to specify BF custom fields other than alt-text?
//    ];

    $asset_dependent_attributes = [
      'name',
      'description',
      'created',
      'updated',
      'alt_text',
//      'tags', ??
    ];
    $custom_field_attributes = [
      'alt_text'
    ];

    if (in_array($attribute_name, $asset_dependent_attributes)) {
      $api_params = in_array($attribute_name, $custom_field_attributes) ? ['include' => 'custom_fields'] : [];
      $asset = $this->brandfolderClient->fetchAsset($attachment->asset->id, $api_params);
      if (!$asset) {

        return FALSE;
      }
    }

    switch ($attribute_name) {
      case 'thumbnail_uri':
//        return $attachment->data->attributes->thumbnail_url;
        // Alternate approach. Media expects a Drupal file entity.
        // @todo.
        if ($fid = brandfolder_map_attachment_to_file($bf_attachment_id)) {
          if ($file = File::load($fid)) {
            $uri = $file->getFileUri();
            if (!empty($uri)) {

              return $uri;
            }
          }
        }

      case 'name':
        return "{$asset->data->attributes->name} - {$attachment->data->attributes->filename}";

      case 'created':
        return $asset->data->attributes->created;

      case 'updated':
        return $asset->data->attributes->updated;

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
        return $attachment->data->attributes->position;

      case 'description':
        return $asset->data->attributes->description;

      case 'default_name':
        return ($asset ? "{$asset->data->attributes->name} - {$attachment->data->attributes->filename}" : $attachment->data->attributes->filename);

      case 'alt_text':
        // @todo: Make this field name configurable, show messaging encouraging admins to create it, etc.
        return $asset->data->custom_field_values['alt-text'] ?? FALSE;

//        default:
//          return isset($this->metadata[$bf_attachment_id][$name]) ? $this->metadata[$bf_attachment_id][$name] : FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bf_connection_error = FALSE;
    if ($this->brandfolderClient) {
      try {
        $this->brandfolderClient->listAssets();
      }
      catch (\Exception $exception) {
        $bf_connection_error = TRUE;
      }
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
   * @return \Drupal\field\FieldStorageConfigInterface
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
      ]);
  }

}

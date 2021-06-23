<?php

namespace Drupal\brandfolder\Plugin\media\Source;

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
 * Allows Brandfolder image assets to be used by Drupal's Media system.
 *
 * @todo: generic BrandfolderAsset class and various default type-based classes (image, video, etc.)
 *
 * @MediaSource(
 *   id = "brandfolder_image",
 *   label = @Translation("Brandfolder Image"),
 *   description = @Translation("Allows Brandfolder assets to be used by Drupal's Media system."),
 *   allowed_field_types = {"string", "image"}
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
   * Statically cached metadata information for the given assets.
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
      'asset_id' => $this->t('Asset ID'),
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
      'type' => $this->t('Type'),
      'thumbnail_url' => $this->t('Thumbnail url'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'created' => $this->t('Date created'),
      'updated' => $this->t('Date updated'),
      'tags' => $this->t('Tags'),
      // @todo: Custom BF fields, including semi-required fields such as alt-text.
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'field_brandfolder_asset_id',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $bf_asset_id = $this->getSourceFieldValue($media);
    if ($name == 'bf_asset_id') {
      return $bf_asset_id;
    }

    switch ($name) {
      case 'thumbnail_uri':
        // @todo: Use specified thumb from Brandfolder. Store in local metadata cache/store. Look into "sig" (signature) URL param lifespan and expiry param, etc.
//          $thumbnail_url = "https://thumbs.bfldr.com/as/$bf_asset_id?expiry=1624467346&fit=bounds&height=162&sig=ZTY3ZDA5MTFjMWQxMmQ1Yjk5ZjJjYTg3OTczZGYxZDgzODYwZWQ3Yw%3D%3D&width=262";

        $thumb_uri = NULL;

        // Alt.
        if ($fid = brandfolder_map_asset_to_file($bf_asset_id)) {
          $file = File::load($fid);
          $thumb_uri = $file->getFileUri();
        }

        return $thumb_uri;

//        case 'created':
//          return isset($this->metadata[$bf_asset_id]['dateCreated']) ? $this->metadata[$bf_asset_id]['dateCreated'] : FALSE;
//
//        case 'modified':
//          return isset($this->metadata[$bf_asset_id]['dateModified']) ? $this->metadata[$bf_asset_id]['dateModified'] : FALSE;

      case 'default_name':
//          return isset($this->metadata[$bf_asset_id]['name']) ? $this->metadata[$bf_asset_id]['name'] : parent::getMetadata($media, 'default_name');
        // @todo
        return "Brandfolder Asset $bf_asset_id";

//        default:
//          return isset($this->metadata[$bf_asset_id][$name]) ? $this->metadata[$bf_asset_id][$name] : FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    try {
      $this->brandfolderClient->listAssets();
    }
    catch (\Exception $exception) {
      if ($this->accountProxy->hasPermission('administer brandfolder settings')) {
        $this->messenger()->addError($this->t('Could not connect to Brandfolder. Make sure the connection is <a href=":url">configured</a> properly.', [
          ':url' => $this->urlGenerator->generateFromRoute('brandfolder.brandfolder_settings_form'),
        ]));
      }
      else {
        $this->messenger()->addError($this->t('Something went wrong with the Brandfolder connection. Please contact the site administrator.'));
      }
    }

    // @todo: config such as allowed collections, etc.

    return parent::buildConfigurationForm($form, $form_state);
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

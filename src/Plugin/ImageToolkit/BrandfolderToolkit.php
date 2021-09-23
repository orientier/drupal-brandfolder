<?php

namespace Drupal\brandfolder\Plugin\ImageToolkit;

use Drupal\Component\Utility\Color;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines the Brandfolder toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "brandfolder",
 *   title = @Translation("Brandfolder image toolkit supporting CDN-based image transformations")
 * )
 */
class BrandfolderToolkit extends ImageToolkitBase {

  /**
   * DB connection.
   *
   * @var Connection
   */
  protected Connection $db;

//  /**
//   * An image resource.
//   *
//   * @var resource|null
//   */
//  protected $resource = NULL;
//
//  /**
//   * Image type represented by a PHP IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG).
//   *
//   * @var int
//   */
//  protected $type;
//
//  /**
//   * Image information from a file, available prior to loading the GD resource.
//   *
//   * This contains a copy of the array returned by executing getimagesize()
//   * on the image file when the image object is instantiated. It gets reset
//   * to NULL as soon as the GD resource is loaded.
//   *
//   * @var array|null
//   *
//   * @see \Drupal\system\Plugin\ImageToolkit\BrandfolderToolkit::parseFile()
//   * @see \Drupal\system\Plugin\ImageToolkit\BrandfolderToolkit::setResource()
//   * @see http://php.net/manual/function.getimagesize.php
//   */
//  protected $preLoadInfo = NULL;
//
//  /**
//   * The StreamWrapper manager.
//   *
//   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
//   */
//  protected $streamWrapperManager;
//
//  /**
//   * The file system.
//   *
//   * @var \Drupal\Core\File\FileSystemInterface
//   */
//  protected $fileSystem;

//  /**
//   * Destructs a GDToolkit object.
//   *
//   * Frees memory associated with a GD image resource.
//   */
//  public function __destruct() {
//    if (is_resource($this->resource)) {
//      imagedestroy($this->resource);
//    }
//  }

  /**
   * Parameters to apply to the CDN URL for the source image.
   *
   * @var array
   */
  protected array $brandfolder_cdn_url_params = [];

  /**
   * Array with key file info.
   *
   * @var array
   */
  protected array $file_data = [];

  /**
   * Constructs a BrandfolderToolkit object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   *   The toolkit operation manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   A database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory, Connection $db_connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $operation_manager, $logger, $config_factory);
    $this->db = $db_connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): BrandfolderToolkit {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.toolkit.operation.manager'),
      $container->get('logger.channel.image'),
      $container->get('config.factory'),
      $container->get('database')
    );
  }

//  /**
//   * Sets the GD image resource.
//   *
//   * @param resource $resource
//   *   The GD image resource.
//   *
//   * @return $this
//   *   An instance of the current toolkit object.
//   */
//  public function setResource($resource) {
//    if (!is_resource($resource) || get_resource_type($resource) != 'gd') {
//      throw new \InvalidArgumentException('Invalid resource argument');
//    }
//    $this->preLoadInfo = NULL;
//    $this->resource = $resource;
//    return $this;
//  }
//
//  /**
//   * Retrieves the GD image resource.
//   *
//   * @return resource|null
//   *   The GD image resource, or NULL if not available.
//   */
//  public function getResource() {
//    if (!is_resource($this->resource)) {
//      $this->load();
//    }
//    return $this->resource;
//  }

  /**
   * Populate an array of Brandfolder CDN URL query parameters relevant to the
   * current image operation.
   *
   * @param array $params
   */
  public function setCdnUrlParams(array $params) {
    $this->brandfolder_cdn_url_params = array_merge($this->brandfolder_cdn_url_params, $params);
  }

  /**
   * Retrieve an array of Brandfolder CDN URL query parameters relevant to the
   * current image operation.
   *
   * @return array
   */
  public function getCdnUrlParams(): array {
    return $this->brandfolder_cdn_url_params;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

//  /**
//   * Loads a GD resource from a file.
//   *
//   * @return bool
//   *   TRUE or FALSE, based on success.
//   */
//  protected function load() {
//    // Return immediately if the image file is not valid.
//    if (!$this->isValid()) {
//      return FALSE;
//    }
//
//    $function = 'imagecreatefrom' . image_type_to_extension($this->getType(), FALSE);
//    if (function_exists($function) && $resource = $function($this->getSource())) {
//      $this->setResource($resource);
//      if (imageistruecolor($resource)) {
//        return TRUE;
//      }
//      else {
//        // Convert indexed images to truecolor, copying the image to a new
//        // truecolor resource, so that filters work correctly and don't result
//        // in unnecessary dither.
//        $data = [
//          'width' => imagesx($resource),
//          'height' => imagesy($resource),
//          'extension' => image_type_to_extension($this->getType(), FALSE),
//          'transparent_color' => $this->getTransparentColor(),
//          'is_temp' => TRUE,
//        ];
//        if ($this->apply('create_new', $data)) {
//          imagecopy($this->getResource(), $resource, 0, 0, 0, 0, imagesx($resource), imagesy($resource));
//          imagedestroy($resource);
//        }
//      }
//      return (bool) $this->getResource();
//    }
//    return FALSE;
//  }

  /**
   * {@inheritdoc}
   */
  public function isValid(): bool {
    // @todo
    return !empty($this->file_data);
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile(): bool {
    $uri = $this->getSource();
    if (preg_match('/^bf:\/\/[^\/]+\/at\/([^\/]+)\/([^\.]*\.([^\?]+))?/', $uri, $matches)) {
      $bf_attachment_id = $matches[1];
      $query = $this->db->select('brandfolder_file', 'bf')
        ->fields('bf', ['filesize', 'width', 'height', 'mime_type', 'bf_attachment_id'])
        ->condition('bf_attachment_id', $bf_attachment_id);
      if ($query->countQuery()->execute()->fetchField() > 0) {
        $result = $query->execute();
        $this->file_data = $result->fetchAssoc();

        $extension = $matches[3] ?? '';
        $this->setType($this->extensionToImageType($extension));

        return TRUE;
      }
    }
    // @todo: Consider trying to (re)load the attachment from Brandfolder if no local record is found, and/or log, and/or perform cleanup operations.

    return FALSE;
  }

//  /**
//   * Gets the color set for transparency in GIF images.
//   *
//   * @return string|null
//   *   A color string like '#rrggbb', or NULL if not set or not relevant.
//   */
//  public function getTransparentColor() {
//    if (!$this->getResource() || $this->getType() != IMAGETYPE_GIF) {
//      return NULL;
//    }
//    // Find out if a transparent color is set, will return -1 if no
//    // transparent color has been defined in the image.
//    $transparent = imagecolortransparent($this->getResource());
//    if ($transparent >= 0) {
//      // Find out the number of colors in the image palette. It will be 0 for
//      // truecolor images.
//      $palette_size = imagecolorstotal($this->getResource());
//      if ($palette_size == 0 || $transparent < $palette_size) {
//        // Return the transparent color, either if it is a truecolor image
//        // or if the transparent color is part of the palette.
//        // Since the index of the transparent color is a property of the
//        // image rather than of the palette, it is possible that an image
//        // could be created with this index set outside the palette size.
//        // (see http://stackoverflow.com/a/3898007).
//        $rgb = imagecolorsforindex($this->getResource(), $transparent);
//        unset($rgb['alpha']);
//        return Color::rgbToHex($rgb);
//      }
//    }
//    return NULL;
//  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() : bool {
    // @todo: Perhaps check to ensure BF credentials/API are available.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    if (empty($this->file_data['width'])) {
      $this->parseFile();
    }

    return $this->file_data['width'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    if (empty($this->file_data['height'])) {
      $this->parseFile();
    }

    return $this->file_data['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFileSize() {
    if (empty($this->file_data['filesize'])) {
      $this->parseFile();
    }

    return $this->file_data['filesize'];
  }

  /**
   * Gets the PHP type of the image.
   *
   * @return int
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   */
  public function getType() : int {
    return $this->type;
  }

  /**
   * Sets the PHP type of the image.
   *
   * @param int $type
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   *
   * @return $this
   */
  public function setType(int $type) : BrandfolderToolkit {
    if (in_array($type, static::supportedTypes())) {
      $this->type = $type;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {

    return $this->getType() ? image_type_to_mime_type($this->getType()) : '';
  }

  /**
   * {@inheritdoc}
   *
   * @todo: is this necessary? We want to serve a CDN URL regardless of apparent file extension.
   */
  public static function getSupportedExtensions(): array {
    $extensions = [];
    foreach (static::supportedTypes() as $image_type) {
      // @todo Automatically fetch possible extensions for each mime type.
      // @see https://www.drupal.org/node/2311679
      $extension = mb_strtolower(image_type_to_extension($image_type, FALSE));
      $extensions[] = $extension;
    }
    // Add some more variants.
    $extensions[] = 'jpg';
    $extensions[] = 'jpe';

    return $extensions;
  }

  /**
   * Returns the IMAGETYPE_xxx constant for the given extension.
   *
   * This is the reverse of the image_type_to_extension() function.
   *
   * @param string $extension
   *   The extension to get the IMAGETYPE_xxx constant for.
   *
   * @return int
   *   The IMAGETYPE_xxx constant for the given extension, or IMAGETYPE_UNKNOWN
   *   for unsupported extensions.
   *
   * @see image_type_to_extension()
   */
  public function extensionToImageType(string $extension) {
    if (in_array($extension, ['jpe', 'jpg'])) {
      $extension = 'jpeg';
    }
    foreach ($this->supportedTypes() as $type) {
      if (image_type_to_extension($type, FALSE) === $extension) {

        return $type;
      }
    }

    return IMAGETYPE_UNKNOWN;
  }

  /**
   * Returns a list of image types supported by the toolkit.
   *
   * @return array
   *   An array of available image types. An image type is represented by a PHP
   *   IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   */
  protected static function supportedTypes(): array {
    return [
      IMAGETYPE_UNKNOWN,
      IMAGETYPE_GIF,
      IMAGETYPE_JPEG,
      IMAGETYPE_PNG,
      IMAGETYPE_SWF,
      IMAGETYPE_PSD,
      IMAGETYPE_BMP,
      IMAGETYPE_TIFF_II,
      IMAGETYPE_TIFF_MM,
      IMAGETYPE_JPEG2000,
      IMAGETYPE_JPC,
      IMAGETYPE_JP2,
      IMAGETYPE_JPX,
      IMAGETYPE_JB2,
      IMAGETYPE_SWC,
      IMAGETYPE_IFF,
      IMAGETYPE_WBMP,
      IMAGETYPE_XBM,
      IMAGETYPE_ICO,
      IMAGETYPE_WEBP,
    ];
  }

}

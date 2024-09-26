<?php

namespace Drupal\brandfolder\Plugin\ImageToolkit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
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

  /**
   * A record of all image operations applied to the current image.
   *
   * @var array
   */
  protected array $operationsRecord = [];

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
   * A semi-immutable copy of the file data array, for reference.
   * This will only be set/reset when parseFile() is called.
   *
   * @var array
   */
  protected array $original_file_data = [];

  /**
   * Image type represented by a PHP IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG).
   *
   * @var int
   */
  protected int $type;

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

  /**
   * Record an image processing operation in order to maintain a record of all
   * operations, in sequence. This context can be used to convert traditional
   * image manipulation sequences into a set of CDN URL parameters that will
   * achieve the same end result.
   *
   * @param string $operation_name The name of the operation, e.g. "resize."
   * @param array $operation_arguments All arguments passed to the operation.
   */
  public function recordOperation(string $operation_name, array $operation_arguments): void {
    $this->operationsRecord[] = [
      'operation' => $operation_name,
      'arguments' => $operation_arguments,
      'file_data' => $this->file_data,
      'bf_cdn_url_params' => $this->brandfolder_cdn_url_params,
    ];
  }

  /**
   * Get a record of all operations performed so far.
   *
   * @return array
   *   The operations record.
   */
  public function getOperationsRecord(): array {
    return $this->operationsRecord;
  }

  /**
   * Populate/overwrite the file_data array.
   *
   * @param array $file_data
   *
   * @return void
   */
  public function setFileData(array $file_data): void {
    $this->file_data = $file_data;
  }

  /**
   * Set an individual item in the file_data array.
   *
   * @param string $key
   * @param $value
   *
   * @return void
   */
  public function setFileDataItem(string $key, $value): void {
    $this->file_data[$key] = $value;
  }

  /**
   * Get the file_data array.
   *
   * @return array
   */
  public function getFileData(): array {
    return $this->file_data;
  }

  /**
   * Get an individual item from the file_data array.
   *
   * @param string $key
   *
   * @return mixed
   */
  public function getFileDataItem(string $key): mixed {
    return $this->file_data[$key] ?? NULL;
  }

  /**
   * Get the original_file_data array.
   *
   * @return array
   */
  public function getOriginalFileData(): array {
    return $this->original_file_data;
  }

  /**
   * Get an individual item from the original_file_data array.
   *
   * @param string $key
   *
   * @return mixed
   */
  public function getOriginalFileDataItem(string $key): mixed {
    return $this->original_file_data[$key] ?? NULL;
  }

  /**
   * Populate an array of Brandfolder CDN URL query parameters relevant to the
   * current image operation.
   *
   * @param array $params
   */
  public function setCdnUrlParams(array $params): void {
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {}

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
    if (preg_match('/^bf:\/\/[^\/]+\/at\/([^\/]+)\/([^.]*\.([^?]+))?/', $uri, $matches)) {
      $bf_attachment_id = $matches[1];
      $query = $this->db->select('brandfolder_file', 'bf')
        ->fields('bf', ['filesize', 'width', 'height', 'mime_type', 'bf_attachment_id'])
        ->condition('bf_attachment_id', $bf_attachment_id);
      if ($query->countQuery()->execute()->fetchField() > 0) {
        $result = $query->execute();
        $file_data = $result->fetchAssoc();
        if (empty($this->original_file_data)) {
          $this->original_file_data = $file_data;
        }
        $this->file_data = $file_data;

        $extension = $matches[3] ?? '';
        $this->setType($this->extensionToImageType($extension));

        return TRUE;
      }
    }
    // @todo: Consider trying to (re)load the attachment from Brandfolder if no local record is found, and/or log, and/or perform cleanup operations.

    return FALSE;
  }

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
   * Get the file size of the image, in bytes.
   *
   * @return int
   */
  public function getFileSize(): int {
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
  public function getMimeType(): string {

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
  public function extensionToImageType(string $extension): int {
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

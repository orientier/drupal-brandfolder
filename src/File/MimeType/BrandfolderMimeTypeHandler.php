<?php

namespace Drupal\brandfolder\File\MimeType;

use Symfony\Component\Mime\MimeTypesInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface as LegacyMimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Drupal\Core\Database\Connection;

use function brandfolder_parse_uri;

/**
 * Extend the core mimetype guesser to support Brandfolder-hosted files.
 * Without this, any module that uses the core guesser for Brandfolder files
 * (the file_entity module does this, for example), could cause those files to
 * be assigned the generic "application/octet-stream" mimetype if the URI does
 * not end with a recognized file extension.
 * Also provide mimetype and extension-related utilities.
 */
class BrandfolderMimeTypeHandler extends ExtensionMimeTypeGuesser implements MimeTypeGuesserInterface, MimeTypesInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Connection $database_connection) {
    $this->database = $database_connection;

    parent::__construct($module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType($path): ?string {
    // If this path is a URI for a Brandfolder attachment, look up the mimetype
    // that we've stored locally, or, if necessary, try to look it up via BF
    // API.
    if ($bf_data = brandfolder_parse_uri($path)) {
      if (!empty($bf_data['type']) && $bf_data['type'] == 'attachment' && !empty($bf_data['id'])) {
        $query = $this->database->select('brandfolder_file', 'bf')
          ->fields('bf', ['mime_type'])
          ->condition('bf_attachment_id', $bf_data['id']);
        if ($query->countQuery()->execute()->fetchField()) {
          $result = $query->execute();
          $mimetype = $result->fetch()->mime_type;
          if (!empty($mimetype)) {

            return $mimetype;
          }
        }

        // If we don't have data for this attachment in our DB for some reason,
        // try to fetch from Brandfolder.
        $bf = brandfolder_api();
        if ($attachment = $bf->fetchAttachment($bf_data['id'])) {
          if (!empty($attachment->data->attributes->mimetype)) {

            return $attachment->data->attributes->mimetype;
          }
        }
      }
    }

    // Default: use the ExtensionMimeTypeGuesser guesser.
    return method_exists($this, 'guessMimeType') ? parent::guessMimeType($path) : parent::guess($path);
  }

  /**
   * Additional method to perform a straightforward extension-based mimetype
   * guess when desired.
   *
   * @param $path
   *
   * @return string|null
   */
  public function guessMimeTypeFromExtension($path) {

    return method_exists($this, 'guessMimeType') ? parent::guessMimeType($path) : parent::guess($path);
  }

  /**
   * Get the mimetype mapping.
   *
   * @return array
   */
  public function getMapping(): array {
    if ($this->mapping === NULL) {
      $mapping = $this->defaultMapping;
      // Allow modules to alter the default mapping.
      $this->moduleHandler->alter('file_mimetype_mapping', $mapping);
    }
    else {
      $mapping = $this->mapping;
    }

    return $mapping;
  }

  /**
   * Attempt to determine a good filename extension based on a file's mimetype.
   *
   * @param $mimetype
   *
   * @return false|int|string
   */
  public function getExtensionFromMimetype($mimetype) {
    $explicit_mapping = [
      'image/gif' => 'gif',
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/svg+xml' => 'svg',
      'image/tiff' => 'tiff',
      'image/webp' => 'webp',
    ];
    if (isset($explicit_mapping[$mimetype])) {

      return $explicit_mapping[$mimetype];
    }

    $mapping = $this->getMapping();
    $mimetype_key = array_search($mimetype, $mapping['mimetypes']);
    if ($mimetype_key !== FALSE) {
      // Note: multiple entries may exist for a given mimetype. This will simply
      // return the first one. Unfortunately, they are not listed in any
      // apparent order.
      $extension = array_search($mimetype_key, $mapping['extensions']);
      if ($extension !== FALSE) {

        return $extension;
      }
    }

    return FALSE;
  }

}

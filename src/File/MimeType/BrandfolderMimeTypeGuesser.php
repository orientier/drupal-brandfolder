<?php

namespace Drupal\brandfolder\File\MimeType;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface as LegacyMimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Drupal\Core\Database\Connection;

use function brandfolder_parse_uri;

/**
 * Extend the core mimetype guesser to support Brandfolder-hosted files.
 * Without this, any module that uses the core guesser for Brandfolder files
 * (the file_entity module does this, for example), will cause those files to
 * be assigned the generic "application/octet-stream" mimetype (due to
 * simplistic parsing of URIs in an attempt to extract a file extension).
 */
class BrandfolderMimeTypeGuesser extends ExtensionMimeTypeGuesser implements MimeTypeGuesserInterface, LegacyMimeTypeGuesserInterface {

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
    return parent::guessMimeType($path);
  }

}

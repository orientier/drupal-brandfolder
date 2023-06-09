<?php

namespace Drupal\brandfolder\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Psr\Log\LoggerInterface;
use Drupal\brandfolder\File\MimeType\BrandfolderMimeTypeHandler;

/**
 * Drupal stream wrapper implementation for Brandfolder.
 *
 * Implements StreamWrapperInterface to provide a Brandfolder wrapper
 * tied to the "bf://" scheme.
 *
 * @ingroup brandfolder
 */
class BrandfolderStreamWrapper implements StreamWrapperInterface {

  use StringTranslationTrait;

  /**
   * Base URL.
   *
   * @var string
   */
  protected $baseUrl = NULL;

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * Filemime mapping.
   *
   * @var array
   *   Default map for determining filemime types.
   */
  protected static $mimeTypeMapping = NULL;

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $connection;

  /**
   * Drupal logger.
   *
   * @var LoggerInterface $logger
   */
  protected $logger;

  /**
   * @inheritDoc
   */
  public static function getType(): int {
    return StreamWrapperInterface::WRITE_VISIBLE;
  }

  /**
   * Class constructor.
   *
   * @todo: The following code doesn't seem to be necessary, but keep it on hand for now.
   * @code
   *   $this->context = stream_context_get_default();
   *   stream_context_set_option($this->context, 'bf', '<option>', '<value>');
   * @endcode
   *
   * @todo: Consider performing common actions here (like loading a Brandfolder API client instance?) and storing results statically, a la:
   * @code
   *   $settings = &drupal_static('BrandfolderStreamWrapper_constructed_settings');
   *   if (!$settings) {
   *     $settings = [];
   *     $settings['client'] = brandfolder_api();
   *     // ...
   *   }
   * @endcode
   */
  public function __construct() {
    $this->baseUrl = 'https://cdn.bfldr.com';
    // Note: we can't use dependency injection for the DB connection because
    // certain low-level operations like is_dir() will not pass any params
    // even if arguments are registered in the stream wrapper service
    // definition.
    $this->connection = \Drupal::database();
    $this->logger = \Drupal::logger('brandfolder');
  }

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName(): string {
    return $this->t('Brandfolder');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Stream wrapper that supports interacting with attachments stored and managed in Brandfolder as though they were something akin to native files');
  }

  /**
   * Base implementation of setUri().
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Base implementation of getUri().
   */
  public function getUri(): string {
    return $this->uri;
  }

  /**
   * Base implementation of realpath().
   */
  public function realpath(): string {
    return $this->getUri();
  }

  /**
   * Stream context resource.
   *
   * @var Resource
   */
  public $context;

  /**
   * A generic resource handle.
   *
   * @var Resource
   */
  public $handle = NULL;

  /**
   * Stat-related constant.
   *
   * As part of the inode protection mode returned by stat(), identifies the
   * file as a regular file, as opposed to a directory, symbolic link, or other
   * type of "file".
   *
   * @see http://linux.die.net/man/2/stat
   */
  const S_IFREG = 0100000;

  /**
   * Template for stat calls.
   *
   * All elements must be initialized.
   *
   * @var array
   */
// @codingStandardsIgnoreStart
  protected $_stat = [
// @codingStandardsIgnoreEnd
    // Device number.
    0 => 0,
    'dev' => 0,
    // Inode number.
    1 => 0,
    'ino' => 0,
    // Inode protection mode. file_unmanaged_delete() requires is_file() to
    // return TRUE.
    2 => self::S_IFREG,
    // S_IFREG indicates that the item is a regular file, not a directory.
    'mode' => self::S_IFREG,
    // Number of links.
    3 => 0,
    'nlink' => 0,
    // Userid of owner.
    4 => 0,
    'uid' => 0,
    // Groupid of owner.
    5 => 0,
    'gid' => 0,
    // Device type, if inode device *.
    6 => -1,
    'rdev' => -1,
    // Size in bytes.
    7 => 0,
    'size' => 0,
    // Time of last access (Unix timestamp).
    8 => 0,
    'atime' => 0,
    // Time of last modification (Unix timestamp).
    9 => 0,
    'mtime' => 0,
    // Time of last inode change (Unix timestamp).
    10 => 0,
    'ctime' => 0,
    // Blocksize of filesystem IO.
    11 => -1,
    'blksize' => -1,
    // Number of blocks allocated.
    12 => -1,
    'blocks' => -1,
  ];

  /**
   * Support for stat().
   *
   * @param string $url
   * @param int $flags
   *
   * @return bool|array
   */
// @codingStandardsIgnoreStart
  public function url_stat($url, $flags): array {
// @codingStandardsIgnoreEnd
    // Load file size from DB before proceeding.
    $file_data_loaded = $this->loadFileData($url);
    if (!$file_data_loaded) {
      $this->logger->error('Could not load file data for :url', [':url' => $url]);
    }

    return $this->stream_stat();
  }

  /**
   * Determine the mime type for a given URI.
   */
  public static function getMimeType($uri, $mapping = NULL): string {
    $mimetype_handler = new BrandfolderMimeTypeHandler(\Drupal::Service('module_handler'), \Drupal::database());

    return $mimetype_handler->guessMimeType($uri);
  }

  /**
   * Support for fopen(), file_get_contents(), file_put_contents() etc.
   *
   * @param string $url
   *   A string containing the path to the file to open.
   * @param string $mode
   *   The file mode ("r", "wb" etc.).
   * @param bitmask $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string &$opened_url
   *   A string containing the path actually opened.
   *
   * @return bool
   *   TRUE if file was opened successfully.
   */
// @codingStandardsIgnoreStart
  public function stream_open($url, $mode, $options, &$opened_url): bool {
// @codingStandardsIgnoreEnd

    // We only handle Read-Only mode by default.
    if ($mode != 'r' && $mode != 'rb') {
      return FALSE;
    }

    if ($options & STREAM_USE_PATH) {
      $opened_url = $url;
    }

    return TRUE;
  }

  /**
   * Get the absolute URL for a Brandfolder file.
   *
   * @return string
   *   A URL string.
   */
  public function getExternalUrl(): string {
    $config = \Drupal::configFactory()->get('brandfolder.settings');

    // The current approach is to store most of the URL information right in the
    // URI, so we do not need to perform any additional lookups against the
    // BF API or Drupal DB. Thus, a typical URI looks something like
    // "bf://SH123456/at/abc123-echvmo-7qf0za/my_image.jpg."
    // This is not quite as slick or readable as
    // "bf://abc123-echvmo-7qf0za/my_image.jpg"
    // or "bf://abc123-echvmo-7qf0za," but it should be more
    // performant and allow for straightforward management of (a) attachments from
    // multiple Brandfolders if we choose to support that in the future.
    // The effective limit on BF attachment filenames (including extension) is
    // therefore 217 characters.
    $url_options = [
      'absolute' => TRUE,
    ];

    // @todo: Get config 'brandfolder_default_cdn_file_format';
    $default_file_extension = 'jpg';

    $scheme_prefix = 'bf://';
    $uri_sans_scheme = substr($this->getUri(), strlen($scheme_prefix));
    $extension_pattern = '/\.([^\.\?]+)(\?[^\?]*)?$/';
    $result = preg_match($extension_pattern, $uri_sans_scheme, $matches);
    $extension = $result ? strtolower($matches[1]) : $default_file_extension;
    $query_params = [];

    // Handle image styles.
    $image_style_unsupported_extensions = [
      'svg',
    ];
    if (preg_match("/^styles\/([^\/]+)\/bf\/(.*)$/", $uri_sans_scheme, $matches)) {
      $image_style_id = $matches[1];
      // Remove the style portion of the URI.
      $uri_sans_scheme = $matches[2];

      // Append style name as query param for clarity.
      // @todo: Decide whether to keep this, kill it, or make it configurable.
      $query_params['drupal-image-style'] = $image_style_id;

      if (!in_array($extension, $image_style_unsupported_extensions)) {
        if ($image_style = ImageStyle::load($image_style_id)) {
          // Apply all effects from the given image style. Our image toolkit will
          // handle compatible effects and add corresponding Smart CDN URL
          // transformation params to the image object.
          // @todo: Test scenarios with stacked effects; try to provide more robust pass-through support for non BF images.
          $full_uri = "bf://$uri_sans_scheme";
          // Note: we will always use the BF image toolkit for BF images, without
          // making BF the default sitewide toolkit.
          // @see \Drupal\brandfolder\Image\BrandfolderImageFactory.
          $image = \Drupal::service('image.factory')->get($full_uri);
          if ($image->isValid()) {
            $effects = $image_style->getEffects();
            foreach ($effects as $effect) {
              if (!$effect->applyEffect($image)) {
                $this->logger->error('Could not apply the image effect :effect_name to the Brandfolder image :uri.', [
                  ':effect_name' => $effect->label(),
                  ':uri'         => $full_uri,
                ]);
              }
            }
            $bf_params = $image->getToolkit()->getCdnUrlParams();
            if (!empty($bf_params)) {
              $query_params = array_merge($query_params, $bf_params);
            }
          }
        }
      }
    }

    // Lastly, convert the file format/extension to match the globally
    // configured preference for certain image types if applicable.
    // It's important to do this as late as possible so other modules can
    // accurately map the URI to a managed file.
    // @todo: Get config 'brandfolder_default_cdn_file_format';
    $default_static_image_format = 'jpg';
    $convertable_image_extensions = [
      'jpeg',
      'jpg',
      'png',
      'tiff',
      'bmp',
    ];
    // @todo: More sophisticated/granular handling for various file types.
    if ($default_static_image_format && $extension != $default_static_image_format && in_array($extension, $convertable_image_extensions)) {
      $uri_sans_scheme = preg_replace($extension_pattern, ".$default_static_image_format$2", $uri_sans_scheme);
    }

    $url = "{$this->baseUrl}/{$uri_sans_scheme}";
    // Remove any query params from the original URL and add them to the query
    // params array.
    $url_components = parse_url($url);
    if (!empty($url_components['query'])) {
      $original_query = $url_components['query'];
      $url = str_replace("?$original_query", '', $url);
      $original_query_pairs = explode('&', $original_query);
      foreach ($original_query_pairs as $pair) {
        $key_value = explode('=', $pair);
        if (count($key_value) == 2) {
          [$key, $value] = $key_value;
          $query_params[$key] = $value;
        }
      }
    }
    $url_options['query'] = $query_params;

    $mimetype = $this->getMimeType($this->getUri());
    // Functionality for non-SVG images.
    if (!empty($mimetype) && str_starts_with($mimetype, 'image/') && $mimetype != 'image/svg+xml') {
      if ($config->get('io_auto_webp')) {
        $url_options['query']['auto'] = 'webp';
      }
      if (!empty($config->get('io_quality'))) {
        $url_options['query']['quality'] = $config->get('io_quality');
      }
    }

    // Allow other modules to alter the URL.
    \Drupal::moduleHandler()->alter('brandfolder_file_url', $url, $url_options, $image_style);

    return Url::fromUri($url, $url_options)->toString();
  }

  /**
   * Get file data for a given URI.
   *
   * @param $uri
   *
   * @return bool
   */
  protected function loadFileData($uri): bool {
    $success = FALSE;

    // For more robust lookups, extract the Brandfolder attachment ID
    // from the URI and query against the corresponding column rather than the
    // URI. URI is a bit more volatile since BF filenames can change. We also
    // want to return a positive match when the URI is an image style derivative
    // of a bf:// file.
    // This will need to be updated if we start supporting asset URIs or
    // supporting multiple Brandfolders in a single Drupal site.
    $uri_parts = brandfolder_parse_uri($uri);
    if ($uri_parts && isset($uri_parts['type']) && $uri_parts['type'] === 'attachment' && !empty($uri_parts['id'])) {
      $attachment_id = $uri_parts['id'];

      // @todo: static/cache; data other than file size; etc.
      $query = $this->connection->select('brandfolder_file', 'bf')
        ->fields('bf', ['filesize'])
        ->condition('bf_attachment_id', $attachment_id);

      if ($query->countQuery()->execute()->fetchField()) {
        $result = $query->execute();
        $row = $result->fetch();
        if (!empty($row->filesize)) {
          // Set the appropriate items in the _stat array, which is used to
          // deliver data in response to file-system-esque requests.
          $this->_stat[7] = $this->_stat['size'] = $row->filesize;
        }

        $success = TRUE;
      }
    }

    return $success;
  }

  /**
   * DrupalStreamWrapperInterface implementations, etc.
   */

// @codingStandardsIgnoreStart
  /**
   * Undocumented PHP stream wrapper method.
   */
  public function stream_lock($operation): bool {
    return TRUE;
  }

  /**
   * Support for fread(), file_get_contents() etc.
   *
   * @param int $count
   *   Maximum number of bytes to be read.
   *
   * @return bool
   *   The string that was read, or FALSE in case of an error.
   */
  public function stream_read($count): bool {
    return FALSE;
  }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * Since this is a read only stream wrapper this always returns false.
   *
   * @param string $data
   *   The string to be written.
   *
   * @return bool
   *   Returns FALSE.
   */
  public function stream_write($data): bool {
    return FALSE;
  }

  /**
   * Support for feof().
   *
   * @return bool
   *   TRUE if end-of-file has been reached.
   */
  public function stream_eof(): bool {
    return FALSE;
  }

  /**
   * Support for fseek().
   *
   * @param int $offset
   *   The byte offset to got to.
   * @param string $whence
   *   SEEK_SET, SEEK_CUR, or SEEK_END.
   *
   * @return bool
   *   TRUE on success
   */
  public function stream_seek($offset, $whence = SEEK_SET): bool {
    return FALSE;
  }

  /**
   * Support for fflush().
   *
   * @return bool
   *   TRUE if data was successfully stored (or there was no data to store).
   */
  public function stream_flush(): bool {
    return TRUE;
  }

  /**
   * Support for ftell().
   *
   * @return bool
   *   The current offset in bytes from the beginning of file.
   */
  public function stream_tell(): bool {
    return FALSE;
  }

  /**
   * Support for fstat().
   *
   * @return array
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   */
  public function stream_stat(): array {
    return $this->_stat;
  }

  /**
   * Support for fclose().
   *
   * @return bool
   *   TRUE if stream was successfully closed.
   */
  public function stream_close(): bool {
    return TRUE;
  }

  /**
   * Support for opendir().
   *
   * @param string $url
   *   A string containing the url to the directory to open.
   * @param int $options
   *   Whether or not to enforce safe_mode (0x04).
   *
   * @return bool
   *   TRUE on success.
   */
  public function dir_opendir($url, $options): bool {
    return FALSE;
  }

  /**
   * Support for readdir().
   *
   * @return bool
   *   The next filename, or FALSE if there are no more files in the directory.
   */
  public function dir_readdir(): bool {
    return FALSE;
  }

  /**
   * Support for rewinddir().
   *
   * @return bool
   *   TRUE on success.
   */
  public function dir_rewinddir(): bool {
    return FALSE;
  }

  /**
   * Support for closedir().
   *
   * @return bool
   *   TRUE on success.
   */
  public function dir_closedir(): bool {
    return FALSE;
  }

  /**
   * Gets the path that the wrapper is responsible for.
   *
   * This function isn't part of DrupalStreamWrapperInterface, but the rest
   * of Drupal calls it as if it were, so we need to define it.
   *
   * @return string
   *   The empty string. Since this is a remote stream wrapper,
   *   it has no directory path.
   */
  public function getDirectoryPath(): string {

    return '';
  }

  /**
   * Implements DrupalStreamWrapperInterface::unlink().
   */
  public function unlink($uri): bool {
    // Although the remote file itself can't be deleted, return TRUE so that
    // file_delete() can remove the file record from the Drupal database.
    return TRUE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::rename().
   */
  public function rename($from_uri, $to_uri): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::mkdir().
   */
  public function mkdir($uri, $mode, $options): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::rmdir().
   */
  public function rmdir($uri, $options): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::chmod().
   */
  public function chmod($mode): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::dirname().
   */
  public function dirname($uri = NULL): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::stream_truncate().
   */
  public function stream_truncate($new_size): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::stream_set_option().
   */
  public function stream_set_option($option, $arg1, $arg2): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::stream_cast().
   */
  public function stream_cast($cast_as): bool {
    return FALSE;
  }

  /**
   * Implements DrupalStreamWrapperInterface::stream_metadata().
   *
   * @see http://www.php.net/manual/streamwrapper.stream-metadata.php
   */
  public function stream_metadata($path, $option, $value): bool {
    // Allow chown, etc even though we don't let these operations have any
    // effect on the underlying Brandfolder attachments.

    return TRUE;
  }
// @codingStandardsIgnoreEnd

}

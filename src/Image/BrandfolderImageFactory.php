<?php

namespace Drupal\brandfolder\Image;

use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\Image;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Provides a factory for image objects.
 */
class BrandfolderImageFactory extends ImageFactory {

  /**
   * Constructs a new Image object.
   *
   * Normally, the toolkit set as default in the admin UI is used by the
   * factory to create new Image objects. This can be overridden through
   * \Drupal\Core\Image\ImageInterface::setToolkitId() so that any new Image
   * object created will use the new toolkit specified. Finally, a single
   * Image object can be created using a specific toolkit, regardless of the
   * current factory settings, by passing its plugin ID in the $toolkit_id
   * argument.
   *
   * @param string|null $source
   *   (optional) The path to an image file, or NULL to construct the object
   *   with no image source.
   * @param string|null $toolkit_id
   *   (optional) The ID of the image toolkit to use for this image, or NULL
   *   to use the current toolkit.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   An Image object.
   *
   * @see ImageFactory::setToolkitId()
   */
  public function get($source = NULL, $toolkit_id = NULL) {
    $toolkit_id = $toolkit_id ?: $this->toolkitId;
    $uri = StreamWrapperManager::getScheme($source);
    if ($uri === 'bf') {
      $toolkit_id = 'brandfolder';
    }

    return new Image($this->toolkitManager->createInstance($toolkit_id), $source);
  }

}

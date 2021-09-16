<?php
// phpcs:ignoreFile

namespace Drupal\brandfolder\ProxyClass\File\MimeType {

  /**
   * Provides a proxy class for \Drupal\brandfolder\File\MimeType\BrandfolderMimeTypeGuesser.
   * Since the signature of that class does not differ substantively from the
   * class it's extending (for the purposes of proxying), we should be able to
   * simply reuse the parent class' proxy.
   */
  class BrandfolderMimeTypeGuesser extends \Drupal\Core\ProxyClass\File\MimeType\ExtensionMimeTypeGuesser implements \Symfony\Component\Mime\MimeTypeGuesserInterface, \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
  {

  }

}

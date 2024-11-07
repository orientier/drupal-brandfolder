<?php

namespace Drupal\brandfolder\Plugin\ImageToolkit\Operation\brandfolder;

/**
 * Defines Brandfolder Convert operation.
 *
 * @ImageToolkitOperation(
 *   id = "brandfolder_convert",
 *   toolkit = "brandfolder",
 *   operation = "convert",
 *   label = @Translation("Convert"),
 *   description = @Translation("Converts an image to a different format, if supported.")
 * )
 */
class Convert extends BrandfolderImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'extension' => [
        'description' => 'The file extension indicating the desired image format',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments): array {
    if (empty($arguments['extension'])) {
      throw new \InvalidArgumentException("A file extension/format must be provided to the image 'convert' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments): bool {
    $toolkit = $this->getToolkit();
    $desired_extension = $arguments['extension'];
    if ($desired_extension === 'webp') {
      // Use the more sophisticated/dynamic "auto=webp" URL parameter to deliver
      // a WebP image if the browser supports it, rather than "format=webp."
      $url_params = [
        'auto' => 'webp',
      ];
    }
    else {
      $allowed_formats = [
        'auto', // Automatically use the best format.
        'bjpg', // Baseline JPEG (also bjpeg).
        'gif', // Graphics Interchange Format.
        'jpg', // JPEG (also jpeg).
        'jxl', // JPEGXL (also jpegxl).
        'pjpg', // Progressive JPEG (also pjpeg).
        'pjxl', // Progressive JPEGXL (also pjpegxl).
        'png', // Portable Network Graphics.
        'png8', // PNG palette image with 256 colors and 8-bit transparency.
        'webpll', // WebP (Lossless).
        'webply', // WebP (Lossy).
      ];
      if (in_array($desired_extension, $allowed_formats)) {
        $url_params = [
          'format' => $desired_extension,
        ];
      }
      // If the requested format is not supported, quietly proceed with no
      // changes rather than failing.
    }
    $toolkit->setCdnUrlParams($url_params);
    // Update the file data with a new mimetype if the requested format
    // corresponds to one.
    $mimetype_mapping = [
      'webp' => 'image/webp',
      'webpll' => 'image/webp',
      'webply' => 'image/webp',
      'png' => 'image/png',
      'png8' => 'image/png',
      'gif' => 'image/gif',
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'bjpg' => 'image/jpeg',
      'jxl' => 'image/jpeg',
      'pjpg' => 'image/jpeg',
      'pjxl' => 'image/jpeg',
    ];
    if (isset($mimetype_mapping[$desired_extension])) {
      $mimetype = $mimetype_mapping[$desired_extension];
      $toolkit->setFileDataItem('mime_type', $mimetype);
    }
    $toolkit->recordOperation('convert', $arguments);

    return TRUE;
  }

}

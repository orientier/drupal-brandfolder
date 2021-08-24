<?php

namespace Drupal\brandfolder\Plugin\ImageToolkit\Operation\brandfolder;

/**
 * Defines Brandfolder Crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "brandfolder_crop",
 *   toolkit = "brandfolder",
 *   operation = "crop",
 *   label = @Translation("Crop"),
 *   description = @Translation("Crops an image to a rectangle specified by the given dimensions.")
 * )
 */
class Crop extends BrandfolderImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'x' => [
        'description' => 'The starting x offset at which to start the crop, in pixels',
      ],
      'y' => [
        'description' => 'The starting y offset at which to start the crop, in pixels',
      ],
      'width' => [
        'description' => 'The width of the cropped area, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'height' => [
        'description' => 'The height of the cropped area, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Assure at least one dimension.
    if (empty($arguments['width']) && empty($arguments['height'])) {
      throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'crop' operation");
    }

    // Preserve aspect.
    $aspect = $this->getToolkit()->getHeight() / $this->getToolkit()->getWidth();
    $arguments['height'] = empty($arguments['height']) ? $arguments['width'] * $aspect : $arguments['height'];
    $arguments['width'] = empty($arguments['width']) ? $arguments['height'] / $aspect : $arguments['width'];

    // Assure integers for all arguments.
    foreach (['x', 'y', 'width', 'height'] as $key) {
      $arguments[$key] = (int) round($arguments[$key]);
    }

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'crop' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // @todo: check context and figure out if we want precrop, regular crop (crop=123,123), etc.
    // @todo: Make the "safe" crop mode configurable globally for Drupal-BF integration, at least.
    $params = [
      'crop' => "{$arguments['width']},{$arguments['height']},x{$arguments['x']},y{$arguments['y']},safe",
    ];
    $this->getToolkit()->setCdnUrlParams($params);

    return TRUE;
  }

}

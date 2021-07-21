<?php

namespace Drupal\brandfolder\Plugin\ImageToolkit\Operation\brandfolder;

/**
 * Defines Brandfolder Scale and crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "brandfolder_scale_and_crop",
 *   toolkit = "brandfolder",
 *   operation = "scale_and_crop",
 *   label = @Translation("Scale and crop"),
 *   description = @Translation("Scales an image to the exact width and height given. This plugin achieves the target aspect ratio by cropping the original image equally on both sides, or equally on the top and bottom. This function is useful to create uniform sized avatars from larger images.")
 * )
 */
class ScaleAndCrop extends BrandfolderImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'x' => [
        'description' => 'The horizontal offset for the start of the crop, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'y' => [
        'description' => 'The vertical offset for the start the crop, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'width' => [
        'description' => 'The target width, in pixels',
      ],
      'height' => [
        'description' => 'The target height, in pixels',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    $actualWidth = $this->getToolkit()->getWidth();
    $actualHeight = $this->getToolkit()->getHeight();

    $widthScaleFactor = $arguments['width'] / $actualWidth;
    $heightScaleFactor = $arguments['height'] / $actualHeight;
    $scaleFactor = max($widthScaleFactor, $heightScaleFactor);

    // @todo: Consult with focal point config.
    $arguments['x'] = isset($arguments['x']) ?
      (int) round($arguments['x']) :
      (int) round(($actualWidth * $scaleFactor - $arguments['width']) / 2);
    $arguments['y'] = isset($arguments['y']) ?
      (int) round($arguments['y']) :
      (int) round(($actualHeight * $scaleFactor - $arguments['height']) / 2);

    // @todo: Determine desired behavior of post-crop scaling wrt user input. Drupal's default explanation for the "Scale and Crop" image effect indicates that there will be no post-crop scaling at all.
    $arguments['resize'] = [
      'width' => (int) $arguments['width'],
      'height' => (int) $arguments['height'],
    ];
    // @todo: Determine whether we are supporting upscaling.
    if ($scaleFactor < 1) {
      // We want to preserve the aspect ratio of the cropped region. Only scale
      // down until one of the two dimensions matches the desired output
      // dimension. In other words, use the scale factor that is closer to 1.0.
//      if ($widthScaleFactor >= $heightScaleFactor) {
//        $arguments['resize'] = [
//          'width' => (int) round($actualWidth * $scaleFactor),
//          'height' => (int) round($actualHeight * $scaleFactor),
//        ];
//      }
//      else {
//        $arguments['resize'] = [
//          'width' => (int) round($actualWidth * $scaleFactor),
//          'height' => (int) round($actualHeight * $scaleFactor),
//        ];
//      }
    }

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'scale_and_crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'scale_and_crop' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []) {
    return $this->getToolkit()->apply('resize', $arguments['resize'])
        && $this->getToolkit()->apply('crop', $arguments);
  }

}

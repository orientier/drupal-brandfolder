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
 *   description = @Translation("Scale and crop will maintain the aspect-ratio of the original image, then crop the larger dimension. This is most useful for creating perfectly square thumbnails without stretching the image.")
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
    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'scale_and_crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'scale_and_crop' operation");
    }

    // Translate from "scale, then crop" to "crop, then scale," since
    // Brandfolder/Fastly seems to always apply crop before scale regardless of
    // whether we use the `crop` or `precrop` params.
    // @todo: Consolidate this code with that in Crop::execute().
    // @todo: Talk with Brandfolder about state of Fastly Image Optimizer API support. They do not currently seem to support upscaling, for instance (a la ?width=150p). See https://developer.fastly.com/reference/io.
    $current_width = $this->getToolkit()->getWidth();
    $current_height = $this->getToolkit()->getHeight();

    $widthScaleFactor = $arguments['width'] / $current_width;
    $heightScaleFactor = $arguments['height'] / $current_height;
    $scaleFactor = max($widthScaleFactor, $heightScaleFactor);

    $arguments['crop_x'] = (int) round($arguments['x'] / $scaleFactor);
    $arguments['crop_y'] = (int) round($arguments['y'] / $scaleFactor);
    $arguments['crop_width'] = (int) round($arguments['width'] / $scaleFactor);
    $arguments['crop_height'] = (int) round($arguments['height'] / $scaleFactor);

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []) {
    $params = [
      // @todo: Make the "safe" crop mode configurable globally for Drupal-BF integration, at least.
      'precrop' => "{$arguments['crop_width']},{$arguments['crop_height']},x{$arguments['crop_x']},y{$arguments['crop_y']},safe",
      'width' => $arguments['width'],
      'height' => $arguments['height'],
    ];
    $toolkit = $this->getToolkit();
    $toolkit->setCdnUrlParams($params);
    $toolkit->setFileDataItem('width', $arguments['crop_width']);
    $toolkit->setFileDataItem('height', $arguments['crop_height']);
    $toolkit->recordOperation("scale_and_crop", $arguments);

    return TRUE;
  }

}

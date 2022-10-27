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

    // If either the width or height argument is missing, try to calculate the
    // missing arg from the available arg, based on the image aspect ratio.
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
    $toolkit = $this->getToolkit();
    $new_width = $crop_width = $arguments['width'];
    $new_height = $crop_height = $arguments['height'];
    $crop_x = $arguments['x'];
    $crop_y = $arguments['y'];
    // Check to see if the image was scaled prior to this crop operation.
    // If so, convert the crop values so they can be applied to the original
    // image (Brandfolder/Fastly applies crop before scale).
    $previous_operations = $toolkit->getOperationsRecord();
    $previously_scaled = FALSE;
    while ($previous_operation = array_pop($previous_operations)) {
      if ($previous_operation['operation'] == 'resize') {
        $previously_scaled = TRUE;
        break;
      }
    }
    if ($previously_scaled) {
      $original_width = $toolkit->getOriginalFileDataItem('width');
      $current_width = $toolkit->getWidth();
      $width_scale_factor = $original_width / $current_width;
      $original_height = $toolkit->getOriginalFileDataItem('height');
      $current_height = $toolkit->getheight();
      $height_scale_factor = $original_height / $current_height;
      $scale_factor = max($width_scale_factor, $height_scale_factor);
      $crop_x = round($crop_x * $scale_factor);
      $crop_y = round($crop_y * $scale_factor);
      $crop_width = round($new_width * $scale_factor);
      $crop_height = round($new_height * $scale_factor);
    }
    $params = [
      // Note: Brandfolder's Fastly Image Optimizer implementation seems to
      // always apply the "crop" param before width and height scaling, thereby
      // rendering "crop" and "precrop" identical for our purposes. Use precrop
      // in case this changes. Revisit if adding support for other
      // transformations.
      // @todo: Make the "safe" crop mode configurable globally for Drupal-BF integration?
      'precrop' => "{$crop_width},{$crop_height},x{$crop_x},y{$crop_y},safe",
      'width' => $new_width,
      'height' => $new_height,
    ];
    $toolkit->setCdnUrlParams($params);
    $toolkit->setFileDataItem('width', $new_width);
    $toolkit->setFileDataItem('height', $new_height);
    $toolkit->recordOperation("crop", $arguments);

    return TRUE;
  }

}

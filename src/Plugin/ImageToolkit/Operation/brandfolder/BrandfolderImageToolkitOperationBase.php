<?php

namespace Drupal\brandfolder\Plugin\ImageToolkit\Operation\brandfolder;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;

abstract class BrandfolderImageToolkitOperationBase extends ImageToolkitOperationBase {

  /**
   * The correctly typed image toolkit for Brandfolder operations.
   *
   * @return \Drupal\brandfolder\Plugin\ImageToolkit\BrandfolderToolkit
   */
  protected function getToolkit() {
    return parent::getToolkit();
  }

}

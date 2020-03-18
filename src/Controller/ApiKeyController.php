<?php

namespace Drupal\brandfolder\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ApiKeyController.
 */
class ApiKeyController extends ControllerBase {

  /**
   * Showkey.
   *
   * @return string
   *   Return Hello string.
   */
  public function ShowKey() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: ShowKey')
    ];
  }

}

<?php

namespace Drupal\brandfolder\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to perform a custom action.
 */
class BrandfolderSetAltTextCommand implements CommandInterface {

  /**
   * Element selector.
   *
   * @var string
   */
  protected string $selector;

  /**
   * Alt text value.
   *
   * @var string
   */
  protected string $alt_text;

  /**
   * Constructs a BrandfolderSetAltTextCommand object.
   *
   * @param string $selector
   * The CSS selector for the element to target.
   *
   * @param string $alt_text
   * The alt text to set for the Brandfolder attachment.
   */
  public function __construct(string $selector, string $alt_text) {
    $this->selector = $selector;
    $this->alt_text = $alt_text;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'brandfolderSetAltTextCommand',
      'selector' => $this->selector,
      'altText' => $this->alt_text,
    ];
  }
}

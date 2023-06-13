<?php

namespace Drupal\brandfolder\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Multipurpose event that will be fired when Drupal receives a webhook
 * transmission from Brandfolder.
 */
class BrandfolderWebhookEvent extends Event {

  public const ASSET_CREATE = 'asset.create';
  public const ASSET_UPDATE = 'asset.update';
  public const ASSET_DELETE = 'asset.delete';

  /**
   * Relevant webhook payload data.
   *
   * @var array
   */
  public array $data;

  /**
   * Event object constructor.
   *
   * @param array $data
   *   Webhook payload data.
   */
  public function __construct(array $payload) {
    // Consolidate payload data.
    $data = $payload['attributes'];
    $data['webhook_id'] = $payload['webhook_id'];
    $this->data = $data;
  }

}

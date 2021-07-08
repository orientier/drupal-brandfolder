<?php

namespace Drupal\brandfolder\EventSubscriber;

use Drupal\brandfolder\Event\BrandfolderWebhookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class WebhookEventSubscriber.
 *
 * @package brandfolder
 */
class WebhookEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      BrandfolderWebhookEvent::ASSET_CREATE => 'assetCreate',
      BrandfolderWebhookEvent::ASSET_UPDATE => 'assetUpdate',
      BrandfolderWebhookEvent::ASSET_DELETE => 'assetDelete',
    ];
  }

  /**
   * React to a Brandfolder asset being created.
   *
   * @param \Drupal\brandfolder\Event\BrandfolderWebhookEvent $event
   */
  public function assetCreate(BrandfolderWebhookEvent $event) {
    $r = 5;
  }

  /**
   * React to a Brandfolder asset being updated.
   *
   * @param \Drupal\brandfolder\Event\BrandfolderWebhookEvent $event
   */
  public function assetUpdate(BrandfolderWebhookEvent $event) {
    $bf_asset_id = $event->data['key'];
  }

  /**
   * React to a Brandfolder asset being deleted.
   *
   * @param \Drupal\brandfolder\Event\BrandfolderWebhookEvent $event
   */
  public function assetDelete(BrandfolderWebhookEvent $event) {
    $r = 5;
  }

}

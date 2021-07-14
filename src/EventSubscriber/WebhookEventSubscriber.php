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
//      BrandfolderWebhookEvent::ASSET_CREATE => 'assetCreate',
      BrandfolderWebhookEvent::ASSET_UPDATE => 'assetUpdate',
//      BrandfolderWebhookEvent::ASSET_DELETE => 'assetDelete',
    ];
  }

  /**
   * React to a Brandfolder asset being created.
   *
   * @param \Drupal\brandfolder\Event\BrandfolderWebhookEvent $event
   */
//  public function assetCreate(BrandfolderWebhookEvent $event) {
//
//  }

  /**
   * React to a Brandfolder asset being updated.
   *
   * @param \Drupal\brandfolder\Event\BrandfolderWebhookEvent $event
   */
  public function assetUpdate(BrandfolderWebhookEvent $event) {
    $bf_asset_id = $event->data['key'];

    // Handle asset merge events. This asset's attachments may formerly have
    // been attached to another asset. Update any of our records to ensure the
    // latest asset-attachment relationship is reflected.
    $bf = brandfolder_api();
    $params = [
      'include' => 'attachments',
    ];
    if ($asset = $bf->fetchAsset($bf_asset_id, $params)) {
      if (!empty($asset->attachments)) {
        $attachment_ids = array_map(function($attachment) {
          return $attachment->id;
        }, $asset->attachments);

        $db = \Drupal::database();
        $db->update('brandfolder_file')
          ->fields(['bf_asset_id' => $bf_asset_id])
          ->condition('bf_attachment_id', $attachment_ids, 'IN')
          ->execute();
      }
    }
  }

  /**
   * React to a Brandfolder asset being deleted.
   *
   * @param \Drupal\brandfolder\Event\BrandfolderWebhookEvent $event
   */
//  public function assetDelete(BrandfolderWebhookEvent $event) {
//
//  }

}

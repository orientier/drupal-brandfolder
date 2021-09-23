<?php

namespace Drupal\brandfolder\EventSubscriber;

use Drupal\brandfolder\Event\BrandfolderWebhookEvent;
use Drupal\brandfolder\Plugin\media\Source\BrandfolderImage;
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

    $bf = brandfolder_api();
    $params = [
      'include' => 'attachments,custom_fields',
    ];
    if ($asset = $bf->fetchAsset($bf_asset_id, $params)) {
      $db = \Drupal::database();
      // Handle asset merge events. This asset's attachments may formerly have
      // been attached to another asset. Update any of our records to ensure the
      // latest asset-attachment relationship is reflected.
      if (!empty($asset->data->attachments)) {
        $attachment_ids = array_map(function($attachment) {
          return $attachment->id;
        }, $asset->data->attachments);

        $db->update('brandfolder_file')
          ->fields(['bf_asset_id' => $bf_asset_id])
          ->condition('bf_attachment_id', $attachment_ids, 'IN')
          ->execute();
      }

      // Metadata sync.
      // Current functionality: if there is a BF custom field designated for
      // storing image alt text, and this updated asset has a value for that
      // field, see if corresponding Drupal image fields and/or media entities
      // are lacking alt text. If so, pull the text from BF to populate those.
      $config = \Drupal::config('brandfolder.settings');
      $alt_text_custom_field_id = $config->get('alt_text_custom_field');
      if (!empty($alt_text_custom_field_id)) {
        // Note: we always Look up the current name associated with the given
        // custom field key ID. The name can change at any time in Brandfolder
        // without Drupal knowing, but the ID never changes (but we can't
        // use the ID directly when getting field values for an asset).
        if ($custom_field_keys = $bf->listCustomFields(NULL, FALSE, TRUE)) {
          if (isset($custom_field_keys[$alt_text_custom_field_id])) {
            $custom_field_name = $custom_field_keys[$alt_text_custom_field_id];
            if (!empty($asset->data->custom_field_values[$custom_field_name])) {
              $alt_text = $asset->data->custom_field_values[$custom_field_name];

              $query = $db->select('brandfolder_file', 'bf')
                ->fields('bf', ['fid', 'bf_attachment_id'])
                ->condition('bf_asset_id', $bf_asset_id);
              if ($query->countQuery()->execute()->fetchField()) {
                $entity_type_manager = \Drupal::entityTypeManager();
                $relevant_fids = $query->execute()->fetchCol(0);

                // File Entity module support.
                $fe_alt_text_field_name = 'field_image_alt_text';
                $fe_alt_text_field_file_ids = \Drupal::entityQuery('file')
                  ->condition('fid', $relevant_fids, 'IN')
                  ->notExists($fe_alt_text_field_name)
                  ->execute();
                if (count($fe_alt_text_field_file_ids) > 0) {
                  $fe_alt_text_field_files = $entity_type_manager
                    ->getStorage('file')
                    ->loadMultiple($fe_alt_text_field_file_ids);
                  foreach ($fe_alt_text_field_files as $file) {
                    $file->set($fe_alt_text_field_name, $alt_text);
                    $file->save();
                  }
                }

                // Standard image fields referencing relevant files.
                $updated_entities = [];
                $field_manager = \Drupal::service('entity_field.manager');
                $image_field_registry = $field_manager->getFieldMapByFieldType('image');
                foreach ($image_field_registry as $entity_type => $image_fields) {
                  foreach ($image_fields as $field_name => $field_data) {
                    $table_name = "{$entity_type}__{$field_name}";
                    if ($db->schema()->tableExists($table_name)) {
                      $entity_id_query = $db->select($table_name, 'img')
                        ->fields('img', ['entity_id'])
                        ->condition("{$field_name}_target_id", $relevant_fids, 'IN')
                        ->isNull("{$field_name}_alt");
                      if ($entity_id_query->countQuery()
                        ->execute()
                        ->fetchField()) {
                        $entity_ids = $entity_id_query->execute()->fetchCol();
                        $entities = $entity_type_manager
                          ->getStorage($entity_type)
                          ->loadMultiple($entity_ids);
                        foreach ($entities as $entity_id => $entity) {
                          $field_values = $entity->get($field_name)->getValue();
                          foreach ($field_values as $index => $field_value) {
                            if (in_array($field_value['target_id'], $relevant_fids)) {
                              $field_values[$index]['alt'] = $alt_text;
                            }
                          }
                          $entity->set($field_name, $field_values);
                          $entity->save();
                          $updated_entities[$entity_type][] = $entity_id;
                        }
                      }
                    }
                  }
                }

                // The following code finds media entities with any of the given
                // attachments as their source field and triggers a metadata
                // update. In practice, this set of entities will almost
                // always be a subset of the set processed above
                // (because all relevant BF-sourced media entities also
                // have a "bf_image" image field with a file ID that maps to one
                // of these attachments). In that case, this code will find no
                // eligible entities, because any that were processed above are
                // excluded. However, it's possible that an eligible entity
                // may not exist in the set above if someone  manually added alt
                // text to the relevant image field in Drupal.
                // In that scenario, we still need to trigger a
                // media metadata update because the field to which alt text
                // is mapped may still be empty.
                $relevant_attachment_ids = $query->execute()->fetchCol(1);
                $media_types = $entity_type_manager
                  ->getStorage('media_type')
                  ->loadMultiple();
                $media_storage = $entity_type_manager->getStorage('media');
                $updated_media_entity_ids = $updated_entities['media'] ?? [-1];
                foreach ($media_types as $media_type_id => $media_type) {
                  $source = $media_type->getSource();
                  if ($source instanceof BrandfolderImage) {
                    // If this type has a mapping for the custom alt text
                    // field...
                    $field_mapping = $media_type->getFieldMap();
                    if (!empty($field_mapping['alt_text'])) {
                      // Find any entities of this type that are missing a value
                      // in the corresponding Drupal field.
                      $target_field = $field_mapping['alt_text'];
                      $media_entity_ids = \Drupal::entityQuery('media')
                        ->condition('bundle', $media_type_id)
                        ->condition('mid', $updated_media_entity_ids, 'NOT IN')
                        ->condition($target_field, '')
                        ->condition('field_brandfolder_attachment_id', $relevant_attachment_ids, 'IN')
                        ->execute();
                      if (count($media_entity_ids) > 0) {
                        $media_entities = $media_storage->loadMultiple($media_entity_ids);
                        foreach ($media_entities as $media_entity) {
                          // Save the entity to force a metadata update.
                          $media_entity->save();
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
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

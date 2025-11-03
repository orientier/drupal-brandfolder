<?php

namespace Drupal\brandfolder\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Defines a computed field item list for the virtual media image field.
 *
 *  NOTE: It extends EntityReferenceFieldItemList to ensure compatibility
 *  with display formatters and other code expecting entity reference methods,
 *  even though it does not implement EntityReferenceFieldItemListInterface
 *  directly (it inherits the necessary methods).
 */
class VirtualMediaImageFieldValue extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * Computes the value of the field based on the 'bf_image' field.
   */
  protected function computeValue(): void {
    /** @var \Drupal\media\MediaInterface $media_entity */
    $media_entity = $this->getEntity();
    $source_field_name = 'bf_image';

    if ($media_entity->hasField($source_field_name)) {
      $source_field_items = $media_entity->get($source_field_name);

      if (!$source_field_items->isEmpty()) {

        // We only copy the first item, because we know that both bf_image and
        // the core field_media_image field are single-cardinality fields.
        /** @var \Drupal\Core\Field\FieldItemInterface $source_item */
        try {
          $source_item = $source_field_items->first();
        }
        catch (MissingDataException $e) {

          return;
        }

        $properties_to_copy = [
          'target_id',
          'alt',
          'title',
          'width',
          'height',
        ];

        $copied_values = [];
        try {
          foreach ($properties_to_copy as $property_name) {
            $copied_values[$property_name] = $source_item->get($property_name)
              ->getValue();
          }
        }
        catch (MissingDataException $e) {}
        // Target ID is the minimum required to have a valid image reference.
        if (empty($copied_values['target_id'])) {

          return;
        }

        $this->list[0] = $this->createItem(0, $copied_values);
      }
    }
  }
}

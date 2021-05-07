<?php

namespace Drupal\brandfolder\Plugin\Field\FieldWidget;

use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'brandfolder_image_browser' widget.
 *
 * @FieldWidget(
 *   id = "brandfolder_image_browser",
 *   label = @Translation("Brandfolder Image Browser"),
 *   field_types = {
 *     "image"
 *   }
 * )
 *
 * @todo: BrandfolderBrowser or BrandfolderBrowserWidget class that can be used by image browser, video browser, etc. descendant widget classes.
 */
class BrandfolderImageBrowserWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['bf_memo'] = [
      '#markup' => '<h4>Ultra-Simple Brandfolder Image Browser</h4>',
    ];

    if ($bf = brandfolder_api()) {
      $assets = $bf->listAssets(['per' => 20, 'page' => 1,]);
      $bf_asset_list_string = '';
      if (!empty($assets->data)) {
        // @todo: Theme pattern.
        $bf_asset_list = array_map(function ($asset) {
          $output =   "<li class=\"brandfolder-asset\" data-bf-asset-id=\"{$asset->id}\">"
                  .     "<figure>"
                  .       "<img src=\"{$asset->attributes->thumbnail_url}\" />"
                  .       "<figcaption>"
                  .         "<div class=\"bf-asset-metadata\"><strong>Name:</strong> {$asset->attributes->name}</div>"
                  .         "<div class=\"bf-asset-metadata\"><strong>Asset ID:</strong> {$asset->id}</div>"
                  .       "</figcaption>"
                  .     "</figure>"
                  .   "</li>";

          return $output;
        }, $assets->data);
        $bf_asset_list_string = implode(' ', $bf_asset_list);
      }
      $element['bf_asset_list'] = [
        '#markup' => "<div class=\"brandfolder-browser\"><div class=\"brandfolder-assets\"><ul class=\"brandfolder-asset-list\">$bf_asset_list_string</ul></div></div>",
      ];
      $element['bf_asset_ids'] = [
        '#type' => 'hidden',
        '#value' => [
          '4g6zpzmbkc97pxjpjpks9r4t'
        ],
        '#attributes' => [
          'class' => 'bf-asset-ids'
        ],
      ];
    }

    $element['#attached']['library'][] = 'brandfolder/brandfolder-browser';

//    $field_settings = $this->getFieldSettings();
//
//    // Add image validation.
//    $element['#upload_validators']['file_validate_is_image'] = [];
//
//    // Add upload resolution validation.
//    if ($field_settings['max_resolution'] || $field_settings['min_resolution']) {
//      $element['#upload_validators']['file_validate_image_resolution'] = [$field_settings['max_resolution'], $field_settings['min_resolution']];
//    }
//
//    $extensions = $field_settings['file_extensions'];
//    $supported_extensions = $this->imageFactory->getSupportedExtensions();
//
//    // If using custom extension validation, ensure that the extensions are
//    // supported by the current image toolkit. Otherwise, validate against all
//    // toolkit supported extensions.
//    $extensions = !empty($extensions) ? array_intersect(explode(' ', $extensions), $supported_extensions) : $supported_extensions;
//    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);
//
//    // Add mobile device image capture acceptance.
//    $element['#accept'] = 'image/*';
//
//    // Add properties needed by process() method.
//    $element['#preview_image_style'] = $this->getSetting('preview_image_style');
//    $element['#title_field'] = $field_settings['title_field'];
//    $element['#title_field_required'] = $field_settings['title_field_required'];
//    $element['#alt_field'] = $field_settings['alt_field'];
//    $element['#alt_field_required'] = $field_settings['alt_field_required'];
//
//    // Default image.
//    $default_image = $field_settings['default_image'];
//    if (empty($default_image['uuid'])) {
//      $default_image = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
//    }
//    // Convert the stored UUID into a file ID.
//    if (!empty($default_image['uuid']) && $entity = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid'])) {
//      $default_image['fid'] = $entity->id();
//    }
//    $element['#default_image'] = !empty($default_image['fid']) ? $default_image : [];

    return $element;
  }

  /**
   * @inerhitDoc
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    $element['#theme'] = 'brandfolder_browser_widget';

    return $element;
  }


  /**
   * @inerhitDoc
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    // Map selected Brandfolder asset IDs to Drupal file IDs as applicable.
//    $asset_ids = $form_state->getValue('bf_asset_ids', []);
    $asset_ids = $element['bf_asset_ids']['#value'];

    // @todo Multi vs. single cardinality, etc.
    foreach ($asset_ids as $asset_id) {
      if ($fid = brandfolder_map_asset_to_file($asset_id)) {
        $return['fids'][] = $fid;
        $return['target_id'] = $fid;
      }
    }

    return $return;
  }

}



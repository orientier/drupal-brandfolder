<?php

namespace Drupal\brandfolder\Plugin\Field\FieldWidget;

use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
      $selected_bf_asset_ids = '';
      $input = $form_state->getUserInput();
      $field_name = $element['#field_name'];
      if (!empty($input[$field_name][$delta]['bf_asset_ids'])) {
        $selected_bf_asset_ids = $input[$field_name][$delta]['bf_asset_ids'];
      }
      elseif (!empty($element['#default_value']['target_id'])) {
        if ($bf_asset_id = brandfolder_map_file_id_to_asset($element['#default_value']['target_id'])) {
          $selected_bf_asset_ids = $bf_asset_id;
        }
      }
      $element['bf_asset_ids'] = [
        '#type' => 'hidden',
        '#value' => [
          $selected_bf_asset_ids,
        ],
        '#attributes' => [
          'class' => 'bf-asset-ids'
        ],
      ];
    }

    $element['#attached']['library'][] = 'brandfolder/brandfolder-browser';

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

    $cardinality = $element['#cardinality'];

    // Map selected Brandfolder asset IDs to Drupal file IDs as applicable.
    if (!empty($input)) {
      //    $asset_ids = $form_state->getValue('bf_asset_ids', []);
      $asset_ids = $input['bf_asset_ids'];
      if (!is_array($asset_ids)) {
        $asset_ids = [$asset_ids];
      }

      // @todo Multi vs. single cardinality, etc.
      if ($cardinality > 0) {
        $asset_ids = array_slice($asset_ids, 0, $cardinality);
      }

      foreach ($asset_ids as $index => $asset_id) {
        if ($fid = brandfolder_map_asset_to_file($asset_id)) {
          $return['fids'][$index] = $fid;
          // @todo
          $return['target_id'] = $fid;
        }
      }
    }

    return $return;
  }

}



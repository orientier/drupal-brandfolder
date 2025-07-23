<?php

namespace Drupal\brandfolder\Plugin\Field\FieldWidget;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'brandfolder_image_browser' widget.
 *
 * @todo: BrandfolderBrowserWidget class that can be used by image browser, video browser, etc. descendant widget classes.
 */
// @todo: Open this up when it's actually ready for use.
//#[FieldWidget(
//  id: 'brandfolder_image_browser',
//  label: new TranslatableMarkup('Brandfolder Image Browser'),
//  field_types: ['image'],
//)]
class BrandfolderImageBrowserWidget extends ImageWidget {

  /**
   * The Brandfolder Gatekeeper service.
   *
   * @var ?\Drupal\brandfolder\Service\BrandfolderGatekeeper
   */
  protected ?BrandfolderGatekeeper $bfGatekeeper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): WidgetBase|ContainerFactoryPluginInterface|FileWidget|BrandfolderImageBrowserWidget {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->bfGatekeeper = $container->get('brandfolder.gatekeeper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    if (isset($element['progress_indicator'])) {
      // Remove the progress indicator setting, as it is not applicable.
      unset($element['progress_indicator']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    foreach ($summary as $key => $value) {
      if ($value instanceof TranslatableMarkup) {
        $arguments = $value->getArguments();
        if (isset($arguments['@progress_indicator'])) {
          unset($summary[$key]);
        }
      }
    }

    return $summary;
  }

  /**
   * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state): array {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // @todo: custom form element type for Brandfolder browser widget?

    // @todo: Do we want to keep $element["#process"][0][1]: 'processManagedFile'?

    $field_name = $element['#field_name'];

    $element['bf_memo'] = [
      '#markup' => '<h4>Brandfolder Image Browser</h4>',
    ];

    $field_context_items = ['field_widget', 'brandfolder_image_browser', ...$element['#field_parents']];
    $field_context_items[] = $field_name;
    $field_context_items[] = $delta;
    $field_context_string = implode('__', $field_context_items);

    $field_definition = $items->getFieldDefinition();
    $this->bfGatekeeper->loadFromFieldDefinition($field_definition);
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $selection_limit = $cardinality > 0 ? $cardinality : NULL;

    brandfolder_browser_init($element, $form_state, $this->bfGatekeeper, [], $selection_limit, $field_context_string);

    return $element;

    // Generate a unique wrapper HTML ID.
//    $ajax_wrapper_id = Html::getUniqueId(implode('-', $field_context_items) . '-ajax-wrapper');
//
//    $ajax_settings = [
//      'callback' => [get_called_class(), 'assetSelectionAjaxCallback'],
//      'options' => [
//        'query' => [
//          'element_parents' => implode('/', $element['#array_parents']),
//        ],
//      ],
//      'wrapper' => $ajax_wrapper_id,
//      'effect' => 'fade',
//      'progress' => [
//        'type' => $element['#progress_indicator'],
//        'message' => t('Processing your selection...'),
//      ],
//    ];
//
//    $element['bf_asset_selection_button'] = [
//      '#name' => $field_context_string . '_bf_asset_selection_button',
//      '#type' => 'submit',
//      '#value' => t('Confirm Selection'),
//      '#validate' => [],
//      '#limit_validation_errors' => [],
//      '#ajax' => $ajax_settings,
//      '#weight' => 1,
//    ];
//
//    if ($bf = brandfolder_api()) {
//      $assets = $bf->listAssets(['per' => 20, 'page' => 1,]);
//      $bf_asset_list_string = '';
//      if (!empty($assets->data)) {
//        // @todo: Theme pattern.
//        $bf_asset_list = array_map(function ($asset) {
//          $output =   "<li class=\"brandfolder-asset\" data-bf-asset-id=\"{$asset->id}\">"
//                  .     "<figure>"
//                  .       "<img src=\"{$asset->attributes->thumbnail_url}\" />"
//                  .       "<figcaption>"
//                  .         "<div class=\"bf-asset-metadata\"><strong>Name:</strong> {$asset->attributes->name}</div>"
//                  .         "<div class=\"bf-asset-metadata\"><strong>Asset ID:</strong> {$asset->id}</div>"
//                  .       "</figcaption>"
//                  .     "</figure>"
//                  .   "</li>";
//
//          return $output;
//        }, $assets->data);
//        $bf_asset_list_string = implode(' ', $bf_asset_list);
//      }
//      $element['bf_asset_list'] = [
//        '#markup' => "<div class=\"brandfolder-browser\"><div class=\"brandfolder-assets\"><ul class=\"brandfolder-asset-list\">$bf_asset_list_string</ul></div></div>",
//      ];
//      $selected_bf_asset_ids = '';
//      $input = $form_state->getUserInput();
//      if (!empty($input[$field_name][$delta]['bf_asset_ids'])) {
//        $selected_bf_asset_ids = $input[$field_name][$delta]['bf_asset_ids'];
//      }
//      elseif (!empty($element['#default_value']['target_id'])) {
//        if ($bf_asset_id = brandfolder_map_file_id_to_asset($element['#default_value']['target_id'])) {
//          $selected_bf_asset_ids = $bf_asset_id;
//        }
//      }
//      $element['bf_asset_ids'] = [
//        '#type' => 'hidden',
//        '#value' => [
//          $selected_bf_asset_ids,
//        ],
//        '#attributes' => [
//          'class' => 'bf-asset-ids'
//        ],
//      ];
//    }
//
//    // Note: This will be used when wrapping element in a container for AJAX
//    // replacement. That will be done in our process callback to avoid parent
//    // class overwriting prefix and suffix.
//    $element['#bf_browser_ajax_wrapper_id'] = $ajax_wrapper_id;
//
//    $element['#attached']['library'][] = 'brandfolder/brandfolder-browser';
//
//    return $element;
  }

//  /**
//   * #ajax callback for asset selection submission/confirmation/processing.
//   *
//   * @param array $form
//   *   The build form.
//   * @param \Drupal\Core\Form\FormStateInterface $form_state
//   *   The form state.
//   * @param \Symfony\Component\HttpFoundation\Request $request
//   *   The current request.
//   *
//   * @return \Drupal\Core\Ajax\AjaxResponse
//   *   The ajax response of the ajax upload.
//   */
//  public static function assetSelectionAjaxCallback(&$form, FormStateInterface &$form_state, Request $request): AjaxResponse {
//    /** @var \Drupal\Core\Render\RendererInterface $renderer */
//    $renderer = \Drupal::service('renderer');
//
//    $form_parents = explode('/', $request->query->get('element_parents'));
//
//    // Sanitize form parents before using them.
//    $form_parents = array_filter($form_parents, [Element::class, 'child']);
//
//    // Retrieve the element to be rendered.
//    $form = NestedArray::getValue($form, $form_parents);
//
//    // Note: converting the selected asset to a managed file, generating preview
//    // image, adding supplemental fields like "alt text," etc. will all be
//    // handled by the respective field processor methods. All we're doing here
//    // is re-rendering the relevant portion of the form and using AJAX API to
//    // use that output to replace relevant HTML.
//    // However, this is the place to perform any additional form modifications
//    // that are tied on the asset selection event.
//
//    $status_messages = ['#type' => 'status_messages'];
//    $form['#prefix'] .= $renderer->renderRoot($status_messages);
//    $output = $renderer->renderRoot($form);
//
//    $response = new AjaxResponse();
//    $response->setAttachments($form['#attached']);
//
//    return $response->addCommand(new ReplaceCommand(NULL, $output));
//  }


  /**
   * Form API callback: Processes an image_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    return parent::process($element, $form_state, $form);
  }

  /**
   * Validate callback for alt and title field, if the user wants them required.
   *
   * This is separated in a validate function instead of a #required flag to
   * avoid being validated on the process callback.
   */
  public static function validateRequiredFields($element, FormStateInterface $form_state): void {
//    // Only do validation if the function is triggered from other places than
//    // the image process form.
//    $triggering_element = $form_state->getTriggeringElement();
//    if (!empty($triggering_element['#submit']) && in_array('file_managed_file_submit', $triggering_element['#submit'], TRUE)) {
//      $form_state->setLimitValidationErrors([]);
//    }

    $r = 5;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $new_values = parent::massageFormValues($values, $form, $form_state);

    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * @inerhitDoc
   */
//  public static function process($element, FormStateInterface $form_state, $form) {
//    $element = parent::process($element, $form_state, $form);
//
//    $element['#theme'] = 'brandfolder_image_browser_widget';
//
//    return $element;
//  }

  /**
   * @inerhitDoc
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    $cardinality = $element['#cardinality'];

    // Map selected Brandfolder attachment IDs to Drupal file IDs as applicable.
    if (!empty($input)) {
      //    $asset_ids = $form_state->getValue('bf_asset_ids', []);
      $attachment_ids = $input['selected_bf_attachment_ids'];
      if (!is_array($attachment_ids)) {
        $attachment_ids = [$attachment_ids];
      }

      // @todo Multi vs. single cardinality, etc.
      if ($cardinality > 0) {
        $attachment_ids = array_slice($attachment_ids, 0, $cardinality);
      }

      foreach ($attachment_ids as $index => $attachment_id) {
        if ($fid = brandfolder_map_attachment_to_file($attachment_id)) {
          $return['fids'][$index] = $fid;
          // @todo: Review wrt how Drupal handles this single value vs. the 'fids' array.
          $return['target_id'] = $fid;
        }
      }
    }

    return $return;
  }

}



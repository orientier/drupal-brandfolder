<?php

namespace Drupal\brandfolder\Element;

use Drupal\brandfolder\Ajax\BrandfolderSetAltTextCommand;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form element for selecting attachments from Brandfolder and
 * translating them to Drupal managed files. This element will provide a
 * Brandfolder browser interface for selecting files, and will return
 * corresponding Drupal managed files.
 * This can be used instead of the classic "managed_file" form element,
 * and is designed to maintain as similar a structure to that element as
 * possible/practical.
 */
#[FormElement('brandfolder_file')]
class BrandfolderFileFormElement extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
//      '#pre_render' => [
//        [$class, 'preRenderElement'],
//      ],
//      '#theme' => 'brandfolder_browser_form_element',
//      '#tree' => FALSE, // What's the default here? \Drupal\file\Element\ManagedFile::getInfo() doesn't spec this.
      '#theme_wrappers' => ['form_element'],
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#bf_browser_open_label' => t('Browse'),
      '#bf_browser_submit_label' => t('Submit selection'),
      '#upload_validators' => [],
      '#upload_location' => NULL,
      '#size' => 22,
      '#multiple' => FALSE, // For compatibility with Drupal\file\Element\ManagedFile.
      '#extended' => FALSE, // For compatibility with Drupal\file\Element\ManagedFile.
      '#selection_limit' => NULL,
//      '#attached' => [
//        'library' => ['brandfolder/bf-browser-host-manager'],
//      ],
      '#accept' => NULL,
    ];
  }

  /**
   * Render API callback: Processes the element and expands it to include
   * various sub elements.
   */
  public static function processElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents_prefix = implode('_', $element['#parents']);

    $fids = $element['#value']['fids'] ?? [];

    // Add a class for styling.
    $element['#attributes']['class'][] = 'brandfolder-file-form-element';

    // Set some default element properties.
    $element['#progress_indicator'] = empty($element['#progress_indicator']) ? 'none' : $element['#progress_indicator'];
    $element['#files'] = !empty($fids) ? File::loadMultiple($fids) : [];
    $element['#tree'] = TRUE;

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('brandfolder-file-element-ajax-wrapper');

    $ajax_settings = [
      'callback' => [static::class, 'elementAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => $element['#progress_indicator'],
        'message' => $element['#progress_message'],
      ],
    ];

    $element['fids'] = [
      '#type' => 'hidden',
      '#value' => $fids,
    ];

    if (!empty($fids) && $element['#files']) {
      foreach ($element['#files'] as $delta => $file) {
        $file_link = [
          '#theme' => 'file_link',
          '#file' => $file,
        ];
        if ($element['#multiple']) {
          $element['file_' . $delta]['selected'] = [
            '#type' => 'checkbox',
            '#title' => \Drupal::service('renderer')->renderInIsolation($file_link),
          ];
        }
        else {
          $element['file_' . $delta]['filename'] = $file_link + ['#weight' => -10];
        }
      }

      // Force the progress indicator for the remove button to be either 'none' or
      // 'throbber', even if the selection button is using something else.
      $ajax_settings['progress']['type'] = ($element['#progress_indicator'] == 'none') ? 'none' : 'throbber';
      $ajax_settings['progress']['message'] = NULL;
      $ajax_settings['effect'] = 'none';
      $element['remove_button'] = [
        '#name' => $parents_prefix . '_remove_button',
        '#type' => 'submit',
        '#value' => $element['#multiple'] ? t('Remove selected') : t('Remove'),
        '#validate' => [],
        '#submit' => [[static::class, 'submitHandler']],
        '#limit_validation_errors' => [$element['#parents']],
        '#ajax' => $ajax_settings,
        //      '#weight' => 1,
      ];
    }


    // @todo: Open browser in modal. Auto-submit selections upon close/button press.
//    $element['bf_browser_open_button'] = [
//      '#name' => $parents_prefix . '_bf_browser_open_button',
//      '#type' => 'button',
//      '#value' => !empty($element['#bf_browser_open_label']) ? $element['#bf_browser_open_label'] : t('Browse'),
//      '#attributes' => ['class' => ['bf-browser-open-button']],
//      '#validate' => [],
//      '#weight' => -4,
//    ];


    // Allowed extensions list.
    $allowed_extensions_array = [];
    if (isset($element['#upload_validators']['file_validate_extensions'][0]) || isset($element['#upload_validators']['FileExtension']['extensions'])) {
      if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
        @trigger_error('\'file_validate_extensions\' is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'FileExtension\' constraint instead. See https://www.drupal.org/node/3363700', E_USER_DEPRECATED);
        $allowed_extensions = $element['#upload_validators']['file_validate_extensions'][0];
      }
      else {
        $allowed_extensions = $element['#upload_validators']['FileExtension']['extensions'];
      }
      $allowed_extensions_array = array_filter(explode(' ', $allowed_extensions));
    }

    $selected_bf_attachments = brandfolder_map_files_to_attachments($fids, 'array');

    /* @var \Drupal\brandfolder\Service\BrandfolderGatekeeper $gatekeeper */
    $gatekeeper = \Drupal::service('brandfolder.gatekeeper');
    $gatekeeper->setAllowedFiletypes($allowed_extensions_array);
    // @todo: Add more params based on form element settings/form_state/etc.
    $selection_limit = 1;
    if ($element['#multiple']) {
      $selection_limit = (is_numeric($element['#selection_limit']) && $element['#selection_limit'] > 0) ? (int) $element['#selection_limit'] : NULL;
    }
    $context_array = [
      'brandfolder_file_form_element',
      $parents_prefix,
    ];
    // @todo: Change $context_id param to an array and do this implosion within the init function, to standardize.
    $context_id = implode('__', $context_array);
    // @todo: Stop setting explicit height after moving to on-demand modal format.
    $bf_browser_settings = ['height' => 600];

    brandfolder_browser_init($element, $form_state, $gatekeeper, $selected_bf_attachments, $selection_limit, $context_id, $bf_browser_settings);


    $element['selection_submit_button'] = [
      '#name' => $parents_prefix . '_selection_submit_button',
      '#type' => 'submit',
      '#value' => t('Save your selections'),
      // @todo Trigger this upon BF browser submission/close.
//      '#attributes' => ['class' => ['js-hide', 'brandfolder-file-element-selection-submit-button']],
      '#validate' => [],
      '#submit' => [[static::class, 'submitHandler']],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
//      '#weight' => -5,
    ];

    // Wrap element in HTML element used for browser scoping and Ajax
    // replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '" class="brandfolder-browser-form-element">';
    $element['#suffix'] = '</div>';

    return $element;
  }


  /**
   * Form submission handler for selection / removal buttons.
   */
  public static function submitHandler($form, FormStateInterface $form_state) {
    // Determine whether it was the upload or the remove button that was clicked,
    // and set $element to the managed_file element that contains that button.
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    // No action is needed here for the selection_submit_button button, because
    // selections will be processed by ::valueCallback()
    // regardless of which button was clicked. Action is needed here for the
    // remove button, because we only remove a file in response to its remove
    // button being clicked.
    if ($button_key == 'remove_button') {
      $fids = array_keys($element['#files']);
      // Get files that will be removed.
      if ($element['#multiple']) {
        $remove_fids = [];
        foreach (Element::children($element) as $name) {
          if (str_starts_with($name, 'file_') && $element[$name]['selected']['#value']) {
            $remove_fids[] = (int) substr($name, 5);
          }
        }
        $fids = array_diff($fids, $remove_fids);
      }
      else {
        // If we deal with single upload element remove the file and set
        // element's value to empty array (file could not be removed from
        // element if we don't do that).
        $remove_fids = $fids;
        $fids = [];
      }

      // Update both $form_state->getValues() and FormState::$input to reflect
      // that the file has been removed, so that the form is rebuilt correctly.
      // $form_state->getValues() must be updated in case additional submit
      // handlers run, and for form building functions that run during the
      // rebuild, such as when the managed_file element is part of a field widget.
      // FormState::$input must be updated so that
      // \Drupal\file\Element\ManagedFile::valueCallback() has correct information
      // during the rebuild.
      $form_state->setValueForElement($element['fids'], implode(' ', $fids));
      NestedArray::setValue($form_state->getUserInput(), $element['fids']['#parents'], implode(' ', $fids));
      // Remove corresponding Brandfolder attachments from the selected
      // attachments list.
      $bf_attachment_ids_to_remove = brandfolder_map_files_to_attachments($remove_fids);
      $selected_bf_attachments = $element['selected_bf_attachment_ids']['#value'] ? explode(',', $element['selected_bf_attachment_ids']['#value']) : [];
      $selected_bf_attachments = array_diff($selected_bf_attachments, $bf_attachment_ids_to_remove);
      $updated_attachment_list = implode(',', $selected_bf_attachments);
      $form_state->setValueForElement($element['selected_bf_attachment_ids'], $updated_attachment_list);
      NestedArray::setValue($form_state->getUserInput(), $element['selected_bf_attachment_ids']['#parents'], $updated_attachment_list);

      foreach ($remove_fids as $fid) {
        // If it's a temporary file we can safely remove it immediately, otherwise
        // it's up to the implementing module to remove usages of files to have them
        // removed.
        if ($element['#files'][$fid] && $element['#files'][$fid]->isTemporary()) {
          $element['#files'][$fid]->delete();
        }
      }
    }

    // Set the form to rebuild so that $form is correctly updated in response to
    // processing the file removal. Since this function did not change $form_state
    // if the upload button was clicked, a rebuild isn't necessary in that
    // situation and calling $form_state->disableRedirect() would suffice.
    // However, we choose to always rebuild, to keep the form processing workflow
    // consistent between the two buttons.
    $form_state->setRebuild();
  }

  /**
   * #ajax callback. Right now this just renders status messages. We may need to
   * use it to actually handle the file creation/addition and removal.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function elementAjaxCallback(&$form, FormStateInterface &$form_state, Request $request): AjaxResponse {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    // Add the special AJAX class if a new file was added (so UI can be styled
    // accordingly).
//    $current_file_count = $form_state->get('file_upload_delta_initial');
//    if (isset($form['#file_upload_delta']) && $current_file_count < $form['#file_upload_delta']) {
//      $form[$current_file_count]['#attributes']['class'][] = 'ajax-new-content';
//    }

    // Render any status messages that may have been set during the
    // processing of the file selection/removal.
    $status_messages = ['#type' => 'status_messages'];
    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    // Try getting alt text from the BF asset corresponding to the selected
    // attachment, and add a custom AJAX command to update the relevant alt text
    // field in the form. This currently only supports the case where
    // a single attachment selection is allowed per BF file element.
    if (isset($form['selected_bf_attachment_ids'])) {
      $selected_bf_attachments = explode(',', $form['selected_bf_attachment_ids']['#value']);
      if (count($selected_bf_attachments) === 1) {
        $alt_text = brandfolder_get_alt_text_from_attachment($selected_bf_attachments[0]) ?? '';
        $triggering_element_name = $form_state->getTriggeringElement()['#name'] ?? '';
        $alt_text_command = new BrandfolderSetAltTextCommand(
          '[name="' . $triggering_element_name . '"]',
          $alt_text
        );
        $response->addCommand($alt_text_command);
        $response->addAttachments(['library' => ['brandfolder/brandfolder-ajax-commands']]);
      }
    }

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }


  /**
   * Render API callback: Validates the element.
   *
   * @see \Drupal\Core\Render\Element\ManagedFile::validateManagedFile()
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    $clicked_button = isset($triggering_element['#parents']) ? end($triggering_element['#parents']) : '';
    if ($clicked_button != 'remove_button' && !empty($element['fids']['#value'])) {
      $fids = $element['fids']['#value'];
      foreach ($fids as $fid) {
        if ($file = File::load($fid)) {
          // If referencing an existing file, only allow if there are existing
          // references. This prevents unmanaged files from being deleted if
          // this item were to be deleted. When files that are no longer in use
          // are automatically marked as temporary (now disabled by default),
          // it is not safe to reference a permanent file without usage. Adding
          // a usage and then later on removing it again would delete the file,
          // but it is unknown if and where it is currently referenced. However,
          // when files are not marked temporary (and then removed)
          // automatically, it is safe to add and remove usages, as it would
          // simply return to the current state.
          // @see https://www.drupal.org/node/2891902
          if ($file->isPermanent() && \Drupal::config('file.settings')->get('make_unused_managed_files_temporary')) {
            $references = static::fileUsage()->listUsage($file);
            if (empty($references)) {
              // We expect the field name placeholder value to be wrapped in t()
              // here, so it won't be escaped again as it's already marked safe.
              $form_state->setError($element, t('The file used in the @name field may not be referenced.', ['@name' => $element['#title']]));
            }
          }
        }
        else {
          // We expect the field name placeholder value to be wrapped in t()
          // here, so it won't be escaped again as it's already marked safe.
          $form_state->setError($element, t('The file referenced by the @name field does not exist.', ['@name' => $element['#title']]));
        }
      }
    }

    // Check required property based on the FID.
    if ($element['#required'] && empty($element['fids']['#value']) && !in_array($clicked_button, ['selection_submit_button', 'remove_button'])) {
      // We expect the field name placeholder value to be wrapped in t()
      // here, so it won't be escaped again as it's already marked safe.
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Consolidate the array value of this field to array of FIDs.
    if (!$element['#extended']) {
      $form_state->setValueForElement($element, $element['fids']['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Find the current value of this field.
    $bf_attachment_ids = !empty($input['selected_bf_attachment_ids']) ? explode(',', $input['selected_bf_attachment_ids']) : [];
    // Map attachment IDs to Drupal file IDs.
    // @todo: Support media entity IDs. (Make the target entity type a form element setting or otherwise contextually determined).
    $fids = array_map('brandfolder_map_attachment_to_file', $bf_attachment_ids);
    $fids = array_filter($fids);

    // Process any input.
    if ($input !== FALSE) {
      $input['fids'] = $fids;
      $return = $input;

      // Check for #filefield_value_callback values.
      // Because FAPI does not allow multiple #value_callback values like it
      // does for #element_validate and #process, this fills the missing
      // functionality to allow File fields to be extended through FAPI.
      if (isset($element['#file_value_callbacks'])) {
        foreach ($element['#file_value_callbacks'] as $callback) {
          $callback($element, $input, $form_state);
        }
      }

      // Load files if the FIDs have changed to confirm they exist.
      if (!empty($input['fids'])) {
        $fids = [];
        foreach ($input['fids'] as $fid) {
          if ($file = File::load($fid)) {
            $fids[] = $file->id();
          }
        }
      }
    }
    else {
      // If there is no input, use the default value.
      if ($element['#extended']) {
        $default_fids = $element['#default_value']['fids'] ?? [];
        $return = $element['#default_value'] ?? ['fids' => []];
      }
      else {
        $default_fids = $element['#default_value'] ?? [];
      }

      // Confirm that the file exists when used as a default value.
      if (!empty($default_fids)) {
        $fids = [];
        foreach ($default_fids as $fid) {
          if ($file = File::load($fid)) {
            $fids[] = $file->id();
          }
        }
      }
    }

    $return['fids'] = $fids;

    return $return;
  }
}

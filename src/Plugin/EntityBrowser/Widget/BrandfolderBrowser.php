<?php

namespace Drupal\brandfolder\Plugin\EntityBrowser\Widget;

use Drupal;
use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\WidgetBase;

/**
 * Allows users to browse Brandfolder assets and select attachments for use in
 * Drupal.
 *
 * @EntityBrowserWidget(
 *   id = "brandfolder_browser",
 *   label = @Translation("Brandfolder Browser"),
 *   description = @Translation("Widget that will allow users to browse Brandfolder assets and attachments, creating Drupal media entities for selected attachments if they don't already exist."),
 *   auto_select = FALSE
 * )
 */
class BrandfolderBrowser extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return array_merge(parent::defaultConfiguration(), [
      'submit_text' => $this->t('Select'),
      'media_type' => NULL,
    ]);
  }

  /**
   * Retrieve the ID of the single Brandfolder-sourced media type linked to
   * this Entity Browser widget.
   *
   * @return string|null
   */
  protected function getMediaTypeId(): ?string {
    $config = $this->getConfiguration();

    return $config['settings']['media_type'] ?? NULL;
  }

  /**
   * Load the Brandfolder-sourced media type linked to this Entity Browser
   * widget.
   *
   * @return EntityInterface|null
   */
  protected function getMediaType(): ?EntityInterface {
    $media_type_id = $this->getMediaTypeId();

    if (empty($media_type_id)) {
      return NULL;
    }

    try {
      $media_type = Drupal::entityTypeManager()
        ->getStorage('media_type')
        ->load($media_type_id);
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      $this->messenger()
        ->addError($this->t('There was a problem loading the media type %media_type_id.', ['%media_type_id' => $media_type_id]));

      return NULL;
    }

    return $media_type;
  }

  /**
   * Get a storage instance for media entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected function getMediaStorage(): ?Drupal\Core\Entity\EntityStorageInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('media');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $storage = NULL;
    }

    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $media_type_options = [];

    try {
      $media_types = $this
        ->entityTypeManager
        ->getStorage('media_type')
        ->loadByProperties(['source' => 'brandfolder_image']);

      foreach ($media_types as $media_type) {
        $media_type_options[$media_type->id()] = $media_type->label();
      }
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      $this->messenger()->addError($this->t('There was a problem loading media types.'));
    }

    if (empty($media_type_options)) {
      $url = Url::fromRoute('entity.media_type.add_form')->toString();
      $form['media_type'] = [
        '#markup' => $this->t("You don't have any Brandfolder Image media types yet. You should <a href='!link'>create one</a>.", ['!link' => $url]),
      ];
    }
    else {
      $form['media_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Media type'),
        '#default_value' => !empty($this->configuration['media_type']) ? $this->configuration['media_type'] : NULL,
        '#options' => $media_type_options,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters): array {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $media_type = $this->getMediaType();

    if (!$media_type) {
      return $form;
    }

    $context = ['entity_browser', 'brandfolder_browser'];
    $context_string = implode('-', $context);
    $media_source = $media_type->getSource();
    $gatekeeper = \Drupal::getContainer()
      ->get(BrandfolderGatekeeper::class);
    $gatekeeper->loadFromMediaSource($media_source);

    // @todo: Test in field contexts where we might have previously selected entities on first browser load.
    $selected_bf_attachment_ids = [];
    $selected_entities = &$form_state->get(['entity_browser', 'selected_entities']);
    if (!empty($selected_entities)) {
      $selected_bf_attachment_ids = array_walk($selected_entities, 'brandfolder_map_media_entity_to_attachment');
    }

    $selection_limit = NULL;
    $validators = $form_state->get(['entity_browser', 'validators']);
    if (!empty($validators['cardinality']['cardinality'])) {
      $selection_limit = $validators['cardinality']['cardinality'];
    }

    brandfolder_browser_init($form, $form_state, $gatekeeper, $selected_bf_attachment_ids, [], $selection_limit, $context_string);

    return $form;
  }

  /**
   * Check to see whether a form submission was triggered by one of our
   * Brandfolder browser controls (e.g. search, filters, etc.).
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  protected function isFormSubmissionTriggeredByBrowserControls(FormStateInterface $form_state): bool {
    return is_form_submission_triggered_by_bf_browser_controls($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    if ($this->isFormSubmissionTriggeredByBrowserControls($form_state)) {
      // If the submission was triggered by one of our browser controls, the
      // only outcome should be to update the browser contents. We do not care
      // about other validation logic/errors.
      $form_state->clearErrors();
    }
    else {
      parent::validate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      $entities = $this->prepareEntities($form, $form_state);
      $this->selectEntities($entities, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state): array {

    // We don't need or want to load entities when the submission is
    // triggered by one of our browser controls - only when the final selection
    // is submitted.
    // @todo: Probe and verify that this is true in an Entity Browser context. What about, e.g., Media Browsers with selection trays showing the selected entities rendered in some special way?
    if ($this->isFormSubmissionTriggeredByBrowserControls($form_state)) {

      return [];
    }

    $selected_media_entities = [];
    $selected_attachment_list = $form_state->getValue('selected_bf_attachment_ids');
    if (!empty($selected_attachment_list)) {
      $selected_attachments = explode(',', $selected_attachment_list);
      $media_type_id = $this->getMediaTypeId();
      $storage = $this->getMediaStorage();
      if ($media_type_id && $storage) {
        foreach ($selected_attachments as $attachment_id) {
          $bf_media_entity_id = brandfolder_map_attachment_to_media_entity($attachment_id, $media_type_id);
          if ($bf_media_entity_id) {
            $media_entity = $storage->load($bf_media_entity_id);
            if ($media_entity) {
              $selected_media_entities[] = $media_entity;
            }
          }
        }
      }
    }

    return $selected_media_entities;
  }

}

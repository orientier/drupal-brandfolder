<?php

namespace Drupal\brandfolder\Plugin\EntityBrowser\Widget;

use Drupal;
use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * Brandfolder Gatekeeper service.
   *
   * @var \Drupal\brandfolder\Service\BrandfolderGatekeeper
   */
  protected BrandfolderGatekeeper $brandfolderGatekeeper;

  /**
   * EntityTypeBundleInfo service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\brandfolder\Service\BrandfolderGatekeeper $brandfolder_gatekeeper
   *   The Brandfolder Gatekeeper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BrandfolderGatekeeper $brandfolder_gatekeeper, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->brandfolderGatekeeper = $brandfolder_gatekeeper;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('brandfolder.gatekeeper'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return array_merge(parent::defaultConfiguration(), [
      'submit_text' => $this->t('Select'),
      'media_type' => NULL,
      'browser_height' => NULL,
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

    if (empty($media_type_id) || $media_type_id === 'none') {
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
   * Get a storage instance for file entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected function getFileStorage(): ?Drupal\Core\Entity\EntityStorageInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('file');
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

    $media_type_options = [
      'none' => $this->t('None (use this Entity Browser for Image fields only).'),
    ];

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
        '#markup' => $this->t('You don\'t have any Brandfolder Image media types yet. You will need to <a href=":link">create one</a> if you want to use this Entity Browser for any media entity reference fields. Alternatively, you can use this Entity Browser for Image fields, in which case a media type is not necessary.', [':link' => $url]),
      ];
    }
    else {
      $form['media_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Media type'),
        '#description' => $this->t('If you want to use this Entity Browser widget to select Brandfolder images for media entity reference fields, you will need to tell it which media type to use when creating Drupal media items for the selected Brandfolder images. Alternatively, you can use this Entity Browser for Image fields, in which case choosing a media type is not necessary.'),
        '#default_value' => $this->configuration['media_type'],
        '#options' => $media_type_options,
      ];
    }

    $form['browser_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Browser height'),
      '#description' => $this->t('The desired height of the Brandfolder browser in pixels. Set this for best results (because the browser cannot easily deduce the optimal height when in an iframe and unaware of other variables).'),
      // @todo: Let people enter values in %, vh, etc.
      '#default_value' => $this->configuration['browser_height'],
      '#min' => 300,
      '#max' => 10000,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters): array {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $context = ['entity_browser', 'brandfolder_browser'];
    $context_string = implode('-', $context);
    $gatekeeper = $this->brandfolderGatekeeper;

    $selection_limit = NULL;
    $validators = $form_state->get(['entity_browser', 'validators']);
    if (!empty($validators['cardinality']['cardinality'])) {
      $selection_limit = $validators['cardinality']['cardinality'];
    }

    if (isset($validators['entity_type']['type']) && $validators['entity_type']['type'] === 'file') {
      if (!empty($validators['file']['validators'])) {
        // When Entity Browser is used with a Brandfolder file/image field, it
        // should always use the "Brandfolder Entity Browser"
        // (brandfolder_entity_browser_file) field widget, which extends
        // \Drupal\entity_browser\Plugin\Field\FieldWidget\FileBrowserWidget
        // and provides a field-specific BF gatekeeper.
        // Use that here if present.
        if (isset($validators['file']['validators']['brandfolder_gatekeeper_criteria'])) {
          $criteria = $validators['file']['validators']['brandfolder_gatekeeper_criteria'];
          $gatekeeper->setCriteria($criteria);
        }
        else {
          // Otherwise, do our best to conjure some validation based on the more
          // limited information that Entity Browser provides.
          $file_validators = $validators['file']['validators'];
          $gatekeeper->loadFromEntityBrowserFileValidators($file_validators);
        }
      }
    }
    else {
      // Default to Media.
      // @todo: Consider explicitly checking for a media type validator.
      $media_type = $this->getMediaType();
      if ($media_type) {
        $media_source = $media_type->getSource();
        $gatekeeper->loadFromMediaSource($media_source);
      }
    }

    // @todo: Selected entities won't be found here even when the host field has some of its values set (in the case of a multi-cardinality field). And the cardinality value below will be the total number of allowed items, not the number of items actually allowed to be selected in this session.
    // @todo: Test with various EB config options (append/replace/prepend).
    $selected_bf_attachments = [];
    $selected_entities = &$form_state->get(['entity_browser', 'selected_entities']);
    if (!empty($selected_entities)) {
      $selected_bf_attachments = brandfolder_map_media_entities_to_attachments($selected_entities);
    }

    $entity_browser_id = $this->configuration['entity_browser_id'];
    $entity_browser = \Drupal::service('entity_type.manager')->getStorage('entity_browser')->load($entity_browser_id);
    $bf_browser_settings = [];
    if ($entity_browser->display == 'iframe' || $entity_browser->display == 'modal') {
      $bf_browser_settings['format'] = 'full';
    }
    // Determine the best fixed height for the Brandfolder browser.
    $bf_browser_height = NULL;
    $config = $this->getConfiguration();
    if (!empty($config['settings']['browser_height'])) {
      $bf_browser_height = $config['settings']['browser_height'];
    }
    elseif ($entity_browser->display_configuration['height']) {
      // Venture a rough guess at an optimal height based on a minimal
      // containing page with the Claro theme. We could get more nuanced here,
      // but should just encourage people to explicitly set a height for
      // these contexts.
      $bf_browser_height = max([$entity_browser->display_configuration['height'] - 180, 300]);
    }
    $bf_browser_settings['height'] = $bf_browser_height;

    brandfolder_browser_init($form, $form_state, $gatekeeper, $selected_bf_attachments, $selection_limit, $context_string, $bf_browser_settings);

    return $form;
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
    $selected_entities = [];
    $selected_attachment_list = $form_state->getValue('selected_bf_attachment_ids');
    if (!empty($selected_attachment_list)) {
      $selected_attachments = explode(',', $selected_attachment_list);
      // Map the selected attachments to the appropriate entity type.
      // Check the widget validators to see what entity_type we're dealing with.
      $validators = $form_state->get(['entity_browser', 'validators']);
      $required_entity_type = $validators['entity_type']['type'] ?? 'media';
      if ($required_entity_type === 'file') {
        $storage = $this->getFileStorage();
        if ($storage) {
          foreach ($selected_attachments as $attachment_id) {
            $bf_file_id = brandfolder_map_attachment_to_file($attachment_id);
            if ($bf_file_id) {
              $file_entity = $storage->load($bf_file_id);
              if ($file_entity) {
                $selected_entities[] = $file_entity;
              }
            }
          }
        }
      }
      elseif ($required_entity_type === 'media') {
        $media_type_id = $this->getMediaTypeId();
        $entity_browser_id = $this->configuration['entity_browser_id'];
        $widget_config_url = Url::fromRoute('entity.entity_browser.edit_widgets', ['entity_browser' => $entity_browser_id]);
        if (!$media_type_id || $media_type_id === 'none') {
          // If this user has Entity Browser admin permissions, show an error directing them to the config page for the BF widget for this browser.
          if (Drupal::currentUser()->hasPermission('administer entity browser')) {
            $form_state->setError($form['widget'], $this->t('No Brandfolder media type has been configured for this Entity Browser widget, so it cannot be used to select media entities. Please <a href=":link">select a Brandfolder media type</a> for this widget.', [':link' => $widget_config_url->toString()]));
          }
          else {
            // Otherwise, show a more generic error.
            $form_state->setError($form['widget'], $this->t('No Brandfolder media type has been configured for this Entity Browser widget, so it cannot be used to select media entities. Please contact a site administrator.'));
          }

          return $selected_entities;
        }
        else {
          // Make sure this media type still exists.
          $media_bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
          if (!isset($media_bundles[$media_type_id])) {
            // If this user has Entity Browser admin permissions, show an error directing them to the config page for the BF widget for this browser.
            if (Drupal::currentUser()
              ->hasPermission('administer entity browser')) {
              $form_state->setError($form['widget'], $this->t('The Brandfolder media type %media_type_id configured for this Entity Browser widget no longer exists, so it cannot be used to select media entities. Please <a href=":link">select a different Brandfolder media type</a> for this widget.', [
                '%media_type_id' => $media_type_id,
                ':link'          => $widget_config_url->toString()
              ]));
            }
            else {
              // Otherwise, show a more generic error.
              $form_state->setError($form['widget'], $this->t('The Brandfolder media type %media_type_id configured for this Entity Browser widget no longer exists, so it cannot be used to select media entities. Please contact a site administrator.', ['%media_type_id' => $media_type_id]));
            }

            return $selected_entities;
          }

          $storage = $this->getMediaStorage();
          if ($storage) {
            foreach ($selected_attachments as $attachment_id) {
              $bf_media_entity_id = brandfolder_map_attachment_to_media_entity($attachment_id, $media_type_id);
              if ($bf_media_entity_id) {
                $media_entity = $storage->load($bf_media_entity_id);
                if ($media_entity) {
                  $selected_entities[] = $media_entity;
                }
              }
            }
          }
        }
      }
      else {
        // Unsupported entity type.
        $form_state->setError($form['widget'], $this->t('The Brandfolder Entity Browser widget does not support selection of entities of type %entity_type.', ['%entity_type' => $required_entity_type]));
      }
    }

    return $selected_entities;
  }

}

<?php

namespace Drupal\brandfolder\Plugin\Field\FieldType;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Plugin implementation of the 'image' field type. This overrides the core
 * 'image' field type to enable Image fields to be used with Brandfolder.
 * When not using Brandfolder, the field should function as normal.
 */
class BrandfolderCompatibleImageItem extends ImageItem {

  /**
   * The Brandfolder Gatekeeper service.
   *
   * @var \Drupal\brandfolder\Service\BrandfolderGatekeeper
   */
  protected BrandfolderGatekeeper $brandfolderGatekeeper;

  /**
   * {@inheritdoc}
   */
  public function __construct(ComplexDataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    /* @var \Drupal\brandfolder\Service\BrandfolderGatekeeper $gatekeeper */
    $gatekeeper = \Drupal::getContainer()
      ->get('brandfolder.gatekeeper');
    $this->brandfolderGatekeeper = $gatekeeper;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    // We need the field-level 'default_image' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    // Revise the language for the URI scheme field to accurately reflect the
    // available options now that Brandfolder is in the mix. The BF scheme is
    // effectively read-only but is registered as writeable so that Drupal's
    // image style system will feel comfortable allowing it to handle BF image
    // derivatives.
    $bf_scheme_label = 'Brandfolder (read-only)';
    $element['uri_scheme']['#options']['bf'] = t($bf_scheme_label);
    $element['uri_scheme']['#title'] = t('Storage location');
    $public_private_scheme_message = isset($element['uri_scheme']['#options']['private']) ? 'For standard file uploads, select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.' : 'For standard file uploads, choose the "public files" option.';
    $args = [
      '@public_private_scheme_msg' => $public_private_scheme_message,
      '@bf_scheme_label'    => $bf_scheme_label,
    ];
    $element['uri_scheme']['#description'] = t('@public_private_scheme_msg If you choose "@bf_scheme_label," you can use this field to select images stored in Brandfolder (and use them in Drupal without copying any files).', $args);

//    if ($settings['uri_scheme'] === 'bf') {
////      $element['default_image']['#title'] = $this->t('Default Brandfolder Image');
//      // @todo: Use BF Browser widget for this default image selector if bf is the active scheme.
//    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();

    // If the selected file URI scheme for this field is the Brandfolder
    // scheme, then modify the config form accordingly.
    if ($settings['uri_scheme'] === 'bf') {

      // @todo: Revisit.
      $element['file_directory']['#default_value'] = '';
      $element['file_directory']['#access'] = FALSE;

      // @todo: Replace the "Default Image" upload interface with a Brandfolder Browser widget.
      $element['default_image']['#title'] = t('Default Brandfolder Image');

      if ($field_definition = $this->getFieldDefinition()) {
        $this->brandfolderGatekeeper->loadFromFieldDefinition($field_definition);
      }
      $this->brandfolderGatekeeper->buildConfigForm($element);
    }

    return $element;
  }

}

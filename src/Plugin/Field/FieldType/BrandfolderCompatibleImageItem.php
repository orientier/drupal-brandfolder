<?php

namespace Drupal\brandfolder\Plugin\Field\FieldType;

use Drupal\brandfolder\Plugin\Field\FieldWidget\BrandfolderImageBrowserWidget;
use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
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
   * @var ?\Drupal\brandfolder\Service\BrandfolderGatekeeper
   */
  protected ?BrandfolderGatekeeper $bfGatekeeper;

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, ?TraversableTypedDataInterface $parent = NULL): BrandfolderCompatibleImageItem {
    $instance = new static($definition, $name, $parent);

    /* @var \Drupal\brandfolder\Service\BrandfolderGatekeeper $gatekeeper */
    $gatekeeper = \Drupal::getContainer()->get('brandfolder.gatekeeper');
    $instance->setGatekeeper($gatekeeper);

    return $instance;
  }

  /**
   * Sets the Brandfolder Gatekeeper service.
   *
   * @param \Drupal\brandfolder\Service\BrandfolderGatekeeper $gatekeeper
   *
   * @return void
   */
  public function setGatekeeper(BrandfolderGatekeeper $gatekeeper): void {
    $this->bfGatekeeper = $gatekeeper;
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

    if ($settings['uri_scheme'] === 'bf') {
      if (isset($element['default_image']['uuid'])) {
        // Use BF Browser element for this default image selector.
        $element['default_image']['uuid']['#type'] = 'brandfolder_file';
        $element['default_image']['uuid']['#description'] = $element['default_image']['#description'] = t('When no image is selected, this image will be shown on display.');
        $element['default_image']['uuid']['#element_validate'] = [
          '\Drupal\brandfolder\Element\BrandfolderFileFormElement::validateElement',
          [static::class, 'validateDefaultImageForm'],
        ];
      }
      else {
        $element['default_image']['#access'] = FALSE;
      }
    }

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

      // "Max upload file size" doesn't really make sense for Brandfolder,
      // at least with the current one-way integration, so we hide it.
      // We could use it to limit search results to only BF attachments whose
      // listed size complies with this limit, but if people are following best
      // practices and using BF image optimization settings & Drupal image
      // styles, the original file size is not so relevant.
      $element['max_filesize']['#default_value'] = '';
      $element['max_filesize']['#access'] = FALSE;

      if (isset($element['default_image']['uuid'])) {
        // Switch the "Default Image" upload interface with a custom Brandfolder form element
        $element['default_image']['uuid']['#type'] = 'brandfolder_file';
        $element['default_image']['#description'] = t("When no image is selected, this image will be shown on display and will override the field's default image.");
        $element['default_image']['uuid']['#element_validate'] = [
          '\Drupal\brandfolder\Element\BrandfolderFileFormElement::validateElement',
          [static::class, 'validateDefaultImageForm'],
        ];
        // @todo: Load gatekeeper criteria from field definition and pass through to brandfolder_file element? Obviously the field definition is a bit volatile since it's editable on the same parent form as this element.
      }

      if ($field_definition = $this->getFieldDefinition()) {
        $this->bfGatekeeper->loadFromFieldDefinition($field_definition);
      }
      $this->bfGatekeeper->buildConfigForm($element);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
//  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
//    $random = new Random();
//    $settings = $field_definition->getSettings();
//    //    static $images = [];
//    //
//    //    $min_resolution = empty($settings['min_resolution']) ? '100x100' : $settings['min_resolution'];
//    //    $max_resolution = empty($settings['max_resolution']) ? '600x600' : $settings['max_resolution'];
//    //    $extensions = array_intersect(explode(' ', $settings['file_extensions']), ['png', 'gif', 'jpg', 'jpeg']);
//    //    $extension = array_rand(array_combine($extensions, $extensions));
//    //
//    //    $min = explode('x', $min_resolution);
//    //    $max = explode('x', $max_resolution);
//    //    if (intval($min[0]) > intval($max[0])) {
//    //      $max[0] = $min[0];
//    //    }
//    //    if (intval($min[1]) > intval($max[1])) {
//    //      $max[1] = $min[1];
//    //    }
//    //    $max_resolution = "$max[0]x$max[1]";
//    //
//    //    // Generate a max of 5 different images.
//    //    if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= 5) {
//    //      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
//    //      $file_system = \Drupal::service('file_system');
//    //      $tmp_file = $file_system->tempnam('temporary://', 'generateImage_');
//    //      $destination = $tmp_file . '.' . $extension;
//    //      try {
//    //        $file_system->move($tmp_file, $destination);
//    //      }
//    //      catch (FileException $e) {
//    //        // Ignore failed move.
//    //      }
//    //      if ($path = $random->image($file_system->realpath($destination), $min_resolution, $max_resolution)) {
//    //        $image = File::create();
//    //        $image->setFileUri($path);
//    //        $image->setOwnerId(\Drupal::currentUser()->id());
//    //        $guesser = \Drupal::service('file.mime_type.guesser');
//    //        $image->setMimeType($guesser->guessMimeType($path));
//    //        $image->setFileName($file_system->basename($path));
//    //        $destination_dir = static::doGetUploadLocation($settings);
//    //        $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);
//    //        $destination = $destination_dir . '/' . basename($path);
//    //        $file = \Drupal::service('file.repository')->move($image, $destination);
//    //        $images[$extension][$min_resolution][$max_resolution][$file->id()] = $file;
//    //      }
//    //      else {
//    //        return [];
//    //      }
//    //    }
//    //    else {
//    //      // Select one of the images we've already generated for this field.
//    //      $image_index = array_rand($images[$extension][$min_resolution][$max_resolution]);
//    //      $file = $images[$extension][$min_resolution][$max_resolution][$image_index];
//    //    }
//    //
//    //    [$width, $height] = getimagesize($file->getFileUri());
//    //    $values = [
//    //      'target_id' => $file->id(),
//    //      'alt' => $random->sentences(4),
//    //      'title' => $random->sentences(4),
//    //      'width' => $width,
//    //      'height' => $height,
//    //    ];
//    //    return $values;
//
//    // If this field is using Brandfolder, then return a specific file.
//    // Otherwise, invoke the parent method.
//    if ($settings['uri_scheme'] === 'bf') {
//      return [
//        // Temp.
//        'target_id' => 72,
//        'alt'       => $random->sentences(4),
//        'title'     => $random->sentences(4),
//        'width'     => 5457,
//        'height'    => 3596,
//      ];
//    }
//    else {
//      return parent::generateSampleValue($field_definition);
//    }
//  }

}

<?php

namespace Drupal\brandfolder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define the administrative form used to configure the Brandfolder integration.
 */
class BrandfolderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'brandfolder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brandfolder_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('brandfolder.settings');
    $api_key = $config->get('api_key');
    $default_brandfolder = $config->get('default_brandfolder');
    $default_collection = $config->get('default_collection');
    $brandfolders_list = $collections_list = [];

    /************************************
     * Credentials
     ************************************/
    $form['credentials'] = [
      '#type'  => 'details',
      '#title' => $this->t('Brandfolder Credentials'),
      '#open'  => empty($api_key),
    ];

    $form['credentials']['brandfolder_api_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Brandfolder API key'),
      '#description' => $this->t('An API key for a Brandfolder user who has access to the Brandfolder you wish to integrate with your Drupal site. This can be found in Brandfolder under "My Profile > Integrations > API Keys."'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $api_key,
    ];

    if ($api_key) {
      $bf = brandfolder_api($api_key);
      $messenger = $this->messenger();
      try {
        $brandfolders_list = $bf->getBrandfolders();
      }
      catch (\Exception $e) {
        $messenger->addMessage($this->t('After you enter an API key, you can select a default Brandfolder'));
      }
      try {
        $collections_list = $default_brandfolder ? $bf->getCollectionsInBrandfolder($default_brandfolder) : [];
      }
      catch (\Exception $e) {
        $messenger->addMessage($this->t('After you choose a default Brandfolder, you can select a default collection if you wish'));
      }
    }

    $brandfolders_list['none'] = $this->t('< None >');
    $collections_list['none'] = $this->t('< None >');

    /************************************
     * Basic Configuration
     ************************************/
    $form['basic'] = [
      '#type'  => 'details',
      '#title' => $this->t('Basic Configuration Options'),
      '#open'  => empty($default_brandfolder),
    ];

    $form['basic']['brandfolder_default_brandfolder'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Default Brandfolder'),
      '#options'       => $brandfolders_list,
      '#default_value' => $default_brandfolder ? $default_brandfolder : 'none',
      '#description'   => $this->t('The Brandfolder to use for all operations unless otherwise specified.'),
    ];

    $form['basic']['brandfolder_default_collection'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Default Collection'),
      '#options'       => $collections_list,
      '#default_value' => $default_collection ? $default_collection : 'none',
      '#description'   => $this->t('The collection to use for all operations unless otherwise specified.'),
    ];

    /************************************
     * Sample Images
     ************************************/
    // Display some images from the selected Brandfolder/collection if
    // applicable.
    if ($default_brandfolder && isset($bf)) {
      $bf->default_brandfolder_id = $default_brandfolder;
      if ($default_collection) {
        $assets = $bf->listAssets([], $default_collection);
      }
      else {
        $assets = $bf->listAssets();
      }
      if ($assets) {
        $thumbnails = array_map(function ($asset) {
          return '<img src="' . $asset->attributes->thumbnail_url . '">';
        }, $assets->data);

        $form['sample_pics'] = [
          '#type' => 'markup',
          '#prefix' => '<h2>Sample Images</h2>',
          '#markup' => implode(' ', $thumbnails),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('brandfolder.settings');
    $config->set('api_key', $form_state->getValue('brandfolder_api_key'));
    $old_brandfolder = $config->get('default_brandfolder');
    $specified_brandfolder = $form_state->getValue('brandfolder_default_brandfolder');
    if ($specified_brandfolder == 'none') {
      $specified_brandfolder = NULL;
    }
    $config->set('default_brandfolder', $specified_brandfolder);
    // If the Brandfolder selection is being changed, reset the collection,
    // which is dependent on the Brandfolder. Otherwise, use the value
    // specified by the form.
    $specified_collection = $form_state->getValue('brandfolder_default_collection');
    if ($specified_brandfolder != $old_brandfolder || $specified_collection == 'none') {
      $collection = NULL;
    }
    else {
      $collection = $specified_collection;
    }
    $config->set('default_collection', $collection);
    $config->save();
  }

}

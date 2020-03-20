<?php

namespace Drupal\brandfolder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BrandfolderSettingsForm.
 */
class BrandfolderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'brandfolder.brandfoldersettings',
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
    $config = $this->config('brandfolder.brandfoldersettings');

    $form['brandfolder_api_key'] = [
      '#type' => 'textarea',
      '#title' => t('Insert Brandfolder API key here'),
      '#description' => $this->t('Store the Brandfolder API Key'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('brandfolder_api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('brandfolder.brandfoldersettings')
      ->set('brandfolder_api_key', $form_state->getValue('brandfolder_api_key'))
      ->save();
  }

}

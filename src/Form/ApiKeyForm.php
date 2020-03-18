<?php

namespace Drupal\brandfolder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApiKeyForm.
 */
class ApiKeyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'brandfolder.apikey',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_key_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('brandfolder.apikey');

    $form['api_key'] = [
      '#type' => 'textarea',
      '#title' => t('Insert Brandfolder API key here'),
      '#description' => $this->t('Store the Brandfolder API Key'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('brandfolder.apikey')
      // default api key = api_key
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

  }

}

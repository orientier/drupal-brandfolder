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

    $form['brandfolder_api_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Brandfolder API key'),
      '#description' => $this->t('An API key for a Brandfolder user who has access to the Brandfolder you wish to integrate with your Drupal site. This can be found in Brandfolder under "My Profile > Integrations > API Keys."'),
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

    $this->config('brandfolder.settings')
      ->set('api_key', $form_state->getValue('brandfolder_api_key'))
      ->save();
  }

}

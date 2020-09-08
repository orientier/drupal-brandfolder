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
    $api_key = $config->get('api_key');
    $default_brandfolder = $config->get('default_brandfolder');
    $default_collection = $config->get('default_collection');
    $brandfolders_list = $collections_list = [];

    /************************************
     * Credentials.
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
      $messenger = \Drupal::messenger();
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

    /************************************
     * Basic Configuration
     ************************************/
    $form['basic'] = array(
      '#type'  => 'details',
      '#title' => $this->t('Basic Configuration Options'),
      '#open'  => empty($default_brandfolder),
    );
    $form['basic']['brandfolder_default_brandfolder'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Default Brandfolder'),
      '#options'       => $brandfolders_list,
      '#default_value' => $default_brandfolder,
      '#description'   => $this->t('The Brandfolder to use for all operations unless otherwise specified.'),
    ];
    $form['basic']['brandfolder_default_collection'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Default Collection'),
      '#options'       => $collections_list,
      '#default_value' => $default_collection,
      '#description'   => $this->t('The collection to use for all operations unless otherwise specified.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('brandfolder.settings');
    $config->set('api_key', $form_state->getValue('brandfolder_api_key'));
    $config->set('default_brandfolder', $form_state->getValue('brandfolder_default_brandfolder'));
    $config->set('default_collection', $form_state->getValue('brandfolder_default_collection'));
    $config->save();
  }

}

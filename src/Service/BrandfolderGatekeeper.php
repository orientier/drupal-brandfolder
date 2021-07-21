<?php

namespace Drupal\brandfolder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media\MediaSourceInterface;
use Brandfolder\Brandfolder;

/**
 * Helps determine which Brandfolder entities should be available in a given
 * Drupal context.
 */
class BrandfolderGatekeeper {

  use StringTranslationTrait;

  /**
   * Criteria for determining whether Brandfolder entities are valid.
   *
   * @var array
   *  Array with the following structure:
   *
   * @code
   *  [
   *    'allowed' => [
   *      'collections' => [
   *        'abc123def456',
   *        'abc123def457',
   *      ],
   *      'labels' => [
   *        'lmn123lmn987',
   *      ],
   *    ],
   *    'disallowed' => [
   *      'collections' => [
   *        'abc123def458',
   *      ],
   *      'sections' => [
   *        'xyz123abc100',
   *      ],
   *    ],
   * ]
   * @endcode
   *
   *  For an entity to be considered valid, it must match at least one of the
   *  criteria in *each* of the "allowed" criteria sets, and, additionally,
   *  must not match any of the "disallowed" criteria.
   *
   * @todo: Consider supporting more complex boolean logic. However, that may be better left to per-site alteration.
   */
  protected array $criteria;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannelInterface $logger;

  /**
   * All Brandfolder entities for consideration.
   *
   * @var array
   */
  protected array $all_bf_entities;

  /**
   * Valid entities.
   *
   * @var array
   */
  protected array $valid_bf_entities;

  /**
   * Invalid entities.
   *
   * @var array
   */
  protected array $invalid_bf_entities;

  /**
   * Readable message pertaining to validation, etc.
   *
   * @var string
   */
  protected string $message;

  /**
   * Brandfolder API client.
   *
   * @var Brandfolder $bf_client
   */
  protected Brandfolder $bf_client;

  /**
   * BrandfolderGatekeeper constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *
   * @throws \Exception
   */
  public function __construct(TranslationInterface $string_translation, LoggerChannelFactory $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->criteria = [];
    $this->stringTranslation = $string_translation;
    $this->logger = $logger_factory->get('brandfolder');
    $this->configFactory = $config_factory;
    $bf_config = $this->configFactory->get('brandfolder.settings');
    $api_key = $bf_config->get('api_key');
    // @todo: Consider not referring to this as the "default Brandfolder," and, rather, as the "Brandfolder," "global Brandfolder," etc. We are requiring the use of one Brandfolder per Drupal site.
    $default_brandfolder = $bf_config->get('default_brandfolder');
    if ($api_key && $default_brandfolder) {
      // @todo: Brandfolder as a service; DI, etc.
      $this->bf_client = new Brandfolder($api_key, $default_brandfolder);
    }
    else {
      $msg = 'You must configure an API key and select a Brandfolder. Visit the Brandfolder configuration page or request assistance from an administrator.';
      $this->logger->error($msg);
      // @todo.
      throw new \Exception($msg);
    }
  }

  /**
   * Instance creator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *
   * @return static
   *   Returns an instance of this service.
   *
   * @throws \Exception
   */
  public function create(TranslationInterface $string_translation, LoggerChannelFactory $logger_factory, ConfigFactoryInterface $config_factory) {
    return new static(
      $string_translation,
      $logger_factory,
      $config_factory,
    );
  }

  /**
   * Load criteria defined on a media source, to be used when determining
   * whether a given set of Brandfolder entities is valid.
   *
   * @param MediaSourceInterface $source
   */
  public function loadFromMediaSource(MediaSourceInterface $source) {
    $source_config = $source->getConfiguration();
    // @todo: Build on this.
    if (!empty($source_config['brandfolder']['bf_entity_criteria'])) {
      $this->criteria = $source_config['brandfolder']['bf_entity_criteria'];
    }
  }

  /**
   * Process a set of Brandfolder entities and see if they are allowed by the
   * relevant Drupal configuration/rules.
   *
   * @param array $bf_entities
   *  An array with keys corresponding to Brandfolder entity types.
   *  Current supported keys are "assets" and "attachments." Array values are
   *  arrays of Brandfolder entity IDs/keys for each type.
   *
   * @return bool
   */
  public function validateBrandfolderEntities(array $bf_entities) {
    $this->all_bf_entities = $bf_entities;
    $this->invalid_bf_entities = $this->valid_bf_entities = [];
    $processing_queue = [];
    $api_params = [
      'include' => 'collections,section,labels',
      // @todo: Consider allowing configuration/rules/restrictions based on tags, at which point we'd need to figure out whether to work with fields:tag_names or relationships:tags, which do not seem to match each other for most attachments returned from API presently. Tag names are probably preferable in order to match user expectations given the fact that multiple tags can exist with the same name, etc.
//      'fields' => 'tag_names'
    ];

    $type_ops = [
      'assets' => 'fetchAsset',
      'attachments' => 'fetchAttachment',
    ];
    foreach ($type_ops as $bf_entity_type => $fetch_method) {
      if (!empty($bf_entities[$bf_entity_type])) {
        // @todo: Fetch multiple assets/attachments by ID in a single call if/when Brandfolder confirms that this is possible.
        foreach ($bf_entities[$bf_entity_type] as $bf_entity_id) {
          if ($bf_entity = $this->bf_client->{$fetch_method}($bf_entity_id, $api_params)) {
            $bf_entity_data_for_validation = [
              'collections' => $bf_entity->included['collections'] ? array_keys($bf_entity->included['collections']) : [],
              'sections' => $bf_entity->included['sections'] ? array_keys($bf_entity->included['sections']) : [],
              'labels' => $bf_entity->included['labels'] ? array_keys($bf_entity->included['labels']) : [],
            ];
            $processing_queue[$bf_entity_type][$bf_entity_id] = $bf_entity_data_for_validation;
          }
          else {
            $this->invalid_bf_entities[$bf_entity_type][$bf_entity_id] = $bf_entity_id;
          }
        }
      }
    }
    // Check each remaining entity against all specified criteria.
    foreach ($processing_queue as $bf_entity_type => $bf_entities) {
      foreach ($bf_entities as $bf_entity_id => $bf_entity_data) {
        $bf_entity_is_valid = TRUE;
        foreach ($this->criteria['allowed'] as $criteria_family => $criteria) {
          if (!empty($criteria)) {
            $qualifying_attributes = array_intersect($criteria, $bf_entity_data[$criteria_family]);
            if (count($qualifying_attributes) == 0) {
              $this->invalid_bf_entities[$bf_entity_type][$bf_entity_id] = $bf_entity_id;
              $bf_entity_is_valid = FALSE;
              break;
            }
          }
        }
        if ($bf_entity_is_valid) {
          foreach ($this->criteria['disallowed'] as $criteria_family => $criteria) {
            if (!empty($criteria)) {
              $disqualifying_attributes = array_intersect($criteria, $bf_entity_data[$criteria_family]);
              if (count($disqualifying_attributes) > 0) {
                $this->invalid_bf_entities[$bf_entity_type][$bf_entity_id] = $bf_entity_id;
                break;
              }
            }
          }
        }
      }
    }

    $bf_entities_are_valid = empty($this->invalid_bf_entities);

    // Set validation message.
    if ($bf_entities_are_valid) {
      $this->message = t('All Brandfolder entities are valid.');
    }
    else {
      $messages = [];
      foreach ($this->invalid_bf_entities as $bf_entity_type => $entity_ids) {
        $messages[] = $this->t('The following @type are invalid: ', ['@type' => $bf_entity_type]) . implode('|', $entity_ids);
      }
      $this->message = implode('. ', $messages) . '.';
    }

    return $bf_entities_are_valid;
  }

  /**
   * Retrieve the most recent human-readable message pertaining to validation,
   * etc.
   *
   * @return string
   */
  public function getMessage() {
    return $this->message ?? '';
  }

  /**
   * Add elements to a form array to support configuring Brandfolder entity
   * rules.
   *
   * @param array $form
   */
  public function buildConfigForm(&$form) {
    // @todo: Additional config such as allowed labels, sub-collection differentiation, etc.
    $collections_list = $this->bf_client->getCollectionsInBrandfolder();
    $sections_list = $this->bf_client->listSectionsInBrandfolder(NULL, [], TRUE);
    $form['brandfolder'] = [
      '#type'  => 'fieldset',
      '#title' => 'Brandfolder',
    ];
    $form['brandfolder']['bf_entity_criteria'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('@brandfolder Entity Criteria', ['@brandfolder' => 'Brandfolder']),
      '#description' => $this->t('Control which @brandfolder entities can be used.', ['@brandfolder' => 'Brandfolder']),
    ];
    $form['brandfolder']['bf_entity_criteria']['allowed'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Allowed'),
      '#description' => $this->t('Only allow @brandfolder entities that meet *all* of the following criteria.', ['@brandfolder' => 'Brandfolder']),
    ];
    $form['brandfolder']['bf_entity_criteria']['allowed']['collections'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Collections'),
      '#options'       => $collections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['allowed']['collections'] ?? [],
    ];
    $form['brandfolder']['bf_entity_criteria']['allowed']['sections'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Sections'),
      '#options'       => $sections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['allowed']['sections'] ?? [],
    ];
    $form['brandfolder']['bf_entity_criteria']['disallowed'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Disallowed'),
      '#description' => $this->t('Do not allow @brandfolder entities that meet *any* of the following criteria.', ['@brandfolder' => 'Brandfolder']),
    ];
    $form['brandfolder']['bf_entity_criteria']['disallowed']['collections'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Collections'),
      '#options'       => $collections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['disallowed']['collections'] ?? [],
    ];
    $form['brandfolder']['bf_entity_criteria']['disallowed']['sections'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Sections'),
      '#options'       => $sections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['disallowed']['sections'] ?? [],
    ];
  }

//  public function configFormValidate() {
//
//  }

//  public function configFormSubmit() {
//
//  }

}

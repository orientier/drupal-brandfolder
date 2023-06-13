<?php

namespace Drupal\brandfolder\Service;

use Drupal\brandfolder\Plugin\media\Source\BrandfolderImage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media\MediaSourceInterface;
use Brandfolder\Brandfolder;
use GuzzleHttp\Exception\GuzzleException;

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
   *      'collection' => [
   *        'abc123def456' => 'abc123def456',
   *        'abc123def457' => 'abc123def457',
   *      ],
   *      'label' => [
   *        'lmn123lmn987' => 'lmn123lmn987',
   *      ],
   *    ],
   *    'disallowed' => [
   *      'collection' => [
   *        'abc123def458' => 'abc123def458',
   *      ],
   *      'section' => [
   *        'xyz123abc100' => 'xyz123abc100',
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
  protected $criteria = [];

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * All Brandfolder entities for consideration.
   *
   * @var array
   */
  protected $all_bf_entities;

  /**
   * Valid entities.
   *
   * @var array
   */
  protected $valid_bf_entities;

  /**
   * Invalid entities.
   *
   * @var array
   */
  protected $invalid_bf_entities;

  /**
   * Readable message pertaining to validation, etc.
   *
   * @var string
   */
  protected $message;

  /**
   * Brandfolder API client.
   *
   * @var Brandfolder $bf_client
   */
  protected $bf_client;

  /**
   * BrandfolderGatekeeper constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *
   * @throws \Exception
   */
  public function __construct(TranslationInterface $string_translation, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->stringTranslation = $string_translation;
    $this->logger = $logger_factory->get('brandfolder');
    $this->configFactory = $config_factory;
    $bf_config = $this->configFactory->get('brandfolder.settings');
    $api_key = $bf_config->get('api_keys.admin');
    $brandfolder_id = $bf_config->get('brandfolder_id');
    if ($api_key && $brandfolder_id) {
      // @todo: Brandfolder as a service; DI, etc.
      $this->bf_client = new Brandfolder($api_key, $brandfolder_id);
      if ($bf_config->get('verbose_log_mode')) {
        $this->bf_client->enableVerboseLogging();
      }
    }
    else {
      $msg = 'You must configure an API key and select a Brandfolder. Visit the Brandfolder configuration page or request assistance from an administrator.';
      $this->logger->error($msg);
      // @todo Friendlier failure/messaging.
      throw new \Exception($msg);
    }
  }

  /**
   * Instance creator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *
   * @return static
   *   Returns an instance of this service.
   *
   * @throws \Exception
   */
  public function create(TranslationInterface $string_translation, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
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
    // @todo: Expose this in config.
    if ($source instanceof BrandfolderImage) {
      $this->criteria['allowed']['filetype'] = [
        'jpg',
        'png',
        'gif',
        'tiff',
        'svg',
        'webp',
      ];
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
              'collection' => isset($bf_entity->included['collections']) ? array_keys($bf_entity->included['collections']) : [],
              'section' => isset($bf_entity->included['sections']) ? array_keys($bf_entity->included['sections']) : [],
              'label' => isset($bf_entity->included['labels']) ? array_keys($bf_entity->included['labels']) : [],
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
          // Check all criteria families except filetype.
          // @todo: Consider file type/extension validation here.
          if (!empty($criteria) && $criteria_family != 'filetype') {
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

    $bf_config = $this->configFactory->get('brandfolder.settings');
    if ($bf_config->get('verbose_log_mode')) {
      foreach ($this->bf_client->getLogData() as $log_entry) {
        $this->logger->debug($log_entry);
      }
      $this->bf_client->clearLogData();
    }

    return $bf_entities_are_valid;
  }

  /**
   * Fetch assets from Brandfolder that comply with this gatekeeper's criteria.
   *
   * @param array $query_params
   *
   * @return mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetchAssets(array $query_params = []) {
    $search_components = !empty($query_params['search']) ? [$query_params['search']] : [];

    $key_based_criteria = [
      'collection',
      'section',
    ];
    foreach ($key_based_criteria as $criterion) {
      if (!empty($this->criteria['allowed'][$criterion])) {
        $criteria = $this->criteria['allowed'][$criterion];
        array_walk($criteria, function (&$item) {
          $item = "\"$item\"";
        });
        $search_components[] = "{$criterion}_key:(" . implode(' OR ', $criteria) . ')';
      }
      if (!empty($this->criteria['disallowed'][$criterion])) {
        $criteria = $this->criteria['disallowed'][$criterion];
        array_walk($criteria, function (&$item) {
          $item = "\"$item\"";
        });
        $search_components[] = "NOT {$criterion}_key:(" . implode(' OR ', $criteria) . ')';
      }
    }

    // Brandfolder only supports label searches by human-readable name, rather
    // than key/ID. Therefore, map the ID/key-based criteria to the
    // corresponding names.
    // @todo: Update if/when Brandfolder adds support for key-based search.
    $label_ids_and_names = $this->bf_client->listLabelsInBrandfolder( NUll, TRUE);
    if (!empty($this->criteria['allowed']['label'])) {
      $criteria = array_intersect_key($label_ids_and_names, $this->criteria['allowed']['label']);
      array_walk($criteria, function (&$criterion) {
        $criterion = "\"$criterion\"";
      });
      $search_components[] = "label:(" . implode(' OR ', $criteria) . ')';
    }
    if (!empty($this->criteria['disallowed']['label'])) {
      $criteria = array_intersect_key($label_ids_and_names, $this->criteria['disallowed']['label']);
      array_walk($criteria, function (&$criterion) {
        $criterion = "\"$criterion\"";
      });
      $search_components[] = "NOT label:(" . implode(' OR ', $criteria) . ')';
    }

    if (!empty($this->criteria['allowed']['filetype'])) {
      $extension_list = $this->criteria['allowed']['filetype'];
      array_walk($extension_list, function (&$criterion) {
        $criterion = "\"$criterion\"";
      });
      $search_components[] = "filetype:(" . implode(' OR ', $extension_list) . ')';
    }

    array_walk($search_components, function(&$component) {
      $component = "($component)";
    });
    $query_params['search'] = implode(' AND ', $search_components);

    $assets = $this->bf_client->listAssets($query_params, 'all');

    $bf_config = $this->configFactory->get('brandfolder.settings');
    if ($bf_config->get('verbose_log_mode')) {
      foreach ($this->bf_client->getLogData() as $log_entry) {
        $this->logger->debug($log_entry);
      }
      $this->bf_client->clearLogData();
    }

    return $assets;
  }

  /**
   * Get a list of all valid collections.
   *
   * @return array
   *   An array keyed by collection ID whose values are collection names.
   */
  public function getCollections(): array {
    try {
      // Start with all collections in the Brandfolder.
      $collections = $this->bf_client->getCollectionsInBrandfolder();

      $bf_config = $this->configFactory->get('brandfolder.settings');
      if ($bf_config->get('verbose_log_mode')) {
        foreach ($this->bf_client->getLogData() as $log_entry) {
          $this->logger->debug($log_entry);
        }
        $this->bf_client->clearLogData();
      }

      // Return empty array if no collections exist or some error has occurred.
      if (empty($collections)) {

        return [];
      }
      // Reduce the list per allowed/disallowed collection criteria, as
      // applicable.
      if (!empty($this->criteria['allowed']['collection'])) {
        $collections = array_intersect_key($collections, $this->criteria['allowed']['collection']);
      }
      if (!empty($this->criteria['disallowed']['collection'])) {
        $collections = array_diff_key($collections, $this->criteria['disallowed']['collection']);
      }
    }
    catch (GuzzleException $e) {
      $collections = [];
    }

    return $collections;
  }

  /**
   * Get a list of all valid sections.
   *
   * @return array
   *   An array keyed by section ID whose values are section names.
   */
  public function getSections(): array {
    try {
      // Start with all sections in the Brandfolder.
      $sections = $this->bf_client->listSectionsInBrandfolder(NULL, [], TRUE);
      // Return empty array if no sections exist or some error has occurred.
      if (empty($sections)) {

        return [];
      }
      // Reduce the list per allowed/disallowed section criteria, as
      // applicable.
      if (!empty($this->criteria['allowed']['section'])) {
        $sections = array_intersect_key($sections, $this->criteria['allowed']['section']);
      }
      if (!empty($this->criteria['disallowed']['section'])) {
        $sections = array_diff_key($sections, $this->criteria['disallowed']['section']);
      }
    }
    catch (GuzzleException $e) {
      $sections = [];
    }

    return $sections;
  }

  /**
   * Get a list of all valid labels.
   *
   * @return array
   *   An array keyed by label ID whose values are label names.
   */
  public function getLabels(): array {
    try {
      // Start with all labels in the Brandfolder.
      $labels = $this->bf_client->listLabelsInBrandfolder();
      // Return empty array if no labels exist or some error has occurred.
      if (empty($labels)) {

        return [];
      }
      // Reduce the list per allowed/disallowed label criteria, as
      // applicable.
      // @todo: Test with nested labels. The current setup requires explicit whitelisting of all descendants (i.e. choices do not cascade).
      if (!empty($this->criteria['allowed']['label'])) {
        array_walk_recursive($labels, function(&$value, $key, $allowed_labels) {
          if ($key === 'children') {
            $value = array_intersect_key($value, $allowed_labels);
          }
        }, $this->criteria['allowed']['label']);
      }
      if (!empty($this->criteria['disallowed']['label'])) {
        array_walk_recursive($labels, function(&$value, $key, $allowed_labels) {
          if ($key === 'children') {
            $value = array_diff_key($value, $allowed_labels);
          }
        }, $this->criteria['allowed']['label']);
      }
    }
    catch (GuzzleException $e) {
      $labels = [];
    }

    return $labels;
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
   * Get all specified criteria.
   *
   * @return array
   */
  public function getCriteria(): array {
    return $this->criteria ?? [];
  }

  /**
   * Set criteria.
   *
   * @param array $criteria
   */
  public function setCriteria(array $criteria) {
    $this->criteria = $criteria;
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
      '#description' => $this->t('Only allow @brandfolder entities that meet *all* of these criteria.', ['@brandfolder' => 'Brandfolder']),
    ];
    $form['brandfolder']['bf_entity_criteria']['allowed']['collection'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Collections'),
      '#options'       => $collections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['allowed']['collection'] ?? [],
    ];
    $form['brandfolder']['bf_entity_criteria']['allowed']['section'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Sections'),
      '#options'       => $sections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['allowed']['section'] ?? [],
    ];
    $form['brandfolder']['bf_entity_criteria']['disallowed'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Disallowed'),
      '#description' => $this->t('Do not allow @brandfolder entities that meet *any* of these criteria.', ['@brandfolder' => 'Brandfolder']),
    ];
    $form['brandfolder']['bf_entity_criteria']['disallowed']['collection'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Collections'),
      '#options'       => $collections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['disallowed']['collection'] ?? [],
    ];
    $form['brandfolder']['bf_entity_criteria']['disallowed']['section'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Sections'),
      '#options'       => $sections_list,
      '#multiple'      => TRUE,
      '#default_value' => $this->criteria['disallowed']['section'] ?? [],
    ];
  }

//  public function configFormValidate(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
//
//  }

//  public function configFormSubmit(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
//  }

}

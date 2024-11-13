<?php

namespace Drupal\brandfolder\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

//use Drupal\Core\Ajax\AppendCommand;
//use Drupal\Core\Url;
//use Drupal\examples\Utility\DescriptionTemplateTrait;
//use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for Brandfolder browser data requests.
 */
class BrandfolderBrowserController extends ControllerBase {

  /**
   * Shared storage.
   *
   * @var SharedTempStore
   */
  protected SharedTempStore $bfBrowserDataStore;

  /**
   * Brandfolder Gatekeeper service.
   *
   * @var \Drupal\brandfolder\Service\BrandfolderGatekeeper
   */
  protected BrandfolderGatekeeper $brandfolderGatekeeper;

  /**
   * Constructs a new BrandfolderBrowserController object.
   */
  public function __construct(SharedTempStoreFactory $shared_temp_store_factory, BrandfolderGatekeeper $brandfolder_gatekeeper) {
    $this->bfBrowserDataStore = $shared_temp_store_factory->get('brandfolder_browser_data');
    $this->brandfolderGatekeeper = $brandfolder_gatekeeper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): BrandfolderBrowserController|static {
    return new static(
      $container->get('tempstore.shared'),
      $container->get('brandfolder.gatekeeper')
    );
  }

  /**
   * Handler for data update requests from Brandfolder browsers.
   */
  public function bfBrowserUpdate(Request $request) : JsonResponse {
    $request_data = [];
    $content = $request->getContent();
    if (!empty($content)) {
      $request_data = Json::decode($content);
    }

    $required_request_fields = [
      'bfBrowserId',
    ];
    foreach ($required_request_fields as $required_key) {
      if (empty($request_data[$required_key])) {
        return new JsonResponse(FALSE);
      }
    }

    $bf_browser_id = $request_data['bfBrowserId'];

    // Testing.
    if ($bf_browser_id == 'abc123') {
      $gatekeeper_criteria = [
        'approved'    => TRUE,
        'expired'     => FALSE,
        'unpublished' => FALSE,
        'allowed'     => [
          'filetype' => [
            'jpg',
            'png',
            'gif',
            'tiff',
            'svg',
            'webp',
          ],
        ],
        'disallowed'  => [
          'section' => [],
        ],
      ];
    }
    else {
      $bf_browser_data = $this->bfBrowserDataStore->get($bf_browser_id);
      if (empty($bf_browser_data)) {
        return new JsonResponse(FALSE);
      }
      $required_browser_data_fields = [
        'gatekeeper_criteria',
      ];
      foreach ($required_browser_data_fields as $required_key) {
        if (empty($bf_browser_data[$required_key])) {
          return new JsonResponse(FALSE);
        }
      }

      $gatekeeper_criteria = $bf_browser_data['gatekeeper_criteria'];
    }

    // Default to fetching the first page, unless the user has requested
    // another page.
    $page_to_fetch = $request_data['requestedPage'] ?? 1;
    // @todo: Config option, or centralized default.
    $assets_per_page = $request_data['assetsPerPage'] ?? 100;
    $query_params = [
      'per' => $assets_per_page,
      'page' => $page_to_fetch,
    ];

    // @todo
    $tag_key_mapping = $request_data['tagKeyMapping'] ?? [];

    // Process user search text and all filters.
    $user_input_mapping = [
      'collection_key' => 'collections',
      'section_key' => 'sections',
    ];
    $user_criteria = [
      'collection_key' => [],
      'section_key' => [],
      'aspect' => [],
      'filetype' => [],
      'tags' => [],
    ];
    if (!empty($request_data['userInput'])) {
      foreach (array_keys($user_criteria) as $criterion_type) {
        $user_input_key = $user_input_mapping[$criterion_type] ?? $criterion_type;
        if (!empty($request_data['userInput'][$user_input_key])) {
          $criterion = $request_data['userInput'][$user_input_key];
          if ($criterion_type == 'tags') {
            if (isset($tag_key_mapping[$criterion])) {
              $user_criteria[$criterion_type][] = $tag_key_mapping[$criterion];
            }
            continue;
          }
          $user_criteria[$criterion_type] = $criterion;
        }
      }
    }
    $search_query_components = [];
    $user_search_query = $request_data['userInput']['searchText'] ?? '';
    if (!empty($user_search_query)) {
      $search_query_components[] = $user_search_query;
    }
    foreach ($user_criteria as $criterion => $allowed_values) {
      if (count($allowed_values) > 0) {
        array_walk($allowed_values, function(&$value) {
          $value = "\"$value\"";
        });
        if ($criterion == 'tags' && $request_data['userInput']['tagFilterMode'] == 'all') {
          $separator = ' AND ';
        }
        else {
          $separator = ' OR ';
        }
        $search_query_components[] = "$criterion:(" . implode($separator, $allowed_values) . ')';
      }
    }
    // Labels.
    if (!empty($request_data['userInput']['labels'])) {
      $selected_label_names = $request_data['userInput']['labels'];
      array_walk($selected_label_names, function(&$value) {
        $value = "\"$value\"";
      });
      $search_query_components[] = "labels:(" . implode(' OR ', $selected_label_names) . ')';
    }

    // Date range options.
    $date_range_control_mapping = [
      'creationDate' => 'created_at',
      'modificationDate' => 'updated_at',
      'publicationDate' => 'published_at',
    ];
    foreach ($date_range_control_mapping as $control_key => $date_field) {
      if (!empty($request_data['userInput'][$control_key])) {
        $date_input = $request_data['userInput'][$control_key];
        if ($date_input != 'all') {
          $search_query_components[] = "$date_field:>now-$date_input";
        }
      }
    }

    // Assemble the search query string.
    if (!empty($search_query_components)) {
      array_walk($search_query_components, function(&$subquery) {
        $subquery = "($subquery)";
      });
      $query_params['search'] = implode(' AND ', $search_query_components);
    }

    // Sorting.
    $query_params['sort_by'] = $request_data['userInput']['sortCriterion'] ?? 'created_at';
    $query_params['order'] = $request_data['userInput']['sortOrder'] ?? 'desc';

    $gatekeeper = $this->brandfolderGatekeeper;
    $gatekeeper->setCriteria($gatekeeper_criteria);
    $query_params['include'] = 'attachments';

    $result = $gatekeeper->fetchAssets($query_params);

    $response_data = FALSE;
    if ($result) {

      // Assemble data related to browser controls
      // (user search/filtering/sorting/etc.).
      // @todo: Consider proactively customizing/winnowing this list based on which filter combinations will yield results.
      $predefined_date_range_options = [
        'all'        => t('All'),
        '30m' => t('Last 30 Minutes'),
        '1d'   => t('Last 24 Hours'),
        '7d'     => t('Last 7 Days'),
        '30d'    => t('Last 30 Days'),
        '60d'    => t('Last 60 Days'),
        '90d'    => t('Last 90 Days'),
      ];
      $control_schema = [
        'collections' => $gatekeeper->getCollections(),
        'sections' => $gatekeeper->getSections(),
        'labels' => $gatekeeper->getLabels(),
        'aspect' => [
          'landscape' => t('Horizontal'),
          'portrait' => t('Vertical'),
          'square' => t('Square'),
          'panorama' => t('Panoramic'),
        ],
        // @todo: maintain a registry of file types that are actually used and popular for the given Brandfolder and use those.
        'filetype' => [
          'jpg',
          'png',
          'svg',
          'gif',
          'webp',
          //    'mp4',
        ],
        'creationDate' => $predefined_date_range_options,
        'modificationDate' => $predefined_date_range_options,
        'publicationDate' => $predefined_date_range_options,

        // @todo: Tags.

        'sortCriteria' => [
          'name'       => t('Name'),
          'score'      => t('Score'),
          'position'   => t('Position'),
          'updated_at' => t('Date Last Updated'),
          'created_at' => t('Date Uploaded/Created'),
        ],
        'sortOrder' => [
          'asc'  => t('Ascending'),
          'desc' => t('Descending'),
        ],
      ];

      $response_data = [
        'assets' => $result->data,
        'meta'   => $result->meta,
        'controlSchema' => $control_schema,
      ];
    }

    return new JsonResponse($response_data);
  }

}

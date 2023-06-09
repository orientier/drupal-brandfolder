<?php

namespace Drupal\brandfolder\Controller;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;

//use Drupal\Core\Ajax\AppendCommand;
//use Drupal\Core\Url;
//use Drupal\examples\Utility\DescriptionTemplateTrait;
//use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for Brandfolder browser asset fetching.
 */
class AssetFetchController extends ControllerBase {

  /**
   * Callback for stub route used in connection with nested AJAX form requests,
   * etc.
   *
   * @return array
   *
   * @todo: Remove this after confirming we won't need it for any AJAX callbacks.
   */
  public function assetFetchStubRouteHandler() : array {
    $output = [
      '#markup' => $this->t('Fetching assets...'),
    ];

    return $output;
  }

  /**
   * AJAX callback to fetch Brandfolder assets.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function assetFetchFormAjaxCallback(array &$form, FormStateInterface $form_state, \Symfony\Component\HttpFoundation\Request $request): array {
    $all_form_values = $form_state->getValues();

    $tag_key_mapping = isset($all_form_values['brandfolder_controls_tag_key_mapping']) ? json_decode($all_form_values['brandfolder_controls_tag_key_mapping'], TRUE) : [];

    $query_params = [];

    // Process user search text and all filters.
    $user_criteria = [
      'collection_key' => [],
      'section_key' => [],
      'aspect' => [],
      'filetype' => [],
      'tags' => [],
    ];
    $valid_criterion_types = implode('|', array_keys($user_criteria));
    foreach ($all_form_values as $input_key => $input_value) {
      if ($input_value) {
        if (preg_match("/^brandfolder_controls_($valid_criterion_types)_(.+)$/", $input_key, $matches)) {
          $criterion_type = $matches[1];
          $criterion = $matches[2];
          if ($criterion_type == 'tags') {
            if (isset($tag_key_mapping[$criterion])) {
              $user_criteria[$criterion_type][] = $tag_key_mapping[$criterion];
            }
            continue;
          }
          $user_criteria[$criterion_type][] = $criterion;
        }
      }
    }
    $search_query_components = [];
    $user_search_query = $form_state->getValue('brandfolder_controls_search_text') ?? '';
    if (!empty($user_search_query)) {
      $search_query_components[] = $user_search_query;
    }
    foreach ($user_criteria as $criterion => $allowed_values) {
      if (count($allowed_values) > 0) {
        array_walk($allowed_values, function(&$value) {
          $value = "\"$value\"";
        });
        if ($criterion == 'tags' && $form_state->getValue('brandfolder_controls_tag_filter_mode') == 'all') {
          $separator = ' AND ';
        }
        else {
          $separator = ' OR ';
        }
        $search_query_components[] = "$criterion:(" . implode($separator, $allowed_values) . ')';
      }
    }
    // Labels.
    if (!empty($all_form_values['brandfolder_controls_labels'])) {
      // Translate label IDs to their latest names (caching isn't good enough
      // here), since Brandfolder doesn't seem to support searching for assets
      // by label ID/key.
      $bf = brandfolder_api();
      $bf_config = \Drupal::config('brandfolder.settings');
      if ($bf_config->get('verbose_log_mode')) {
        $bf->enableVerboseLogging();
      }
      $label_id_name_mapping = $bf->listLabelsInBrandfolder(NULL, TRUE);
      if ($bf_config->get('verbose_log_mode')) {
        $logger = \Drupal::logger('brandfolder');
        foreach ($bf->getLogData() as $log_entry) {
          $logger->debug($log_entry);
        }
        $bf->clearLogData();
      }
      $selected_label_names = array_intersect_key($label_id_name_mapping, $all_form_values['brandfolder_controls_labels']);
      array_walk($selected_label_names, function(&$value) {
        $value = "\"$value\"";
      });
      $search_query_components[] = "labels:(" . implode(' OR ', $selected_label_names) . ')';
    }

    // Upload recency.
    if (!empty($all_form_values['brandfolder_controls_upload_date'])) {
      $upload_date_input = $all_form_values['brandfolder_controls_upload_date'];
      if ($upload_date_input != 'all') {
        $search_query_components[] = "created_at:>now-$upload_date_input";
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
    $query_params['sort_by'] = $all_form_values['brandfolder_controls_sort_criterion'] ?? 'created_at';
    $query_params['order'] = $all_form_values['brandfolder_controls_sort_order'] ?? 'desc';

    $gatekeeper = \Drupal::getContainer()
      ->get(BrandfolderGatekeeper::class);
    $gatekeeper_criteria_string = $form_state->getValue('bf_gatekeeper_criteria');
    if (!empty($gatekeeper_criteria_string)) {
      $gatekeeper_criteria = json_decode($gatekeeper_criteria_string, TRUE);
      if (!empty($gatekeeper_criteria)) {
        $gatekeeper->setCriteria($gatekeeper_criteria);
      }
    }
    $query_params['include'] = 'attachments';

    $bf_asset_list = '<p class="brandfolder-browser__no-results-message">' . t('No assets found') . '</p>';
    $assets = $gatekeeper->fetchAssets($query_params);
    if (!empty($assets->data)) {
      $disabled_bf_attachment_ids = [];
      if (!empty($all_form_values['disabled_bf_attachment_ids'])) {
        $disabled_bf_attachment_ids = explode(',', $all_form_values['disabled_bf_attachment_ids']);
      }
      $selected_bf_attachment_ids = [];
      if (!empty($all_form_values['selected_bf_attachment_ids'])) {
        $selected_bf_attachment_ids = explode(',', $all_form_values['selected_bf_attachment_ids']);
      }
      $bf_asset_list = brandfolder_format_asset_list($assets, $disabled_bf_attachment_ids, $selected_bf_attachment_ids);
    }

    // The form structure varies depending on context. Determine where the
    // Brandfolder browser lives within this particular form.
    // @todo: Consider something less rigid.

    // Media Library context:
    if (isset($form['output']['brandfolder_browser'])) {
      $parent = &$form['output']['brandfolder_browser'];
    }
    // Entity Browser context:
    elseif (isset($form['widget']['brandfolder_browser'])) {
      $parent = &$form['widget']['brandfolder_browser'];
    }
    // Possible generic context:
    elseif (isset($form['brandfolder_browser'])) {
      $parent = &$form['brandfolder_browser'];
    }
    // Fall back to placing the assets at the top level of the form. This is
    // likely to yield unexpected results.
    else {
      $parent = &$form;
    }
    $parent['brandfolder_browser_assets'] = [
      '#markup' => "<div class=\"brandfolder-assets\">
                    $bf_asset_list
                  </div>",
    ];

    return $parent;
  }

  /**
   * Fetch assets applicable to the current context.
   *
   * @param string $context_id
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
//  public function fetchAssetsCallback(string $context_id) {
//
//    // @todo: Configurable passphrase or generate once per install and keep behind the scenes, etc.
//    $context = openssl_decrypt($context_id, 'aes-256-gcm', 'brandfolder_encrypt');
//
//    $response = new AjaxResponse();
//
//    $params = \Drupal::request()->query->all();
//
//    $bf_params = [
//      'fields' => 'cdn_url',
//      'include' => 'custom_fields',
//    ];
//    $search = '*';
//    if (!empty($params['search'])) {
//      $search = $params['search'];
//    }
//    // If we're in an image field context, load the field/instance and see if
//    // there are any relevant restrictions we should incorporate into our search.
//    if (isset($context['#field_name'])) {
//      // @todo: media type BF config, etc.
////      if ($instance = field_read_instance($context['#entity_type'], $context['#field_name'], $context['#bundle'])) {
////        if (!empty($instance['settings']['file_extensions'])) {
////          $file_extensions = $instance['settings']['file_extensions'];
////        }
////        if (!empty($instance['settings']['min_resolution'])) {
////          $min_res = $instance['settings']['min_resolution'];
////        }
////        if (!empty($instance['settings']['max_resolution'])) {
////          $max_res = $instance['settings']['max_resolution'];
////        }
////      }
//    }
//    else {
//      if (!empty($params['allowed_file_extensions'])) {
//        $file_extensions = $params['allowed_file_extensions'];
//      }
//      // @todo: Determine whether this is/will be supported by Brandfolder API.
//      // phpcs:disable Drupal.Commenting.InlineComment
//      // if (!empty($params['max_file_size'])) {
//      //   $max_file_size = $params['max_file_size'];
//      // }
//      // phpcs:enable
//      if (!empty($params['min_resolution'])) {
//        $min_res = $params['min_resolution'];
//      }
//      if (!empty($params['max_resolution'])) {
//        $max_res = $params['max_resolution'];
//      }
//    }
//
//    if (isset($file_extensions)) {
//      $search .= ' AND extensions:(' . $file_extensions . ')';
//    }
//    if (isset($min_res)) {
//      $min_res = explode('x', $min_res);
//      if (count($min_res) == 2) {
//        [$min_width, $min_height] = $min_res;
//        if (is_numeric($min_width)) {
//          $search .= " AND width:>=$min_width";
//        }
//        if (is_numeric($min_height)) {
//          $search .= " AND height:>=$min_height";
//        }
//      }
//    }
//    if (isset($max_res)) {
//      $max_res = explode('x', $max_res);
//      if (count($max_res) == 2) {
//        [$max_width, $max_height] = $max_res;
//        if (is_numeric($max_width)) {
//          $search .= " AND width:<=$max_width";
//        }
//        if (is_numeric($max_height)) {
//          $search .= " AND height:<=$max_height";
//        }
//      }
//    }
//
//    if (isset($params['sort'])) {
//      $sort = $params['sort'];
//      // @todo: Consider moving this sort of validation into the Brandfolder PHP API library.
//      $valid_sort_criteria = [
//        'name',
//        'score',
//        'position',
//        'updated_at',
//        'created_at',
//      ];
//      if (in_array($sort, $valid_sort_criteria)) {
//        $bf_params['sort_by'] = $sort;
//      }
//    }
//    if (isset($params['order'])) {
//      $bf_params['order'] = $params['order'];
//    }
//    if (isset($params['orientation'])) {
//      $aspect_values = explode(',', $params['orientation']);
//      if (count($aspect_values) > 0) {
//        $search .= ' AND (';
//        $search .= implode(' OR ', array_map(function ($aspect) {
//          return "aspect:$aspect";
//        }, $aspect_values));
//        $search .= ')';
//      }
//    }
//    if (isset($params['created_at'])) {
//      $search .= ' AND created_at:>now-' . $params['created_at'];
//    }
//    // @todo: Add date_updated?
//    if (isset($params['tags'])) {
//      $quoted_tags = array_map(function ($tag_name) {
//        return "\"$tag_name\"";
//      }, explode(',', $params['tags']));
//      $search .= ' AND tags:(' . implode(' ', $quoted_tags) . ')';
//    }
//
//    // @todo: Consider putting this in an array or some other format that would be more cleanly altered by other modules.
//    $baseline_search_criteria = 'approved:true AND expired:false AND unpublished:false';
//
//    if (strlen($search) > 0) {
//      $bf_params['search'] = "$search AND $baseline_search_criteria";
//    }
//    else {
//      $bf_params['search'] = $baseline_search_criteria;
//    }
//
//    // Allow other modules to alter the query.
//    $collection = NULL;
//    // @todo
////    drupal_alter('brandfolder_get_assets', $bf_params, $collection, $context);
//
//    $bf = brandfolder_api();
//    $assets = $bf->listAssets($bf_params, $collection);
//
//    if (is_array($assets->data)) {
//      $thumbnails = array_map(function ($asset) {
//        // Use an alternate file for GIF images so that animated GIFs will appear
//        // animated in the browser preview (element.gif is a still image).
//        $thumbnail_url = str_replace('/element.gif', '/view.gif', $asset->attributes->thumbnail_url);
//        $alt_text = isset($asset->custom_field_values['alt-text']) ? $asset->custom_field_values['alt-text'] : '';
//        $cdn_url = $asset->attributes->cdn_url;
//
////        return '<div class="brandfolder-asset" data-bf-asset-id="' . $asset->id . '"><img src="' . $thumbnail_url . '" class="brandfolder-asset__image" data-bf-asset-id="' . $asset->id . '" data-bf-asset-cdn-url="' . $cdn_url . '" alt="' . $alt_text . '"></div>';
//
//        $markup = "<li class=\"brandfolder-asset\" data-bf-asset-id=\"{$asset->id}\">"
//          .   "<figure>"
//          .     '<img src="' . $thumbnail_url . '" class="brandfolder-asset__image" data-bf-asset-id="' . $asset->id . '" data-bf-asset-cdn-url="' . $cdn_url . '" alt="' . $alt_text . '">'
//          .     "<figcaption>"
//          .       "<div class=\"bf-asset-metadata\"><strong>Name:</strong> {$asset->attributes->name}</div>"
//          .       "<div class=\"bf-asset-metadata\"><strong>Asset ID:</strong> {$asset->id}</div>"
//          .     "</figcaption>"
//          .   "</figure>"
//          . "</li>";
//
//        return $markup;
//
//      }, $assets->data);
//      $content = count($assets->data) > 0 ? implode(' ', $thumbnails) : t('No images found.');
//      $output = [
//        'content' => $content,
//      ];
//
//      // @todo Offer an alternate response format with assets as JSON objects instead of HTML.
//    }
//    else {
//      $output = [];
//    }
//
//    $response->setData($output);
//
//    return $response;
//  }

}

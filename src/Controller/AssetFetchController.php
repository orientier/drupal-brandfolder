<?php

namespace Drupal\brandfolder\Controller;

use Drupal\Core\Ajax\AjaxResponse;
//use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Url;
//use Drupal\examples\Utility\DescriptionTemplateTrait;
//use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for AJAX example routes.
 */
class AssetFetchController extends ControllerBase {

  /**
   * Fetch assets applicable to the current context.
   *
   * @return AjaxResponse
   */
  public function fetchAssetsCallback($context_id) {

    // @todo: Configurable passphrase or generate once per install and keep behind the scenes, etc.
    $context = openssl_decrypt($context_id, 'aes-256-gcm', 'brandfolder_encrypt');

    $response = new AjaxResponse();

    $params = \Drupal::request()->query->all();

    $bf_params = [
      'fields' => 'cdn_url',
      'include' => 'custom_fields',
    ];
    $search = '*';
    if (!empty($params['search'])) {
      $search = $params['search'];
    }
    // If we're in an image field context, load the field/instance and see if
    // there are any relevant restrictions we should incorporate into our search.
    if (isset($context['#field_name'])) {
      // @todo: media type BF config, etc.
//      if ($instance = field_read_instance($context['#entity_type'], $context['#field_name'], $context['#bundle'])) {
//        if (!empty($instance['settings']['file_extensions'])) {
//          $file_extensions = $instance['settings']['file_extensions'];
//        }
//        if (!empty($instance['settings']['min_resolution'])) {
//          $min_res = $instance['settings']['min_resolution'];
//        }
//        if (!empty($instance['settings']['max_resolution'])) {
//          $max_res = $instance['settings']['max_resolution'];
//        }
//      }
    }
    else {
      if (!empty($params['allowed_file_extensions'])) {
        $file_extensions = $params['allowed_file_extensions'];
      }
      // @todo: Determine whether this is/will be supported by Brandfolder API.
      // phpcs:disable Drupal.Commenting.InlineComment
      // if (!empty($params['max_file_size'])) {
      //   $max_file_size = $params['max_file_size'];
      // }
      // phpcs:enable
      if (!empty($params['min_resolution'])) {
        $min_res = $params['min_resolution'];
      }
      if (!empty($params['max_resolution'])) {
        $max_res = $params['max_resolution'];
      }
    }

    if (isset($file_extensions)) {
      $search .= ' AND extensions:(' . $file_extensions . ')';
    }
    if (isset($min_res)) {
      $min_res = explode('x', $min_res);
      if (count($min_res) == 2) {
        [$min_width, $min_height] = $min_res;
        if (is_numeric($min_width)) {
          $search .= " AND width:>=$min_width";
        }
        if (is_numeric($min_height)) {
          $search .= " AND height:>=$min_height";
        }
      }
    }
    if (isset($max_res)) {
      $max_res = explode('x', $max_res);
      if (count($max_res) == 2) {
        [$max_width, $max_height] = $max_res;
        if (is_numeric($max_width)) {
          $search .= " AND width:<=$max_width";
        }
        if (is_numeric($max_height)) {
          $search .= " AND height:<=$max_height";
        }
      }
    }

    if (isset($params['sort'])) {
      $sort = $params['sort'];
      // @todo: Consider moving this sort of validation into the Brandfolder PHP API library.
      $valid_sort_criteria = [
        'name',
        'score',
        'position',
        'updated_at',
        'created_at',
      ];
      if (in_array($sort, $valid_sort_criteria)) {
        $bf_params['sort_by'] = $sort;
      }
    }
    if (isset($params['order'])) {
      $bf_params['order'] = $params['order'];
    }
    if (isset($params['orientation'])) {
      $aspect_values = explode(',', $params['orientation']);
      if (count($aspect_values) > 0) {
        $search .= ' AND (';
        $search .= implode(' OR ', array_map(function ($aspect) {
          return "aspect:$aspect";
        }, $aspect_values));
        $search .= ')';
      }
    }
    if (isset($params['created_at'])) {
      $search .= ' AND created_at:>now-' . $params['created_at'];
    }
    // @todo: Add date_updated?
    if (isset($params['tags'])) {
      $quoted_tags = array_map(function ($tag_name) {
        return "\"$tag_name\"";
      }, explode(',', $params['tags']));
      $search .= ' AND tags:(' . implode(' ', $quoted_tags) . ')';
    }

    // @todo: Consider putting this in an array or some other format that would be more cleanly altered by other modules.
    $baseline_search_criteria = 'approved:true AND expired:false AND unpublished:false';

    if (strlen($search) > 0) {
      $bf_params['search'] = "$search AND $baseline_search_criteria";
    }
    else {
      $bf_params['search'] = $baseline_search_criteria;
    }

    // Allow other modules to alter the query.
    $collection = NULL;
    // @todo
//    drupal_alter('brandfolder_get_assets', $bf_params, $collection, $context);

    $bf = brandfolder_api();
    $assets = $bf->listAssets($bf_params, $collection);

    if (is_array($assets->data)) {
      $thumbnails = array_map(function ($asset) {
        // Use an alternate file for GIF images so that animated GIFs will appear
        // animated in the browser preview (element.gif is a still image).
        $thumbnail_url = str_replace('/element.gif', '/view.gif', $asset->attributes->thumbnail_url);
        $alt_text = isset($asset->custom_field_values['alt-text']) ? $asset->custom_field_values['alt-text'] : '';
        $cdn_url = $asset->attributes->cdn_url;

//        return '<div class="brandfolder-asset" data-bf-asset-id="' . $asset->id . '"><img src="' . $thumbnail_url . '" class="brandfolder-asset__image" data-bf-asset-id="' . $asset->id . '" data-bf-asset-cdn-url="' . $cdn_url . '" alt="' . $alt_text . '"></div>';

        $markup = "<li class=\"brandfolder-asset\" data-bf-asset-id=\"{$asset->id}\">"
          .   "<figure>"
          .     '<img src="' . $thumbnail_url . '" class="brandfolder-asset__image" data-bf-asset-id="' . $asset->id . '" data-bf-asset-cdn-url="' . $cdn_url . '" alt="' . $alt_text . '">'
          .     "<figcaption>"
          .       "<div class=\"bf-asset-metadata\"><strong>Name:</strong> {$asset->attributes->name}</div>"
          .       "<div class=\"bf-asset-metadata\"><strong>Asset ID:</strong> {$asset->id}</div>"
          .     "</figcaption>"
          .   "</figure>"
          . "</li>";

        return $markup;

      }, $assets->data);
      $content = count($assets->data) > 0 ? implode(' ', $thumbnails) : t('No images found.');
      $output = [
        'content' => $content,
      ];

      // @todo Offer an alternate response format with assets as JSON objects instead of HTML.
    }
    else {
      $output = [];
    }

    $response->setData($output);

    return $response;
  }

}

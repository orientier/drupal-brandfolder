<?php

/**
 * @file
 * Documentation for Brandfolder module hooks, etc.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the absolute, external URL for a Brandfolder file.
 *
 * $url and $url_options can be altered here. They will then both be passed to
 *   \Drupal\Core\Url::fromUri() when generating the Brandfolder file URL.
 *
 * @param string $url The URL for the Brandfolder file.
 * @param array $url_options An array of options as accepted by
 *  \Drupal\Core\Url::fromUri()
 * @param array $context Additional data, including:
 *  - 'file_uri': The main file URI, not including any image style component.
 *  - 'image_style': The image style object, if any.
 *
 * @see \Drupal\brandfolder\StreamWrapper::getExternalUrl()
 */
function hook_brandfolder_file_url_alter(string &$url, array &$url_options, array $context) {
  if (isset($url_options['query']['width']) && $url_options['query']['width'] < 480) {
    if ($context['image_style'] && $context['image_style']->id() === 'hifi_teaser') {
      $url_options['query']['quality'] = 90;
    }
    else {
      $url_options['query']['quality'] = 65;
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
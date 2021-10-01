<?php

namespace Drupal\brandfolder\Controller;

use Drupal\brandfolder\Event\BrandfolderWebhookEvent;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle incoming Brandfolder webhook requests/events.
 */
class IncomingWebhookController extends ControllerBase implements AccessInterface {

  /**
   * Valid event types.
   *
   * @var array $valid_event_types
   */
  protected $valid_event_types = [
    'asset.create',
    'asset.update',
    'asset.delete',
  ];

  /**
   * Access management. Block requests that are blatantly invalid.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request): \Drupal\Core\Access\AccessResultInterface {
    $valid_payload = FALSE;
    $payload = json_decode($request->getContent(), TRUE);
    if (isset($payload['data']['attributes'])) {
      $data = $payload['data']['attributes'];
      $event_type = $data['event_type'];
      if (in_array($event_type, $this->valid_event_types)) {
        if (isset($data['key']) && is_string($data['key']) && strlen($data['key']) > 0) {
          $valid_payload = TRUE;
        }
      }
    }

    return AccessResult::allowedIf($valid_payload);
  }

  /**
   * Handle incoming webhooks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function webhookListener(Request $request) {
    $payload = json_decode($request->getContent(), TRUE);
    if (isset($payload['data']['attributes'])) {
      $bf_event_type = $payload['data']['attributes']['event_type'];
      // Fire/dispatch events so Drupal modules can act on this webhook.
      $dispatcher = \Drupal::service('event_dispatcher');
      $dispatcher->dispatch($bf_event_type, new BrandfolderWebhookEvent($payload['data']));
    }

    return new Response($this->t('Webhook handling complete.'), Response::HTTP_OK);
  }

}

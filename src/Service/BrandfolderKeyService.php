<?php

namespace Drupal\brandfolder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\key\KeyRepository;

/**
 * Manages API keys for Brandfolder interactions.
 */
class BrandfolderKeyService {

  /**
   * Brandfolder module configuration.
   */
  protected ImmutableConfig $bfConfig;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannelInterface $logger;

  /**
   * Key repository.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected KeyRepository $keyRepository;

  /**
   * BrandfolderKeyService constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\key\KeyRepository $key_repository
   *
   * @throws \Exception
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, KeyRepository $key_repository) {
    $this->logger = $logger_factory->get('brandfolder');
    $this->bfConfig = $config_factory->get('brandfolder.settings');
    $this->keyRepository = $key_repository;
  }

  /**
   * Instance creator.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\key\KeyRepository $key_repository
   *
   * @return static
   *   Returns an instance of this service.
   *
   * @throws \Exception
   */
  public function create(LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, KeyRepository $key_repository): static {
    return new static(
      $logger_factory,
      $config_factory,
      $key_repository,
    );
  }

  /**
   * Get a Brandfolder API key of a given type.
   *
   * @param string $key_type
   *  "admin", "collaborator", or "guest"
   *
   * @return string
   */
  public function getApiKey(string $key_type): string {
    $api_key = NULL;
    // Attempt to retrieve the corresponding key managed by the Key module and
    // referenced in BF module settings.
    $api_key_id = $this->bfConfig->get("api_key_ids.$key_type");
    if ($api_key_id) {
      if ($key_entity = $this->keyRepository->getKey($api_key_id)) {
        $api_key = $key_entity->getKeyValue();
      }
    }
    // Fall back to legacy config if no newer key was found.
    else {
      $api_key = $this->bfConfig->get("api_keys.$key_type");
      // Log a warning encouraging admins to switch to new key storage model.
      $msg = 'The Brandfolder module is now using the Key module to manage API keys. Please visit the Brandfolder configuration page to reconfigure your API keys.';
      $this->logger->warning($msg);
    }

    return $api_key;
  }

}

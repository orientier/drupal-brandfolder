<?php

namespace Drupal\brandfolder\Plugin\Field\FieldWidget;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\FileBrowserWidget;

/**
 * Brandfolder-specific Entity Browser file widget.
 *
 * @FieldWidget(
 *   id = "brandfolder_entity_browser_file",
 *   label = @Translation("Brandfolder Entity Browser"),
 *   provider = "brandfolder",
 *   multiple_values = TRUE,
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BrandfolderFileEntityBrowserWidget extends FileBrowserWidget {

  /**
   * The Brandfolder Gatekeeper service.
   *
   * @var ?\Drupal\brandfolder\Service\BrandfolderGatekeeper
   */
  protected ?BrandfolderGatekeeper $bfGatekeeper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->bfGatekeeper = $container->get('brandfolder.gatekeeper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileValidators($upload = FALSE) {
    $validators = parent::getFileValidators($upload);

    // Add the Brandfolder Gatekeeper so BF Entity Browser plugins can use it.
    $this->bfGatekeeper->loadFromFieldDefinition($this->fieldDefinition);
    $validators['brandfolder_gatekeeper_criteria'] = $this->bfGatekeeper->getCriteria();

    return $validators;
  }

  // @todo: Try getting alt text from BF assets when BF files are newly selected, then feed that text to the applicable alt text field on the selected entities form/metadata table.
}

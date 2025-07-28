<?php

namespace Drupal\brandfolder\Plugin\Field\FieldWidget;

use Drupal\brandfolder\Service\BrandfolderGatekeeper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\FileBrowserWidget;
use Drupal\Core\Render\Element;

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

  /**
   * {@inheritdoc}
   */
  public function displayCurrentSelection($details_id, array $field_parents, array $entities) {
    $current_selection = parent::displayCurrentSelection($details_id, $field_parents, $entities);

    // If no alt text has been specified for a selected file, try to fetch an
    // alt text value from Brandfolder.
    foreach (Element::children($current_selection) as $key) {
      if (is_int($key)) {
        // The key should be a file ID.
        if ($bf_attachment_id = brandfolder_map_file_to_attachment($key)) {
          if ($current_selection[$key]['meta']['alt']['#access'] && empty($current_selection[$key]['meta']['alt']['#default_value'])) {
            if ($alt_text = brandfolder_get_alt_text_from_attachment($bf_attachment_id)) {
              $current_selection[$key]['meta']['alt']['#default_value'] = $alt_text;
            }
          }
        }
      }
    }

    return $current_selection;
  }
}

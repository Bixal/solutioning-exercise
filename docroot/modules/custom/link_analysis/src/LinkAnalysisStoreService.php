<?php

namespace Drupal\link_analysis;

use DOMDocument;
use DOMXPath;
use Drupal;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Exception;
use PDO;

/**
 * Class LinkAnalysisStoreService.
 */
class LinkAnalysisStoreService {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal \Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current config for Link Analysis module.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private $config;

  /**
   * Constructs a new LinkAnalysisStoreService object.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   Drupal database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal entity interface manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Drupal config manager.
   */
  public function __construct(Connection $database,
                              EntityTypeManagerInterface $entityTypeManager,
                              RendererInterface $renderer,
                              ConfigManagerInterface $config_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
    $this->config = $config_manager;

  }

  /**
   * Process current entity to make entry into the database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Drupal entity interface.
   */
  public function process(EntityInterface $entity) {
    $settings = $this->config->getConfigFactory()
      ->get('link_analysis.settings')
      ->get('link_analysis');
    $regions = $settings['regions_to_be_parsed'];
    if (!$entity->id()) {
      return;
    }
    // If regions is empty then early exit.
    if (empty($regions)) {
      return;
    }
    // HTML string from rendered regions.
    $html = '';
    foreach ($regions as $region) {
      $html .= $this->renderRegion($entity, $region);
    }

    $anchors = $this->getAnchorsFromHtml($html);

    // Loop through all anchor tags and if they are not same page add them to
    // the appropriate node.
    foreach ($anchors as $a) {
      $urlParts = parse_url($a->getAttribute('href'));
      if ($urlParts['path'] === "/" || $urlParts['path'] === "<front>") {
        $path = Drupal::config('system.site')->get('page.front');
      } else {
        $path = Drupal::service('path.alias_manager')
          ->getPathByAlias($urlParts['path']);
      }

      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        if ($matches[1] !== $entity->id()) {
          $this->handleId($entity->id(), $matches[1]);
        }
      }
    }
  }

  /**
   * DomDocument will parse HTML and return all found anchor links.
   *
   * @param string $html
   *   HTML string from rendered region.
   *
   * @return \DOMNodeList
   *   DomReader node list of anchors from html string.
   */
  public function getAnchorsFromHtml($html) {
    // Create a Dom and load HTML.
    $dom = new DOMDocument();
    // Use this to hide HTML 5 errors.
    libxml_use_internal_errors(TRUE);
    // Load Initial HTML.
    $dom->loadHTML("<html><body>$html</body></html>");
    // Load the Dom parser.
    $finder = new DOMXPath($dom);
    // Remove local actions.
    foreach ($finder->query("//*[@class='block-local-tasks-block']") as $localTask) {
      $localTask->parentNode->removeChild($localTask);
    }
    // Return all anchor tags.
    $dom->loadHTML($finder->document->saveHTML());
    return $dom->getElementsByTagName('a');
  }

  /**
   * Get render array for a region that has been set in the setting config.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Drupal entity interface.
   * @param string $region
   *   Theme region that has been selected in config.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   Return the html output of the region.
   */
  private function renderRegion(EntityInterface $entity, $region) {
    $viewBuilder = $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId());

    $build = $viewBuilder->view($entity);

    if ($build) {
      $build['#region'] = $region;
      $build['#theme_wrappers'] = ['region'];
    }

    return $this->renderer->renderPlain($build);
  }

  /**
   * The entry needs to be added as a new entry or update an existing entry.
   *
   * @param int $entity_id
   *   Entity id that was referenced on current processed entity.
   * @param int $id
   *   Current entity id being processed.
   */
  private function handleId($entity_id, $id) {
    if ($entry = $this->getEntry($id)) {
      $ids = json_decode($entry[0]['referenced_ids']);
      if (!in_array((int) $entity_id, is_array($ids) ? $ids : [$ids])) {
        $ids[] = $entity_id;
        $this->updateEntry($id, $ids);
      }
    }
    else {
      $this->insertEntry($id, $entity_id);
    }
  }

  /**
   * Database method to get entry by target_id.
   *
   * @param int $target_id
   *   The entity that is being referenced.
   *
   * @return bool|array
   *   Returns false if error, return assoc array if entry exist
   */
  public function getEntry($target_id) {
    try {
      $query = $this->database->select('link_analysis', 'la')
        ->fields('la', ['target_id', 'referenced_ids'])
        ->condition("target_id", $target_id, "=")
        ->execute()
        ->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (Exception $e) {
      Drupal::messenger()
        ->addError($e->getMessage());
      return FALSE;
    }

    if (!empty($query)) {
      return $query;
    }

    return FALSE;
  }

  /**
   * Method to up date an entry and convert $ids array in to a JSON string.
   *
   * @param int $target_id
   *   Entity that is being referenced.
   * @param array $ids
   *   The ID of all the places it has been referenced.
   */
  private function updateEntry($target_id, array $ids) {
    try {
      $this->database->update('link_analysis')
        ->condition('target_id', $target_id)
        ->fields([
          "referenced_ids" => json_encode($ids),
        ])
        ->execute();
    }
    catch (Exception $e) {
      Drupal::messenger()
        ->addError($e->getMessage());
    }
  }

  /**
   * Database method that inserts new entries.
   *
   * @param int $target_id
   *   Referenced entity.
   * @param int $ids
   *   The ids that entity is referenced on.
   */
  private function insertEntry($target_id, $ids) {
    try {
      $this->database->insert('link_analysis')
        ->fields([
          'target_id' => $target_id,
          'referenced_ids' => json_encode([0 => $ids]),
        ])
        ->execute();
    }
    catch (Exception $e) {
      Drupal::messenger()
        ->addError($e->getMessage());
    }
  }

}

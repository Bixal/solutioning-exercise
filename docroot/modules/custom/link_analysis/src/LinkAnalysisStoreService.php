<?php

namespace Drupal\link_analysis;

use DOMDocument;
use DOMXPath;
use Drupal;
use Drupal\Core\Database\Driver\mysql\Connection;
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
   * @var Connection
   */
  protected $database;

  /**
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
   * @var array
   */
  private $config;

  /**
   * Constructs a new LinkAnalysisStoreService object.
   *
   * @param Connection $database
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(Connection $database,
                              EntityTypeManagerInterface $entityTypeManager,
                              RendererInterface $renderer) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
    $this->config = Drupal::config('link_analysis.settings')
      ->get('link_analysis');
  }

  /**
   * Process method pulls in the selected regions and entity, get the
   * render output per region then parse HTML looking for any internal links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function process($entity) {
    $regions = $this->config['regions_to_be_parsed'];
    if (!$entity->id()) {
      return;
    }
    // If regions is empty then early exit
    if (empty($regions)) {
      return;
    }
    // HTML string from rendered regions
    $html = '';
    foreach ($regions as $region) {
      $html .= $this->renderRegion($entity, $region);
    }
    // Create a Dom and load HTML
    $dom = new DOMDocument();
    // Use this to hide HTML 5 errors
    libxml_use_internal_errors(TRUE);
    // Load Initial HTML
    $dom->loadHTML("<html><body>$html</body></html>");
    // Load the Dom parser
    $finder = new DOMXPath($dom);
    // Remove local actions
    foreach ($finder->query("//*[@class='block-local-tasks-block']") as $localTask) {
      $localTask->parentNode->removeChild($localTask);
    }
    // GET all anchor tags
    $dom->loadHTML($finder->document->saveHTML());
    $anchors = $dom->getElementsByTagName('a');
    // Loop through all anchor tags and if they are not same page add them to
    // the appropriate node
    foreach ($anchors as $a) {
      $urlParts = parse_url($a->getAttribute('href'));
      $path = Drupal::service('path.alias_manager')
        ->getPathByAlias($urlParts['path'] === "/" ? "<front>" : $urlParts['path']);

      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        if ($matches[1] !== $entity->id()) {
          $this->handleID($entity->id(), $matches[1]);
        }
      }
    }


  }

  /**
   * Uses the entity to get the render array for a region that has been set in
   * the setting config.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $region
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  private function renderRegion($entity, $region) {
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
   * Will handle if the entry needs to be added as a new entry or update an
   * existing entry
   *
   * @param int $entity_id
   * @param array $id
   */
  private function handleID($entity_id, $id) {
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
   * Database method to get entry by target_id
   *
   * @param $target_id
   *
   * @return bool
   */
  public function getEntry($target_id) {
    try {
      $query = $this->database->select('link_analysis', 'la')
        ->fields('la', ['target_id', 'referenced_ids'])
        ->condition("target_id", $target_id, "=")
        ->execute()
        ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
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
   * Database method to up date an entry and convert $ids array in to a JSON
   * string.
   *
   * @param $target_id
   * @param $ids
   */
  private function updateEntry($target_id, $ids) {
    try {
      $this->database->update('link_analysis')
        ->condition('target_id', $target_id)
        ->fields([
          "referenced_ids" => json_encode($ids),
        ])
        ->execute();
    } catch (Exception $e) {
      Drupal::messenger()
        ->addError($e->getMessage());
    }
  }

  /**
   * Database method that inserts new entries.,
   *
   * @param $target_id
   * @param $ids
   */
  private function insertEntry($target_id, $ids) {
    try {
      $this->database->insert('link_analysis')
        ->fields([
          'target_id' => $target_id,
          'referenced_ids' => json_encode([0 => $ids]),
        ])
        ->execute();
    } catch (Exception $e) {
      Drupal::messenger()
        ->addError($e->getMessage());
    }
  }

}

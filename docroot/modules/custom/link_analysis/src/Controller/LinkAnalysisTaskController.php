<?php

namespace Drupal\link_analysis\Controller;

use Drupal;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class LinkAnalysisTaskController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\link_analysis\LinkAnalysisStoreService
   */
  private $linkAnalysisStore;

  /**
   *
   */
  public function __construct(RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->linkAnalysisStore = Drupal::service('link_analysis.store');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * This is used to build a render array for table that will be used on the
   * Reference tab on the node screens.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function build(NodeInterface $node) {
    // Table header used in render array.
    $header = [
      'title' => $this->t("Title"),
      'status' => $this->t("Status"),
      "link" => 'Link',
    ];

    $rows = [];
    // Get entry by using node ID.
    if ($entry = $this->linkAnalysisStore->getEntry($node->id())) {
      // Decode the json to array.
      $ids = json_decode($entry[0]['referenced_ids'], TRUE);
      if (!empty($ids)) {
        /**
         * Here we validate if it is an array then use IDS as they are if not we
         * force an array
         */
        $nodes = $this->entityTypeManager
          ->getStorage('node')
          ->loadMultiple(is_array($ids) ? $ids : [$ids]);
        // Loop through all the nodes building a link to the page and a row.
        foreach ($nodes as $node) {
          $linkMarkup = [
            'data' => new FormattableMarkup('<a target="_blank" class="button button--primary" href=":link">@name</a>', [
              ':link' =>
              $node->toUrl()->toString(),
              '@name' => "View",
            ]),
          ];
          $rows[] = [
            'title' => $node->label(),
            'status' => $node->isPublished() ? "Published" : "Not Published",
            'link' => $linkMarkup,
          ];
        }
      }
    }
    // Render array for table that pulls in the header and row.
    $build['table'] = [
      '#type' => 'table',
      '#title' => $this->t('Reference List'),
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => "No references currently exists for this page.",
      '#weight' => '1',
      '#attributes' => [
        'id' => "replace-table",
      ],
    ];
    return $build;
  }

}

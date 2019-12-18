<?php

namespace Drupal\entity_tasks\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\entity_tasks\Form\EntityTasksConfigForm;

/**
 * Class ToolbarService.
 *
 * @package Drupal\entity_tasks\Service
 */
class ToolbarService {
  const ENTITY_TASKS_CLASSIC_DISPLAY_MODE = 'classic';
  const ENTITY_TASKS_EXPANDED_DISPLAY_MODE = 'expanded';
  const ENTITY_TASKS_DROPDOWN_DISPLAY_MODE = 'dropdown';

  /**
   * The local tasks manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  private $localTaskManager;

  /**
   * The current route matcher.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  private $adminContext;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  private $translationManager;

  /**
   * ToolbarService constructor.
   *
   * @param \Drupal\Core\Menu\LocalTaskManager $localTaskManager
   *   The local tasks manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The admin context.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translationManager
   *   The translation manager.
   */
  public function __construct(LocalTaskManager $localTaskManager, CurrentRouteMatch $currentRouteMatch, ConfigFactoryInterface $configFactory, AdminContext $adminContext, AccountProxyInterface $currentUser, TranslationManager $translationManager) {
    $this->localTaskManager = $localTaskManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->configFactory = $configFactory;
    $this->adminContext = $adminContext;
    $this->currentUser = $currentUser;
    $this->translationManager = $translationManager;
  }

  /**
   * Builds the toolbar configuration.
   *
   * @param array $items
   *   The toolbar items from hook_toolbar().
   */
  public function buildToolbarConfiguration(array &$items) {
    // Only add the tasks on non admin pages.
    if (!$this->adminContext->isAdminRoute() && $this->currentUser->hasPermission('access entity tasks')) {
      // Get the correct display mode.
      $displayMode = $this->getCorrectDisplayMode();
      // Handle the items based on the selected display mode.
      switch ($displayMode) {
        case self::ENTITY_TASKS_CLASSIC_DISPLAY_MODE:
          $this->addClassicItems($items);
          break;

        case self::ENTITY_TASKS_EXPANDED_DISPLAY_MODE:
          $this->addExpandedItems($items);
          break;

        case self::ENTITY_TASKS_DROPDOWN_DISPLAY_MODE:
          $this->addDropdownItems($items);
          break;

        default:
          // If none of the display modes are set the view is disabled.
          break;
      }
    }
  }

  /**
   * Adds the local tasks to the toolbar with the classic display mode.
   *
   * @param array $items
   *   The toolbar items from hook_toolbar().
   */
  private function addClassicItems(array &$items) {
    // Get the links.
    $links = $this->getLinks();
    // Add the links to the toolbar.
    $items['entity_tasks'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->translationManager->translate('Tasks'),
        '#attributes' => [
          'class' => [
            'toolbar-icon',
            'toolbar-icon-entity-tasks-tasks',
          ],
          'aria-pressed' => 'false',
        ],
      ],
      'tray' => [
        '#theme' => 'links__entity_tasks',
        '#links' => $links,
        '#attributes' => [
          'class' => ['toolbar-menu'],
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'entity-tasks-toolbar-tab',
        ],
      ],
      '#weight' => 1000,
      '#attached' => [
        'library' => [
          'entity_tasks/toolbar',
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Adds the local tasks to the toolbar with the expanded display mode.
   *
   * @param array $items
   *   The toolbar items from hook_toolbar().
   */
  private function addExpandedItems(array &$items) {
    // Get the links.
    $links = $this->getLinks();
    // Reverse the array to display the links in a logical order.
    $links = array_reverse($links);
    // Add the links to the toolbar.
    foreach ($links as $link) {
      $linkName = strtolower($link['title']);
      $routeClass = $this->getRouteNameClass($link['url']->getRouteName());

      $items['entity_tasks_item_' . $linkName] = [
        '#type' => 'toolbar_item',
        'tab' => [
          '#type' => 'link',
          '#url' => $link['url'],
          '#title' => $link['title'],
          '#attributes' => [
            'class' => [
              'toolbar-icon',
              'toolbar-icon-entity-tasks-' . $routeClass,
            ],
            'aria-pressed' => 'false',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => [
            'entity-tasks-toolbar-tab',
          ],
        ],
        '#weight' => 1000,
        '#attached' => [
          'library' => [
            'entity_tasks/toolbar',
          ],
        ],
        '#cache' => ['max-age' => 0],
      ];
    }
  }

  /**
   * Adds the local tasks to the toolbar with the dropdown display mode.
   *
   * @param array $items
   *   The toolbar items from hook_toolbar().
   */
  private function addDropdownItems(array &$items) {
    // Get the links.
    $links = $this->getLinks();
    // Add the links to the toolbar.
    $items['entity_tasks'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->translationManager->translate('Tasks'),
        '#attributes' => [
          'class' => [
            'toolbar-icon',
            'toolbar-icon-entity-tasks-tasks',
          ],
          'aria-pressed' => 'false',
        ],
      ],
      'tray' => [
        '#theme' => 'entity_tasks_dropdown',
        '#links' => $links,
      ],
      '#wrapper_attributes' => [
        'class' => [
          'entity-tasks-toolbar-tab',
          'entity-tasks-toolbar-dropdown',
        ],
      ],
      '#weight' => 1000,
      '#attached' => [
        'library' => [
          'entity_tasks/dropdown',
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Returns the local tasks.
   *
   * @return array
   *   Array containing all local task links.
   */
  private function getLinks() {
    // Get the local tasks.
    $localTasks = $this->localTaskManager->getLocalTasks($this->currentRouteMatch->getRouteName(), 0);
    // Create a usable array.
    $links = [];
    // Only add the links where the user has access to.
    foreach ($localTasks['tabs'] as $link) {
      // @todo Check if this can be called on the object.
      if (is_a($link['#access'], 'Drupal\Core\Access\AccessResultAllowed')) {
        $links[] = $link['#link'];
      }
    }

    return $links;
  }

  /**
   * Checks the correct display mode and sets it when non-existant.
   *
   * @return string
   *   Returns the correct display mode.
   */
  private function getCorrectDisplayMode() {
    // Get the display mode from the config.
    $displayMode = $this->configFactory
      ->get(EntityTasksConfigForm::ENTITY_TASKS_CONFIG_NAME)
      ->get('display_mode');
    // Set the default display mode if required.
    if ($displayMode === NULL) {
      $displayMode = self::ENTITY_TASKS_CLASSIC_DISPLAY_MODE;
      // Set the default display mode.
      $this->configFactory
        ->getEditable(EntityTasksConfigForm::ENTITY_TASKS_CONFIG_NAME)
        ->set($displayMode, self::ENTITY_TASKS_CLASSIC_DISPLAY_MODE)
        ->save();
    }

    return $displayMode;
  }

  /**
   * Return the route name as a class.
   *
   * @param string $routeName
   *   The route name.
   *
   * @return string
   *   A string containing the converted route name.
   */
  private function getRouteNameClass($routeName) {
    $replacePattern = [
      '.' => '--',
      '_' => '-',
    ];
    return str_replace(array_keys($replacePattern), array_values($replacePattern), $routeName);
  }

}

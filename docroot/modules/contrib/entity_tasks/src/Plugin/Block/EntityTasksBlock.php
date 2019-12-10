<?php

namespace Drupal\entity_tasks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Entity tasks block.
 *
 * @Block(
 *   id = "entity_tasks_block",
 *   admin_label = @Translation("Entity tasks block")
 * )
 */
class EntityTasksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The local tasks manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected $localTasksManager;

  /**
   * The current route matcher.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * EntityTasksBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\LocalTaskManager $localTasksManager
   *   The local tasks manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route matcher.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, LocalTaskManager $localTasksManager, CurrentRouteMatch $currentRouteMatch, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->localTasksManager = $localTasksManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['tasks_left'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place tasks left'),
      '#default_value' => isset($config['tasks_left']) ? $config['tasks_left'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $this->setConfigurationValue('tasks_left', $values['tasks_left']);
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('plugin.manager.menu.local_task'),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->hasPermission('access entity tasks')) {
      $localTasks = $this->localTasksManager->getLocalTasks($this->currentRouteMatch->getRouteName(), 0);
      $pathToModule = drupal_get_path('module', 'entity_tasks');
      $config = $this->getConfiguration();
      $left = isset($config['tasks_left']) ? $config['tasks_left'] === 1 : FALSE;

      return [
        '#theme' => $this->getBaseId(),
        '#content' => $localTasks['tabs'],
        '#left' => $left,
        '#attached' => [
          'library' => [
            'entity_tasks/block',
          ],
          'drupalSettings' => [
            'entity-tasks-view' => file_get_contents($pathToModule . '/images/view.svg'),
            'entity-tasks-add' => file_get_contents($pathToModule . '/images/add.svg'),
            'entity-tasks-translations' => file_get_contents($pathToModule . '/images/translate.svg'),
            'entity-tasks-edit' => file_get_contents($pathToModule . '/images/edit.svg'),
            'entity-tasks-webform' => file_get_contents($pathToModule . '/images/webform.svg'),
            'entity-tasks-delete' => file_get_contents($pathToModule . '/images/delete.svg'),
            'entity-tasks-webform-results' => file_get_contents($pathToModule . '/images/webform-results.svg'),
            'entity-tasks-shortcuts' => file_get_contents($pathToModule . '/images/star.svg'),
          ],
        ],
        '#cache' => [
          'contexts' => [
            'user.roles',
            'url',
            'languages',
          ],
        ],
      ];
    }

    return NULL;
  }

}

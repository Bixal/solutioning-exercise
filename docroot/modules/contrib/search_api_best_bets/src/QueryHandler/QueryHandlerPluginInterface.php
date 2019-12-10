<?php

namespace Drupal\search_api_best_bets\QueryHandler;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface for best best query handler plugins.
 *
 * @ingroup plugin_api
 */
interface QueryHandlerPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Alter the search query.
   *
   * E.g. for adding elevate / exclude parameters.
   *
   * @param array $entities
   *   An array containing a list of entities matching with configured
   *   best bets matching the search query.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The Search API query object.
   */
  public function alterQuery(array $entities, \Drupal\search_api\Query\QueryInterface &$query);

  /**
   * Alter results after receiving them from the search backend.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface
   *   The Search API result object.
   */
  public function alterResults(\Drupal\search_api\Query\ResultSetInterface &$results);

}

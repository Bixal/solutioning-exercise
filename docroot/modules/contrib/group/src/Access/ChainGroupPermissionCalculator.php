<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\CoreFix\Cache\VariationCacheInterface;

/**
 * Collects group permissions for an account.
 */
class ChainGroupPermissionCalculator implements ChainGroupPermissionCalculatorInterface {

  /**
   * The calculators.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface[]
   */
  protected $calculators = [];

  /**
   * The variation cache backend to use as a persistent cache.
   *
   * @var \Drupal\group\CoreFix\Cache\VariationCacheInterface
   */
  protected $cache;

  /**
   * The variation cache backend to use as a static cache.
   *
   * @var \Drupal\group\CoreFix\Cache\VariationCacheInterface
   */
  protected $static;

  /**
   * The regular cache backend to use as a static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $regularStatic;

  /**
   * Constructs a ChainGroupPermissionCalculator object.
   *
   * @param \Drupal\group\CoreFix\Cache\VariationCacheInterface $cache
   *   The variation cache to use as a persistent cache.
   * @param \Drupal\group\CoreFix\Cache\VariationCacheInterface $static
   *   The variation cache to use as a static cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $regular_static
   *   The regular cache backend to use as a static cache.
   */
  public function __construct(VariationCacheInterface $cache, VariationCacheInterface $static, CacheBackendInterface $regular_static) {
    $this->cache = $cache;
    $this->static = $static;
    $this->regularStatic = $regular_static;
  }

  /**
   * {@inheritdoc}
   */
  public function addCalculator(GroupPermissionCalculatorInterface $calculator) {
    $this->calculators[] = $calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculators() {
    return $this->calculators;
  }

  /**
   * Performs the calculation of permissions with caching support.
   *
   * @param string[] $cache_keys
   *   The cache keys to store the calculation with.
   * @param string[] $persistent_cache_contexts
   *   The cache contexts that are always used for this calculation.
   * @param string $method
   *   The method to invoke on each calculator.
   * @param array $args
   *   The arguments to pass to the calculator method.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   The calculated group permissions, potentially served from a cache.
   */
  protected function doCacheableCalculation(array $cache_keys, array $persistent_cache_contexts, $method, array $args = []) {
    $initial_cacheability = (new CacheableMetadata())->addCacheContexts($persistent_cache_contexts);

    // Retrieve the permissions from the static cache if available.
    if ($static_cache = $this->static->get($cache_keys, $initial_cacheability)) {
      return $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cache_keys, $initial_cacheability)) {
      $calculated_permissions = $cache->data;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $calculated_permissions = new RefinableCalculatedGroupPermissions();
      foreach ($this->getCalculators() as $calculator) {
        $calculated_permissions = $calculated_permissions->merge(call_user_func_array([$calculator, $method], $args));
      }

      // Apply the persistent cache contexts.
      $calculated_permissions->addCacheContexts($persistent_cache_contexts);

      // Apply a cache tag to easily flush the calculated group permissions.
      $calculated_permissions->addCacheTags(['group_permissions']);

      // Cache the permissions as an immutable value object.
      $calculated_permissions = new CalculatedGroupPermissions($calculated_permissions);
      $this->cache->set($cache_keys, $calculated_permissions, $calculated_permissions, $initial_cacheability);
    }

    // Store the permissions in the static cache.
    $this->static->set($cache_keys, $calculated_permissions, $calculated_permissions, $initial_cacheability);

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAnonymousPermissions() {
    return $this->doCacheableCalculation(
      ['group_permissions', 'anonymous'],
      $this->getPersistentAnonymousCacheContexts(),
      __FUNCTION__
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateOutsiderPermissions(AccountInterface $account) {
    return $this->doCacheableCalculation(
      ['group_permissions', 'outsider'],
      $this->getPersistentOutsiderCacheContexts(),
      __FUNCTION__,
      [$account]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMemberPermissions(AccountInterface $account) {
    return $this->doCacheableCalculation(
      ['group_permissions', 'member'],
      $this->getPersistentMemberCacheContexts(),
      __FUNCTION__,
      [$account]
    );
  }

  /**
   * Performs the retrieval of persistent cache contexts.
   *
   * @param string $constant_name
   *   The constant to read from each calculator.
   *
   * @return string[]
   *   The combined persistent cache contexts from all calculators.
   */
  protected function getPersistentCacheContexts($constant_name) {
    $cid = 'group_permission:chain_calulator:contexts:' . $constant_name;

    // Retrieve the contexts from the regular static cache if available.
    if ($static_cache = $this->regularStatic->get($cid)) {
      $contexts = $static_cache->data;
    }
    else {
      $contexts = [];
      foreach ($this->getCalculators() as $calculator) {
        $contexts = array_merge($contexts, constant(get_class($calculator) . '::' . $constant_name));
      }

      // Store the contexts in the regular static cache.
      $this->regularStatic->set($cid, $contexts);
    }

    return $contexts;
  }

  /**
   * Gets the cache contexts that always apply to the anonymous permissions.
   *
   * @return string[]
   */
  protected function getPersistentAnonymousCacheContexts() {
    return $this->getPersistentCacheContexts('ANONYMOUS_CACHE_CONTEXTS');
  }

  /**
   * Gets the cache contexts that always apply to the outsider permissions.
   *
   * @return string[]
   */
  protected function getPersistentOutsiderCacheContexts() {
    return $this->getPersistentCacheContexts('OUTSIDER_CACHE_CONTEXTS');
  }

  /**
   * Gets the cache contexts that always apply to the member permissions.
   *
   * @return string[]
   */
  protected function getPersistentMemberCacheContexts() {
    return $this->getPersistentCacheContexts('MEMBER_CACHE_CONTEXTS');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAuthenticatedPermissions(AccountInterface $account) {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $calculated_permissions
      ->merge($this->calculateOutsiderPermissions($account))
      ->merge($this->calculateMemberPermissions($account));
    return new CalculatedGroupPermissions($calculated_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account) {
    return $account->isAnonymous()
      ? $this->calculateAnonymousPermissions()
      : $this->calculateAuthenticatedPermissions($account);
  }

}

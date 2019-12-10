<?php

/**
 * @file
 * Toolbar hook.
 */

/**
 * Implements hook_toolbar().
 */
function entity_tasks_toolbar_alter(&$items) {
  \Drupal::service('entity_tasks.toolbar')->buildToolbarConfiguration($items);
}

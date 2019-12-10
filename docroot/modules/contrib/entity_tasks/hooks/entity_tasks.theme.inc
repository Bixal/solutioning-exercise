<?php

/**
 * @file
 * Theme hook.
 */

use Drupal\Component\Serialization\Yaml;

/**
 * Implements hook_theme().
 */
function entity_tasks_theme($existing, $type, $theme, $path) {
  return Yaml::decode(file_get_contents(DRUPAL_ROOT . '/' . $path . '/' . $theme . '.themes.yml'));
}

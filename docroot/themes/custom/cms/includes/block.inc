<?php

/**
 * @file
 * Provides the necessary hooks for the block theme suggestions.
 */

use Drupal\block_content\BlockContentInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function cms_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  $content = $variables['elements']['content'];
  if (isset($content['#block_content']) and $content['#block_content'] instanceof BlockContentInterface) {
    $suggestions = [];
    $bundle = $content['#block_content']->bundle();
    $view_mode = $content['#view_mode'];
    $suggestions[] = 'block__' . $bundle;
    $suggestions[] = 'block__' . $view_mode;
    $suggestions[] = 'block__' . $bundle . '__' . $view_mode;
    if (!empty($variables['elements']['#id'])) {
      $suggestions[] = 'block__' . $variables['elements']['#id'];
    }
  }
}

/**
 * Implements theme_preprocess_block().
 */
function cms_preprocess_block(&$variables) {
  if ($variables['plugin_id'] === "language_block:language_interface") {
    $variables["content"]["#links"]["es"]["title"] = "Español";
  }
}

/**
 * Implements hook_preprocess_breadcrumb().
 */
function cms_preprocess_breadcrumb(&$variables) {
  $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  if ($language === "es") {
    $variables["breadcrumb"][0]["text"] = "Cobertura de medicamentos (Parte D)";
  }
}

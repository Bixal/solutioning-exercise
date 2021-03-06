<?php

/**
 * @file
 * Preprocess functions for taxonomy.
 */

/**
 * Implements theme_suggestions_taxonomy_term_alter()
 *
 * @param array $suggestions
 *   Alternative taxonomy terms.
 * @param array $variables
 *   Current theme variables.
 */
function cms_theme_suggestions_taxonomy_term_alter(array &$suggestions, array $variables) {
  $bundle = $variables['elements']['name']['#bundle'];
  $view_mode = $variables['elements']['#view_mode'];
  $suggestions[] = 'taxonomy_term__' . $view_mode;
  $suggestions[] = 'taxonomy_term__' . $bundle . '__' . $view_mode;
}

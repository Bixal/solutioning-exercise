<?php

namespace Drupal\quicklink;

use Drupal\TestSite\TestSetupInterface;

/**
 * Setup file used by TestSiteApplicationTest.
 *
 * @see \Drupal\Tests\Scripts\TestSiteApplicationTest
 */
class TestSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install(['quicklink']);
    $quicklink_settings = \Drupal::configFactory()->getEditable('quicklink.settings');
    $quicklink_settings->set('enable_debug_mode', TRUE);
    $quicklink_settings->save();
  }

}

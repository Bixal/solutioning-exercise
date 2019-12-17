<?php

namespace Drupal\Tests\link_analysis\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Class LinkAnalysisUnitTest.
 *
 * @package Drupal\Tests\link_analysis\Unit
 */
class LinkAnalysisUnitTest extends UnitTestCase {

  /**
   * Validate that the service loads.
   */
  public function testLinkAnalysisService() {
    $this->assertNotNull(\Drupal::service('link_analysis.store'));
  }

}

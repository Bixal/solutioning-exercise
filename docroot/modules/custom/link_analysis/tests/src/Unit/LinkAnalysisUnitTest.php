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
    $services = $this->getMockBuilder('Drupal\link_analysis')
      ->disableOriginalConstructor()
      ->getMock();
    $this->assertNotNull(\Drupal::service('link_analysis.store'));
  }

}

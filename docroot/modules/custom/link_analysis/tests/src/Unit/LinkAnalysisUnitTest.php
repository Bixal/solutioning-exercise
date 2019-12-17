<?php

namespace Drupal\Tests\link_analysis\Unit;

use Drupal\link_analysis\LinkAnalysisStoreService;
use Drupal\Tests\UnitTestCase;

/**
 * Class LinkAnalysisUnitTest.
 *
 * @package Drupal\Tests\link_analysis\Unit
 */
class LinkAnalysisUnitTest extends UnitTestCase {

  /**
   * Mock object of \Drupal\Core\Database\Driver\mysql\Connection.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $database;

  /**
   * Mock object of \Drupal\Core\Entity\EntityTypeManagerInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock object of \Drupal\Core\Render\RendererInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $renderer;

  /**
   * Mock object of \Drupal\Core\Render\RendererInterface.
   *
   * @var \Drupal\link_analysis\LinkAnalysisStoreService
   */
  private $linkAnalysisService;

  /**
   * Mock Object of \Drupal\Core\Config\ConfigManagerInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $configManager;

  /**
   * Establish a LinkAnalysisStoreService object for testing.
   */
  public function setUp() {
    parent::setUp();
    // Create a connection object.
    $this->database =
      $this->getMockBuilder('\Drupal\Core\Database\Driver\mysql\Connection')
        ->disableOriginalConstructor()
        ->getMock();
    // Create a entityTypeManagerInterface object.
    $this->entityTypeManager =
      $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
        ->disableOriginalConstructor()
        ->getMock();
    // Create a renderer object.
    $this->renderer =
      $this->getMockBuilder("\Drupal\Core\Render\RendererInterface")
        ->disableOriginalConstructor()
        ->getMock();
    // Create a config manager object.
    $this->configManager =
      $this->getMockBuilder("\Drupal\Core\Config\ConfigManagerInterface")
        ->disableOriginalConstructor()
        ->getMock();
    // Manually create the service with mocked objects.
    $this->linkAnalysisService = new LinkAnalysisStoreService(
      $this->database,
      $this->entityTypeManager,
      $this->renderer,
      $this->configManager
    );

  }

  /**
   * Validate that the service loads.
   */
  public function testLinkAnalysisService() {
    // Check to see if service is not null.
    $this->assertNotNull($this->linkAnalysisService);
  }

  /**
   * Validate the getAnchorFromHtml is getting links from html.
   */
  public function testLinkAnalysisServiceGetAnchorsFromHtml() {
    // Create a simple test case for link href.
    $testCase = "www.site.com/test-href";
    // Create html with link.
    $testHtml = '<div><a href="' . $testCase . '">test link</a></div>';
    // Call getAnchorFromHtml function from service object.
    $result = $this->linkAnalysisService->getAnchorsFromHtml($testHtml);
    // Get the link from the return results.
    $testHref = $result->item(0)->getAttribute('href');
    // Validate that testCase and testHref are equal.
    $this->assertEquals($testCase, $testHref);
  }

}

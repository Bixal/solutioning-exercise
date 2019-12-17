<?php

/**
 * @file
 * Contains \Drupal\acquia_connector\Tests\Unit\AcquiaConnectorUnitTest.
 */

namespace Drupal\Tests\acquia_connector\Unit;

use Drupal\acquia_connector\Client;
use Drupal\acquia_connector\Controller\StatusController;
use Drupal\Tests\UnitTestCase;

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);
}

/**
 * @coversDefaultClass \Drupal\acquia_connector\Client
 *
 * @group Acquia connector
 */
class AcquiaConnectorUnitTest extends UnitTestCase {

  /**
   * Test authenticators.
   */
  public function testAuthenticators() {
    $identifier = $this->randomMachineName();
    $key = $this->randomMachineName();
    $params = ['time', 'nonce', 'hash'];

    $client = new ClientTest();
    $result = $client->buildAuthenticator($key, $params);
    // Test Client::buildAuthenticator.
    $valid = is_array($result);
    $this->assertTrue($valid, 'Client::buildAuthenticator returns an array');
    if ($valid) {
      foreach ($params as $key) {
        if (!array_key_exists($key, $result)) {
          $valid = FALSE;
          break;
        }
      }
      $this->assertTrue($valid, 'Array has expected keys');
    }
    // Test Client::buildAuthenticator.
    $result = $client->buildAuthenticator($identifier, []);
    $valid = is_array($result);
    $this->assertTrue($valid, 'Client::buildAuthenticator returns an array');
    if ($valid) {
      foreach ($params as $key) {
        if (!array_key_exists($key, $result)) {
          $valid = FALSE;
          break;
        }
      }
      $this->assertTrue($valid, 'Array has expected keys');
    }
  }

  /**
   * Test Id From Subscription.
   */
  public function testIdFromSub() {
    $statusController = new StatusControllerTest();
    $uuid = $statusController->getIdFromSub(['uuid' => 'test']);
    $this->assertEquals('test', $uuid, 'UUID property identical');
    $data = ['href' => 'http://example.com/network/uuid/test/dashboard'];
    $uuid = $statusController->getIdFromSub($data);
    $this->assertEquals('test', $uuid, 'UUID extracted from href');
  }

}
/**
 * {@inheritdoc}
 */
class ClientTest extends Client {

  /**
   * Construction method.
   */
  public function __construct(){}

  /**
   * {@inheritdoc}
   */
  public function buildAuthenticator($key, array $params = []) {

    $authenticator = parent::buildAuthenticator($key, $params);

    return $authenticator;

  }

}

/**
 * Class StatusController.
 */
class StatusControllerTest extends StatusController {

  /**
   * Construction method.
   */
  public function __construct(){}

}

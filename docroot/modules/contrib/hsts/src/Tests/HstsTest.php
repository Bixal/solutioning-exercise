<?php

/**
 * @file
 * Definition of Drupal\hsts\Tests\HstsTest.
 */

namespace Drupal\hsts\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * HSTS tests.
 *
 * @group HSTS
 */
class HstsTest extends RESTTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['hsts'];

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp(){
    parent::setUp();
    $this->web_user = $this->drupalCreateUser(['administer hsts']);
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests that a HSTS header is set.
   */
  public function testHsts() {
    $this->drupalGet('admin/config/system/hsts');
    $fields = [
      'enabled' => TRUE,
      'max_age' => 63072000,
      'subdomains' => TRUE,
    ];
    $this->drupalPostForm(NULL, $fields, t('Save configuration'));
    $this->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
  }
}

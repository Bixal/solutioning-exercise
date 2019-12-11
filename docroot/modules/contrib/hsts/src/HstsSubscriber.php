<?php

/**
 * @file
 * Contains \Drupal\hsts\HstsSubscriber.
 */

namespace Drupal\hsts;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the kernel request event to add HAL media types.
 */
class HstsSubscriber implements EventSubscriberInterface {

  /**
   * A config object for the HSTS configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a FinishResponseSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('hsts.settings');
  }

  /**
   * Sets the header in all responses to include the HSTS max-age value.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$this->config->get('enabled')) {
      return;
    }
    // Add the max age header.
    $header = 'max-age=' . (int) $this->config->get('max_age');
    if ($this->config->get('subdomains')) {
      // Include subdomains
      $header .= '; includeSubDomains';
    }
    // Add the header.
    $event->getResponse()->headers->set('Strict-Transport-Security', $header);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }
}

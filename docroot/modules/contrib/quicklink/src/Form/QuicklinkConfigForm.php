<?php

namespace Drupal\quicklink\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QuicklinkConfig.
 */
class QuicklinkConfigForm extends ConfigFormBase {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a QuicklinkConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'quicklink.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quicklink_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quicklink.settings');

    // Build form elements.
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['quicklink']],
    ];

    // Ignore tab.
    $form['ignore'] = [
      '#type' => 'details',
      '#title' => $this->t('Prefetch Ignore Settings'),
      '#description' => $this->t('On this tab, specify what Quicklink should not prefetch.'),
      '#group' => 'settings',
    ];
    $form['ignore']['ignore_admin_paths'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not prefetch admin paths'),
      '#description' => $this->t('Highly recommended. Ignore administrative paths.'),
      '#default_value' => $config->get('ignore_admin_paths'),
    ];
    $form['ignore']['ignore_ajax_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not prefetch AJAX links'),
      '#description' => $this->t('Highly recommended. Ignore links that trigger AJAX behavior.'),
      '#default_value' => $config->get('ignore_ajax_links'),
    ];
    $form['ignore']['ignore_hashes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore paths with hashes (#) in them'),
      '#description' => $this->t('Recommended. Prevents multiple prefetches of the same page.'),
      '#default_value' => $config->get('ignore_hashes'),
    ];
    $form['ignore']['ignore_file_ext'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore paths with file extensions'),
      '#description' => $this->t('Recommended. This will ignore links that end with a file extension.
        It will match paths ending with a period followed by 1-5 characters. Querystrings are supported.'),
      '#default_value' => $config->get('ignore_file_ext'),
    ];
    $form['ignore']['url_patterns_to_ignore'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URL patterns to ignore (optional)'),
      '#description' => $this->t('Quicklink will not fetch data if the URL contains any of these patterns. One per line.'),
      '#default_value' => $config->get('url_patterns_to_ignore'),
      '#attributes' => [
        'style' => 'max-width: 600px;',
      ],
    ];

    // Overrides tab.
    $form['overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('Optional Overrides'),
      '#description' => $this->t('On this tab, specify various overrides.'),
      '#group' => 'settings',
    ];
    $form['overrides']['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override parent selector (optional)'),
      '#description' => $this->t('Quicklink will search this CSS selector for URLs to prefetch (ex. <code>.body-inner</code>). Defaults to the whole document.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('selector'),
      '#attributes' => [
        'style' => 'max-width: 600px;',
      ],
    ];
    $form['overrides']['allowed_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Override allowed domains (optional)'),
      '#description' => $this->t('List of domains to prefetch from. If empty, Quicklink will only prefetch links from the origin domain.
        If you configure this, be sure to input the origin domain. Add <code>true</code> here to allow <em>every</em> origin.'),
      '#default_value' => $config->get('allowed_domains'),
      '#attributes' => [
        'style' => 'max-width: 600px;',
      ],
    ];
    $form['overrides']['prefetch_only_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prefetch these paths only (overrides everything else)'),
      '#description' => $this->t('If enabled, will override other settings. <strong>Only these paths will be prefetched.</strong> Include the forward slash at the beginning of the path.'),
      '#default_value' => $config->get('prefetch_only_paths'),
      '#attributes' => [
        'style' => 'max-width: 600px;',
      ],
    ];

    // When to Prefetch tab.
    $form['when_load_library'] = [
      '#type' => 'details',
      '#title' => $this->t('When to Load Library'),
      '#description' => $this->t('On this tab, specify when the Quicklink library will be loaded.'),
      '#group' => 'settings',
    ];

    $form['when_load_library']['no_load_when_authenticated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefetch for anonymous users only'),
      '#description' => $this->t('Highly recommended. Quicklink library will not be loaded for authenticated users.'),
      '#default_value' => $config->get('no_load_when_authenticated'),
    ];

    $form['when_load_library']['no_load_when_session'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not prefetch during sessions'),
      '#description' => $this->t('Recommended. Disables loading of the Quicklink library when a PHP session has been started. Useful for modules that use sessions (e.g. Drupal Commerce shopping carts).'),
      '#default_value' => $config->get('no_load_when_session'),
    ];

    $options = [];
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }
    $form['when_load_library']['no_load_content_types'] = [
      '#title' => $this->t('Do not load library on these content types:'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('no_load_content_types'),
    ];

    // Polyfill tab.
    $form['polyfill'] = [
      '#type' => 'details',
      '#title' => $this->t('Extended Browser Support'),
      '#description' => $this->t('On this tab, include support of additional browsers via polyfill.'),
      '#group' => 'settings',
    ];
    $form['polyfill']['load_polyfill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load <em>Intersection Observer</em> polyfill'),
      '#description' => $this->t('This checkbox will enable loading of necessary polyfills from <a href="https://polyfill.io" target="_blank">polyfill.io</a>. This will enable usage of Quicklink in IE11 and older versions modern browsers.'),
      '#default_value' => $config->get('load_polyfill'),
    ];

    // Debug tab.
    $form['debug'] = [
      '#type' => 'details',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('On this tab, enable debug logging.'),
      '#group' => 'settings',
    ];
    $form['debug']['enable_debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#description' => $this->t("Log Quicklink development information to the HTML and JavaScript console."),
      '#default_value' => $config->get('enable_debug_mode'),
    ];

    if ($this->config('quicklink.settings')->get('enable_debug_mode')) {
      $this->messenger()->addWarning($this->t('Quicklink debug mode enabled. Be sure to disable this on production.'));
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('quicklink.settings')
      ->set('no_load_content_types', array_filter($form_state->getValue('no_load_content_types')))
      ->set('selector', trim($form_state->getValue('selector')))
      ->set('url_patterns_to_ignore', trim($form_state->getValue('url_patterns_to_ignore')))
      ->set('prefetch_only_paths', trim($form_state->getValue('prefetch_only_paths')))
      ->set('no_load_when_authenticated', $form_state->getValue('no_load_when_authenticated'))
      ->set('no_load_when_session', $form_state->getValue('no_load_when_session'))
      ->set('ignore_admin_paths', $form_state->getValue('ignore_admin_paths'))
      ->set('ignore_ajax_links', $form_state->getValue('ignore_ajax_links'))
      ->set('ignore_hashes', $form_state->getValue('ignore_hashes'))
      ->set('ignore_file_ext', $form_state->getValue('ignore_file_ext'))
      ->set('allowed_domains', trim($form_state->getValue('allowed_domains')))
      ->set('load_polyfill', $form_state->getValue('load_polyfill'))
      ->set('enable_debug_mode', $form_state->getValue('enable_debug_mode'))
      ->save();
  }

}

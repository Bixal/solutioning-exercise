<?php

/**
 * @file
 * Contains \Drupal\hsts\Form\HstsConfigForm.
 */

namespace Drupal\hsts\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a Hsts Config form.
 */
class HstsConfigForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a HstsConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatter $date_formatter) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return ['hsts.settings'];
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'hsts_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hsts.settings');
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable HSTS'),
      '#description' => t('Whether to start adding the HSTS header information or not.'),
      '#default_value' => $config->get('enabled'),
    ];
    $options = [0, 300, 31536000, 63072000, 94608000];
    $form['max_age'] = [
      '#type' => 'select',
      '#title' => t('Max age'),
      '#description' => t('The maximum age value for the header. See the <a href="https://tools.ietf.org/html/rfc6797">Strict Transport Security Definition</a> for more information.'),
      '#options' => array_map([$this->dateFormatter, 'formatInterval'], array_combine($options, $options)),
      '#default_value' => $config->get('max_age'),
    ];
    $form['subdomains'] = [
      '#type' => 'checkbox',
      '#title' => t('Include subdomains'),
      '#description' => t('Whether to include the subdomains as part of the HSTS implementation.'),
      '#default_value' => $config->get('subdomains'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('hsts.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('max_age', $form_state->getValue('max_age'))
      ->set('subdomains', $form_state->getValue('subdomains'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('max_age')) || $form_state->getValue('max_age') < 0) {
      $form_state->setErrorByName('max_age', t('Value is not a number or out of bounds.'));
    }
    parent::validateForm($form, $form_state);
  }
}

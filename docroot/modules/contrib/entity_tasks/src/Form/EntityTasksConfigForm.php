<?php

namespace Drupal\entity_tasks\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_tasks\Service\ToolbarService;

/**
 * Class EntityTasksConfigForm.
 *
 * @package Drupal\entity_tasks\Form
 */
class EntityTasksConfigForm extends ConfigFormBase implements ContainerInjectionInterface {
  const ENTITY_TASKS_CONFIG_NAME = 'entity_tasks.config';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::ENTITY_TASKS_CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_tasks_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::ENTITY_TASKS_CONFIG_NAME);

    $options = [
      -1 => $this->t('Disabled'),
      ToolbarService::ENTITY_TASKS_CLASSIC_DISPLAY_MODE => ucfirst(ToolbarService::ENTITY_TASKS_CLASSIC_DISPLAY_MODE),
      ToolbarService::ENTITY_TASKS_EXPANDED_DISPLAY_MODE => ucfirst(ToolbarService::ENTITY_TASKS_EXPANDED_DISPLAY_MODE),
      ToolbarService::ENTITY_TASKS_DROPDOWN_DISPLAY_MODE => ucfirst(ToolbarService::ENTITY_TASKS_DROPDOWN_DISPLAY_MODE),
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Toolbar display settings'),
      '#open' => TRUE,
    ];

    $form['display']['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('This setting is used to change the way the tasks are displayed in the toolbar. To use the block add the Entity Tasks Block to the content region.'),
      '#default_value' => $config->get('display_mode') !== NULL ? $config->get('display_mode') : ToolbarService::ENTITY_TASKS_CLASSIC_DISPLAY_MODE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $excludeArr = [
      'submit',
      'form_build_id',
      'form_id',
      'form_token',
      'op',
    ];

    $config = $this->config(self::ENTITY_TASKS_CONFIG_NAME);
    foreach ($form_state->getValues() as $key => $formValue) {
      if (!in_array($key, $excludeArr)) {
        if (!is_array($formValue)) {
          $config->set($key, $formValue);
        }
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}

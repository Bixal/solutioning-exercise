<?php

namespace Drupal\link_analysis\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LinkAnalysisSettingsForm.
 */
class LinkAnalysisSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Theme\ThemeManagerInterface definition.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->themeManager = $container->get('theme.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'link_analysis.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_analysis_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Submit element that triggers a batch process for link analysis sync.
    $form['run_sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Sync'),
      '#description' => $this->t('This will run the link analysis sync'),
      '#weight' => '0',
      '#submit' => ["::triggerSync"],
    ];

    // Get the Link Analysis settings.
    $config = $this->config('link_analysis.settings')
      ->get('link_analysis');
    // Get regions from the active front end theme.
    $regions = $this->themeManager->getActiveTheme()->getRegions();
    $options = [];
    foreach ($regions as $region) {
      $options[$region] = $region;
    }
    // Checkboxes form FAPI will be used to control what regions will be parsed.
    $form['regions_to_be_parsed'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Regions to be parsed'),
      '#description' => $this->t('When a region is checked it will be
      used to find links that are internal to the site.'),
      '#options' => $options,
      '#value' => $config['regions_to_be_parsed'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * When triggered creates a batch operation and pass all nodes to a processor.
   *
   * @param array $form
   *   The config form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The config form state.
   */
  public function triggerSync(array $form, FormStateInterface $form_state) {
    Drupal::database()->truncate('link_analysis')->execute();
    $nodes = Drupal::entityQuery('node')->execute();

    $operations = [];
    foreach ($nodes as $node) {
      $operations[] = [
        "\Drupal\link_analysis\Form\LinkAnalysisSettingsForm::batch",
        [$node],
      ];
    }

    $batch = [
      'title' => t('Processing link analysis'),
      'init_message' => t('Process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
      'operations' => $operations,
    ];

    batch_set($batch);
  }

  /**
   * Process node looking for link references.
   *
   * @param int $id
   *   The node id being processed.
   * @param array $context
   *   The batch context state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function batch($id, array $context) {
    $linkAnalysisStore = Drupal::service('link_analysis.store');
    $entity = Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($id);

    $linkAnalysisStore->process($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Pull out the user input from the form.
    $input = $form_state->getUserInput();
    $values = [];
    foreach ($input['regions_to_be_parsed'] as $region) {
      if ($region) {
        $values[$region] = $region;
      }
    }
    // Set the config with new values.
    $this->config('link_analysis.settings')
      ->set('link_analysis', ['regions_to_be_parsed' => $values])
      ->save();
  }

}

<?php

namespace Drupal\bg_img_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Robo\State\Data;

/**
 * Plugin implementation of the 'bg_img_field' field type.
 *
 * @FieldType(
 *   id = "bg_img_field",
 *   label = @Translation("Background Image Field"),
 *   description = @Translation("Field for creating responsive background
 *   images."),
 *   default_widget = "bg_img_field_widget",
 *   default_formatter = "bg_img_field_formatter",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class BgImgItem extends ImageItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();

    $settings['css_settings']['css_selector'] = '';
    $settings['css_settings']['css_repeat'] = 'inherit';
    $settings['css_settings']['css_background_size'] = 'inherit';
    $settings['css_settings']['css_background_position'] = 'inherit';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);

    // Remove title and alt from the setting form, they are not need
    // in background images.
    unset($elements['default_image']['alt']);
    unset($elements['default_image']['title']);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    // Change value of setting  set in image field.
    $settings['file_extensions'] = "png jpg jpeg svg";
    $settings['alt_field'] = 0;
    $settings['alt_field_required'] = 0;
    // Add the specific css settings.
    $settings['css_settings']['css_selector'] = '';
    $settings['css_settings']['css_repeat'] = 'inherit';
    $settings['css_settings']['css_background_size'] = 'inherit';
    $settings['css_settings']['css_background_position'] = 'inherit';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['css_selector'] = [
      'description' => t("CSS selector to target the background image placement."),
      'type' => 'text',
    ];

    $schema['columns']['css_repeat'] = [
      'description' => t("CSS property that determines the repeat attribute."),
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['css_background_size'] = [
      'description' => t("CSS property that determines the background size attribute."),
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['css_background_position'] = [
      'description' => t("CSS property that determines the background position attribute."),
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['css_selector'] = DataDefinition::create('string')
      ->setLabel(t('CSS Selector'))
      ->setDescription(t("CSS selector that will be used to place the background image. attribute."));

    $properties['css_repeat'] = DataDefinition::create('string')
      ->setLabel(t('CSS Repeat Property'))
      ->setDescription(t("CSS attribute that set the repeating value of the background image."));

    $properties['css_background_size'] = DataDefinition::create('string')
      ->setLabel(t('CSS Background Size Property'))
      ->setDescription(t("CSS attribute that set the background size value of the background image."));

    $properties['css_background_position'] = DataDefinition::create('string')
      ->setLabel(t('CSS Background Position Property'))
      ->setDescription(t("CSS attribute that set the background position value of the background image."));

    $properties['hide_css_Settings'] = DataDefinition::create('boolean')
      ->setLabel(t("Hide CSS Settings"))
      ->setDescription(t("Hides CSS settings on edit screens of the background image field type"));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $parentElements = parent::fieldSettingsForm($form, $form_state);
    // Unset fields from image field that will not be used.
    unset($parentElements['alt_field']);
    unset($parentElements['alt_field_required']);
    unset($parentElements['title_field']);
    unset($parentElements['title_field_required']);
    // Unset to clean up the UI.
    unset($parentElements['default_image']['alt']);
    unset($parentElements['default_image']['title']);

    $elements['css_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('CSS Settings'),
      '#description' => $this->t('Set default CSS properties for the background image.'),
      '#open' => FALSE,
    ];

    // Load tokens based on the entity type it is on.
    $token_types = [$this->getFieldDefinition()->getTargetEntityTypeId()];

    // Get defined settings.
    $css_option_settings = $this->getSetting('css_settings');

    // The css selector input field needed to.
    $elements['css_settings']['css_selector'] = [
      '#type'             => 'textfield',
      '#title'            => $this->t('Selector'),
      '#description'      => $this->t('CSS Selector for background image.'),
      '#default_value'    => $css_option_settings['css_selector'],
      '#token_types'      => $token_types,
    ];

    // The tokens that are scoped for the selector input.
    $elements['css_settings']['tokens'] = [
      '#theme'        => 'token_tree_link',
      '#token_types'  => $token_types,
      '#global_types' => TRUE,
      '#show_nested'  => FALSE,
    ];

    // User ability to select a background repeat option.
    $elements['css_settings']['css_repeat'] = [
      '#type' => 'radios',
      '#title' => $this->t('Repeat Options'),
      '#description' => $this->t('Add the css no repeat value to the background image.'),
      '#default_value' => $css_option_settings['css_repeat'],
      '#options' => [
        "inherit" => $this->t("inherit"),
        "no-repeat" => $this->t("no-repeat"),
        "repeat" => $this->t('repeat'),
      ],
    ];

    // User the ability to choose background size.
    $elements['css_settings']['css_background_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Size'),
      '#description' => $this->t("Add the background size setting you would like for the image, use inherit for default."),
      '#default_value' => $css_option_settings['css_background_size'],
      '#options' => [
        'inherit' => $this->t('inherit'),
        'auto' => $this->t('auto'),
        'cover' => $this->t('cover'),
        'contain' => $this->t('contain'),
        'initial' => $this->t('initial'),
      ],
    ];

    // User the ability to set the background position.
    $elements['css_settings']['css_background_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Position'),
      '#description' => $this->t('Set a background position'),
      '#default_value' => $css_option_settings['css_background_position'],
      '#options' => [
        "inherit" => $this->t("inherit"),
        "left top" => $this->t("left top"),
        "left center" => $this->t("left center"),
        "left bottom" => $this->t("left bottom"),
        "right top" => $this->t("right bottom"),
        "right center" => $this->t("right center"),
        "right bottom" => $this->t("right bottom"),
        "center top" => $this->t("center top"),
        "center center" => $this->t("center center"),
        "center bottom" => $this->t("center bottom"),
      ],
      '#tree' => TRUE,
    ];

    $elements['file_settings'] = [
      '#type' => 'details',
      '#title' => $this->t("File Settings"),
      '#open' => FALSE,
    ] + $parentElements;

    return $elements;
  }

}

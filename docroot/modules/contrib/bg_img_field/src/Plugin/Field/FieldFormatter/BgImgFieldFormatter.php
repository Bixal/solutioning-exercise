<?php

namespace Drupal\bg_img_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\bg_img_field\Component\Render\CSSSnippet;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "bg_img_field_formatter",
 *   label = @Translation("Background Image Field Widget"),
 *   field_types = {
 *     "bg_img_field"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class BgImgFieldFormatter extends ResponsiveImageFormatter implements ContainerFactoryPluginInterface {

  // @var Drupal\Core\Logger\LoggerChannelTrait
  use LoggerChannelTrait;

  /**
   * Constructor for the Background Image Formatter.
   *
   * @param string $plugin_id
   *   The plugin unique id.
   * @param string $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The stored setting for the formatter.
   * @param string $label
   *   The formatters label.
   * @param string $view_mode
   *   Which view mode the formatter is in.
   * @param array $third_party_settings
   *   Any third party setting that might change how the formatter render the
   *   css.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image styles created in the system.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image styles that have been created int eh system.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   Help generate links.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityStorageInterface $responsive_image_style_storage,
    EntityStorageInterface $image_style_storage,
    LinkGeneratorInterface $link_generator,
    AccountInterface $current_user
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $responsive_image_style_storage,
      $image_style_storage,
      $link_generator,
      $current_user
    );

    $this->logger = $this->getLogger('bg_img_field');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    $container = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    return $container;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    // Get the options for responsive image styles.
    $options = $elements['responsive_image_style']['#options'];
    // New options array for storing new option values.
    $new_options = [];
    // Loop through the options to locate only the ones that are labeled
    // image styles. This will eliminate any by size styles.
    foreach ($options as $key => $option) {
      $storage = $this->responsiveImageStyleStorage->load($key);
      $image_style_mappings = $storage->get('image_style_mappings');
      if (isset($image_style_mappings[0]) && $image_style_mappings[0]['image_mapping_type']
      === 'image_style') {
        $new_options += [$key => $option];
      }
    }
    $elements['responsive_image_style']['#options'] = $new_options;
    // Remove the image link element.
    unset($elements['image_link']);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = $this->t('Responsive image style: @responsive_image_style',
        ['@responsive_image_style' => $responsive_image_style->label()]);
    }
    else {
      $summary[] = $this->t('Select a responsive image style.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity = $items->getEntity();

    // Load the files to render.
    $files = [];
    foreach ($items->getValue() as $item) {
      $files[] = [
        'file' => File::load($item['target_id']),
        'item' => $item,
      ];
    }
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    return $this->buildElement($files, $entity);
  }

  /**
   * Build the inline css style based on a set of files and a selector.
   *
   * @param array $files
   *   An array of image files.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity the field belongs to. Used for token replacement in the
   *   selector.
   *
   * @return array
   *   Returns the built image with the prepared css in the html_head of
   *   render array
   */
  protected function buildElement(array $files,
                                  EntityInterface $entity) {
    $elements = [];
    $css = "";

    $image_link_setting = $this->getSetting('image_link');

    $cache_contexts = [];
    if ($image_link_setting == 'file') {
      $cache_contexts[] = 'url.site';
    }
    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    $image_styles_to_load = [];
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    // Get image styles.
    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    // Process the files to get the css markup.
    foreach ($files as $file) {
      $selector = $file['item']['css_selector'];
      $selector = \Drupal::token()->replace($selector, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
      $css .= $this->generateBackgroundCss(
        $file['file'],
        $responsive_image_style,
        $selector,
        $file['item']
      );

      // Attach to head on element to create style tag in the html head.
      if (!empty($css)) {
        $current_path =  \Drupal::request()->getRequestUri();
        if(preg_match('/node\/(\d+)\/layout/', $current_path, $matches)) {
          $elements = [
            '#theme' => 'background_style',
            '#css' => $css,
            '#cache' => [
              'tags' => $cache_tags,
              'contexts' => $cache_contexts,
            ],
          ];
        } else {
          // Use the selector in the id to avoid collisions with multiple
          // background formatters on the same page.
          $id = 'picture-background-formatter-' . $selector;
          $elements['#attached']['html_head'][] = [[
            '#tag' => 'style',
            '#value' => new CSSSnippet($css),
            '#cache' => [
              'tags' => $cache_tags,
              'contexts' => $cache_contexts,
            ],
          ], $id];
        }
      }
    }

    return $elements;
  }

  /**
   * CSS Generator Helper Function.
   *
   * @param object $image
   *   URI of the field image.
   * @param string $responsive_image_style
   *   Desired picture mapping to generate CSS.
   * @param string $selector
   *   CSS selector to target.
   * @param array $options
   *   CSS options.
   *
   * @return string
   *   Generated background image CSS.
   */
  protected function generateBackgroundCss($image, $responsive_image_style, $selector, array $options) {
    $css = "";

    $css .= $selector . '{';
    $css .= "background-repeat: " . $options['css_repeat'] . ";";
    $css .= "background-size: " . $options['css_background_size'] . ";";
    $css .= "background-position: " . $options['css_background_position'] . ";";
    $css .= '}';

    // $responsive_image_style holds the configuration from the responsive_image
    // module for a given responsive style
    // We need to check that this exists or else we get a WSOD.
    if (!$responsive_image_style) {
      $field_definition = $this->fieldDefinition->getFieldStorageDefinition();

      $this->logger->error('
        There is no responsive image style set for the {field_name} field on the {entity_type} entity. Please ensure
        that the responsive image style is configured at <a href="{link}">{link}</a>.  Then set the correct style on the
        formatter for the entity display.
      ', [
        'field_name' => $field_definition->get('field_name'),
        'entity_type' => $field_definition->get('entity_type'),
        'link' => Url::fromRoute('entity.responsive_image_style.collection')->toString(),
      ]);
    }
    else {
      $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($responsive_image_style->getBreakpointGroup());
      foreach (array_reverse($responsive_image_style->getKeyedImageStyleMappings()) as $breakpoint_id => $multipliers) {
        if (isset($breakpoints[$breakpoint_id])) {

          $multipliers = array_reverse($multipliers);

          $query = $breakpoints[$breakpoint_id]->getMediaQuery();
          if ($query != "") {
            $css .= ' @media ' . $query . ' {';
          }

          foreach ($multipliers as $multiplier => $mapping) {
            $multiplier = rtrim($multiplier, "x");

            if ($mapping['image_mapping_type'] != 'image_style') {
              continue;
            }

            if ($mapping['image_mapping'] == "_original image_") {
              $url = file_create_url($image->getFileUri());
            }
            else {
              $url = ImageStyle::load($mapping['image_mapping'])->buildUrl($image->getFileUri());
            }

            if ($multiplier != 1) {
              $css .= ' @media (-webkit-min-device-pixel-ratio: ' . $multiplier . '), (min-resolution: ' . $multiplier * 96 . 'dpi), (min-resolution: ' . $multiplier . 'dppx) {';
            }
            $css .= $selector . ' {background-image: url(' . $url . ');}';

            if ($multiplier != 1) {
              $css .= '}';
            }
          }

          if ($query != "") {
            $css .= '}';
          }
        }
      }
    }

    return $css;
  }

}

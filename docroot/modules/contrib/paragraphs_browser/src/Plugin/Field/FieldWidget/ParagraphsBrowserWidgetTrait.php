<?php

/**
 * @file
 * Paragraphs Previewer widget trait implementation for paragraphs.
 */

namespace Drupal\paragraphs_browser\Plugin\Field\FieldWidget;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Trait of Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * @var Trait
 * @see \Drupal\paragraphs_browser\Plugin\Field\FieldWidget\ParagraphsBrowserWidget
 * @see \Drupal\paragraphs_browser\Plugin\Field\FieldWidget\InlineParagraphsBrowserWidget
 */
trait ParagraphsBrowserWidgetTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['paragraphs_browser'] = '_na';
    $settings['modal_width']  = '80%';
    $settings['modal_height'] = 'auto';


    return $settings;
  }
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['add_mode'] = array(
      '#type' => 'hidden',
      '#value' => 'paragraphs_browser'
    );

    $paragraph_browsers = entity_load_multiple('paragraphs_browser_type');
    $options = array('_na' => 'No Groups');
    foreach($paragraph_browsers as $type) {
      $options[$type->id] = $type->label;
    }
    $elements['paragraphs_browser'] = array(
      '#type' => 'select',
      '#title' => $this->t('Paragraphs Browser'),
      '#description' => $this->t('Select which browser to use.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('paragraphs_browser'),
      '#required' => TRUE,
    );

    $elements['modal_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Paragraphs Browser Modal Width'),
      '#description' => 'The width of the modal in px or percentage.',
      '#default_value' => $this->getSetting('modal_width'),
      '#required' => FALSE,
    );

    $elements['modal_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Paragraphs Browser Modal Height'),
      '#description' => 'The height of the modal in px or "auto".',
      '#default_value' => $this->getSetting('modal_height'),
      '#required' => FALSE,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Call parent function
    $element = parent::formElement($items, $delta, $element,$form,  $form_state);

    // Add styles
    $form['#attached']['library'][] = 'paragraphs_browser/modal';

    $type = $element['#paragraph_type'];
    $current_title = $element['top']['paragraph_type_title']['info']['#markup'];

    // Get Paragraph group info
    $paragraph_types = \Drupal::entityTypeManager()->getStorage('paragraphs_browser_type')->loadMultiple();
    foreach($paragraph_types as $paragraph_type) {
      $groups = $paragraph_type->groupManager();
      $group_name = $paragraph_type->getGroupMap($type);
      $group = $groups->getGroup($group_name);
      if ($group) {
        $element['top']['paragraph_type_title']['info'] = array(
          '#markup' => '<span class="paragraph-group">' . $group->getLabel() . '</span> ' . $current_title,
        );
      }
    }

    return $element;
  }

  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    // If the Paragraph browser hasn't been set, return un-modified form.
    if ($this->getSetting('paragraphs_browser') == '_na') {
      return $elements;
    }

//    $elements['add_more'] = array(
//      '#type' => 'container',
//      '#theme_wrappers' => array('paragraphs_dropbutton_wrapper'),
//    );
    if (empty($this->uuid)) {
      $this->uuid = \Drupal::service('uuid')->generate();
    }
    $elements['add_more']['add_more_select']['#attributes']['data-uuid'] = $this->uuid;
    $elements['add_more']['add_more_select']['#attributes']['class'][] = 'js-hide';
    $elements['add_more']['add_more_select']['#title_display'] = 'hidden';
    $elements['add_more']['add_more_button']['#attributes']['data-uuid'] = $this->uuid;
    $elements['add_more']['add_more_button']['#attributes']['class'][] = 'js-hide';
    unset($elements['add_more']['add_more_button']['#suffix']);
    unset($elements['add_more']['add_more_button']['#prefix']);

    $elements['#attached']['library'][] = 'paragraphs_browser/modal';
    
    $storage = $form_state->getStorage();

    if (isset($storage['content_translation'])) {
      unset($elements['add_more']);
    }
    else {
      $elements['add_more']['browse'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Add :title', [':title' => $this->getSetting('title')]),
        '#attributes' => ['class' => ['js-show']],
        '#ajax' => [
          'url' => Url::fromRoute(
            'paragraphs_browser.paragraphs_browser_controller', [
              'field_config' => implode('.', array($items->getEntity()->getEntityTypeId(), $items->getEntity()->bundle(), $this->fieldDefinition->getName())),
              'paragraphs_browser_type' => $this->getSetting('paragraphs_browser'),
              'uuid' => $this->uuid,
            ]
          ),
        ],
      );
    }
    
    if (isset($elements['#cardinality']) && $elements['#cardinality'] != -1) {
      $keyCount = count(
        array_filter(
          array_keys($elements),
          'is_numeric'
        )
      );
      if ($elements['#cardinality'] <= $keyCount) {
        unset($elements['add_more']);
      }
    }
    
    return $elements;
  }

}

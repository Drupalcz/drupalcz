<?php

/**
 * @file
 * Definition of Drupal\jssor\Plugin\views\row\Jssor.
 */

namespace Drupal\jssor\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;


/**
 * Row handler plugin for displaying search results.
 *
 * @ViewsRow(
 *   id = "jssor_row",
 *   theme = "views_view_jssor_row",
 *   title = @Translation("Jssor"),
 *   help = @Translation("Provides a row plugin to display image slider with caption."),
 *   display_types = {"normal"}
 * )
 */
class Jssor extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['image_options'] = array('default' => array());
    $options['caption_options'] = array('default' => array());
    $options['play_in_transition'] = array('default' => '');
    $options['play_in_mode'] = array('default' => 1);
    $options['play_out_transition'] = array('default' => '');
    $options['play_out_mode'] = array('default' => 1);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get all image and text fields.
    $image_options = [];
    $caption_options = [];
    $transitions_options = [
      'Move' => [
        'L' => 'L',
        'R' => 'R',
        'T' => 'T',
        'B' => 'B',
        'TL' => 'TL',
        'TR' => 'TR',
        'BL' => 'BL',
        'BR' => 'BR',
      ],
      'Move|IB' => [
        'L|IB' => 'L|IB',
        'R|IB' => 'R|IB',
        'T|IB' => 'T|IB',
        'B|IB' => 'B|IB',
        'TL|IB' => 'TL|IB',
        'TR|IB' => 'TR|IB',
        'BL|IB' => 'BL|IB',
        'BR|IB' => 'BR|IB',
      ],
      'Move|IE' => [
        'L|IE' => 'L|IE',
        'R|IE' => 'R|IE',
        'T|IE' => 'T|IE',
        'B|IE' => 'B|IE',
        'TL|IE' => 'TL|IE',
        'TR|IE' => 'TR|IE',
        'BL|IE' => 'BL|IE',
        'BR|IE' => 'BR|IE',
      ],
      'Move|EP' => [
        'L|EP' => 'L|EP',
        'R|EP' => 'R|EP',
        'T|EP' => 'T|EP',
        'B|EP' => 'B|EP',
        'TL|EP' => 'TL|EP',
        'TR|EP' => 'TR|EP',
        'BL|EP' => 'BL|EP',
        'BR|EP' => 'BR|EP',
      ],
      'Move with Rotate' => [
        'L*' => 'L*',
        'R*' => 'R*',
        'T*' => 'T*',
        'B*' => 'B*',
        'TL*' => 'TL*',
        'TR*' => 'TR*',
        'BL*' => 'BL*',
        'BR*' => 'BR*',
      ],
      'Move with Rotate IE' => [
        'L*IE' => 'L*IE',
        'R*IE' => 'R*IE',
        'T*IE' => 'T*IE',
        'B*IE' => 'B*IE',
        'TL*IE' => 'TL*IE',
        'TR*IE' => 'TR*IE',
        'BL*IE' => 'BL*IE',
        'BR*IE' => 'BR*IE',
      ],
      'Move with Rotate IB' => [
        'L*IB' => 'L*IB',
        'R*IB' => 'R*IB',
        'T*IB' => 'T*IB',
        'B*IB' => 'B*IB',
        'TL*IB' => 'TL*IB',
        'TR*IB' => 'TR*IB',
        'BL*IB' => 'BL*IB',
        'BR*IB' => 'BR*IB',
      ],
      'Move with Rotate IW' => [
        'L*IW' => 'L*IW',
        'R*IW' => 'R*IW',
        'T*IW' => 'T*IW',
        'B*IW' => 'B*IW',
        'TL*IW' => 'TL*IW',
        'TR*IW' => 'TR*IW',
        'BL*IW' => 'BL*IW',
        'BR*IW' => 'BR*IW',
      ],
      'Move IE with Rotate IE' => [
        'L*IE*IE' => 'L*IE*IE',
        'R*IE*IE' => 'R*IE*IE',
        'T*IE*IE' => 'T*IE*IE',
        'B*IE*IE' => 'B*IE*IE',
        'TL*IE*IE' => 'TL*IE*IE',
        'TR*IE*IE' => 'TR*IE*IE',
        'BL*IE*IE' => 'BL*IE*IE',
        'BR*IE*IE' => 'BR*IE*IE',
      ],
      'Clip' => [
        'CLIP' => 'CLIP',
        'CLIP|LR' => 'CLIP|LR',
        'CLIP|TB' => 'CLIP|TB',
        'CLIP|L' => 'CLIP|L',
        'CLIP|R' => 'CLIP|R',
        'CLIP|T' => 'CLIP|T',
        'CLIP|B' => 'CLIP|B',
      ],
      'MClip' => [
        'MCLIP|L' => 'MCLIP|L',
        'MCLIP|R' => 'MCLIP|R',
        'MCLIP|T' => 'MCLIP|T',
        'MCLIP|B' => 'MCLIP|B',
      ],
      'Zoom' => [
        'ZM' => 'ZM',
        'ZM|P30' => 'ZM|P30',
        'ZM|P50' => 'ZM|P50',
        'ZM|P70' => 'ZM|P70',
        'ZM|P80' => 'ZM|P80',
        'ZMF|2' => 'ZMF|2',
        'ZMF|3' => 'ZMF|3',
        'ZMF|4' => 'ZMF|4',
        'ZMF|5' => 'ZMF|5',
        'ZMF|10' => 'ZMF|10',
        'ZML|L' => 'ZML|L',
        'ZML|R' => 'ZML|R',
        'ZML|T' => 'ZML|T',
        'ZML|B' => 'ZML|B',
        'ZML|TL' => 'ZML|TL',
        'ZML|TR' => 'ZML|TR',
        'ZML|BL' => 'ZML|BL',
        'ZML|BR' => 'ZML|BR',
        'ZML|IE|L' => 'ZML|IE|L',
        'ZML|IE|R' => 'ZML|IE|R',
        'ZML|IE|T' => 'ZML|IE|T',
        'ZML|IE|B' => 'ZML|IE|B',
        'ZML|IE|TL' => 'ZML|IE|TL',
        'ZML|IE|TR' => 'ZML|IE|TR',
        'ZML|IE|BL' => 'ZML|IE|BL',
        'ZML|IE|BR' => 'ZML|IE|BR',
        'ZMS|L' => 'ZMS|L',
        'ZMS|R' => 'ZMS|R',
        'ZMS|T' => 'ZMS|T',
        'ZMS|B' => 'ZMS|B',
        'ZMS|TL' => 'ZMS|TL',
        'ZMS|TR' => 'ZMS|TR',
        'ZMS|BL' => 'ZMS|BL',
        'ZMS|BR' => 'ZMS|BR',
      ],
    ];

    if ($fields = $this->view->display_handler->getOption('fields')) {
      foreach ($fields as $id => $field) {
        switch ($field['type']) {
          case 'image':
            $image_options[$id] = $field['id'];
            break;
          case 'string':
            $caption_options[$id] = $field['id'];
            break;
        }
      }
    }

    $form['image_options'] = array(
      '#type' => 'select',
      '#title' => $this->t('Image field'),
      '#description' => $this->t('Select image field.'),
      '#options' => $image_options,
      '#default_value' => $this->options['image_options'],
    );

    $form['caption_options'] = array(
      '#type' => 'select',
      '#title' => $this->t('Caption field'),
      '#description' => $this->t('Select caption field.'),
      '#options' => $caption_options,
      '#default_value' => $this->options['caption_options'],
    );

    $form['play_in_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Play in mode'),
      '#description' => $this->t('Specifies how captions will play in.'),
      '#options' => [
          0 => $this->t('No play'),
          1 => $this->t('Goes after main slide played in, play captions in one by one'),
          3 => $this->t('Goes after main slide played in, play all captions in parallelly'),
      ],
      '#default_value' => $this->options['play_in_mode'],
    ];

    $form['play_in_transition'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#description' => $this->t('Transition.'),
      '#options' => $transitions_options,
      '#default_value' => $this->options['play_in_transition'],
    ];

    $form['play_out_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Play in mode'),
      '#description' => $this->t('Specifies how captions will play in.'),
      '#options' => [
        0 => $this->t('No play'),
        1 => $this->t('Goes after main slide played in, play captions in one by one'),
        3 => $this->t('Goes after main slide played in, play all captions in parallelly'),
      ],
      '#default_value' => $this->options['play_out_mode'],
    ];

    $form['play_out_transition'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#description' => $this->t('Transition.'),
      '#options' => $transitions_options,
      '#default_value' => $this->options['play_out_transition'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }
    else {
      $row_index++;
    }

    // Create the RSS item object.
    $item = new \stdClass();
    $item->image = $this->getField($row_index, $this->options['image_options']);
    $item->caption = $this->getField($row_index, $this->options['caption_options']);

    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    );

    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string|null|\Drupal\Component\Render\MarkupInterface
   *   An empty string if there is no style plugin, or the field ID is empty.
   *   NULL if the field value is empty. If neither of these conditions apply,
   *   a MarkupInterface object containing the rendered field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return $this->view->style_plugin->getField($index, $field_id);
  }

}

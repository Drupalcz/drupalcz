<?php

/**
 * @file
 * Definition of Drupal\jssor\Plugin\views\style\Jssor.
 */

namespace Drupal\jssor\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jssor",
 *   title = @Translation("Jssor Slider"),
 *   help = @Translation("Display rows or entity in a Jssor Slider."),
 *   theme = "views_view_jssor",
 *   display_types = {"normal"}
 * )
 */
class Jssor extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Denotes whether the plugin has an additional options form.
   *
   * @var bool
   */
  protected $usesOptions = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Does the style plugin support grouping.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['slide_duration'] = array('default' => 500);
    $options['slide_spacing'] = array('default' => 0);
    $options['drag_orientation'] = array('default' => 1);
    $options['key_navigation'] = array('default' => TRUE);
    $options['autoplay'] = array('default' => TRUE);
    $options['autoplayinterval'] = array('default' => 3000);
    $options['autoplaysteps'] = array('default' => 1);
    $options['pauseonhover'] = array('default' => 1);
    $options['arrownavigator'] = array('default' => FALSE);
    $options['bulletnavigator'] = array('default' => FALSE);
    $options['chancetoshow'] = array('default' => 0);
    $options['arrowskin'] = array('default' => 1);
    $options['bulletskin'] = array('default' => 1);
    $options['autocenter'] = array('default' => 2);
    $options['spacingx'] = array('default' => 0);
    $options['spacingy'] = array('default' => 0);
    $options['orientation'] = array('default' => 1);
    $options['steps'] = array('default' => 1);
    $options['rows'] = array('default' => 1);
    $options['lanes'] = array('default' => 1);
    $options['transition'] = array('default' => 'transition0000');
    $options['action_mode'] = array('default' => 1);
    $options['scale'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['global'] = array(
      '#type' => 'fieldset',
      '#title' => 'Global',
    );

    $form['global']['autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => (isset($this->options['global']['autoplay'])) ?
        $this->options['global']['autoplay'] : $this->options['autoplay'],
      '#description' => t('Enable to auto play.'),
    );

    $form['global']['arrownavigator'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Arrow navigator'),
      '#default_value' => (isset($this->options['global']['arrownavigator'])) ?
        $this->options['global']['arrownavigator'] : $this->options['arrownavigator'],
      '#description' => t('Enable arrow navigator.'),
    );

    $form['global']['bulletnavigator'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Bullet navigator'),
      '#default_value' => (isset($this->options['global']['bulletnavigator'])) ?
        $this->options['global']['bulletnavigator'] : $this->options['bulletnavigator'],
      '#description' => t('Enable bullet navigator.'),
    );

    // Slider.
    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
    );
    $form['general']['slide_duration'] = array(
      '#type' => 'number',
      '#title' => $this->t('Slide duration'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['general']['slide_duration'])) ?
          $this->options['general']['slide_duration'] : $this->options['slide_duration'],
      ),
      '#description' => t('Specifies default duration (swipe) for slide in milliseconds.'),
    );
    /*$form['general']['slide_spacing'] = array(
      '#type' => 'number',
      '#title' => $this->t('Slide spacing'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['general']['slide_spacing'])) ?
          $this->options['general']['slide_spacing'] : $this->options['slide_spacing'],
      ),
      '#description' => t('Space between each slide in pixels.'),
    );*/
    $form['general']['drag_orientation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Drag orientation'),
      '#description' => t('Orientation to drag slide.'),
      '#default_value' => (isset($this->options['general']['drag_orientation'])) ?
        $this->options['general']['drag_orientation'] : $this->options['drag_orientation'],
      '#options' => array(
        0 => $this->t('No drag'),
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
        3 => $this->t('Horizontal and vertical'),
      ),
    );
    $form['general']['key_navigation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Key navigation'),
      '#default_value' => (isset($this->options['general']['key_navigation'])) ?
        $this->options['general']['key_navigation'] : $this->options['key_navigation'],
      '#description'   => t('Allows keyboard (arrow key) navigation or not.'),
    );


    // Autoplay.
    $form['autoplay'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Autoplay'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][autoplay]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['autoplay']['autoplayinterval'] = array(
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['autoplay']['autoplayinterval'])) ?
          $this->options['autoplay']['autoplayinterval'] : $this->options['autoplayinterval'],
      ),
      '#description' => t('Interval (in milliseconds) to go for next slide since the previous stopped.'),
    );
    $form['autoplay']['autoplaysteps'] = array(
      '#type' => 'number',
      '#title' => $this->t('Autoplay step'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['autoplay']['autoplaysteps'])) ?
          $this->options['autoplay']['autoplaysteps'] : $this->options['autoplaysteps'],
      ),
      '#description' => t('Steps to go for each navigation request.'),
    );
    $form['autoplay']['pauseonhover'] = array(
      '#type' => 'select',
      '#title' => $this->t('Pause on hover'),
      '#description' => t('Whether to pause when mouse over if a slider is auto playing.'),
      '#default_value' => (isset($this->options['autoplay']['pauseonhover'])) ?
        $this->options['autoplay']['pauseonhover'] : $this->options['pauseonhover'],
      '#options' => array(
        0 => $this->t('No pause'),
        1 => $this->t('Pause for desktop'),
        2 => $this->t('Pause for touch device'),
        3 => $this->t('Pause for desktop and touch device'),
        4 => $this->t('Freeze for desktop'),
        8 => $this->t('Freeze for touch device'),
        12 => $this->t('Freeze for desktop and touch device'),
      ),
    );

    $form['autoplay']['transition'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#description' => t('Whether to pause when mouse over if a slider is auto playing.'),
      '#default_value' => (isset($this->options['autoplay']['transition'])) ?
        $this->options['autoplay']['transition'] : $this->options['transition'],
      '#options' => [
        'Twins Effects' => [
          'transition0000' => $this->t('Basic'),
          'transition0001' => $this->t('Fade Twins'),
          'transition0002' => $this->t('Rotate Overlap'),
          'transition0003' => $this->t('Switch'),
          'transition0004' => $this->t('Rotate Relay'),
          'transition0005' => $this->t('Doors'),
          'transition0006' => $this->t('Rotate in+ out-'),
          'transition0007' => $this->t('Fly Twins'),
          'transition0008' => $this->t('Rotate in- out+'),
          'transition0009' => $this->t('Rotate Axis up overlap'),
          'transition0010' => $this->t('Chess Replace TB'),
          'transition0011' => $this->t('Chess Replace LR'),
          'transition0012' => $this->t('Shift TB'),
          'transition0013' => $this->t('Shift LR'),
          'transition0014' => $this->t('Return TB'),
          'transition0015' => $this->t('Return LR'),
          'transition0016' => $this->t('Rotate Axis down'),
          'transition0017' => $this->t('Extrude Replace'),
        ],
        'Fade Effects' => [
          'transition0101' => $this->t('Fade'),
          'transition0102' => $this->t('Fade in L'),
          'transition0103' => $this->t('Fade in R'),
          'transition0104' => $this->t('Fade in T'),
          'transition0105' => $this->t('Fade in B'),
          'transition0106' => $this->t('Fade in LR'),
          'transition0107' => $this->t('Fade in LR Chess'),
          'transition0108' => $this->t('Fade in TB'),
          'transition0109' => $this->t('Fade in TB Chess'),
          'transition0110' => $this->t('Fade in Corners'),
          'transition0111' => $this->t('Fade out L'),
          'transition0112' => $this->t('Fade out R'),
          'transition0113' => $this->t('Fade out T'),
          'transition0114' => $this->t('Fade out B'),
          'transition0115' => $this->t('Fade out LR'),
          'transition0116' => $this->t('Fade out LR Chess'),
          'transition0117' => $this->t('Fade out TB'),
          'transition0118' => $this->t('Fade out TB Chess'),
          'transition0119' => $this->t('Fade out Corners'),
          'transition0120' => $this->t('Fade Fly in L'),
          'transition0121' => $this->t('Fade Fly in R'),
          'transition0122' => $this->t('Fade Fly in T'),
          'transition0123' => $this->t('Fade Fly in B'),
          'transition0124' => $this->t('Fade Fly in LR'),
          'transition0125' => $this->t('Fade Fly in LR Chess'),
          'transition0126' => $this->t('Fade Fly in TB'),
          'transition0127' => $this->t('Fade Fly in TB Chess'),
          'transition0128' => $this->t('Fade Fly in Corners'),
          'transition0129' => $this->t('Fade Fly out L'),
          'transition0130' => $this->t('Fade Fly out R'),
          'transition0131' => $this->t('Fade Fly out T'),
          'transition0132' => $this->t('Fade Fly out B'),
          'transition0133' => $this->t('Fade Fly out LR'),
          'transition0134' => $this->t('Fade Fly out LR Chess'),
          'transition0135' => $this->t('Fade Fly out TB'),
          'transition0136' => $this->t('Fade Fly out TB Chess'),
          'transition0137' => $this->t('Fade Fly out Corners'),
          'transition0138' => $this->t('Fade Clip in H'),
          'transition0139' => $this->t('Fade Clip in V'),
          'transition0140' => $this->t('Fade Clip out H'),
          'transition0141' => $this->t('Fade Clip out V'),
          'transition0142' => $this->t('Fade Stairs'),
          'transition0143' => $this->t('Fade Random'),
          'transition0144' => $this->t('Fade Swirl'),
          'transition0145' => $this->t('Fade ZigZag'),
        ],
        'Swing Outside Effects' => [
          'transition0201' => $this->t('Swing Outside in Stairs'),
          'transition0202' => $this->t('Swing Outside in ZigZag'),
          'transition0203' => $this->t('Swing Outside in Swirl'),
          'transition0204' => $this->t('Swing Outside in Random'),
          'transition0205' => $this->t('Swing Outside in Random Chess'),
          'transition0206' => $this->t('Swing Outside in Square'),
          'transition0207' => $this->t('Swing Outside out Stairs'),
          'transition0208' => $this->t('Swing Outside out ZigZag'),
          'transition0209' => $this->t('Swing Outside out Swirl'),
          'transition0210' => $this->t('Swing Outside out Random'),
          'transition0211' => $this->t('Swing Outside out Random Chess'),
          'transition0212' => $this->t('Swing Outside out Square'),
        ],
        'Swing Inside Effects' => [
          'transition0301' => $this->t('Swing Inside in Stairs'),
          'transition0302' => $this->t('Swing Inside in ZigZag'),
          'transition0303' => $this->t('Swing Inside in Swirl'),
          'transition0304' => $this->t('Swing Inside in Random'),
          'transition0305' => $this->t('Swing Inside in Random Chess'),
          'transition0306' => $this->t('Swing Inside in Square'),
          'transition0307' => $this->t('Swing Inside out ZigZag'),
          'transition0308' => $this->t('Swing Inside out Swirl'),
        ],
        'Dodge Dance Outside Effects' => [
          'transition0401' => $this->t('Dodge Dance Outside in Stairs'),
          'transition0402' => $this->t('Dodge Dance Outside in Swirl'),
          'transition0403' => $this->t('Dodge Dance Outside in ZigZag'),
          'transition0404' => $this->t('Dodge Dance Outside in Random'),
          'transition0405' => $this->t('Dodge Dance Outside in Random Chess'),
          'transition0406' => $this->t('Dodge Dance Outside in Square'),
          'transition0407' => $this->t('Dodge Dance Outside out Stairs'),
          'transition0408' => $this->t('Dodge Dance Outside out Swirl'),
          'transition0409' => $this->t('Dodge Dance Outside out ZigZag'),
          'transition0410' => $this->t('Dodge Dance Outside out Random'),
          'transition0411' => $this->t('Dodge Dance Outside out Random Chess'),
          'transition0412' => $this->t('Dodge Dance Outside out Square'),
        ],
        'Dodge Dance Inside Effects' => [
          'transition0501' => $this->t('Dodge Dance Inside in Stairs'),
          'transition0502' => $this->t('Dodge Dance Inside in Swirl'),
          'transition0503' => $this->t('Dodge Dance Inside in ZigZag'),
          'transition0504' => $this->t('Dodge Dance Inside in Random'),
          'transition0505' => $this->t('Dodge Dance Inside in Random Chess'),
          'transition0506' => $this->t('Dodge Dance Inside in Square'),
          'transition0507' => $this->t('Dodge Dance Inside out Stairs'),
          'transition0508' => $this->t('Dodge Dance Inside out Swirl'),
          'transition0509' => $this->t('Dodge Dance Inside out ZigZag'),
          'transition0510' => $this->t('Dodge Dance Inside out Random'),
          'transition0511' => $this->t('Dodge Dance Inside out Random Chess'),
          'transition0512' => $this->t('Dodge Dance Inside out Square'),
        ],
        'Dodge Pet Outside Effects' => [
          'transition0601' => $this->t('Dodge Pet Outside in Stairs'),
          'transition0602' => $this->t('Dodge Pet Outside in Swirl'),
          'transition0603' => $this->t('Dodge Pet Outside in ZigZag'),
          'transition0604' => $this->t('Dodge Pet Outside in Random'),
          'transition0605' => $this->t('Dodge Pet Outside in Random Chess'),
          'transition0606' => $this->t('Dodge Pet Outside in Square'),
          'transition0607' => $this->t('Dodge Pet Outside out Stairs'),
          'transition0608' => $this->t('Dodge Pet Outside out Swirl'),
          'transition0609' => $this->t('Dodge Pet Outside out ZigZag'),
          'transition0610' => $this->t('Dodge Pet Outside out Random'),
          'transition0611' => $this->t('Dodge Pet Outside out Random Chess'),
          'transition0612' => $this->t('Dodge Pet Outside out Square'),
        ],
        'Dodge Pet Inside Effects' => [
          'transition0701' => $this->t('Dodge Pet Inside in Stairs'),
          'transition0702' => $this->t('Dodge Pet Inside in Swirl'),
          'transition0703' => $this->t('Dodge Pet Inside in ZigZag'),
          'transition0704' => $this->t('Dodge Pet Inside in Random'),
          'transition0705' => $this->t('Dodge Pet Inside in Random Chess'),
          'transition0706' => $this->t('Dodge Pet Inside in Square'),
          'transition0707' => $this->t('Dodge Pet Inside out Stairs'),
          'transition0708' => $this->t('Dodge Pet Inside out Swirl'),
          'transition0709' => $this->t('Dodge Pet Inside out ZigZag'),
          'transition0710' => $this->t('Dodge Pet Inside out Random'),
          'transition0711' => $this->t('Dodge Pet Inside out Random Chess'),
          'transition0712' => $this->t('Dodge Pet Inside out Square'),
        ],
        'Dodge Outside Effects' => [
          'transition0801' => $this->t('Dodge Outside out Stairs'),
          'transition0802' => $this->t('Dodge Outside out Swirl'),
          'transition0803' => $this->t('Dodge Outside out ZigZag'),
          'transition0804' => $this->t('Dodge Outside out Random'),
          'transition0805' => $this->t('Dodge Outside out Random Chess'),
          'transition0806' => $this->t('Dodge Outside out Square'),
          'transition0807' => $this->t('Dodge Outside in Stairs'),
          'transition0808' => $this->t('Dodge Outside in Swirl'),
          'transition0809' => $this->t('Dodge Outside in ZigZag'),
          'transition0810' => $this->t('Dodge Outside in Random'),
          'transition0811' => $this->t('Dodge Outside in Random Chess'),
          'transition0812' => $this->t('Dodge Outside in Square'),
        ],
        $this->t('Dodge Inside Effects') => [
          'transition0901' => $this->t('Dodge Inside out Stairs'),
          'transition0902' => $this->t('Dodge Inside out Swirl'),
          'transition0903' => $this->t('Dodge Inside out ZigZag'),
          'transition0904' => $this->t('Dodge Inside out Random'),
          'transition0905' => $this->t('Dodge Inside out Random Chess'),
          'transition0906' => $this->t('Dodge Inside out Square'),
          'transition0907' => $this->t('Dodge Inside in Stairs'),
          'transition0908' => $this->t('Dodge Inside in Swirl'),
          'transition0909' => $this->t('Dodge Inside in ZigZag'),
          'transition0910' => $this->t('Dodge Inside in Random'),
          'transition0911' => $this->t('Dodge Inside in Random Chess'),
          'transition0912' => $this->t('Dodge Inside in Square'),
          'transition0913' => $this->t('Dodge Inside in TL'),
          'transition0914' => $this->t('Dodge Inside in TR'),
          'transition0915' => $this->t('Dodge Inside in BL'),
          'transition0916' => $this->t('Dodge Inside in BR'),
          'transition0917' => $this->t('Dodge Inside out TL'),
          'transition0918' => $this->t('Dodge Inside out TR'),
          'transition0919' => $this->t('Dodge Inside out BL'),
          'transition0920' => $this->t('Dodge Inside out BR'),
        ],
        'Flutter Outside Effects' => [
          'transition1001' => $this->t('Flutter Outside in'),
          'transition1002' => $this->t('Flutter Outside in Wind'),
          'transition1003' => $this->t('Flutter Outside in Swirl'),
          'transition1004' => $this->t('Flutter Outside in Column'),
          'transition1005' => $this->t('Flutter Outside out'),
          'transition1006' => $this->t('Flutter Outside out Wind'),
          'transition1007' => $this->t('Flutter Outside out Swirl'),
          'transition1008' => $this->t('Flutter Outside out Column'),
        ],
        'Flutter Inside Effects' => [
          'transition1101' => $this->t('Flutter Inside in'),
          'transition1102' => $this->t('Flutter Inside in Wind'),
          'transition1103' => $this->t('Flutter Inside in Swirl'),
          'transition1104' => $this->t('Flutter Inside in Column'),
          'transition1105' => $this->t('Flutter Inside out'),
          'transition1106' => $this->t('Flutter Inside out Wind'),
          'transition1107' => $this->t('Flutter Inside out Swirl'),
          'transition1108' => $this->t('Flutter Inside out Column'),
        ],
        'Rotate Effects' => [
          'transition1201' => $this->t('Rotate VDouble+ in'),
          'transition1202' => $this->t('Rotate HDouble+ in'),
          'transition1203' => $this->t('Rotate VDouble- in'),
          'transition1204' => $this->t('Rotate HDouble- in'),
          'transition1205' => $this->t('Rotate VDouble+ out'),
          'transition1206' => $this->t('Rotate HDouble+ out'),
          'transition1207' => $this->t('Rotate VDouble- out'),
          'transition1208' => $this->t('Rotate HDouble- out'),
          'transition1209' => $this->t('Rotate VFork+ in'),
          'transition1210' => $this->t('Rotate HFork+ in'),
          'transition1211' => $this->t('Rotate VFork+ out'),
          'transition1212' => $this->t('Rotate HFork+ out'),
          'transition1213' => $this->t('Rotate Zoom+ in'),
          'transition1214' => $this->t('Rotate Zoom+ in L'),
          'transition1215' => $this->t('Rotate Zoom+ in R'),
          'transition1216' => $this->t('Rotate Zoom+ in T'),
          'transition1217' => $this->t('Rotate Zoom+ in B'),
          'transition1218' => $this->t('Rotate Zoom+ in TL'),
          'transition1219' => $this->t('Rotate Zoom+ in TR'),
          'transition1220' => $this->t('Rotate Zoom+ in BL'),
          'transition1221' => $this->t('Rotate Zoom+ in BR'),
          'transition1222' => $this->t('Rotate Zoom+ out'),
          'transition1223' => $this->t('Rotate Zoom+ out L'),
          'transition1224' => $this->t('Rotate Zoom+ out R'),
          'transition1225' => $this->t('Rotate Zoom+ out T'),
          'transition1226' => $this->t('Rotate Zoom+ out B'),
          'transition1227' => $this->t('Rotate Zoom+ out TL'),
          'transition1228' => $this->t('Rotate Zoom+ out TR'),
          'transition1229' => $this->t('Rotate Zoom+ out BL'),
          'transition1230' => $this->t('Rotate Zoom+ out BR'),
          'transition1231' => $this->t('Rotate Zoom+ in'),
          'transition1232' => $this->t('Rotate Zoom+ in L'),
          'transition1233' => $this->t('Rotate Zoom+ in R'),
          'transition1234' => $this->t('Rotate Zoom+ in T'),
          'transition1235' => $this->t('Rotate Zoom+ in B'),
          'transition1236' => $this->t('Rotate Zoom+ in TL'),
          'transition1237' => $this->t('Rotate Zoom+ in TR'),
          'transition1238' => $this->t('Rotate Zoom+ in BL'),
          'transition1239' => $this->t('Rotate Zoom+ in BR'),
          'transition1240' => $this->t('Rotate Zoom- out'),
          'transition1241' => $this->t('Rotate Zoom- out L'),
          'transition1242' => $this->t('Rotate Zoom- out R'),
          'transition1243' => $this->t('Rotate Zoom- out T'),
          'transition1244' => $this->t('Rotate Zoom- out B'),
          'transition1245' => $this->t('Rotate Zoom- out TL'),
          'transition1246' => $this->t('Rotate Zoom- out TR'),
          'transition1247' => $this->t('Rotate Zoom- out BL'),
          'transition1248' => $this->t('Rotate Zoom- out BR'),
        ],
        'Zoom Effects' => [
          'transition1301' => $this->t('Zoom VDouble+ in'),
          'transition1302' => $this->t('Zoom HDouble+ in'),
          'transition1303' => $this->t('Zoom VDouble- in'),
          'transition1304' => $this->t('Zoom HDouble- in'),
          'transition1305' => $this->t('Zoom VDouble+ out'),
          'transition1306' => $this->t('Zoom HDouble+ out'),
          'transition1307' => $this->t('Zoom VDouble- out'),
          'transition1308' => $this->t('Zoom HDouble- out'),
          'transition1309' => $this->t('Zoom+ in'),
          'transition1310' => $this->t('Zoom+ in L'),
          'transition1311' => $this->t('Zoom+ in R'),
          'transition1312' => $this->t('Zoom+ in T'),
          'transition1313' => $this->t('Zoom+ in B'),
          'transition1314' => $this->t('Zoom+ in TL'),
          'transition1315' => $this->t('Zoom+ in TR'),
          'transition1316' => $this->t('Zoom+ in BL'),
          'transition1317' => $this->t('Zoom+ in BR'),
          'transition1318' => $this->t('Zoom+ out'),
          'transition1319' => $this->t('Zoom+ out L'),
          'transition1320' => $this->t('Zoom+ out R'),
          'transition1321' => $this->t('Zoom+ out T'),
          'transition1322' => $this->t('Zoom+ out B'),
          'transition1323' => $this->t('Zoom+ out TL'),
          'transition1324' => $this->t('Zoom+ out TR'),
          'transition1325' => $this->t('Zoom+ out BL'),
          'transition1326' => $this->t('Zoom+ out BR'),
          'transition1327' => $this->t('Zoom- in'),
          'transition1328' => $this->t('Zoom- in L'),
          'transition1329' => $this->t('Zoom- in R'),
          'transition1330' => $this->t('Zoom- in T'),
          'transition1331' => $this->t('Zoom- in B'),
          'transition1332' => $this->t('Zoom- in TL'),
          'transition1333' => $this->t('Zoom- in TR'),
          'transition1334' => $this->t('Zoom- in BL'),
          'transition1335' => $this->t('Zoom- in BR'),
          'transition1336' => $this->t('Zoom- out'),
          'transition1337' => $this->t('Zoom- out L'),
          'transition1338' => $this->t('Zoom- out R'),
          'transition1339' => $this->t('Zoom- out T'),
          'transition1340' => $this->t('Zoom- out B'),
          'transition1341' => $this->t('Zoom- out TL'),
          'transition1342' => $this->t('Zoom- out TR'),
          'transition1343' => $this->t('Zoom- out BL'),
          'transition1344' => $this->t('Zoom- out BR'),
        ],
        'Collapse Effects' => [
          'transition1401' => $this->t('Collapse Stairs'),
          'transition1402' => $this->t('Collapse Swirl'),
          'transition1403' => $this->t('Collapse Square'),
          'transition1404' => $this->t('Collapse Rectangle Cross'),
          'transition1405' => $this->t('Collapse Rectangle'),
          'transition1406' => $this->t('Collapse Cross'),
          'transition1407' => $this->t('Collapse Circle'),
          'transition1408' => $this->t('Collapse ZigZag'),
          'transition1409' => $this->t('Collapse Random'),
        ],
        'Compound Effects' => [
          'transition1501' => $this->t('Clip &amp; Chess in'),
          'transition1502' => $this->t('Clip &amp; Chess out'),
          'transition1503' => $this->t('Clip &amp; Oblique Chess in'),
          'transition1504' => $this->t('Clip &amp; Oblique Chess out'),
          'transition1505' => $this->t('Clip &amp; Wave in'),
          'transition1506' => $this->t('Clip &amp; Wave out'),
          'transition1507' => $this->t('Clip &amp; Jump in'),
          'transition1508' => $this->t('Clip &amp; Jump out'),
        ],
        'Expand Effects' => [
          'transition1601' => $this->t('Expand Stairs'),
          'transition1602' => $this->t('Expand Straight'),
          'transition1603' => $this->t('Expand Swirl'),
          'transition1604' => $this->t('Expand Square'),
          'transition1605' => $this->t('Expand Rectangle Cross'),
          'transition1606' => $this->t('Expand Rectangle'),
          'transition1607' => $this->t('Expand Cross'),
          'transition1608' => $this->t('Expand ZigZag'),
          'transition1609' => $this->t('Expand Random'),
        ],
        'Stripe Effects' => [
          'transition1701' => $this->t('Dominoes Stripe'),
          'transition1702' => $this->t('Extrude out Stripe'),
          'transition1703' => $this->t('Extrude in Stripe'),
          'transition1704' => $this->t('Horizontal Blind Stripe'),
          'transition1705' => $this->t('Vertical Blind Stripe'),
          'transition1706' => $this->t('Horizontal Stripe'),
          'transition1707' => $this->t('Vertical Stripe'),
          'transition1708' => $this->t('Horizontal Moving Stripe'),
          'transition1709' => $this->t('Vertical Moving Stripe'),
          'transition1710' => $this->t('Horizontal Fade Stripe'),
          'transition1711' => $this->t('Vertical Fade Stripe'),
          'transition1712' => $this->t('Horizontal Fly Stripe'),
          'transition1713' => $this->t('Vertical Fly Stripe'),
          'transition1714' => $this->t('Horizontal Chess Stripe'),
          'transition1715' => $this->t('Vertical Chess Stripe'),
          'transition1716' => $this->t('Horizontal Random Fade Stripe'),
          'transition1717' => $this->t('Vertical Random Fade Stripe'),
          'transition1718' => $this->t('Horizontal Bounce Stripe'),
          'transition1719' => $this->t('Vertical Bounce Stripe'),
        ],
        'Wave out Effects' => [
          'transition1801' => $this->t('Wave out'),
          'transition1802' => $this->t('Wave out Eagle'),
          'transition1803' => $this->t('Wave out Swirl'),
          'transition1804' => $this->t('Wave out ZigZag'),
          'transition1805' => $this->t('Wave out Square'),
          'transition1806' => $this->t('Wave out Rectangle'),
          'transition1807' => $this->t('Wave out Circle'),
          'transition1808' => $this->t('Wave out Cross'),
          'transition1809' => $this->t('Wave out Rectangle Cross'),
        ],
        'Wave in Effects' => [
          'transition1901' => $this->t('Wave in'),
          'transition1902' => $this->t('Wave in Eagle'),
          'transition1903' => $this->t('Wave in Swirl'),
          'transition1904' => $this->t('Wave in ZigZag'),
          'transition1905' => $this->t('Wave in Square'),
          'transition1906' => $this->t('Wave in Rectangle'),
          'transition1907' => $this->t('Wave in Circle'),
          'transition1908' => $this->t('Wave in Cross'),
          'transition1909' => $this->t('Wave in Rectangle Cross'),
        ],
        'Jump out Effects' => [
          'transition2001' => $this->t('Jump out Straight'),
          'transition2002' => $this->t('Jump out Swirl'),
          'transition2003' => $this->t('Jump out ZigZag'),
          'transition2004' => $this->t('Jump out Square'),
          'transition2005' => $this->t('Jump out Square with Chess'),
          'transition2006' => $this->t('Jump out Rectangle'),
          'transition2007' => $this->t('Jump out Circle'),
          'transition2008' => $this->t('Jump out Rectangle Cross'),
        ],
        'Jump in Effects' => [
          'transition2101' => $this->t('Jump in Straight'),
          'transition2101' => $this->t('Jump in Straight'),
          'transition2102' => $this->t('Jump in Swirl'),
          'transition2103' => $this->t('Jump in ZigZag'),
          'transition2104' => $this->t('Jump in Square'),
          'transition2105' => $this->t('Jump in Square with Chess'),
          'transition2106' => $this->t('Jump in Rectangle'),
          'transition2107' => $this->t('Jump in Circle'),
          'transition2108' => $this->t('Jump in Rectangle Cross'),
        ],
        'Parabola Effects' => [
          'transition2201' => $this->t('Parabola Swirl in'),
          'transition2202' => $this->t('Parabola Swirl out'),
          'transition2203' => $this->t('Parabola ZigZag in'),
          'transition2204' => $this->t('Parabola ZigZag out'),
          'transition2205' => $this->t('Parabola Stairs in'),
          'transition2206' => $this->t('Parabola Stairs out'),
        ],
        'Float Effects' => [
          'transition2301' => $this->t('Float Right Random'),
          'transition2302' => $this->t('Float up Random'),
          'transition2303' => $this->t('Float up Random with Chess'),
          'transition2304' => $this->t('Float Right ZigZag'),
          'transition2305' => $this->t('Float up ZigZag'),
          'transition2306' => $this->t('Float up ZigZag with Chess'),
          'transition2307' => $this->t('Float Right Swirl'),
          'transition2308' => $this->t('Float up Swirl'),
          'transition2309' => $this->t('Float up Swirl with Chess'),
        ],
        'Fly Effects' => [
          'transition2401' => $this->t('Fly Right Random'),
          'transition2402' => $this->t('Fly up Random'),
          'transition2403' => $this->t('Fly up Random with Chess'),
          'transition2404' => $this->t('Fly Right ZigZag'),
          'transition2405' => $this->t('Fly up ZigZag'),
          'transition2406' => $this->t('Fly up ZigZag with Chess'),
          'transition2407' => $this->t('Fly Right Swirl'),
          'transition2408' => $this->t('Fly up Swirl'),
          'transition2409' => $this->t('Fly up Swirl with Chess'),
        ],
        'Stone Effects' => [
          'transition2501' => $this->t('Slide Down'),
          'transition2502' => $this->t('Slide Right'),
          'transition2503' => $this->t('Bounce Down'),
          'transition2504' => $this->t('Bounce Right'),
        ],
      ],
    ];

    // Arrow navigator.
    $form['arrownavigator'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Arrow navigator'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][arrownavigator]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $arrowskin = [];
    for ($i = 1 ; $i < 22; $i++) {
      $i = ($i < 10) ? '0' . $i : $i;
      $arrowskin[$i] = $this->t('Arrow ') . $i;
    }
    $form['arrownavigator']['arrowskin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Skin'),
      '#default_value' => (isset($this->options['arrownavigator']['arrowskin'])) ?
        $this->options['arrownavigator']['arrowskin'] : $this->options['arrowskin'],
      '#options' => $arrowskin,
    );
    $form['arrownavigator']['autocenter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Auto center'),
      '#description' => $this->t('Auto center arrows in parent container.'),
      '#default_value' => (isset($this->options['arrownavigator']['autocenter'])) ?
        $this->options['arrownavigator']['autocenter'] : $this->options['autocenter'],
      '#options' => array(
        0 => $this->t('No'),
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
        3 => $this->t('Both'),
      ),
    );
    $form['arrownavigator']['chancetoshow'] = array(
      '#type' => 'select',
      '#title' => $this->t('Chance to show'),
      '#description' => $this->t('How to react on the bullet navigator.'),
      '#default_value' => (isset($this->options['arrownavigator']['chancetoshow'])) ?
        $this->options['arrownavigator']['chancetoshow'] : $this->options['chancetoshow'],
      '#options' => array(
        0 => $this->t('Never'),
        1 => $this->t('Mouse Over'),
        2 => $this->t('Always'),
      ),
    );
    $form['arrownavigator']['steps'] = array(
      '#type' => 'number',
      '#title' => $this->t('Steps'),
      '#description' => t('Steps to go for each navigation request.'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['arrownavigator']['steps'])) ?
          $this->options['arrownavigator']['steps'] : $this->options['steps'],
      ),
    );
    $form['arrownavigator']['scale'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Scales bullet navigator'),
      '#description' => t('Scales bullet navigator or not while slider scale.'),
      '#default_value' => (isset($this->options['arrownavigator']['scale'])) ?
        $this->options['arrownavigator']['scale'] : $this->options['scale'],
    );


    // Bullet navigators.
    $form['bulletnavigator'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Bullet navigator'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][bulletnavigator]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $bulletskin = [];
    for ($i = 1 ; $i < 22; $i++) {
      $i = ($i < 10) ? '0' . $i : $i;
      $bulletskin[$i] = $this->t('Bullet ') . $i;
    }
    $form['bulletnavigator']['bulletskin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Skin'),
      '#default_value' => (isset($this->options['bulletnavigator']['bulletskin'])) ?
        $this->options['bulletnavigator']['bulletskin'] : $this->options['bulletskin'],
      '#options' => $bulletskin,
    );
    $form['bulletnavigator']['chancetoshow'] = array(
      '#type' => 'select',
      '#title' => $this->t('Chance to show'),
      '#description' => $this->t('When to display the bullet navigator.'),
      '#default_value' => (isset($this->options['bulletnavigator']['chancetoshow'])) ?
        $this->options['bulletnavigator']['chancetoshow'] : $this->options['chancetoshow'],
      '#options' => array(
        0 => $this->t('Never'),
        1 => $this->t('Mouse Over'),
        2 => $this->t('Always'),
      ),
    );
    $form['bulletnavigator']['action_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action mode'),
      '#description' => $this->t('How to react on the bullet navigator.'),
      '#default_value' => (isset($this->options['bulletnavigator']['action_mode'])) ?
        $this->options['bulletnavigator']['action_mode'] : $this->options['action_mode'],
      '#options' => array(
        0 => $this->t('None'),
        1 => $this->t('Act by click'),
        2 => $this->t('Act by mouse hover'),
        3 => $this->t('Act by click or mouse hover'),
      ),
    );
    $form['bulletnavigator']['autocenter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Auto center'),
      '#description' => $this->t('Auto center arrows in parent container.'),
      '#default_value' => (isset($this->options['bulletnavigator']['autocenter'])) ?
        $this->options['bulletnavigator']['autocenter'] : $this->options['autocenter'],
      '#options' => array(
        0 => $this->t('No'),
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
        3 => $this->t('Both'),
      ),
    );
    $form['bulletnavigator']['rows'] = array(
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#description' => t('Rows to arrange bullets.'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['rows'])) ?
          $this->options['bulletnavigator']['rows'] : $this->options['rows'],
      ),
    );
    $form['bulletnavigator']['steps'] = array(
      '#type' => 'number',
      '#title' => $this->t('Steps'),
      '#description' => t('Steps to go for each navigation request.'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['steps'])) ?
          $this->options['bulletnavigator']['steps'] : $this->options['steps'],
      ),
    );
    $form['bulletnavigator']['spacingx'] = array(
      '#type' => 'number',
      '#title' => $this->t('Horizontal space'),
      '#description' => t('Horizontal space between each item in pixel.'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['spacingx'])) ?
          $this->options['bulletnavigator']['spacingx'] : $this->options['spacingx'],
      ),
    );
    $form['bulletnavigator']['spacingy'] = array(
      '#type' => 'number',
      '#title' => $this->t('Vertical space'),
      '#description' => t('Vertical space between each item in pixel.'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['spacingy'])) ?
          $this->options['bulletnavigator']['spacingy'] : $this->options['spacingy'],
      ),
    );
    $form['bulletnavigator']['orientation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#description' => t('The orientation of the navigator.'),
      '#default_value' => (isset($this->options['bulletnavigator']['orientation'])) ?
        $this->options['bulletnavigator']['orientation'] : $this->options['orientation'],
      '#options' => array(
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
      ),
    );
    $form['bulletnavigator']['scale'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Scales bullet navigator'),
      '#description' => t('Scales bullet navigator or not while slider scale.'),
      '#default_value' => (isset($this->options['bulletnavigator']['scale'])) ?
        $this->options['bulletnavigator']['scale'] : $this->options['scale'],
    );
  }
}

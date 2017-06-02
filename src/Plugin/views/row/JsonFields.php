<?php

namespace Drupal\json_feed\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which displays fields for a JSON feed
 *
 * @ViewsRow(
 *   id = "json_fields",
 *   title = @Translation("JSON fields"),
 *   help = @Translation("Display fields as JSON items."),
 *   display_types = {"json_feed"}
 * )
 */
class JsonFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['id'] = ['default' => ''];
    $options['url'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('id attribute'),
      '#description' => $this->t('The field that is going to be used as the JSON id attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['id_field'],
    ];

    $form['url_field'] = [
      '#type' => 'select',
      '#title' => $this->t('url attribute'),
      '#description' => $this->t('The field that is going to be used as the JSON url attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['url_field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (empty($this->options['id_field'])) {
      $errors[] = $this->t('Row style plugin requires specifying which views field to use for JSON id attribute.');
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    // Create the JSON item
    $item = [];
    $row_index = $this->view->row_index;
    $item['id'] = $this->getField($row_index, $this->options['id_field']);
    $item['url'] = $this->getField($row_index, $this->options['url_field']);

    // Remove empty attributes.
    $item = array_filter($item);

    return $item;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string
   *   The rendered field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return (string) $this->view->style_plugin->getField($index, $field_id);
  }

}

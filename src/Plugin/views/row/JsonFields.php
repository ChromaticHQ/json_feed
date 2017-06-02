<?php

namespace Drupal\json_feed\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
    $options['title'] = ['default' => ''];
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
      '#required' => TRUE,
    ];

    $form['url_field'] = [
      '#type' => 'select',
      '#title' => $this->t('url attribute'),
      '#description' => $this->t('The field that is going to be used as the JSON url attribute for each row. This must be a drupal relative path.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['url_field'],
      '#required' => TRUE,
    ];

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('title attribute'),
      '#description' => $this->t('The field that is going to be used as the JSON title attribute for each row. This must be plain text, not linked to the content.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = ['id_field', 'url_field'];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for JSON feed item.');
        break;
      }
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
    $item['url'] = Url::fromUserInput($this->getField($row_index, $this->options['url_field']))->setAbsolute()->toString();
    $item['title'] = $this->getField($row_index, $this->options['title_field']);

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

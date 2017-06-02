<?php

namespace Drupal\json_feed\Plugin\views\style;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render a JSON feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "json_feed_serializer",
 *   title = @Translation("JSON Feed"),
 *   help = @Translation("Generates a JSON feed from a view."),
 *   display_types = {"json_feed"}
 * )
 */
class JsonFeedSerializer extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    $build['#attached']['library'][] =  'json_feed/json-feed';

    // Add the feed icon to the view.
    $this->view->feedIcons[] = [
      '#theme' => 'json_feed_icon',
      '#url' => $url,
      '#title' => $title,
    ];

    // Attach a link to the JSON feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'application/json',
      'title' => $title,
      'href' => $url,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['description'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['home_page_url_note'] = [
      '#type' => 'item',
      '#title' => $this->t('JSON Feed home_page_url'),
      '#description' => $this->t('Set Link Display to your view\'s main Page display to enable home_page_url'),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSON Feed description'),
      '#default_value' => $this->options['description'],
      '#description' => $this->t('This will appear in the JSON feed itself.'),
      '#maxlength' => 1024,
    ];
  }

  /**
   * Get RSS feed description.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getDescription() {
    $description = $this->options['description'];

    // Allow substitutions from the first row.
    $description = $this->tokenizeValue($description, 0);

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    // Build items list
    $items = [];
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $items[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Create feed object
    $feed = new \stdClass();
    $feed->version = 'https://jsonfeed.org/version/1';
    $feed->title = $this->getTitle();
    $feed->description = $this->getDescription();
    $feed->home_page_url = $this->getFeedHomePageUrl();
    $feed->feed_url = $this->displayHandler->getUrl()->setAbsolute()->toString();
    $feed->items = $items;

    return Json::encode($feed);
  }

  /**
   * Get the feed title
   *
   * @return string
   */
  protected function getTitle() {
    $config = \Drupal::config('system.site');

    // Find title
    if ($this->view->display_handler->getOption('sitename_title')) {
      $title = $config->get('name');
      if ($slogan = $config->get('slogan')) {
        $title .= ' - ' . $slogan;
      }
    }
    else {
      $title = $this->view->getTitle();
    }

    return $title;
  }

  /**
   * Get the first attached display URL
   */
  protected function getFeedHomePageUrl() {
    // Figure out which display which has a path we're using for this feed. If
    // there isn't one, use the global $base_url
    $link_display_id = $this->view->display_handler->getLinkDisplay();
    if ($link_display_id && $display = $this->view->displayHandlers->get($link_display_id)) {
      $url = $this->view->getUrl(NULL, $link_display_id);
    }

    $url_options = ['absolute' => TRUE];
    $base_url = Url::fromRoute('<front>')->setAbsolute()->toString();

    /** @var \Drupal\Core\Url $url */
    if ($url) {
      // Compare the link to the default home page; if it's the default home page,
      // just use $base_url.
      $config = \Drupal::config('system.site');
      $url_string = $url->setOptions($url_options)->toString();
      if ($url_string === Url::fromUserInput($config->get('page.front'))->toString()) {
        $url_string = $base_url;
      }

      return $url_string;
    }

    return $base_url;
  }

}
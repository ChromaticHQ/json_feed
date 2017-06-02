<?php

namespace Drupal\json_feed\Plugin\views\style;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render an RSS feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "json_feed_serializer",
 *   title = @Translation("JSON Feed"),
 *   help = @Translation("Generates a JSON feed from a view."),
 *   theme = "views_view_json",
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
    $feed->home_page_url = $this->getAttachedDisplayUrl();
    $feed->feed_url = $this->getFeedUrl();
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
   * Get the
   *
   * @return \Drupal\Core\GeneratedUrl|null|string
   */
  protected function getFeedUrl() {
    $config = \Drupal::config('system.site');

    $link_display_id = $this->view->display_handler->getLinkDisplay();
    if ($link_display_id && $display = $this->view->displayHandlers->get($link_display_id)) {
      $url = $this->view->getUrl(NULL, $link_display_id);
    }

    /** @var \Drupal\Core\Url $url */
    if ($url) {
      $url_options = ['absolute' => TRUE];

      // Compare the link to the default home page; if it's the default home page,
      // just use $base_url.
      $url_string = $url->setOptions($url_options)->toString();
      if ($url_string === Url::fromUserInput($config->get('page.front'))->toString()) {
        $url_string = Url::fromRoute('<front>')->setAbsolute()->toString();
      }

      return $url_string;
    }

    return null;
  }

  /**
   * Get the first attached display Url
   */
  protected function getAttachedDisplayUrl() {
    foreach(array_filter($this->displayHandler->options['displays']) as $attachDisplay) {
      $display_handler = $this->view->displayHandlers->get($attachDisplay);
      if ($display_handler->isEnabled()) {
        $url = $this->view->getUrl(NULL, $attachDisplay);
        if ($url) {
          $url_options = ['absolute' => TRUE];
          return $url->setOptions($url_options)->toString();
        }
      }
    }

    return null;
  }

}
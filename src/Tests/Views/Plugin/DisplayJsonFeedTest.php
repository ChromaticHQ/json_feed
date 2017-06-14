<?php

namespace Drupal\json_feed\Tests\Views\Plugin;

use Drupal\Core\Url;
use Drupal\views\Tests\Plugin\PluginTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the json_feed display plugin.
 *
 * @group views
 * @see \Drupal\json_feed\Plugin\views\display\JsonFeed
 */
class DisplayJsonFeedTest extends PluginTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'node',
    'views',
    'json_feed',
    'json_feed_test_views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display_json_feed'];

  /**
   * Path to the JSON Feed feed.
   *
   * @var string
   */
  protected $feedPath = 'test-json-feed-display/json';

  /**
   * The JSON Feed attributes that allow HTML markup.
   *
   * @var array
   */
  protected $htmlAllowedAttributes = ['content_html'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['json_feed_test_views']);

    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalCreateContentType(['type' => 'page']);

    // Verify a title with HTML entities is properly escaped.
    $node_title = 'This "cool" & "neat" article\'s title';
    $node = $this->drupalCreateNode([
      'title' => $node_title,
      'body' => [
        0 => [
          'value' => '<p>A <em>paragraph</em>.</p>',
          'format' => filter_default_format(),
        ],
      ],
    ]);
  }

  /**
   * Tests the rendered feed output.
   */
  public function testFeedOutput() {
    $json_response = $this->drupalGetJSON($this->feedPath);
    $this->assertResponse(200);

    $this->assertTrue(array_key_exists('version', $json_response), 'JSON Feed version present.');
    $this->assertEqual('https://jsonfeed.org/version/1', $json_response['version'], 'JSON Feed version set correctly.');

    $this->assertTrue(array_key_exists('title', $json_response), 'JSON Feed title present.');
    $this->assertEqual('test_display_json_feed', $json_response['title'], 'JSON Feed title set correctly.');

    $this->assertTrue(array_key_exists('description', $json_response), 'JSON Feed description present.');
    $this->assertEqual('Test feed description.', $json_response['description'], 'JSON Feed description set correctly.');

    $this->assertTrue(array_key_exists('home_page_url', $json_response), 'JSON Feed home_page_url present.');
    // @TODO: Implement test for home_page_url attribute value.

    $this->assertTrue(array_key_exists('feed_url', $json_response), 'JSON Feed feed_url present.');
    $this->assertTrue(strpos($json_response['feed_url'], $this->feedPath) !== FALSE, 'JSON Feed feed_url set correctly.');

    $this->assertTrue(array_key_exists('favicon', $json_response), 'JSON Feed favicon present.');
    $favicon_path = Url::fromUserInput(theme_get_setting('favicon.url'))->setAbsolute()->toString();
    $this->assertEqual($favicon_path, $json_response['favicon'], 'JSON Feed favicon set correctly.');

    $this->assertTrue(array_key_exists('expired', $json_response), 'JSON Feed expired attribute present.');
    $this->assertEqual(FALSE, $json_response['expired'], 'JSON Feed expired attribute set to FALSE.');
  }

  /**
   * Tests the feed items.
   */
  public function testFeedItems() {
    $json_response = $this->drupalGetJSON($this->feedPath);
    $this->assertEqual(1, count($json_response['items']), 'JSON Feed returned 1 item.');
    $item = $json_response['items'][0];

    $this->assertTrue(array_key_exists('date_published', $item), 'JSON Feed item date_published attribute present.');
    $this->assertTrue(array_key_exists('date_modified', $item), 'JSON Feed item date_modified attribute present.');

    // @TODO: Test remaining item attributes.
  }

  /**
   * Test fields that should not include HTML.
   */
  public function testHtmlPresence() {
    $json_response = $this->drupalGetJSON($this->feedPath);
    array_walk_recursive($json_response, function ($item, $key) {
      if (!is_array($item) && !in_array($key, $this->htmlAllowedAttributes)) {
        $this->assertEqual(strip_tags($item), $item, 'JSON Feed item: \'' . $key . '\' does not contain HTML.');
      }
    });
  }

}

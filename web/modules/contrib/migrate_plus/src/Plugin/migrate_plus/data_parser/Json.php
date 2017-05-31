<?php

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "json",
 *   title = @Translation("JSON")
 * )
 */
class Json extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request headers passed to the data fetcher.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * Iterator over the JSON data.
   *
   * @var \Iterator
   */
  protected $iterator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceData($url) {
    $iterator = $this->getSourceIterator($url);

    // Recurse through the result array. When there is an array of items at the
    // expected depth, pull that array out as a distinct item.
    $identifierDepth = $this->itemSelector;
    $items = [];
    $iterator->rewind();
    while ($iterator->valid()) {
      $item = $iterator->current();
      if (is_array($item) && $iterator->getDepth() == $identifierDepth) {
        $items[] = $item;
      }
      $iterator->next();
    }
    return $items;
  }

  /**
   * Get the source data for reading.
   *
   * @param string $url
   *   The URL to read the source data from.
   *
   * @return \RecursiveIteratorIterator|resource
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getSourceIterator($url) {
    try {
      $response = $this->getDataFetcherPlugin()->getResponseContent($url);
      // The TRUE setting means decode the response into an associative array.
      $array = json_decode($response, TRUE);

      // Return the results in a recursive iterator that
      // can traverse multidimensional arrays.
      return new \RecursiveIteratorIterator(
        new \RecursiveArrayIterator($array),
        \RecursiveIteratorIterator::SELF_FIRST);
    }
    catch (RequestException $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url);
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $this->currentItem[$field_name] = $current[$selector];
      }
      $this->iterator->next();
    }
  }

}

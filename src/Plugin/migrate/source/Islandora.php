<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate\source;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\SourcePluginExtension;
use function GuzzleHttp\Psr7\build_query;

/**
 * Source plugin for Islandora content.
 *
 * @MigrateSource(
 *   id = "islandora"
 * )
 */
class Islandora extends SourcePluginExtension {

  /**
   * The content model to restrict this search to.
   *
   * @var string
   */
  private $contentModel;

  /**
   * The Solr field to use for content model matching.
   *
   * @var string
   */
  private $contentModelField;

  /**
   * The base URL of the Fedora repo.
   *
   * @var string
   */
  private $fedoraBase;

  /**
   * The base URL for the Solr instance.
   *
   * @var string
   */
  private $solrBase;

  /**
   * The number of batches to run for this source.
   *
   * @var int
   */
  private $batches = 0;

  /**
   * The size of the batch to run. This always runs in batches.
   *
   * @var int
   */
  private $batchSize = 10;

  /**
   * Count of the current batch.
   *
   * @var int
   */
  private $batchCounter;

  /**
   * The count for the current query.
   *
   * @var int|null
   */
  private $count;

  /**
   * Internal client for Solr queries.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * The data parser plugin.
   *
   * @var \Drupal\migrate_plus\DataParserPluginInterface
   */
  protected $dataParserPlugin;

  /**
   * The data parser plugin.
   *
   * @var \Drupal\migrate_plus\DataFetcherPluginInterface
   */
  protected $dataFetcherPlugin;

  /**
   * What are we processing?
   *
   * There is extra processing for datastreams to get the correct count.
   *
   * @var string
   */
  private $processing;

  /**
   * Constant for the configuration to count objects.
   */
  const OBJECT_TYPE = 'objects';

  /**
   * Constant for the configuration to count datastreams.
   */
  const DATASTREAM_TYPE = 'datastreams';

  /**
   * Valid islandora_type values.
   *
   * @var array
   */
  private static $validProcessing = [
    self::OBJECT_TYPE,
    self::DATASTREAM_TYPE,
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    if (!isset($configuration['fedora_base_url'])) {
      throw new MigrateException("Islandora source plugin requires a \"fedora_base_url\" be defined.");
    }
    $this->fedoraBase = rtrim($configuration['fedora_base_url'], '/');
    if (!isset($configuration['solr_base_url'])) {
      throw new MigrateException("Islandora source plugin requires a \"solr_base_url\" be defined.");
    }
    $this->solrBase = rtrim($configuration['solr_base_url'], '/');
    if (!isset($configuration['content_model']) || !isset($configuration['content_model_field'])) {
      throw new MigrateException("Islandora source plugin requires a \"content_model_field\" and \"content_model\" be defined.");
    }
    $this->contentModel = $configuration['content_model'];
    $this->contentModelField = $configuration['content_model_field'];
    if (isset($configuration['batch_size'])) {
      if (is_int($this->configuration['batch_size']) && ($this->configuration['batch_size']) > 0) {
        $this->batchSize = $this->configuration['batch_size'];
      }
      else {
        throw new MigrateException("batch_size must be greater than zero");
      }
    }
    $this->processing = self::OBJECT_TYPE;
    if (isset($configuration['islandora_type'])) {
      if (count(array_intersect([$configuration['islandora_type']], Islandora::$validProcessing)) == 0) {
        throw new MigrateException(t('"islandora_type" must be one of @types', [
          '@types' => implode(', ', Islandora::$validProcessing),
        ]));
      }
      $this->processing = $configuration['islandora_type'];
    }

    if ($this->processing == self::DATASTREAM_TYPE) {
      if (!isset($configuration['datastream_solr_field'])) {
        throw new MigrateException("You must provide a Solr field with the list of datastreams as 'datastream_solr_field'.");
      }
      $this->datastreamSolrField = $configuration['datastream_solr_field'];
    }
    $this->httpClient = \Drupal::httpClient();
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    if (is_null($this->batchCounter)) {
      $this->batchCounter = 0;
    }
    $start = $this->batchCounter * $this->batchSize;
    $pids = $this->getPids($start);
    $current_batch = array_map(function ($i) {
      return "{$this->fedoraBase}/objects/{$i}/objectXML";
    }, $pids);
    $this->configuration['urls'] = $current_batch;
    $this->getDataParserPlugin()->updateUrls($current_batch);
    return $this->getDataParserPlugin();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    if (is_null($this->count)) {
      if ($this->processing == self::DATASTREAM_TYPE) {
        // Doing datastreams, so we need to count per object.
        $query = $this->getQuery(0, 0);
        $result = $this->getDataFetcherPlugin()->getResponseContent($query)->getContents();
        $body = json_decode($result, TRUE);
        $count = intval($body['response']['numFound']);
        $batch_size = 10000;
        $loops = intdiv($count, $batch_size) + ($count % $batch_size ? 1 : 0);
        $count = 0;
        for ($x = 0; $x < $loops; $x += 1) {
          // Now that we know how many objects, loop and count datastreams.
          $start = ($batch_size * $x);
          $query = $this->getQuery($start, $batch_size);
          $result = $this->getDataFetcherPlugin()->getResponseContent($query)->getContents();
          $body = json_decode($result, TRUE);
          foreach ($body['response']['docs'] as $object) {
            // Don't include AUDIT as you don't see if via Tuque/Fedora API-A.
            $count += count(array_diff($object[$this->datastreamSolrField], ['AUDIT']));
          }
        }
        $this->count = $count;
      }
      else {
        // Just do a regular object count.
        $query = $this->getQuery(0, 0);
        $result = $this->getDataFetcherPlugin()->getResponseContent($query)->getContents();
        $body = json_decode($result, TRUE);
        $this->count = $body['response']['numFound'];
      }
      $this->batches = intdiv($this->count, $this->batchSize) + ($this->count % $this->batchSize ? 1 : 0);
    }
    return $this->count;
  }

  /**
   * Returns the initialized data parser plugin.
   *
   * @return \Drupal\migrate_plus\DataParserPluginInterface
   *   The data parser plugin.
   */
  public function getDataParserPlugin() {
    if (!isset($this->dataParserPlugin)) {
      $this->dataParserPlugin = \Drupal::service('plugin.manager.migrate_plus.data_parser')->createInstance($this->configuration['data_parser_plugin'], $this->configuration);
    }
    return $this->dataParserPlugin;
  }

  /**
   * Returns the initialized data fetcher plugin.
   *
   * @return \Drupal\migrate_plus\DataFetcherPluginInterface
   *   The data fetcher plugin.
   */
  public function getDataFetcherPlugin() {
    if (!isset($this->dataFetcherPlugin)) {
      $this->dataFetcherPlugin = \Drupal::service('plugin.manager.migrate_plus.data_fetcher')->createInstance($this->configuration['data_fetcher_plugin'], $this->configuration);
    }
    return $this->dataFetcherPlugin;
  }

  /**
   * Position the iterator to the following row.
   */
  protected function fetchNextRow() {
    $this->getIterator()->next();
    // We might be out of data entirely, or just out of data in the current
    // batch. Attempt to fetch the next batch and see.
    if ($this->batchSize > 0 && !$this->getIterator()->valid()) {
      $this->fetchNextBatch();
    }
  }

  /**
   * Prepares query for the next set of data from the source database.
   */
  protected function fetchNextBatch() {
    $this->batchCounter++;
    unset($this->iterator);
    $this->getIterator()->rewind();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->getQuery(0, 0);
  }

  /**
   * Get a batch of PIDS.
   *
   * @param int $start
   *   The offset of the batch.
   *
   * @return array
   *   Array of the pids.
   */
  private function getPids($start = 0) {
    $query = $this->getQuery($start, $this->batchSize);
    $result = $this->getDataFetcherPlugin()->getResponseContent($query)->getContents();
    $pids = [];
    $body = json_decode($result, TRUE);
    foreach ($body['response']['docs'] as $o) {
      $pids[] = $o['PID'];
    }
    return $pids;
  }

  /**
   * Generate a Solr query string.
   *
   * @param int $start
   *   Row to start on for paging queries.
   * @param int $rows
   *   Number of rows to return for paging queries.
   *
   * @return string
   *   The Full query URL.
   */
  private function getQuery($start = 0, $rows = 200) {
    $params = [];
    $params['rows'] = $rows;
    $params['start'] = $start;
    if (isset($this->datastreamSolrField)) {
      $params['fl'] = 'PID,' . $this->datastreamSolrField;
    }
    else {
      $params['fl'] = 'PID';
    }
    $params['wt'] = 'json';
    $params['q'] = "{$this->contentModelField}:(\"{$this->contentModel}\" OR \"info:fedora/{$this->contentModel}\")";
    $params['sort'] = 'PID+asc';
    return $this->solrBase . "/select?" . build_query($params, FALSE);
  }

}

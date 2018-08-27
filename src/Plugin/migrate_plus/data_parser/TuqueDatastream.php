<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate_plus\data_parser;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;
use Islandora\Tuque\Api\FedoraApi;
use Islandora\Tuque\Api\FedoraApiSerializer;
use Islandora\Tuque\Cache\SimpleCache;
use Islandora\Tuque\Exception\RepositoryException;
use Islandora\Tuque\Guzzle\Client;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * Obtain XML data for migration using the SimpleXML API.
 *
 * @DataParser(
 *   id = "tuque_datastreams",
 *   title = @Translation("Tuque_datastreams")
 * )
 *
 * The field selector will attempt to match a magic property of the datastream.
 *
 * This will auto-populate the following fields
 *  PID -> PID of the fedora object
 *  PID_DSID -> a concatenated ID for use in media <-> file matching.
 *
 * It also will populate with the datastream ID any field with a selector of
 * DSID.
 */
class TuqueDatastream extends DataParserPluginBase {

  /**
   * The repository.
   *
   * @var \Islandora\Tuque\Repository\FedoraRepository
   */
  private static $repository;

  /**
   * The FedoraApi.
   *
   * @var \Islandora\Tuque\API\FedoraApi
   */
  private static $api;

  /**
   * Base URI for Fedora.
   *
   * @var string
   */
  private $baseUrl;

  /**
   * Username for Fedora.
   *
   * @var string
   */
  private $username;

  /**
   * Password for Fedora.
   *
   * @var string
   */
  private $password;

  /**
   * The current datastreams to view.
   *
   * @var array
   */
  private $datastreams;

  /**
   * The PID for currently loaded object.
   *
   * @var string
   */
  private $PID;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!isset($configuration['fedora_base_url'])) {
      throw new MigrateException("TuqueDatastream data fetcher plugin requires a \"fedora_base_url\" be defined.");
    }
    $this->baseUrl = rtrim($configuration['fedora_base_url'], '/') . '/';

    if (!isset($configuration['authentication']) ||
      !isset($configuration['authentication']['username']) ||
      !isset($configuration['authentication']['password'])) {
      throw new MigrateException("TuqueDatastream data fetcher plugin requires an \"authentication\" with \"username\" and \"password\" be defined.");
    }
    $this->username = $configuration['authentication']['username'];
    $this->password = $configuration['authentication']['password'];
  }

  /**
   * Get the repository or initialize it and get it.
   *
   * @return \Islandora\Tuque\Repository\FedoraRepository
   *   The Fedora repository object.
   */
  protected function getConnection() {
    if (!isset(TuqueDatastream::$repository) || is_null(TuqueDatastream::$repository)) {
      TuqueDatastream::initializeConnection($this->baseUrl, $this->username, $this->password);
    }
    return TuqueDatastream::$repository;
  }

  /**
   * Initialize a connection to Fedora.
   *
   * @param string $baseUrl
   *   The base url of the Fedora instance.
   * @param string $username
   *   The username to connect with.
   * @param string $password
   *   The password for the above username.
   */
  private static function initializeConnection($baseUrl, $username, $password) {
    if (!isset(TuqueDatastream::$api) || is_null(TuqueDatastream::$api)) {
      TuqueDatastream::initializeApi($baseUrl, $username, $password);
    }
    $cache = new SimpleCache();
    TuqueDatastream::$repository = new FedoraRepository(TuqueDatastream::$api, $cache);
  }

  /**
   * Initialize a Fedora API client.
   *
   * @param string $baseUrl
   *   The base url of the Fedora instance.
   * @param string $username
   *   The username to connect with.
   * @param string $password
   *   The password for the above username.
   */
  private static function initializeApi($baseUrl, $username, $password) {
    $guzzle = new Client([
      'base_uri' => $baseUrl,
      'auth' => [$username, $password],
    ]);
    TuqueDatastream::$api = new FedoraApi($guzzle, new FedoraApiSerializer());
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    $this->datastreams = [];
    $this->PID = NULL;
    // We expect PIDs or Fedora object URLs.
    if (!preg_match('~^(?>' . preg_quote($this->baseUrl) . 'objects/)([^:/]+:[^:/]+)~', $url, $match)) {
      return FALSE;
    }
    $pid = $match[1];
    try {
      $object = TuqueDatastream::getConnection()->getObject($pid);
      if ($object) {
        $this->PID = $pid;
        foreach ($object as $dsid => $datastream) {
          $this->datastreams[$dsid] = $datastream;
        }
        return TRUE;
      }
    }
    catch (RepositoryException $e) {
      throw new MigrateException("Error connecting to Fedora", 0, $e);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $datastream = array_shift($this->datastreams);

    if ($datastream) {
      $this->currentItem['PID'] = $this->PID;
      foreach ($this->fieldSelectors() as $field_name => $property_name) {
        if (!isset($this->currentItem[$field_name]) && $property_name == 'DSID') {
          $dsid = strtoupper($datastream->id);
          $this->currentItem[$field_name] = $dsid;
        }
        elseif (isset($datastream->$property_name) && !isset($this->currentItem[$field_name])) {
          $this->currentItem[$field_name] = $datastream->$property_name;
        }
      }
      if (isset($dsid)) {
        $this->currentItem['PID_DSID'] = $this->currentItem['PID'] . '_' . $dsid;
      }
      // Reduce single-value results to scalars.
      foreach ($this->currentItem as $field_name => $values) {
        if (is_array($values) && count($values) == 1) {
          $this->currentItem[$field_name] = reset($values);
        }
      }
    }
  }

  /**
   * Update the configuration for the dataparserplugin.
   *
   * The XML dataParserPlugin assumes you give it all the URLs to start,
   * but I am dynamically generating them based on the batch.
   *
   * @param array|string $urls
   *   New array of URLs to add to the FedoraDatastream processor.
   */
  public function updateUrls($urls) {
    if (!is_array($urls)) {
      $urls = [$urls];
    }
    $this->urls = $urls;
  }

  /**
   * {@inheritdoc}
   *
   * Islandora Source can provide 0 urls, we need to exit or it throws an
   * error.
   */
  protected function nextSource() {
    if (count($this->urls) == 0) {
      return FALSE;
    }
    return parent::nextSource();
  }

}

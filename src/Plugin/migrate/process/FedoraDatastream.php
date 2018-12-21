<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FedoraDatastream.
 *
 * @MigrateProcessPlugin(
 *   id = "fedora_datastream"
 * )
 * @package Drupal\migrate_7x_claw\Plugin\process
 */
class FedoraDatastream extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Http client authentication.
   *
   * @var array
   */
  protected $auth = [];

  /**
   * The URI of your Fedora instance.
   *
   * @var string
   */
  protected $fedoraUri;

  /**
   * Constructs a download process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   *
   * @throws \Drupal\migrate\MigrateException
   *   On configuration errors.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Client $http_client) {
    $configuration += [
      'guzzle_options' => [],
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    if (!isset($configuration['settings']['fedora_base_url'])) {
      throw new MigrateException("The fedora_datastream plugin requires a settings: key with a fedora_base_url key of your Fedora Base URI.");
    }
    $this->fedoraUri = $configuration['settings']['fedora_base_url'];
    if (isset($configuration['settings']['authentication'])) {
      $this->auth = [];
      $this->auth[] = $configuration['settings']['authentication']['username'];
      $this->auth[] = $configuration['settings']['authentication']['password'];
      if (isset($configuration['settings']['authentication']['plugin'])) {
        $this->auth[] = (isset($configuration['settings']['authentication']['plugin']) ?
        $configuration['settings']['authentication']['plugin'] :
        $configuration['settings']['authentication']['type']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_null($value) && $value instanceof \SimpleXMLElement) {
echo ("I MADE IT!");
      foreach ($value->attributes() as $key => $attribute) {
        if (strtolower($key) == 'size') {
          $size = (int) $attribute;
          if ($size > 0) {
            $fetch = TRUE;
            if (isset($dsid)) {
              break;
            }
          }
        }
        elseif (strtolower($key) == 'id') {
          $dsid = (string) $attribute;
          if (strpos($dsid, '.') !== FALSE) {
            $dsid = substr($dsid, 0, strpos($dsid, '.'));
          }
          if (isset($fetch)) {
            break;
          }
        }
      }
    }
    $pid = $row->getSourceIdValues()['PID'];
    if (isset($fetch) && isset($dsid)) {
      return self::getDatastream($pid, $dsid);
    }
    return "";
  }

  /**
   * Get the datastream from Fedora 3.
   *
   * @param string $pid
   *   The PID of the remote object.
   * @param string $dsid
   *   The datastream ID of the remote datastream.
   *
   * @return string
   *   The contents of the datastream.
   */
  private function getDatastream($pid, $dsid) {
    $uri = $this->fedoraUri . '/objects/' . $pid . '/datastreams/' . $dsid . '/content';
    $response = $this->httpClient->get($uri, ['auth' => $this->auth]);
    return $response->getBody()->getContents();
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

}

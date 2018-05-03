<?php
/**
 * Created by PhpStorm.
 * User: whikloj
 * Date: 2018-04-13
 * Time: 4:41 PM
 */

namespace Drupal\migrate_7x_claw\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for retrieving data via URLs.
 *
 * Available configuration keys
 * - url: The triplestore SPARQL endpoint.
 * - follow_field: Which field to use to traverse.
 * - initial_id: The ID to start traversing the triplestore.
 *
 * @MigrateSource(
 *   id = "triple_store_query"
 * )
 */
class TripleStoreQuery extends Url {

  protected $start_id;

  protected $tripleStoreUrl;

  protected $followField;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    if (!isset($configuration['url'])) {
      throw new MigrateException('"url" must be configured.');
    }
    if (!isset($configuration['follow_field'])) {
      throw new MigrateException('"follow_field" must be configured.');
    }
    if (!isset($configuration['initial_id'])) {
      throw new MigrateException('"initial_id" must be configured.');
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->tripleStoreUrl = $configuration['url'];
    $this->followField = $configuration['follow_field'];
    $this->start_id = $configuration['initial_id'];
  }


}
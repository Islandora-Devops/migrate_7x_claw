<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Class WrapWithText
 *
 * @MigrateProcessPlugin(
 *   id = "wrap_with_text"
 * )
 *
 * Allows to wrap a source provided value in static text, unlike Concat which
 * requires all values to be source provided.
 *
 * output:
 *   plugin: wrap_with_text
 *   source: input
 *   prefix: "Some text"
 *   suffix: "Some more text"
 *
 * for input == "HI" get "Some textHISome more text"
 *
 * @package Drupal\migrate_7x_claw\Plugin\process
 */
class WrapWithText extends ProcessPluginBase {

  /**
   * Text to prepend to each.
   *
   * @var string
   */
  protected $prefix = "";

  /**
   * Text to append to each.
   *
   * @var string
   */
  protected $suffix = "";

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['prefix']) && !isset($configuration['suffix'])) {
      throw new MigrateException("No prefix or suffix defined, so nothing to do.");
    }
    if (isset($configuration['prefix'])) {
      $this->prefix = $configuration['prefix'];
    }
    if (isset($configuration['suffix'])) {
      $this->suffix = $configuration['suffix'];
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->multiple = is_array($value);
    $values = is_array($value) ? $value : [$value];
    $prefix = $this->prefix;
    $suffix = $this->suffix;
    $return = array_map(function($i) use ($prefix, $suffix) {
      return $prefix . $i . $suffix;
    }, $values);
    if (is_string($this->configuration['source'])) {
      $this->multiple = is_array($return[0]);
      return $return[0];
    }
    return $return;
  }

  /**
   * Simple callback.
   *
   * @param string $item
   *   The text to wrap.
   *
   * @return string
   *   The wrapped string.
   */
  private function wrap($item) {
    return $this->prefix . $item . $this->suffix;
  }
}
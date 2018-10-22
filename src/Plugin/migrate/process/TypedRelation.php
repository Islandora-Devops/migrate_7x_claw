<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Transforms parallel arrays of entity ids and roles into an array of
 * associative arrays prepared for a TypedRelation field.
 *   
 * Available configuration keys:
 * - source: Source property containing entity ids.
 * - role_source: Source poperty containing parallel roles array.
 * 
 * Example:
 *
 * @code
 * source:
 *   my_ids:
 *     - 1 
 *     - 2
 *   my_roles:
 *     - relators:pbl
 *     - relators:ctb
 * process:
 *   field_typed_relation:
 *     plugin: typed_relation 
 *     source: my_ids
 *     role_source: my_roles 
 * @endcode
 *
 * will yield 
 *
 * @code
 * [
 *   ['target_id' => 1, 'rel_type' => 'relators:pbl'],
 *   ['target_id' => 2, 'rel_type' => 'relators:ctb'],
 * ]
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "typed_relation",
 *   handle_multiples = TRUE
 *  * )
 */
class TypedRelation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // No-op is empty array.
    if (empty($value)) {
      return [];
    }

    // We're expecting an array of ids.
    if (!is_array($value)) {
      throw new MigrateException("Process input is not an array");
    }

    // Make sure there's a role array, and that it lines up with the ids.
    if (isset($this->configuration['role_source'])) {
      $roles = $row->getSourceProperty($this->configuration['role_source']);
      if (count($roles) != count($value)) {
        throw new MigrateException("Input and roles arrays must be parallel");
      }
    }
    else {
      throw new MigrateException("Required configuration: role_source");
    }

    // Build an array for a Typed Relation field.
    $out = [];
    for ($i = 0; $i < count($value); ++$i) {
      $out[] = ['target_id' => $value[$i], 'rel_type' => $roles[$i]];
    }

    return $out;
  }
}

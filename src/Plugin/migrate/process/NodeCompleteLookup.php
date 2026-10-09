<?php

namespace Drupal\upgrade7_module\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Looks up the D10 destination nid for a D7 nid, against a node_complete
 * migration map table where sourceid1 may be vid/composite rather than
 * nid, by matching sourceid1 directly regardless of other key columns.
 *
 * Configuration:
 *   plugin: node_complete_lookup
 *   migration_map_table: migrate_map_upgrade_d7_node_complete_amp_collection
 *
 * @MigrateProcessPlugin(
 *   id = "node_complete_lookup"
 * )
 */
class NodeCompleteLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }

    $table = $this->configuration['migration_map_table'];
    $database = \Drupal::database();

    if (!$database->schema()->tableExists($table)) {
      return NULL;
    }

    // Match on sourceid1
    $destid = $database->select($table, 'm')
      ->fields('m', ['destid1'])
      ->condition('m.sourceid1', $value)
      ->execute()
      ->fetchField();

    \Drupal::logger('upgrade7_module')->info('check the destination collection: @coll_id', ['@coll_id'=>$destid]);
    return $destid !== FALSE ? $destid : NULL;
  }

}

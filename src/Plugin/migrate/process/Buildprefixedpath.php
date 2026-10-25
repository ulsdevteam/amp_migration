<?php

namespace Drupal\upgrade7_module\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Builds an internal path string "/<prefix>/<id>" from a resolved
 * destination id, or return NULL if the id is empty.
 *
 * Configuration:
 *   plugin: build_prefixed_path
 *   prefix: taxonomy/term
 *
 * @MigrateProcessPlugin(
 *   id = "build_prefixed_path"
 * )
 */
class BuildPrefixedPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value === NULL || $value === '' || $value === FALSE) {
      return NULL;
    }

    //rm '/' from both endings
    $prefix = trim($this->configuration['prefix'] ?? '', '/');

    return '/' . $prefix . '/' . $value;
  }

}

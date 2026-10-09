<?php
namespace Drupal\upgrade7_module\Plugin\migrate\field;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "islandora_object_field",
 *   source_module = "islandora_object_field",
 *   destination_module = "core",
 *   core = {7},
 *   type_map = {
 *     "islandora_object_field" = "entity_reference"
 *   }
 * )
 */
class IslandoraObjectField extends FieldPluginBase {
  /**
  * {@inheritdoc}
  */
 public function getFieldType(Row $field_definition) {
   //For D7 Islandora Object field type always store references to another node eg. field_manuscript
    return 'entity_reference';
 }

 /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap(): array {
    return [
      'islandora_object_field_label_formatter'            => 'entity_reference_label',
      'islandora_object_field_solr_formatter'              => 'search_api_solr_highlighted_text',
      'islandora_object_field_solrmetadata_byo_formatter'  => 'search_api_solr_highlighted_text',
      'islandora_object_field_pid_formatter'               => 'entity_reference_entity_id',
      'islandora_object_field_thumbnail_formatter'         => 'entity_reference_entity_view',
    ] + parent::getFieldFormatterMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap(): array {
    return [
      'islandora_object_field_autocomplete' => 'entity_reference_autocomplete',
    ] + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   *
   * Sets target_type=node on field storage since entity_reference requires
   * this setting to function
   */
  public function alterFieldMigration(MigrationInterface $migration): void {
    parent::alterFieldMigration($migration);

    $process = $migration->getProcess();

    $process['settings/target_type'] = [
      'plugin'        => 'default_value',
      'default_value' => 'node',
    ];

    $migration->setProcess($process);
  }

}


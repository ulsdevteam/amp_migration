<?php

namespace Drupal\upgrade7_module\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Applies D10 formatter equivalents for D7 formatters that could not migrate.
 */
class FormatterFixer {

  /**
   * Maps D7 unknown formatters to their D10 equivalents.
   *
   * 'field_name' accepts THREE forms:
   *   - a single string:        'field_songbook'
   *   - an array of strings:    ['field_songbook', 'field_manuscript']
   *   - the wildcard:           '*'  (applies to all fields using the D7 formatter)
   */
  protected const FORMATTER_MAP = [

    'islandora_object_field_label_formatter' => [
      'fields' => [
        [
          'entity_type'   => 'node',
          'bundle'        => 'songs',
          // Multiple fields sharing the same D10 formatter settings.
          'field_name'    => ['field_songbook', 'field_correspondence','field_business_records', 'field_manuscript','field_published_score'],
          'view_modes'    => ['default'],
          'd10_formatter' => 'entity_reference_label',
          'weight'        => 10,
          'label'         => 'above',
          'settings'      => ['link' => FALSE],
        ],
      ],
    ],

    'islandora_object_field_solr_formatter' => [
      'fields' => [
        [
          'entity_type'   => 'node',
          'bundle'        => '*',
          'field_name'    => ['field_manuscript'],
          'view_modes'    => ['default'],
          'd10_formatter' => 'solr_highlighted_text_default',
          'weight'        => 7,
          'label'         => 'above',
          'settings'      => [],
        ],
      ],
    ],
    
    'islandora_object_field_solrmetadata_byo_formatter' => [
    'fields' => [
      [
        'entity_type'   => 'node',
        'bundle_field_map' => [
            'songs'     => ['field_published_score','field_recording_songs'],
            'composers' => ['field_related_resources'],
          ],
        'view_modes'    => ['default'],
        'd10_formatter' => 'solr_highlighted_text_default',
        'weight'        => 8,   // matches D7 weight from your data
        'label'         => 'above',
        'settings'      => [],
      ],
    ],
  ],


    'islandora_object_field_thumbnail_formatter' => [
      'fields' => [
        [
          'entity_type'   => 'node',
          'bundle'        => '*',
          'field_name'    => '*',
          'view_modes'    => ['*'],
          'd10_formatter' => 'entity_reference_entity_view',
          'weight'        => 0,
          'label'         => 'hidden',
          'settings'      => [
            'view_mode' => 'default',
            'link'      => FALSE,
          ],
        ],
      ],
    ],

    'colorbox' => [
      'fields' => [
        [
          'entity_type'   => 'node',
          'bundle'        => '*',
          'field_name'    => '*',
          'view_modes'    => ['*'],
          'd10_formatter' => 'image',
          'weight'        => 0,
          'label'         => 'hidden',
          'settings'      => [
            'image_style' => 'large',
            'image_link'  => 'file',
          ],
        ],
      ],
    ],

  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger            = $logger_factory->get('upgrade7_module');
  }

  /**
   * Applies all formatter fixes defined in FORMATTER_MAP.
   */
  /**
   * Applies all formatter fixes defined in FORMATTER_MAP.
   */
  public function applyAll(): void {
    foreach (static::FORMATTER_MAP as $d7_formatter => $config) {
      $this->logger->info(
        'FormatterFixer: processing "@d7"',
        ['@d7' => $d7_formatter]
      );

      foreach ($config['fields'] as $field_config) {
        if (isset($field_config['bundle_field_map'])) {
          $this->applyBundleFieldMap($d7_formatter, $field_config);
        }
        else {
          $this->applyFormatterFix($d7_formatter, $field_config);
        }
      }
    }
  }

    /**
   * Processes a bundle_field_map entry — different fields per bundle.
   */
  protected function applyBundleFieldMap(
    string $d7_formatter,
    array $field_config
  ): void {

    $entity_type    = $field_config['entity_type'];
    $bundle_map     = $field_config['bundle_field_map'];
    $view_modes     = $field_config['view_modes'];
    $d10_formatter  = $this->resolveFormatter($field_config);
    $weight         = $field_config['weight']          ?? 0;
    $label          = $field_config['label']           ?? 'above';
    $settings       = $field_config['settings']        ?? [];
    $d7_note        = $field_config['d7_settings_note'] ?? NULL;

    foreach ($bundle_map as $bundle => $fields) {
      $fields = (array) $fields;

      // Resolve view modes for this specific bundle.
      $resolved_modes = $view_modes === ['*']
        ? $this->getViewModesForBundle($entity_type, $bundle)
        : $view_modes;

      foreach ($resolved_modes as $view_mode) {
        $display_id = implode('.', [$entity_type, $bundle, $view_mode]);

        $display = $this->entityTypeManager
          ->getStorage('entity_view_display')
          ->load($display_id);

        if (!$display) {
          $this->logger->warning(
            'FormatterFixer: display @id not found — skipping.',
            ['@id' => $display_id]
          );
          continue;
        }

        foreach ($fields as $field_name) {
          $this->applyToDisplay(
            $display,
            $display_id,
            $field_name,
            $d7_formatter,
            $d10_formatter,
            $weight,
            $label,
            $settings
          );
        }
      }
    }
  }

  /**
   * Applies the D10 formatter for one field config entry.
   */
  protected function applyFormatterFix(
    string $d7_formatter,
    array $field_config
  ): void {

    $entity_type   = $field_config['entity_type'];
    $bundle        = $field_config['bundle'];
    $field_name    = $field_config['field_name'];  
    $view_modes    = $field_config['view_modes'];
    $d10_formatter = $field_config['d10_formatter'];
    $weight        = $field_config['weight']   ?? 0;
    $label         = $field_config['label']    ?? 'above';
    $settings      = $field_config['settings'] ?? [];

    // Resolve wildcard bundles.
    $bundles = $bundle === '*'
      ? array_keys($this->entityTypeManager
          ->getStorage($entity_type . '_type')
          ->loadMultiple())
      : [$bundle];

    foreach ($bundles as $resolved_bundle) {

      // Resolve wildcard view modes.
      $resolved_modes = $view_modes === ['*']
        ? $this->getViewModesForBundle($entity_type, $resolved_bundle)
        : $view_modes;

      foreach ($resolved_modes as $view_mode) {
        $display_id = implode('.', [$entity_type, $resolved_bundle, $view_mode]);

        $display = $this->entityTypeManager
          ->getStorage('entity_view_display')
          ->load($display_id);

        if (!$display) {
          $this->logger->warning(
            'FormatterFixer: display @id not found — skipping.',
            ['@id' => $display_id]
          );
          continue;
        }

        // ── Resolve field_name into a flat array of field names ──────────────
        // Handles all three accepted forms: string, array, '*'.
        $resolved_fields = $this->resolveFieldNames(
          $field_name,
          $display,
          $d7_formatter
        );

        foreach ($resolved_fields as $resolved_field) {
         $this->applyToDisplay(
            $display,
            $display_id,
            $resolved_field,
            $d7_formatter,
            $d10_formatter,
            $weight,
            $label,
            $settings
          );
        }
      }
    }
  }


  /**
   * Applies a D10 formatter to one field on one display config.
   */
  protected function applyToDisplay(
    $display,
    string $display_id,
    string $field_name,
    string $d7_formatter,
    string $d10_formatter,
    int $weight,
    string $label,
    array $settings
  ): void {

    $existing = $display->getComponent($field_name);

    if ($existing && $existing['type'] === $d10_formatter) {
      $this->logger->info(
        'FormatterFixer: @field on @display already uses @formatter — skipping.',
        [
          '@field'     => $field_name,
          '@display'   => $display_id,
          '@formatter' => $d10_formatter,
        ]
      );
      return;
    }

    $display->setComponent($field_name, [
      'type'     => $d10_formatter,
      'weight'   => $existing['weight'] ?? $weight,
      'label'    => $existing['label']  ?? $label,
      'settings' => $settings,
    ]);

    $display->save();

    $this->logger->info(
      'FormatterFixer: [FIXED] @field on @display → @d10 (was D7: @d7)',
      [
        '@field'   => $field_name,
        '@display' => $display_id,
        '@d10'     => $d10_formatter,
        '@d7'      => $d7_formatter,
      ]
    );

    \Drupal::messenger()->addStatus(
      sprintf('[FIXED] %s on %s → %s (was D7: %s)',
        $field_name, $display_id, $d10_formatter, $d7_formatter)
    );
  }

  // Helpers
  /**
   * Resolves the formatter plugin ID — preferred, or fallback if unavailable.
   */
  protected function resolveFormatter(array $field_config): string {
    $preferred = $field_config['d10_formatter'];
    $fallback  = $field_config['d10_fallback'] ?? NULL;
    $manager   = \Drupal::service('plugin.manager.field.formatter');

    if ($manager->hasDefinition($preferred)) {
      return $preferred;
    }

    if ($fallback) {
      $this->logger->warning(
        'FormatterFixer: "@preferred" not available — falling back to "@fallback". '
        . 'Is the required module installed?',
        ['@preferred' => $preferred, '@fallback' => $fallback]
      );
      return $fallback;
    }

    $this->logger->error(
      'FormatterFixer: "@preferred" not available and no fallback defined.',
      ['@preferred' => $preferred]
    );
    return $preferred;
  }
  /**
   * @param string|array $field_name
   *   One of:
   *     - a single field name string, e.g. 'field_songbook'
   *     - an array of field names, e.g. ['field_songbook', 'field_manuscript']
   *     - the wildcard string '*'
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display being processed — used to resolve '*' to actual fields.
   * @param string $d7_formatter
   *   The D7 formatter id — used when resolving '*'.
   *
   * @return string[]
   *   A flat array of field machine names to apply the fix to.
   */
  protected function resolveFieldNames(
    string|array $field_name,
    $display,
    string $d7_formatter
  ): array {

    // Case 1: wildcard — find fields currently matching the D7 formatter type.
    if ($field_name === '*') {
      return $this->getFieldsWithFormatter($display, $d7_formatter);
    }

    // Case 2: array of field names — return as-is (already a flat list).
    if (is_array($field_name)) {
      return $field_name;
    }

    // Case 3: single field name string.
    return [$field_name];
  }

  /**
   * Returns all view mode IDs for a given entity type and bundle.
   */
  protected function getViewModesForBundle(
    string $entity_type,
    string $bundle
  ): array {
    $displays = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->loadByProperties([
        'targetEntityType' => $entity_type,
        'bundle'           => $bundle,
      ]);

    return array_map(
      fn($display) => $display->getMode(),
      $displays
    );
  }

  /**
   * Returns field names on a display that currently use a given formatter.
   */
  protected function getFieldsWithFormatter(
    $display,
    string $formatter_type
  ): array {
    $fields = [];
    foreach ($display->getComponents() as $field => $component) {
      if (($component['type'] ?? '') === $formatter_type) {
        $fields[] = $field;
      }
    }
    return $fields;
  }
}

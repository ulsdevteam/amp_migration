<?php

namespace Drupal\upgrade7_module\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\upgrade7_module\Service\FormatterFixer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fixes unknown D7 formatters after upgrade_d7_field_formatter_settings runs.
 */
class PostImportSubscriber implements EventSubscriberInterface {

  /**
   * The formatter fixer service.
   *
   * @var \Drupal\custom_migrate\Service\FormatterFixer
   */
  protected FormatterFixer $formatterFixer;

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
    FormatterFixer $formatter_fixer,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->formatterFixer = $formatter_fixer;
    $this->logger         = $logger_factory->get('upgrade7_module');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_IMPORT => 'onPostImport',
    ];
  }

  /**
   * Reacts to the completion of a migration import.
   * 
   */
  public function onPostImport(MigrateImportEvent $event): void {
    $migration = $event->getMigration();

    if ($migration->id() !== 'upgrade_d7_field_formatter_settings') {
      return;
    }

    $this->logger->info(
      'PostImportSubscriber: applying D10 formatter fixes after @id.',
      ['@id' => $migration->id()]
    );

    $this->formatterFixer->applyAll();

    $this->logger->info('PostImportSubscriber: formatter fixes applied.');
  }

}

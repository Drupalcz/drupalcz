<?php

/**
 * @file
 * Contains \Drupal\migrate_upgrade\MigrateUpgradeRunBatch.
 */

namespace Drupal\migrate_upgrade;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Url;

class MigrateUpgradeRunBatch {

  /**
   * Maximum number of previous messages to display.
   */
  const MESSAGE_LENGTH = 20;

  /**
   * The processed items for one batch of a given migration.
   *
   * @var int
   */
  protected static $numProcessed = 0;

  /**
   * Ensure we only add the listeners once per request.
   *
   * @var bool
   */
  protected static $listenersAdded = FALSE;

  /**
   * The maximum length in seconds to allow processing in a request.
   *
   * @var int
   */
  protected static $maxExecTime;

  /**
   * Run a single migration batch.
   *
   * @param $initial_ids
   *   The full set of migration IDs to import.
   * @param $context
   *   The batch context.
   */
  public static function run($initial_ids, &$context) {
    if (!static::$listenersAdded) {
      \Drupal::service('event_dispatcher')->addListener(MigrateEvents::POST_ROW_SAVE,
        array(get_class(), 'onPostRowSave'));
      \Drupal::service('event_dispatcher')->addListener(MigrateEvents::MAP_SAVE,
        array(get_class(), 'onMapSave'));
      static::$maxExecTime = ini_get('max_execution_time');
      if (static::$maxExecTime <= 0) {
        static::$maxExecTime = 60;
      }
      // Set an arbitrary threshold of 3 seconds (e.g., if max_execution_time is
      // 45 seconds, we will quit at 42 seconds so a slow item or cleanup
      // overhead don't put us over 45).
      static::$maxExecTime -= 3;
      static::$listenersAdded = TRUE;
    }
    if (!isset($context['sandbox']['migration_ids'])) {
      $context['sandbox']['max'] = count($initial_ids);
      $context['sandbox']['current'] = 1;
      // migration_ids will be the list of IDs remaining to run.
      $context['sandbox']['migration_ids'] = $initial_ids;
      $context['sandbox']['messages'] = [];
      $context['results']['failures'] = 0;
      $context['results']['successes'] = 0;
    }

    $migration_id = reset($context['sandbox']['migration_ids']);
    /** @var \Drupal\migrate\Entity\Migration $migration */
    $migration = Migration::load($migration_id);
    if ($migration) {
      $messages = new MigrateMessageCapture();
      $executable = new MigrateExecutable($migration, $messages);

      $migration_name = $migration->label() ? $migration->label() : $migration_id;

      try  {
        $migration_status = $executable->import();
      }
      catch (\Exception $e) {
        static::logger()->error($e->getMessage());
        $migration_status = MigrationInterface::RESULT_FAILED;
      }

      switch ($migration_status) {
        case MigrationInterface::RESULT_COMPLETED:
          $singular_message = 'Upgraded @migration (processed 1 item total)';
          $plural_message = 'Upgraded @migration (processed @num_processed items total)';
          $processed = $migration->getIdMap()->processedCount();
          $message = \Drupal::translation()->formatPlural(
            $processed, $singular_message, $plural_message,
            ['@migration' => $migration_name, '@num_processed' => $processed]);
          $context['sandbox']['messages'][] = $message;
          static::logger()->notice($message);
          static::$numProcessed = 0;
          $context['results']['successes']++;
          break;

        case MigrationInterface::RESULT_INCOMPLETE:
          $singular_message = 'Continuing with @migration (processed 1 item)';
          $plural_message = 'Continuing with @migration (processed @num_processed items)';
          $context['sandbox']['messages'][] = \Drupal::translation()->formatPlural(
            static::$numProcessed, $singular_message, $plural_message,
            ['@migration' => $migration_name, '@num_processed' => static::$numProcessed]);
          static::$numProcessed = 0;
          break;

        case MigrationInterface::RESULT_STOPPED:
          $context['sandbox']['messages'][] = t('Upgrade stopped by request');
          break;

        case MigrationInterface::RESULT_FAILED:
          $context['sandbox']['messages'][] = t('Upgrade of @migration failed', ['@migration' => $migration_name]);
          $context['results']['failures']++;
          static::logger()->error('Upgrade of @migration failed', ['@migration' => $migration_name]);
          break;

        case MigrationInterface::RESULT_SKIPPED:
          $context['sandbox']['messages'][] = t('Upgrade of @migration skipped due to unfulfilled dependencies', ['@migration' => $migration_name]);
          static::logger()->error('Upgrade of @migration skipped due to unfulfilled dependencies', ['@migration' => $migration_name]);
          break;

        case MigrationInterface::RESULT_DISABLED:
          // Skip silently if disabled.
          break;
      }

      // Unless we're continuing on with this migration, take it off the list.
      if ($migration_status != MigrationInterface::RESULT_INCOMPLETE) {
        array_shift($context['sandbox']['migration_ids']);
        $context['sandbox']['current']++;
      }

      // Add and log any captured messages.
      foreach ($messages->getMessages() as $message) {
        $context['sandbox']['messages'][] = $message;
        static::logger()->error($message);
      }

      // Only display the last MESSAGE_LENGTH messages, in reverse order.
      $message_count = count($context['sandbox']['messages']);
      $context['message'] = '';
      for ($index = max(0, $message_count - self::MESSAGE_LENGTH); $index < $message_count; $index++) {
        $context['message'] = $context['sandbox']['messages'][$index]. "<br />\n" . $context['message'];
      }
      if ($message_count > self::MESSAGE_LENGTH) {
        // Indicate there are earlier messages not displayed.
        $context['message'] .= '&hellip;';
      }
      // At the top of the list, display the next one (which will be the one
      // that is running while this message is visible).
      if (!empty($context['sandbox']['migration_ids'])) {
        $migration_id = reset($context['sandbox']['migration_ids']);
        $migration = Migration::load($migration_id);
        $migration_name = $migration->label() ? $migration->label() : $migration_id;
        $context['message'] = t('Currently upgrading @migration (@current of @max total tasks)',
          ['@migration' => $migration_name, '@current' => $context['sandbox']['current'],
           '@max' => $context['sandbox']['max']]) . "<br />\n" . $context['message'];
      }
    }
    else {
      array_shift($context['sandbox']['migration_ids']);
      $context['sandbox']['current']++;
    }

    $context['finished'] = 1 - count($context['sandbox']['migration_ids']) / $context['sandbox']['max'];
  }

  /**
   * A helper method to grab the logger using the migrate_upgrade channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger instance.
   */
  protected static function logger() {
    return \Drupal::logger('migrate_upgrade');
  }

  /**
   * Implementation of the Batch API finished method.
   */
  public static function finished($success, $results, $operations, $elapsed) {
    static::displayResults($results);
  }

  /**
   * Display counts of success/failures on the migration upgrade complete page.
   *
   * @param $results
   *   An array of result data built during the batch.
   */
  protected static function displayResults($results) {
    $successes = $results['successes'];
    $failures = $results['failures'];
    $translation = \Drupal::translation();

    // If we had any successes lot that for the user.
    if ($successes > 0) {
      drupal_set_message(t('Completed @count successfully.', ['@count' => $translation->formatPlural($successes, '1 upgrade task', '@count upgrade tasks')]));
    }

    // If we had failures, log them and show the migration failed.
    if ($failures > 0) {
      drupal_set_message(t('@count failed', ['@count' => $translation->formatPlural($failures, '1 upgrade', '@count upgrades')]), 'error');
      drupal_set_message(t('Upgrade process not completed'), 'error');
    }
    else {
      // Everything went off without a hitch. We may not have had successes but
      // we didn't have failures so this is fine.
      drupal_set_message(t('Congratulations, you upgraded Drupal!'));
    }

    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $url = Url::fromRoute('migrate_upgrade.log');
      drupal_set_message(\Drupal::l(t('Review the detailed upgrade log'), $url), $failures ? 'error' : 'status');
    }
  }

  /**
   * React to item import.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post-save event.
   */
  public static function onPostRowSave(MigratePostRowSaveEvent $event) {
    // We want to interrupt this batch and start a fresh one.
    if ((time() - REQUEST_TIME) > static::$maxExecTime) {
      $event->getMigration()->interruptMigration(MigrationInterface::RESULT_INCOMPLETE);
    }
  }

  /**
   * Count up any map save events.
   *
   * @param \Drupal\migrate\Event\MigrateMapSaveEvent $event
   *   The map event.
   */
  public static function onMapSave(MigrateMapSaveEvent $event) {
    static::$numProcessed++;
  }

}

<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

/**
 * Tells afreact's React SearchKit builder that it covers crmAbacus: it names the period in a
 * column's alias itself, so the decorator need not run there (docs/upstream-bugs.md).
 */
final class ReactBuilder extends AutoSubscriber {

  /**
   * @return array<string, string>
   */
  public static function getSubscribedEvents(): array {
    return ['civi.afreact.assets' => 'onAssets'];
  }

  public function onAssets(GenericHookEvent $event): void {
    if ($event->__get('builder') !== 'search_kit') {
      return;
    }
    $replaces = $event->__get('replaces');
    if (!is_array($replaces)) {
      throw new \CRM_Core_Exception('civi.afreact.assets came without a replaces list.');
    }
    $event->__set('replaces', [...$replaces, 'crmAbacus']);
  }

}

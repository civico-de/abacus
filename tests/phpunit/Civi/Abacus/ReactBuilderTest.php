<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Core\Event\GenericHookEvent;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
final class ReactBuilderTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return ck_headless()->apply();
  }

  public function testTheSearchBuilderHearsThatItCoversTheDecorator(): void {
    self::assertSame(['crmAbacus'], $this->replaces('search_kit'));
  }

  public function testOtherPagesHearNothing(): void {
    self::assertSame([], $this->replaces('afform'));
    self::assertSame([], $this->replaces(''));
  }

  public function testAMalformedEventIsAnError(): void {
    $this->expectExceptionMessage('civi.afreact.assets came without a replaces list.');
    \Civi::dispatcher()->dispatch(
      'civi.afreact.assets',
      GenericHookEvent::create(['builder' => 'search_kit', 'panels' => [], 'replaces' => NULL]),
    );
  }

  /**
   * @return array<mixed>
   */
  private function replaces(string $builder): array {
    $event = GenericHookEvent::create(['builder' => $builder, 'panels' => [], 'replaces' => []]);
    \Civi::dispatcher()->dispatch('civi.afreact.assets', $event);
    $replaces = $event->__get('replaces');
    self::assertIsArray($replaces);
    return $replaces;
  }

}

<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
final class InstallTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return ck_headless()->apply();
  }

  public function testTheExtensionIsInstalled(): void {
    $status = \CRM_Extension_System::singleton()->getManager()->getStatus('abacus');
    self::assertSame(\CRM_Extension_Manager::STATUS_INSTALLED, $status);
  }

}

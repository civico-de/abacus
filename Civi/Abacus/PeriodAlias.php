<?php

declare(strict_types = 1);

namespace Civi\Abacus;

/**
 * Default alias of a period function that names the period, so two periods of the same
 * fields do not collide; core builds it from the fields alone (docs/upstream-bugs.md).
 *
 * @require-extends \Civi\Api4\Query\SqlFunction
 */
trait PeriodAlias {

  use FunctionArgs;

  public function getAlias(): string {
    if ($this->alias !== NULL) {
      return $this->alias;
    }
    $period = str_replace('.', '_', $this->argKeyword(0));
    return static::getName() . ':' . $period . '_' . implode('_', $this->fields);
  }

}

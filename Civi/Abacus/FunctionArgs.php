<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Api4\Query\SqlExpression;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Typed access to the parsed arguments of a SqlFunction.
 *
 * @require-extends \Civi\Api4\Query\SqlFunction
 */
trait FunctionArgs {

  protected function argExpr(int $index): SqlExpression {
    $exprs = $this->args[$index]['expr'] ?? NULL;
    $expr = is_array($exprs) ? $exprs[0] ?? NULL : NULL;
    if (!$expr instanceof SqlExpression) {
      throw new \CRM_Core_Exception(E::ts('Argument %1 of %2 is missing.', [1 => $index + 1, 2 => static::getName()]));
    }
    return $expr;
  }

  protected function argKeyword(int $index): string {
    $prefix = $this->args[$index]['prefix'] ?? NULL;
    $keyword = is_array($prefix) ? $prefix[0] ?? NULL : NULL;
    if (!is_string($keyword)) {
      throw new \CRM_Core_Exception(E::ts('Argument %1 of %2 is missing.', [1 => $index + 1, 2 => static::getName()]));
    }
    return $keyword;
  }

}

<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Api4\Query\Api4Query;
use Civi\Api4\Query\SqlExpression;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Continuous percentile of a grouping, interpolated like PERCENTILE_CONT.
 *
 * The sorted values come from GROUP_CONCAT, so a grouping is complete only up to
 * group_concat_max_len bytes (see README).
 */
final class Percentile {

  private const NUMERIC_TYPES = ['Integer', 'Float', 'Money'];

  /**
   * Integer and Money values are interpolated as DECIMAL, so 0.10 and 0.20 give exactly 0.15.
   */
  public static function sql(string $value, float $fraction, string $dataType = 'Float'): string {
    if ($fraction < 0 || $fraction > 1) {
      throw new \CRM_Core_Exception(E::ts('A percentile must lie between 0 and 1.'));
    }
    $sorted = "GROUP_CONCAT({$value} ORDER BY {$value} SEPARATOR ',')";
    $rank = "((COUNT({$value}) - 1) * {$fraction} + 1)";
    $toNumber = $dataType === 'Float'
      ? static fn (string $text) => "({$text} + 0)"
      : static fn (string $text) => "CAST({$text} AS DECIMAL(65,10))";
    $at = static fn (string $position) => $toNumber(
      "SUBSTRING_INDEX(SUBSTRING_INDEX({$sorted}, ',', {$position}), ',', -1)",
    );
    $lower = $at("FLOOR({$rank})");
    $upper = $at("CEIL({$rank})");
    return "({$lower} + ({$rank} - FLOOR({$rank})) * ({$upper} - {$lower}))";
  }

  /**
   * @return string
   *   The numeric data type of the expression.
   */
  public static function assertNumeric(SqlExpression $expr, ?Api4Query $query, string $function): string {
    $dataType = $expr->getRenderedDataType($query);
    if (!is_string($dataType) || !in_array($dataType, self::NUMERIC_TYPES, TRUE)) {
      throw new \CRM_Core_Exception(E::ts('%1 needs a numeric field, got %2.', [
        1 => $function,
        2 => $dataType ?? E::ts('unknown type'),
      ]));
    }
    return $dataType;
  }

}

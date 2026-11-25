<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Api4\Query\Api4Query;
use Civi\Api4\Query\SqlExpression;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Relative date periods ("this.fiscal_year", "previous.month", …) as SQL conditions,
 * with the same boundaries as a relative date filter in APIv4.
 */
final class Period {

  private const DATE_TYPES = ['Date', 'Timestamp'];

  /**
   * @return array<string, string>
   *   Period name => label, from the relative_date_filters option group.
   */
  public static function options(): array {
    $options = [];
    foreach (\CRM_Core_OptionGroup::values('relative_date_filters') ?? [] as $name => $label) {
      if (!is_string($label)) {
        continue;
      }
      $options[(string) $name] = $label;
    }
    return $options;
  }

  public static function condition(string $period, string $date): string {
    $range = array_key_exists($period, self::options()) ? \CRM_Utils_Date::relativeToSql($period) : NULL;
    $from = self::bound($range['from'] ?? NULL);
    $to = self::bound($range['to'] ?? NULL);
    if ($from === NULL && $to === NULL) {
      throw new \CRM_Core_Exception(E::ts('Unknown date period "%1".', [1 => $period]));
    }
    if ($from === NULL) {
      return "({$date} <= {$to})";
    }
    if ($to === NULL) {
      return "({$date} >= {$from})";
    }
    return "({$date} BETWEEN {$from} AND {$to})";
  }

  /**
   * An open-ended period has no SQL bound on that side.
   */
  private static function bound(mixed $sql): ?string {
    return is_string($sql) && $sql !== '' ? $sql : NULL;
  }

  public static function assertDate(SqlExpression $expr, ?Api4Query $query, string $function): void {
    $dataType = $expr->getRenderedDataType($query);
    if (!in_array($dataType, self::DATE_TYPES, TRUE)) {
      throw new \CRM_Core_Exception(E::ts('%1 needs a date field, got %2.', [
        1 => $function,
        2 => $dataType ?? E::ts('unknown type'),
      ]));
    }
  }

}

<?php

declare(strict_types = 1);

namespace Civi\Api4\Query;

use Civi\Abacus\Percentile;
use Civi\Abacus\Period;
use Civi\Abacus\PeriodAlias;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Sum of the values whose date lies in a relative period; 0 when none does.
 */
class SqlFunctionSUM_IN_PERIOD extends SqlFunction {

  use PeriodAlias;

  protected static $category = self::CATEGORY_AGGREGATE;

  /**
   * @return list<array<string, mixed>>
   */
  protected static function params(): array {
    return [
      [
        'label' => E::ts('Period'),
        'flag_before' => Period::options(),
        'max_expr' => 0,
      ],
      [
        'label' => E::ts('Value'),
        'must_be' => ['SqlField'],
      ],
      [
        'label' => E::ts('Date'),
        'must_be' => ['SqlField'],
      ],
    ];
  }

  public static function getTitle(): string {
    return E::ts('Sum in period');
  }

  public static function getDescription(): string {
    return E::ts('The sum of the values whose date lies in the chosen period, e.g. the previous fiscal year.');
  }

  // @mago-expect lint:no-boolean-flag-parameter — signature inherited from SqlFunction::render().
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $value = $this->argExpr(1);
    $date = $this->argExpr(2);
    Percentile::assertNumeric($value, $query, static::getName());
    Period::assertDate($date, $query, static::getName());
    $condition = Period::condition($this->argKeyword(0), $date->render($query));
    $sql = "COALESCE(SUM(IF({$condition}, {$value->render($query)}, NULL)), 0)";
    return $sql . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  /**
   * The first argument is the period, not a field, so the aggregate's type comes from the function.
   *
   * @param array<array-key, mixed> $values
   */
  public function formatOutputValue(?string &$dataType, array &$values, string $key, ?Api4Query $query = NULL): void {
    $dataType = $this->getRenderedDataType($query) ?? $dataType;
  }

  public static function renderExpression(string $output): string {
    throw new \CRM_Core_Exception(E::ts('%1 cannot be used as a column total.', [1 => static::getTitle()]));
  }

}

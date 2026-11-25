<?php

declare(strict_types = 1);

namespace Civi\Api4\Query;

use Civi\Abacus\Period;
use Civi\Abacus\PeriodAlias;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Whether a date lies in a relative period such as "previous.fiscal_year".
 */
class SqlFunctionIN_PERIOD extends SqlFunction {

  use PeriodAlias;

  protected static $category = self::CATEGORY_COMPARISON;

  protected static $dataType = 'Boolean';

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
        'label' => E::ts('Date'),
        'must_be' => ['SqlField'],
      ],
    ];
  }

  public static function getTitle(): string {
    return E::ts('In period');
  }

  public static function getDescription(): string {
    return E::ts('Whether the date lies in the chosen period, e.g. the previous fiscal year.');
  }

  // @mago-expect lint:no-boolean-flag-parameter — signature inherited from SqlFunction::render().
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $date = $this->argExpr(1);
    Period::assertDate($date, $query, static::getName());
    $sql = 'IF(' . Period::condition($this->argKeyword(0), $date->render($query)) . ', 1, 0)';
    return $sql . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  public static function renderExpression(string $output): string {
    throw new \CRM_Core_Exception(E::ts('%1 cannot be used as a column total.', [1 => static::getTitle()]));
  }

}

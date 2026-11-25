<?php

declare(strict_types = 1);

namespace Civi\Api4\Query;

use Civi\Abacus\FunctionArgs;
use Civi\Abacus\Percentile;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Population standard deviation of a grouping.
 */
class SqlFunctionSTDDEV extends SqlFunction {

  use FunctionArgs;

  protected static $category = self::CATEGORY_AGGREGATE;

  protected static $dataType = 'Float';

  /**
   * @return list<array<string, mixed>>
   */
  protected static function params(): array {
    return [
      [
        'must_be' => ['SqlField'],
      ],
    ];
  }

  public static function getTitle(): string {
    return E::ts('Standard deviation');
  }

  public static function getDescription(): string {
    return E::ts('How far the values of the grouping typically lie from their mean.');
  }

  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    Percentile::assertNumeric($this->argExpr(0), $query, static::getName());
    return parent::render($query, $includeAlias);
  }

}

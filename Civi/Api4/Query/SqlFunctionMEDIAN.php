<?php

declare(strict_types = 1);

namespace Civi\Api4\Query;

use Civi\Abacus\FunctionArgs;
use Civi\Abacus\Percentile;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Middle value of a grouping; the mean of the two middle values for an even count.
 */
class SqlFunctionMEDIAN extends SqlFunction {

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
    return E::ts('Median');
  }

  public static function getDescription(): string {
    return E::ts('The middle value of the grouping, unaffected by a few very large or small values.');
  }

  // @mago-expect lint:no-boolean-flag-parameter — signature inherited from SqlFunction::render().
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $value = $this->argExpr(0);
    $dataType = Percentile::assertNumeric($value, $query, static::getName());
    $sql = Percentile::sql($value->render($query), 0.5, $dataType);
    return $sql . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  /**
   * Also renders the column total of a SearchKit table, where $output is the column.
   */
  public static function renderExpression(string $output): string {
    return Percentile::sql($output, 0.5);
  }

}

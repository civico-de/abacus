<?php

declare(strict_types = 1);

namespace Civi\Api4\Query;

use Civi\Abacus\FunctionArgs;
use Civi\Abacus\Percentile;
use CRM_Abacus_ExtensionUtil as E;

/**
 * Value below which the given fraction of a grouping lies, e.g. 0.9 for the 90th percentile.
 */
class SqlFunctionPERCENTILE extends SqlFunction {

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
      [
        'label' => E::ts('Fraction (0 to 1)'),
        'must_be' => ['SqlNumber'],
      ],
    ];
  }

  public static function getTitle(): string {
    return E::ts('Percentile');
  }

  public static function getDescription(): string {
    return E::ts('The value below which the given fraction of the grouping lies, e.g. 0.9 for the top 10%.');
  }

  // @mago-expect lint:no-boolean-flag-parameter — signature inherited from SqlFunction::render().
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $value = $this->argExpr(0);
    $dataType = Percentile::assertNumeric($value, $query, static::getName());
    $fraction = $this->argExpr(1)->getExpr();
    if (!is_numeric($fraction)) {
      throw new \CRM_Core_Exception(E::ts('A percentile must lie between 0 and 1.'));
    }
    $sql = Percentile::sql($value->render($query), (float) $fraction, $dataType);
    return $sql . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  public static function renderExpression(string $output): string {
    throw new \CRM_Core_Exception(E::ts('%1 cannot be used as a column total.', [1 => static::getTitle()]));
  }

}

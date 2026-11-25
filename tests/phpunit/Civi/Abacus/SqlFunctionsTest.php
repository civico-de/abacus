<?php

declare(strict_types = 1);

namespace Civi\Abacus;

use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\Utils\CoreUtil;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
final class SqlFunctionsTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return ck_headless('org.civicrm.search_kit')->apply();
  }

  protected function tearDown(): void {
    \Civi::settings()->revert('fiscalYearStart');
    parent::tearDown();
  }

  public function testFunctionsAreOfferedToSearchKit(): void {
    $names = array_column(CoreUtil::getSqlFunctions(), 'category', 'name');
    self::assertSame('aggregate', $names['MEDIAN'] ?? NULL);
    self::assertSame('aggregate', $names['PERCENTILE'] ?? NULL);
    self::assertSame('aggregate', $names['STDDEV'] ?? NULL);
    self::assertSame('aggregate', $names['SUM_IN_PERIOD'] ?? NULL);
    self::assertSame('aggregate', $names['COUNT_IN_PERIOD'] ?? NULL);
    self::assertSame('comparison', $names['IN_PERIOD'] ?? NULL);
  }

  public function testMedianOfOddAndEvenGroupings(): void {
    $even = $this->createDonor([10, 20, 30, 100]);
    $odd = $this->createDonor([9, 5, 7]);

    $medians = Contribution::get(FALSE)
      ->addSelect('contact_id', 'MEDIAN(total_amount) AS median')
      ->addWhere('contact_id', 'IN', [$even, $odd])
      ->addGroupBy('contact_id')
      ->execute()
      ->column('median', 'contact_id');

    self::assertSame([$even => 25.0, $odd => 7.0], $medians);
  }

  public function testMedianOfMoneyIsExact(): void {
    $donor = $this->createDonor([0.10, 0.20]);

    $median = Contribution::get(FALSE)
      ->addSelect('MEDIAN(total_amount) AS median')
      ->addWhere('contact_id', '=', $donor)
      ->addGroupBy('contact_id')
      ->execute()
      ->single()['median'];

    self::assertSame(0.15, $median);
  }

  public function testMedianSkipsNullAndHandlesNegativeValues(): void {
    $values = 'SELECT -10 AS v UNION ALL SELECT -5 UNION ALL SELECT NULL UNION ALL SELECT -1 UNION ALL SELECT 3';
    $median = \CRM_Core_DAO::singleValueQuery('SELECT ' . Percentile::sql('v', 0.5, 'Integer') . " FROM ({$values}) t");

    self::assertSame(-3.0, (float) $median);
  }

  public function testPercentileInterpolatesBetweenNeighbours(): void {
    $donor = $this->createDonor([10, 20, 30, 100]);

    $result = Contribution::get(FALSE)
      ->addSelect('PERCENTILE(total_amount, 0) AS p0')
      ->addSelect('PERCENTILE(total_amount, 0.9) AS p90')
      ->addSelect('PERCENTILE(total_amount, 1) AS p100')
      ->addWhere('contact_id', '=', $donor)
      ->addGroupBy('contact_id')
      ->execute()
      ->single();

    // Rank (4 - 1) * 0.9 + 1 = 3.7 lies 70% of the way from 30 to 100.
    self::assertSame(10.0, $result['p0']);
    self::assertEqualsWithDelta(79.0, $result['p90'], 0.000_001);
    self::assertSame(100.0, $result['p100']);
  }

  /**
   * MariaDB's PERCENTILE_CONT window function is the reference for the interpolation.
   */
  public function testPercentileMatchesPercentileCont(): void {
    $amounts = [];
    mt_srand(20_261_125);
    for ($i = 0; $i < 37; $i++) {
      $amounts[] = mt_rand(100, 99_999) / 100;
    }
    $donor = $this->createDonor($amounts);

    foreach ([0.1, 0.25, 0.5, 0.9] as $fraction) {
      $expected = (float) \CRM_Core_DAO::singleValueQuery(
        "SELECT DISTINCT PERCENTILE_CONT({$fraction}) WITHIN GROUP (ORDER BY total_amount) OVER ()
         FROM civicrm_contribution WHERE contact_id = %1",
        [1 => [$donor, 'Positive']],
      );
      $actual = Contribution::get(FALSE)
        ->addSelect("PERCENTILE(total_amount, {$fraction}) AS p")
        ->addWhere('contact_id', '=', $donor)
        ->addGroupBy('contact_id')
        ->execute()
        ->single()['p'];
      self::assertEqualsWithDelta($expected, $actual, 0.000_001, "fraction {$fraction}");
    }
  }

  public function testMedianIsNullForAnEmptyGrouping(): void {
    $donor = $this->createDonor([]);

    $result = Contact::get(FALSE)
      ->addSelect('MEDIAN(contribution.total_amount) AS median')
      ->addJoin('Contribution AS contribution', 'LEFT', ['id', '=', 'contribution.contact_id'])
      ->addWhere('id', '=', $donor)
      ->addGroupBy('id')
      ->execute()
      ->single();

    self::assertNull($result['median']);
  }

  public function testGroupConcatLimitCoversThePacketSize(): void {
    $limits = \CRM_Core_DAO::executeQuery(
      'SELECT @@SESSION.group_concat_max_len AS concat, @@GLOBAL.max_allowed_packet AS packet',
    );
    $limits->fetch();
    self::assertGreaterThanOrEqual((int) $limits->packet, (int) $limits->concat);
  }

  public function testPercentileRejectsAFractionAboveOne(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('between 0 and 1');
    Contribution::get(FALSE)
      ->addSelect('PERCENTILE(total_amount, 1.5) AS p')
      ->addGroupBy('contact_id')
      ->execute();
  }

  /**
   * @return array<string, array{0: string}>
   */
  public static function numericAggregates(): array {
    return ['median' => ['MEDIAN'], 'standard deviation' => ['STDDEV']];
  }

  /**
   * @dataProvider numericAggregates
   */
  public function testNumericAggregateRejectsATextField(string $function): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage("{$function} needs a numeric field, got String");
    Contribution::get(FALSE)
      ->addSelect("{$function}(source) AS m")
      ->addGroupBy('contact_id')
      ->execute();
  }

  public function testStandardDeviation(): void {
    $donor = $this->createDonor([10, 20, 30, 100]);

    $result = Contribution::get(FALSE)
      ->addSelect('STDDEV(total_amount) AS spread')
      ->addWhere('contact_id', '=', $donor)
      ->addGroupBy('contact_id')
      ->execute()
      ->single();

    self::assertEqualsWithDelta(sqrt(1250), $result['spread'], 0.000_001);
  }

  /**
   * Totals arrive formatted for display, like core's AVG.
   *
   * @return array<string, array{0: string, 1: string}>
   */
  public static function columnTotals(): array {
    return ['median' => ['MEDIAN', '25'], 'standard deviation' => ['STDDEV', '35.355']];
  }

  /**
   * @dataProvider columnTotals
   */
  public function testColumnTotal(string $function, string $expected): void {
    $donor = $this->createDonor([10, 20, 30, 100]);
    $savedSearch = SavedSearch::create(FALSE)
      ->setValues([
        'name' => 'abacus_median_total',
        'label' => 'abacus_median_total',
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'total_amount'],
          'where' => [['contact_id', '=', $donor]],
        ],
      ])
      ->execute()
      ->single();
    SearchDisplay::create(FALSE)
      ->setValues([
        'name' => 'abacus_median_total',
        'label' => 'abacus_median_total',
        'saved_search_id' => $savedSearch['id'],
        'type' => 'table',
        'settings' => [
          'columns' => [
            ['type' => 'field', 'key' => 'total_amount', 'tally' => ['fn' => $function]],
          ],
          'tally' => ['label' => 'Total'],
        ],
      ])
      ->execute();

    $tally = SearchDisplay::run(FALSE)
      ->setReturn('tally')
      ->setSavedSearch('abacus_median_total')
      ->setDisplay('abacus_median_total')
      ->execute()
      ->single();

    self::assertSame($expected, $tally['total_amount']);
  }

  public function testPeriodFunctionsMatchTheRelativeDateFilter(): void {
    \Civi::settings()->set('fiscalYearStart', ['M' => 4, 'd' => 1]);
    $today = new \DateTimeImmutable('today');
    $thisStart = $today->setDate((int) $today->format('Y'), 4, 1);
    if ($thisStart > $today) {
      $thisStart = $thisStart->modify('-1 year');
    }
    $previousStart = $thisStart->modify('-1 year');
    $donor = $this->createDonor([
      // Two fiscal years ago, previous fiscal year (first day, last second), this fiscal year.
      [1, $previousStart->modify('-1 day')],
      [10, $previousStart],
      [20, $thisStart->modify('-1 second')],
      [300, $thisStart],
    ]);

    $result = Contribution::get(FALSE)
      ->addSelect('SUM_IN_PERIOD(previous.fiscal_year, total_amount, receive_date) AS previous_sum')
      ->addSelect('COUNT_IN_PERIOD(previous.fiscal_year, receive_date) AS previous_count')
      ->addSelect('SUM_IN_PERIOD(this.fiscal_year, total_amount, receive_date) AS this_sum')
      ->addSelect('SUM_IN_PERIOD(next.fiscal_year, total_amount, receive_date) AS next_sum')
      ->addWhere('contact_id', '=', $donor)
      ->addGroupBy('contact_id')
      ->execute()
      ->single();

    $filtered = Contribution::get(FALSE)
      ->addSelect('total_amount')
      ->addWhere('contact_id', '=', $donor)
      ->addWhere('receive_date', '=', 'previous.fiscal_year')
      ->execute();

    self::assertSame(30.0, $result['previous_sum']);
    self::assertSame(2, $result['previous_count']);
    self::assertSame(count($filtered), $result['previous_count']);
    self::assertSame(300.0, $result['this_sum']);
    self::assertSame(0.0, $result['next_sum']);
  }

  public function testInPeriodFlagsEachRow(): void {
    $donor = $this->createDonor([[5, new \DateTimeImmutable('today')], [7, new \DateTimeImmutable('-2 years')]]);

    $flags = Contribution::get(FALSE)
      ->addSelect('total_amount', 'IN_PERIOD(this.year, receive_date) AS this_year')
      ->addWhere('contact_id', '=', $donor)
      ->addOrderBy('total_amount')
      ->execute()
      ->column('this_year');

    self::assertSame([TRUE, FALSE], $flags);
  }

  public function testInPeriodRejectsANumberField(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('IN_PERIOD needs a date field, got Money');
    Contribution::get(FALSE)
      ->addSelect('IN_PERIOD(this.year, total_amount) AS flag')
      ->execute();
  }

  public function testOpenEndedPeriods(): void {
    $donor = $this->createDonor([[1, new \DateTimeImmutable('-2 years')], [10, new \DateTimeImmutable('today')]]);

    $result = Contribution::get(FALSE)
      ->addSelect('SUM_IN_PERIOD(earlier.year, total_amount, receive_date) AS before_this_year')
      ->addSelect('SUM_IN_PERIOD(greater.year, total_amount, receive_date) AS since_this_year')
      ->addWhere('contact_id', '=', $donor)
      ->addGroupBy('contact_id')
      ->execute()
      ->single();

    self::assertSame(1.0, $result['before_this_year']);
    self::assertSame(10.0, $result['since_this_year']);
  }

  public function testTwoPeriodsOfTheSameFieldsGetTheirOwnDefaultAlias(): void {
    $donor = $this->createDonor([[1, new \DateTimeImmutable('-2 years')], [10, new \DateTimeImmutable('today')]]);

    $result = Contribution::get(FALSE)
      ->addSelect('SUM_IN_PERIOD(earlier.year, total_amount, receive_date)')
      ->addSelect('SUM_IN_PERIOD(greater.year, total_amount, receive_date)')
      ->addSelect('COUNT_IN_PERIOD(earlier.year, receive_date)')
      ->addSelect('COUNT_IN_PERIOD(greater.year, receive_date)')
      ->addWhere('contact_id', '=', $donor)
      ->addGroupBy('contact_id')
      ->execute()
      ->single();

    self::assertSame(1.0, $result['SUM_IN_PERIOD:earlier_year_total_amount_receive_date']);
    self::assertSame(10.0, $result['SUM_IN_PERIOD:greater_year_total_amount_receive_date']);
    self::assertSame(1, $result['COUNT_IN_PERIOD:earlier_year_receive_date']);
    self::assertSame(1, $result['COUNT_IN_PERIOD:greater_year_receive_date']);
  }

  public function testInPeriodOnADateField(): void {
    $contactId = Contact::create(FALSE)
      ->setValues(['contact_type' => 'Individual', 'last_name' => 'abacus', 'birth_date' => date('Y-m-d')])
      ->execute()
      ->single()['id'];

    $flag = Contact::get(FALSE)
      ->addSelect('IN_PERIOD(this.year, birth_date) AS this_year')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->single()['this_year'];

    self::assertTrue($flag);
  }

  public function testParserRejectsAPeriodOutsideTheOptionList(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Missing param 2 for SQL function COUNT_IN_PERIOD');
    Contribution::get(FALSE)
      ->addSelect('COUNT_IN_PERIOD(sometime.soon, receive_date) AS n')
      ->addGroupBy('contact_id')
      ->execute();
  }

  public function testConditionRejectsAnUnknownPeriod(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Unknown date period "sometime.soon"');
    Period::condition('sometime.soon', 'receive_date');
  }

  public function testPeriodFunctionCannotBeAColumnTotal(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('cannot be used as a column total');
    \Civi\Api4\Query\SqlFunctionSUM_IN_PERIOD::renderExpression('`total_amount`');
  }

  /**
   * @param array<int, float|int|array{0: float|int, 1: \DateTimeImmutable}> $gifts
   *   Amounts, or [amount, receive date] pairs.
   */
  private function createDonor(array $gifts): int {
    $contactId = (int) Contact::create(FALSE)
      ->setValues(['contact_type' => 'Individual', 'last_name' => 'abacus'])
      ->execute()
      ->single()['id'];
    foreach ($gifts as $gift) {
      [$amount, $date] = is_array($gift) ? $gift : [$gift, new \DateTimeImmutable('today')];
      Contribution::create(FALSE)
        ->setValues([
          'contact_id' => $contactId,
          'financial_type_id:name' => 'Donation',
          'total_amount' => $amount,
          'receive_date' => $date->format('Y-m-d H:i:s'),
        ])
        ->execute();
    }
    return $contactId;
  }

}

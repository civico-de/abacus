# abacus

Extra SearchKit functions for fundraising figures: median, percentile,
standard deviation, and sums and counts per relative period such as the
previous fiscal year.

The functions are ordinary APIv4 SQL functions. Once the extension is enabled
they appear in SearchKit's function picker next to core's `SUM` and `AVG`, and
they work in APIv4 calls from PHP, JavaScript and `cv`.

## Functions

| Function | Kind | Result |
|---|---|---|
| `MEDIAN(field)` | aggregate | Median of a numeric field per group |
| `PERCENTILE(field, fraction)` | aggregate | Continuous percentile, `fraction` from 0 to 1 |
| `STDDEV(field)` | aggregate | Population standard deviation |
| `SUM_IN_PERIOD(period, value, date)` | aggregate | Sum of `value` over the rows whose `date` lies in `period`; 0 when none do |
| `COUNT_IN_PERIOD(period, date)` | aggregate | Number of rows whose `date` lies in `period` |
| `IN_PERIOD(period, date)` | comparison | `TRUE` when the row's `date` lies in `period` |

- `MEDIAN` and `PERCENTILE` take an Integer, Float or Money field and
  interpolate between neighbouring values like SQL's `PERCENTILE_CONT`: the
  median of 10, 20, 30, 100 is 25. A group without values gives `NULL`.
- `period` is one of CiviCRM's relative date filters (`this.fiscal_year`,
  `previous.year`, `previous_2.month`, …). SearchKit offers them as a
  dropdown. The boundaries come from `CRM_Utils_Date::relativeToSql()`, as
  for a relative date filter, so the fiscal year follows the site's fiscal
  year start. A custom period served only by the deprecated
  `hook_civicrm_relativeDate` is rejected.
- Money and Integer values are interpolated as decimals: the median of 0.10
  and 0.20 is exactly 0.15.
- Each period gets its own column name, so this fiscal year and the previous
  one can stand side by side on the same fields. Core names function columns
  after their fields only; abacus works around that for its period functions
  in APIv4 and in the SearchKit editor
  ([docs/upstream-bugs.md](docs/upstream-bugs.md)).
- `date` must be a Date or Timestamp field. A wrong field type, an unknown
  period or a fraction outside 0 to 1 raises an error instead of returning a
  number.
- `MEDIAN` and `STDDEV` can also serve as a column total in a SearchKit table.
  A total sees only the column, not its type, so pick them on numeric columns
  only. SearchKit also lists the other aggregates as totals; they need more
  than one argument, and choosing one makes the totals row fail.

## Examples

Median gift and last fiscal year's income per donor:

```php
\Civi\Api4\Contribution::get()
  ->addSelect('contact_id')
  ->addSelect('MEDIAN(total_amount) AS median_gift')
  ->addSelect('SUM_IN_PERIOD(previous.fiscal_year, total_amount, receive_date) AS last_fy')
  ->addSelect('COUNT_IN_PERIOD(this.fiscal_year, receive_date) AS gifts_this_fy')
  ->addGroupBy('contact_id')
  ->execute();
```

The 90th percentile of all gifts this year:

```bash
cv api4 Contribution.get '{"select":["PERCENTILE(total_amount, 0.9) AS p90"],"where":[["receive_date","=","this.year"]]}'
```

In SearchKit, group the search (for example by contact), then pick the
function in "Select Fields"; for the period functions choose the period in
the dropdown next to the function.

## Requirements

- CiviCRM 6.20 or later (for `CRM_Utils_Date::relativeToSql()`).
- MySQL or MariaDB.

## Large groups

`MEDIAN` and `PERCENTILE` sort a group's values with `GROUP_CONCAT`. MySQL
cuts its result off silently at 1024 bytes by default, so the extension raises
`group_concat_max_len` to `max_allowed_packet` when CiviCRM boots. A group
is then complete up to that packet size (16 MB by default on MariaDB, 64 MB on
MySQL 8.4): about two million amounts like `1234.56` per group.

## Development

The extension is built and tested with
[civikitchen](https://github.com/civico-de/civikitchen):

```bash
docker compose -f .docker/docker-compose.ci.yml up -d --wait
docker compose -f .docker/docker-compose.ci.yml exec -u www-data -w /var/www/html/ext/abacus app ck ci
```

`ck ci` runs lint, conformance, PHPStan and the headless PHPUnit suite with
coverage. One test compares `PERCENTILE` against MariaDB's `PERCENTILE_CONT`
window function and therefore needs MariaDB 10.3 or later.

## Support and licence

Questions and bugs go to the
[issue tracker](https://github.com/civico-de/abacus/issues). Report security
issues by email to security@civico.de, not in a public issue; see
[SECURITY.md](SECURITY.md).

abacus is licensed under [AGPL-3.0](LICENSE.txt), © civico GmbH.

# Upstream bugs

Defects in CiviCRM core that abacus works around. Each entry names the
workaround, so it can be removed once core is fixed.

## Function aliases ignore flag arguments

**Status:** not reported upstream. Measured on CiviCRM 6.20.1.

A SQL function column gets a default name made from its field arguments
only. Flag arguments, such as the unit of `EXTRACT` or the period of
`SUM_IN_PERIOD`, are left out. Two columns that differ only in a flag
therefore get the same name, and APIv4 rejects the query:

```
EXTRACT(YEAR FROM receive_date), EXTRACT(MONTH FROM receive_date)
→ Duplicate alias 'EXTRACT:receive_date' in SELECT clause
```

The name is built in two places:

- APIv4, when the select has no `AS`:
  [`SqlFunction::getAlias()`](https://github.com/civicrm/civicrm-core/blob/50f307c7751a279cd7851ed0f9e7c8d3233438f1/Civi/Api4/Query/SqlFunction.php#L216-L218)
  joins `$this->fields`.
- The SearchKit editor, which always writes an `AS`:
  [`makeAlias()`](https://github.com/civicrm/civicrm-core/blob/50f307c7751a279cd7851ed0f9e7c8d3233438f1/ext/search_kit/ang/crmSearchAdmin/crmSearchFunction.component.js#L228-L235)
  keeps only field, number and string arguments.

The check that raises the error is
[`Api4Query::addExprToSelectClause()`](https://github.com/civicrm/civicrm-core/blob/50f307c7751a279cd7851ed0f9e7c8d3233438f1/Civi/Api4/Query/Api4Query.php#L692).
The query fails loudly; no wrong figures are returned.

For abacus this blocks the main use case: this fiscal year's sum next to
the previous one's, on the same amount and date fields.

**Workaround in abacus:**

- `Civi\Abacus\PeriodAlias` overrides `getAlias()` for `IN_PERIOD`,
  `SUM_IN_PERIOD` and `COUNT_IN_PERIOD` and puts the period into the
  default name: `SUM_IN_PERIOD:previous_fiscal_year_total_amount_receive_date`.
- `ang/crmAbacus.module.js` decorates SearchKit's `crmSearchFunction`
  component so that the editor adds the period to the alias of these three
  functions, using SearchKit's own `createSqlName()`. It changes the alias
  when a column is edited; saved searches keep the alias they were saved
  with.

Core's `EXTRACT` stays affected; the workaround covers abacus functions only.
The decorator depends on `crmSearchFunction` keeping its `writeExpr()` and
`args` shape. If SearchKit changes them, the editor falls back to core's
alias, and two periods of the same fields fail with the error above again.
The decorator loads with the SearchKit admin page (`civicrm/admin/search`),
the only page where core shows the search editor; a custom page that embeds
`crmSearchAdmin` elsewhere gets core's alias. afreact's React SearchKit builder
names the period in the alias itself, so abacus tells it through
`civi.afreact.assets` that it need not run the decorator
(`Civi\Abacus\ReactBuilder`).

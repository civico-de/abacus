---
title: abacus
order: 1
---

# abacus

> **Status: alpha.** Function names and results can still change between releases. Try it on a test site before you use it in production.

abacus adds functions to SearchKit for fundraising figures: the median and other percentiles of gifts, their standard deviation, and sums and counts per period, such as the previous fiscal year. They appear in SearchKit's function picker next to the built-in ones, such as Sum and Average.

## Functions

| Function | What it gives per group |
|---|---|
| Median | The middle amount. A few very large or small gifts do not move it, unlike the average. |
| Percentile | The amount below which a given share of the amounts lies, e.g. 0.9 for the 90th percentile. |
| Standard deviation | How far the amounts typically lie from their average. |
| Sum in period | The sum of the amounts whose date lies in the chosen period; 0 when none does. |
| Count in period | The number of records whose date lies in the chosen period. |
| In period | Not an aggregate: yes or no for each record, depending on whether its date lies in the period. |

Median and Percentile interpolate between neighbouring amounts: the median of 10, 20, 30 and 100 is 25. A group without amounts gives no value.

The periods are CiviCRM's relative dates, as offered in search filters: this fiscal year, previous year, previous 2 months and so on. The fiscal year starts where the site sets it, on the date settings page (`civicrm/admin/setting/date`).

## Example: giving per donor

1. Create a search for Contributions and group it by Contact ID.
2. In "Select Fields", add Total Amount and choose the function **Median**.
3. Add Total Amount again, choose **Sum in period**, pick the period *This fiscal year* and as date Contribution Date.
4. Add it once more with the period *Previous fiscal year*.

Each row then shows a donor's median gift and the sums of this and the previous fiscal year side by side.

## Good to know

- Median, Percentile and Standard deviation need a number or money field; the period functions need a date field. A wrong field or period gives an error, not a number.
- In a table display, Median and Standard deviation can also serve as a column total. Choose them only on numeric columns.
- Very large groups: Median and Percentile handle up to roughly two million amounts per group.

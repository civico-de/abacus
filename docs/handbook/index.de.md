---
title: abacus
---

# abacus

> **Status: Alpha.** Funktionsnamen und Ergebnisse können sich zwischen Versionen noch ändern. Probieren Sie es auf einer Testseite aus, bevor Sie es produktiv einsetzen.

abacus ergänzt SearchKit um Funktionen für Fundraising-Kennzahlen: den Median und andere Perzentile von Zuwendungen, ihre Standardabweichung sowie Summen und Anzahlen je Zeitraum, etwa im letzten Geschäftsjahr. Sie erscheinen in der Funktionsauswahl von SearchKit neben den eingebauten wie Summe und Durchschnitt.

## Funktionen

| Funktion | Ergebnis je Gruppe |
|---|---|
| Median | Der mittlere Betrag. Einzelne sehr große oder kleine Zuwendungen verschieben ihn kaum, anders als den Durchschnitt. |
| Perzentil | Der Betrag, unter dem ein gewählter Anteil der Beträge liegt, z. B. 0,9 für das 90. Perzentil. |
| Standardabweichung | Wie weit die Beträge typischerweise von ihrem Durchschnitt abweichen. |
| Summe im Zeitraum | Die Summe der Beträge, deren Datum im gewählten Zeitraum liegt; 0, wenn keines darin liegt. |
| Anzahl im Zeitraum | Die Zahl der Datensätze, deren Datum im gewählten Zeitraum liegt. |
| Im Zeitraum | Keine Aggregatfunktion: ja oder nein für jeden Datensatz, je nachdem, ob sein Datum im Zeitraum liegt. |

Median und Perzentil interpolieren zwischen benachbarten Beträgen: Der Median von 10, 20, 30 und 100 ist 25. Eine Gruppe ohne Beträge ergibt keinen Wert.

Die Zeiträume sind die relativen Datumsangaben von CiviCRM, wie sie auch in Suchfiltern angeboten werden: dieses Geschäftsjahr, letztes Jahr, letzte 2 Monate und so weiter. Das Geschäftsjahr beginnt dort, wo es auf der Seite mit den Datumseinstellungen (`civicrm/admin/setting/date`) festgelegt ist.

## Beispiel: Zuwendungen je Person

1. Legen Sie eine Suche nach Zuwendungen an und gruppieren Sie nach Kontakt-ID.
2. Fügen Sie unter „Felder auswählen“ den Gesamtbetrag hinzu und wählen Sie die Funktion **Median**.
3. Fügen Sie den Gesamtbetrag noch einmal hinzu, wählen Sie **Summe im Zeitraum**, den Zeitraum *Dieses Geschäftsjahr* und als Datum das Zuwendungsdatum.
4. Fügen Sie ihn ein drittes Mal hinzu, mit dem Zeitraum *Letztes Geschäftsjahr*.

Jede Zeile zeigt dann die mittlere Zuwendung einer Person und die Summen dieses und des letzten Geschäftsjahres nebeneinander.

## Gut zu wissen

- Median, Perzentil und Standardabweichung brauchen ein Zahlen- oder Geldfeld, die Zeitraum-Funktionen ein Datumsfeld. Ein falsches Feld oder ein unbekannter Zeitraum ergibt eine Fehlermeldung statt einer Zahl.
- In einer Tabellenanzeige können Median und Standardabweichung auch als Spaltensumme dienen. Wählen Sie sie dort nur für Zahlenspalten.
- Sehr große Gruppen: Median und Perzentil verarbeiten bis zu etwa zwei Millionen Beträge je Gruppe.

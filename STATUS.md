# JW Calendar – Projektstatus

## Aktueller Status

| Feld | Wert |
|------|------|
| **Version** | 1.8.0 (in Vorbereitung) |
| **Vorgänger** | 1.7.2 (Final, JED-Live seit 2026-04-04) |
| **Status** | Code fertig & gepackt – Staging-Test + Release ausstehend |
| **Offene Bugs** | Keine bekannt |
| **Letzte Aktualisierung** | 2026-06-07 |
| **Update-Server** | GitHub (raw) statt eiwtestzone ab 1.8.0 |

## Features (ausgeliefert)

- Joomla 6 Package (`pkg_jwcalendar`) mit Komponente + Modul
- FullCalendar.js Integration (Monats-, Wochen-, Tages- und Listenansicht)
- Kategorien mit Farbcodierung (5 Default-Kategorien)
- Wiederkehrende Events (täglich, wöchentlich, monatlich, jährlich)
- Erinnerungen (5/10/15/30/60 Min, 1 Tag vorher)
- Drag & Drop (verschieben, resizen)
- Responsives Design, Dark Mode
- Mehrsprachig (DE + EN)
- Backend-Konfiguration mit Live-Vorschau
- Zugriffsrechte-System (ACL)
- PDF-Dokumentation (DE + EN)
- Update-Server für automatische Updates

## Changelog

### v1.8.0 (in Vorbereitung) – Feature-Release
- **Termin-Darstellung wählbar:** Backend-Einstellung „Termin-Darstellung (Monatsansicht)" – Punkt klein/mittel/groß oder farbiger Balken mit automatischem Text-Kontrast
- **Feiertags-Aussetzung bei Wiederholungen:** Wiederkehrende Termine können an gesetzlichen Feiertagen automatisch aussetzen (OpenHolidays-API, mit Cache in `#__calendar_holidays`), Land + Bundesland pro Termin wählbar
- **Manuelle Ausnahmedaten:** Pro Termin einzelne Tage ausschließen (Feld `exception_dates`)
- **Update-Server auf GitHub umgestellt** (raw.githubusercontent.com)
- Neue DB-Spalten: `skip_holidays`, `holiday_country`, `holiday_subdivision`, `exception_dates` (+ Update-SQL 1.8.0)

### v1.7.2 (2026-03-28) – Final Release
- Letzte Bugfixes und Polishing
- Auf JED veröffentlicht (2026-04-04)

## Nächste Schritte (für 1.8.0-Release)

1. **Staging-Test:** Komponente + Modul auf einer Test-Joomla installieren, Update 1.7.2→1.8.0 prüfen, Feiertags-Aussetzung mit echtem Termin testen
2. **GitHub-Release v1.8.0 anlegen** und `pkg_jwcalendar_v1.8.0.zip` als Asset anhängen
3. **Übergang:** `update_server/jwcalendar_update.xml` EINMAL noch auf eiwtestzone hochladen, damit 1.7.2-Nutzer 1.8.0 angeboten bekommen
4. **JED-Eintrag** auf Version 1.8.0 aktualisieren

## Links

- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Update-Server:** https://www.eiwtestzone.ch/huberlabs-extensions/updates/jwcalendar_update.xml
- **Autor:** https://www.eiwtestzone.ch/huberlabs-extensions/

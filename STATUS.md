# JW Calendar – Projektstatus

## Aktueller Status

| Feld | Wert |
|------|------|
| **Version** | 1.8.0 (ausgeliefert & getestet) ✅ |
| **Vorgänger** | 1.7.2 (Final, JED-Live seit 2026-04-04) |
| **Status** | Live – Auto-Update funktioniert, auf Joomla 6.1.1 verifiziert |
| **Offene Bugs** | Keine bekannt |
| **Letzte Aktualisierung** | 2026-06-07 |
| **Update-Server** | GitHub (raw) + eiwtestzone (Übergang für 1.7.2-Nutzer) |
| **Download** | GitHub-Release v1.8.0 (`pkg_jwcalendar_v1.8.0.zip`) |

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

### v1.8.0 (2026-06-07) – Feature-Release ✅ ausgeliefert
- **Termin-Darstellung wählbar:** Backend-Einstellung „Termin-Darstellung (Monatsansicht)" – Punkt klein/mittel/groß oder farbiger Balken mit automatischem Text-Kontrast
- **Feiertags-Aussetzung bei Wiederholungen:** Wiederkehrende Termine können an gesetzlichen Feiertagen automatisch aussetzen (OpenHolidays-API, mit Cache in `#__calendar_holidays`), Land + Bundesland pro Termin wählbar (DE/AT/CH mit Subdivisions)
- **Manuelle Ausnahmedaten:** Pro Termin einzelne Tage ausschließen (Feld `exception_dates`)
- **Update-Server auf GitHub umgestellt** (raw.githubusercontent.com)
- Neue DB-Spalten: `skip_holidays`, `holiday_country`, `holiday_subdivision`, `exception_dates` (+ Update-SQL 1.8.0, neue Tabelle `#__calendar_holidays`)
- **Fix Update-Erkennung:** `<client>site</client>` in der Update-XML ergänzt (sonst nimmt Joomla client_id=1/administrator an → Paket [client_id=0] wird nicht zugeordnet → Update bleibt unsichtbar)

### v1.7.2 (2026-03-28) – Final Release
- Letzte Bugfixes und Polishing
- Auf JED veröffentlicht (2026-04-04)

## Erledigt (1.8.0-Release)

- [x] Staging-Test auf eiwtestzone (Joomla 6.1.1) – Update 1.7.2→1.8.0 erfolgreich
- [x] GitHub-Release v1.8.0 mit ZIP-Asset
- [x] `jwcalendar_update.xml` auf eiwtestzone hochgeladen (Übergang für 1.7.2-Nutzer)
- [x] Auto-Update-Erkennung verifiziert (Update wird angeboten & installiert)
- [x] Beide neuen Features im Frontend getestet (funktionieren)

## Offen

- [ ] **JED-Eintrag** auf Version 1.8.0 aktualisieren (nur Katalogpflege – technisch läuft alles)

## Links

- **GitHub:** https://github.com/IvanosHuberius/HL-Calendar
- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Update-XML (GitHub):** https://raw.githubusercontent.com/IvanosHuberius/HL-Calendar/main/update_server/jwcalendar_update.xml
- **Autor:** https://www.eiwtestzone.ch/huberlabs-extensions/

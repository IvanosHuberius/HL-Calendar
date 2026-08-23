# JW Calendar – Projektstatus

**Stand:** 2026-08-18 · **Aktuelle Version:** 1.9.1 · **GitHub-Release + Auto-Update: LIVE ✅**

---

## ✅ Fertig

- **1.9.1 veröffentlicht.** Enthält beides – 1.9.0 wurde übersprungen:
  - **Startdatum „nächster Termin"** (`start_date_mode` in Komponente **und** Modul: `Heute` / `Nächster Termin wenn Zeitraum leer` / `Immer nächster Termin`). Serverseitig über `EventService::resolveStartDate()` → FullCalendar `initialDate`, kein Flackern; Wiederholungen, Feiertags-Aussetzung und Ausnahmedaten werden berücksichtigt.
  - **Layoutfix für schmale Modulpositionen** über Container-Queries (`@container jwcal`, Stufen ≤600px/≤450px) – nur in `calendar.css`, gilt damit für Komponente + Modul.
  - Refactoring: Event-/Wiederholungs-/Feiertagslogik zentral in `site/src/Service/EventService.php`; neuer Endpunkt `api.getNextEventDate`.
- **Von Georg (GT Studio) getestet und freigegeben.**
- **GitHub:** Commits `520a48d` (Feature+Fix) und `9b9ba71` (Update-XML) auf `main` gepusht. Release **v1.9.1** mit Asset `pkg_jwcalendar_v1.9.1.zip` (90.9 KB) live.
- **Auto-Update aktiv:** Update-XML auf 1.9.1 umgestellt – **erst nach** dem Release, wie es sein muss. Kette Ende-zu-Ende geprüft: XML von GitHub raw → HTTP 200, `client=site`, `version=1.9.1`, Download-URL → HTTP 200.

## ➡️ Nächster Schritt

**JED-Eintrag auf 1.9.1 aktualisieren** (macht Ivan, Claude sagt an, was in welches Feld gehört):
https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/

- Version → `1.9.1`
- Download-URL → `https://github.com/IvanosHuberius/HL-Calendar/releases/download/v1.9.1/pkg_jwcalendar_v1.9.1.zip`
- „Extensions File" (das von JED gehostete ZIP) → `pkg_jwcalendar_v1.9.1.zip` neu hochladen
- Beschreibung/Changelog um die zwei Neuerungen ergänzen

## ⏳ Offen

- Nichts Kritisches. Bestehende Installationen bekommen 1.9.1 automatisch angeboten (Joomla prüft mit 6 h Cache).
- *(optional, niedrige Prio)* eiwtestzone-Update-XML – nur relevant, falls noch jemand auf 1.7.2 hängt.
- *(Aufräumen)* `pkg_jwcalendar_v1.9.0.zip` liegt noch lokal herum (nie veröffentlicht, bewusst nicht im Repo) – kann gelöscht werden.

---

## Links

- **GitHub:** https://github.com/IvanosHuberius/HL-Calendar
- **Release 1.9.1:** https://github.com/IvanosHuberius/HL-Calendar/releases/tag/v1.9.1
- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Update-XML (GitHub raw):** https://raw.githubusercontent.com/IvanosHuberius/HL-Calendar/main/update_server/jwcalendar_update.xml
- **Testseite:** https://www.eiwtestzone.ch (Modul-ID 110 = Kalender in der SP-Page-Builder-Seite `/sphlkalender`)
- **Tester:** Georg Gabitsinashvili (GT Studio), info@gt-max.com

## Changelog (kurz)

| Version | Inhalt |
|---------|--------|
| 1.9.1 | Startdatum „nächster Termin" + schmale Modulpositionen (Container-Queries); EventService-Refactoring |
| 1.8.3 | Popup-/Wiederholungs-Datum browserunabhängig (`CAL_NAMES` statt `toLocale…`) |
| 1.8.2 | Listenansicht-Datum, Höhe von Modul/Liste, CSS-Cache-Busting |
| 1.8.1 | Volle Lokalisierung, 9 Sprachen, RTL, browserunabhängige Datumsnamen |
| 1.8.0 | Termin-Darstellung wählbar, Feiertags-Aussetzung, Update-Server auf GitHub |
| 1.7.2 | Final Release, JED-Veröffentlichung (2026-04-04) |

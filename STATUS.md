# JW Calendar – Projektstatus

**Stand:** 2026-10-09 · **Aktuelle Version:** 1.10.3 · **VOLL RELEASED ✅** (GitHub + Auto-Update + JED)

---

## ✅ Fertig

- **1.10.3 veröffentlicht** – von Ivan und Georg getestet und freigegeben. Enthält:
  - **Formatierungsleiste im Frontend-Dialog** (`media/js/jweditor.js`, einmal geschrieben, von Komponente UND Modul genutzt): H1–H6, fett, kursiv, unterstrichen, durchgestrichen, Listen, Ausrichtung, Link mit „neuer Tab", Link entfernen, Formatierung entfernen, Quelltext-Umschalter. Einfügen aus Word wird bereinigt.
  - **HTML in Terminbeschreibungen** wird im Popup gerendert statt als Code gezeigt; serverseitig bereinigt (`EventService::cleanDescription()`, gegen 13 XSS-Varianten getestet).
  - Dialog schließt nicht mehr beim Klick daneben; Überschriften mit echter Größenabstufung; „Formatierung entfernen" räumt auch Überschriften/Ausrichtung/Links weg; Knöpfe zeigen Aktiv-Zustand; Live-Markierung hat Vorrang; Links verbindlich unterstrichen; Paketname „HL Kalender" in der Verwaltung (`.sys.ini`).
- **GitHub:** Commits `d076f79` (Code) und `43e08c0` (Update-XML) auf `main`. Release **v1.10.3** mit Asset `pkg_jwcalendar_v1.10.3.zip` (107'874 Bytes).
- **Geprüft:** SHA-256 der veröffentlichten ZIP = SHA-256 der getesteten ZIP auf eiwtestzone. Update-XML erst NACH dem Release umgestellt. Kette Ende-zu-Ende: XML von GitHub raw → `client=site`, `version=1.10.3`, Download → HTTP 200. `version_compare` bestätigt 1.10.3 > 1.9.1 (kein Text-Vergleichs-Problem beim Sprung 1.9 → 1.10).

## ➡️ Nächster Schritt

Nichts offen. JED zeigt öffentlich „HL Calendar (v1.10.3)", Download-Button zeigt auf das v1.10.3-Release (verifiziert 2026-10-09). Ivan bestätigt: Updates auf seinen Seiten haben funktioniert.

## ⏳ Offen

- Nichts Kritisches. Bestehende Installationen bekommen 1.10.3 automatisch angeboten (Joomla-Cache max. 6 h).
- *(Option)* JED-Download-URL dauerhaft auf `https://github.com/IvanosHuberius/HL-Calendar/releases/latest` umstellen, dann muss das Feld nie wieder angefasst werden.

---

## Links

- **GitHub:** https://github.com/IvanosHuberius/HL-Calendar
- **Release 1.10.3:** https://github.com/IvanosHuberius/HL-Calendar/releases/tag/v1.10.3
- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Update-XML (GitHub raw):** https://raw.githubusercontent.com/IvanosHuberius/HL-Calendar/main/update_server/jwcalendar_update.xml
- **Testseite:** https://www.eiwtestzone.ch (Modul-ID 110 = Kalender in der SP-Page-Builder-Seite `/sphlkalender`)
- **Tester:** Georg Gabitsinashvili (GT Studio), info@gt-max.com

## Changelog (kurz)

| Version | Inhalt |
|---------|--------|
| 1.10.3 | Formatierungsleiste im Frontend-Dialog (inkl. Quelltext-Umschalter); HTML in Beschreibungen; diverse Nachbesserungen |
| 1.9.2–1.10.2 | *(interne Testfassungen, in 1.10.3 aufgegangen)* |
| 1.9.1 | Startdatum „nächster Termin" + schmale Modulpositionen (Container-Queries); EventService-Refactoring |
| 1.8.3 | Popup-/Wiederholungs-Datum browserunabhängig (`CAL_NAMES` statt `toLocale…`) |
| 1.8.2 | Listenansicht-Datum, Höhe von Modul/Liste, CSS-Cache-Busting |
| 1.8.1 | Volle Lokalisierung, 9 Sprachen, RTL, browserunabhängige Datumsnamen |
| 1.8.0 | Termin-Darstellung wählbar, Feiertags-Aussetzung, Update-Server auf GitHub |
| 1.7.2 | Final Release, JED-Veröffentlichung (2026-04-04) |

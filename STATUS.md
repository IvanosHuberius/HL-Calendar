# JW Calendar – Projektstatus

**Stand:** 2026-08-18 · **Version in Arbeit:** 1.9.1 (gebaut + verifiziert) · **Live/released:** 1.8.3

---

## ✅ Fertig

- **1.8.3 voll ausgeliefert:** GitHub-Release + Asset live, JED auf 1.8.3, JED-Download zeigt aufs GitHub-Release, Auto-Update läuft über GitHub.
- **1.9.0 – Startdatum „nächster Termin"** (nur als Test an Georg, nie öffentlich released):
  Backend-Option `start_date_mode` in Komponente **und** Modul (`Heute` / `Nächster Termin wenn Zeitraum leer` / `Immer nächster Termin`), serverseitig über `EventService::resolveStartDate()` → FullCalendar `initialDate`. Refactoring: Event-/Wiederholungs-/Feiertagslogik von `ApiController` nach `site/src/Service/EventService.php`, neuer Endpunkt `api.getNextEventDate`. **Von Georg bestätigt: „works nice".**
- **1.9.1 – schmale Modulpositionen** (`pkg_jwcalendar_v1.9.1.zip`, 88.8 KB):
  Von Georg gemeldet: Modul in schmaler Position (Sidebar, 3:12) zerfleddert. Reproduziert bei 320px Modulbreite – Seitenleiste 260px, Kalender 60px; ohne Seitenleiste überlappten Toolbar-Buttons den Titel und Kalenderwochen die Tageszahlen.
  Fix rein in `calendar.css` über **Container-Queries** (`@container jwcal`), zwei Stufen ≤600px / ≤450px. Gilt automatisch für Komponente + Modul.
  **Live auf eiwtestzone verifiziert:** bei 320px sind Seitenleiste und Kalender je 100% breit gestapelt, Toolbar `column`, keine Überlappung, kein horizontaler Überlauf.

## ➡️ Nächster Schritt

1. **1.9.1 auf eiwtestzone installieren** und in einer schmalen Modulposition gegenprüfen (Sidebar links/rechts). Wichtig: Nach dem Update **Joomla-Cache leeren**; das CSS ist per `filemtime` versioniert, sollte also automatisch neu laden.
2. ZIP nach `www.eiwtestzone.ch/huberlabs-extensions/` hochladen und **Georg schicken** (Mail-Entwurf kann Claude liefern).
3. Nach Georgs OK releasen, in dieser Reihenfolge:
   a) GitHub-Release **v1.9.1** + Asset `pkg_jwcalendar_v1.9.1.zip` (kein `gh` CLI → `git credential fill` + curl auf die API)
   b) **erst danach** `update_server/jwcalendar_update.xml` auf 1.9.1 (Version + Download-URL), committen + pushen
   c) JED-Eintrag auf 1.9.1 (Version + Download-URL)

## ⏳ Offen

- `update_server/jwcalendar_update.xml` steht bewusst noch auf **1.8.3** – kein Update ausrollen, dessen Asset es noch nicht gibt (sonst 404).
- 1.9.0 wird **übersprungen**: der öffentliche Release ist 1.9.1 (enthält beides).
- *(optional, niedrige Prio)* eiwtestzone-Update-XML – nur relevant, falls noch jemand auf 1.7.2 hängt.

---

## Links

- **GitHub:** https://github.com/IvanosHuberius/HL-Calendar
- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Update-XML (GitHub raw):** https://raw.githubusercontent.com/IvanosHuberius/HL-Calendar/main/update_server/jwcalendar_update.xml
- **Testseite:** https://www.eiwtestzone.ch (Modul-ID 110 = Kalender in der SP-Page-Builder-Seite `/sphlkalender`)
- **Tester:** Georg Gabitsinashvili (GT Studio), info@gt-max.com

## Changelog (kurz)

| Version | Inhalt |
|---------|--------|
| 1.9.1 | Schmale Modulpositionen: Container-Queries statt Media-Queries |
| 1.9.0 | Startdatum „nächster Termin" (Komponente + Modul), EventService-Refactoring |
| 1.8.3 | Popup-/Wiederholungs-Datum browserunabhängig (`CAL_NAMES` statt `toLocale…`) |
| 1.8.2 | Listenansicht-Datum, Höhe von Modul/Liste, CSS-Cache-Busting |
| 1.8.1 | Volle Lokalisierung, 9 Sprachen, RTL, browserunabhängige Datumsnamen |
| 1.8.0 | Termin-Darstellung wählbar, Feiertags-Aussetzung, Update-Server auf GitHub |
| 1.7.2 | Final Release, JED-Veröffentlichung (2026-04-04) |

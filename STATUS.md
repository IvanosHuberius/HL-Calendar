# JW Calendar – Projektstatus

**Stand:** 2026-09-12 · **In Arbeit:** 1.10.3 (gebaut, Nachtest offen) · **Live/released:** 1.9.1

---

## ✅ Fertig

- **1.9.1 voll released** (GitHub-Release + Asset, Update-XML, JED-Eintrag inkl. Download-URL – alles öffentlich verifiziert). Auto-Update läuft.
- **1.9.2 fertig und von Ivan auf eiwtestzone bestätigt** (geht in 1.10.0 auf, nie separat released):
  - **HTML in Terminbeschreibungen** (von Georg gemeldet, 10.09.2026): Beschreibungen mit `<h6>`, `<p>`, Listen, Links wurden im Detail-Popup als roher Code angezeigt – Komponente **und** Modul nutzten `textContent`. Jetzt `innerHTML`.
  - **Serverseitige Bereinigung** in `EventService::cleanDescription()` (`InputFilter([], [], 1, 1)`), zentral für Basis-Termine und Wiederholungen. Zwingend nötig, weil Beschreibungen ungefiltert gespeichert werden – sonst wäre `innerHTML` eine XSS-Lücke.
  - **Neues CSS `.jw-popup-desc`**: Überschriften/Listen/Bilder im Popup gebändigt, max. 240px hoch, dann scrollbar. Nötig, weil `.jw-popup-row` ein Flex-Container ist.
  - **`pkg_jwcalendar.sys.ini` für alle 9 Sprachen** ergänzt → Paket heißt in der Erweiterungsverwaltung „HL Kalender" statt `PKG_JWCALENDAR` (war seit 23.08. als offener Kosmetikpunkt notiert).
- **Geprüft:** `php -l` auf allen geänderten Dateien sauber; Filter lokal gegen 13 Angriffsvarianten getestet (script, onerror/onload/onclick, javascript:- und data:-URLs, iframe/object/embed, verschachteltes `<scr<script>ipt>`) – kein ausführbarer Rest, normale Formatierung unverändert.

- **1.10.3 gebaut** (`pkg_jwcalendar_v1.10.3.zip`, 105.3 KB) – **Formatierungsleiste im Frontend-Dialog**:
  - Eigene Mini-Leiste in `media/js/jweditor.js`, **einmal geschrieben**, von Komponente und Modul genutzt.
  - Format (Normal/H1–H6), Fett, Kursiv, Unterstrichen, Durchgestrichen, Aufzählung, Nummerierung, Ausrichtung links/mitte/rechts, Link mit Wahl „gleicher/neuer Tab", Link entfernen, Formatierung entfernen.
  - **Quelltext-Umschalter** (`<>`): HTML weiterhin von Hand schreibbar – bestehende, handgeschriebene Beschreibungen bleiben voll nutzbar.
  - Einfügen aus Word/Web wird bereinigt; `javascript:`/`data:`-Links abgewiesen, fehlendes Schema wird zu `https://`.
  - 18 neue Sprachschlüssel in allen 9 Sprachen.
  - **Nachbesserungen 1.10.1 (von Ivan im Test gefunden):** Dialoge schließen nicht mehr beim Klick daneben (Eingaben blieben sonst auf der Strecke); h1–h6 haben jetzt echte Größenabstufung (waren alle gleich groß → Format-Wechsel wirkte folgenlos); das Format-Dropdown zeigt das Format an der Cursorposition statt immer „Normal".
  - **Nachbesserungen 1.10.2 (von Ivan im Test gefunden):** „Formatierung entfernen" ließ Überschriften stehen – der Browser-Befehl `removeFormat` räumt nur Inline-Formate weg. Der Knopf setzt jetzt zusätzlich den Block auf Normal, entfernt Ausrichtung und Links. Außerdem zeigen die Knöpfe ihren **Aktiv-Zustand**, damit „fett in einer Überschrift" (schon fett) nicht wie „passiert nichts" wirkt. Auf eiwtestzone mit echter Maus reproduziert und gegengeprüft.
  - **Markierungs-Logik gehärtet:** Die lebende Textmarkierung hat jetzt Vorrang vor der gemerkten. Ivan meldete sprunghaft „Formatierung geht nicht" nach dem Link-Einfügen; auf eiwtestzone in 8 Varianten (Komponente + Modul/SP Page Builder, Ziehen, Doppelklick, kompletter Ablauf von vorn) **nicht reproduzierbar** – dabei aber die Ursache gefunden: Lässt man beim Markieren die Maus außerhalb des Feldes los, erfährt der Editor nichts davon und arbeitet mit der alten Markierung weiter. Gezielt nachgebaut und behoben.
  - **Links verbindlich unterstrichen (1.10.3):** Im SP Page Builder waren Links nicht unterstrichen, in der normalen Modulposition schon – das Template setzt seitenweit `a { text-decoration: none }` und gewinnt gegen Bootstrap. Jetzt setzt der Kalender es selbst, damit es in jedem Template gleich aussieht (und Farbe allein einen Link nicht kennzeichnet, WCAG 1.4.1).
  - **Im Browser getestet:** 14 Strukturprüfungen, alle Toolbar-Befehle, Links mit/ohne neuem Tab, abgewehrter `javascript:`-Link, 5 Einfüge-Szenarien – alles bestanden.

## ➡️ Nächster Schritt

1. **1.10.3 auf eiwtestzone installieren und die Leiste ausprobieren** – Komponente **und** Modul: formatieren, Link mit neuem Tab, Quelltext-Umschalter, Speichern und wieder öffnen. Danach Joomla-Cache leeren.
2. ZIP nach `www.eiwtestzone.ch/huberlabs-extensions/` hochladen und **Georg schicken**.
3. Nach Georgs OK releasen: GitHub-Release v1.10.3 + Asset → **erst danach** Update-XML auf 1.10.3 → JED (Version, **Download-URL**, Extensions-File).

## ⏳ Offen

- `update_server/jwcalendar_update.xml` steht bewusst noch auf **1.9.1**.
- Die `.sys.ini`-Änderung wirkt erst nach einer Neuinstallation des Pakets – bei reinem Update kann der alte Name hängen bleiben (unkritisch).

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
| 1.10.3 | Formatierungsleiste im Frontend-Dialog (inkl. Quelltext-Umschalter); enthält 1.9.2 |
| 1.9.2 | *(nicht separat released)* HTML in Terminbeschreibungen; Paketname in der Verwaltung |
| 1.9.1 | Startdatum „nächster Termin" + schmale Modulpositionen (Container-Queries); EventService-Refactoring |
| 1.8.3 | Popup-/Wiederholungs-Datum browserunabhängig (`CAL_NAMES` statt `toLocale…`) |
| 1.8.2 | Listenansicht-Datum, Höhe von Modul/Liste, CSS-Cache-Busting |
| 1.8.1 | Volle Lokalisierung, 9 Sprachen, RTL, browserunabhängige Datumsnamen |
| 1.8.0 | Termin-Darstellung wählbar, Feiertags-Aussetzung, Update-Server auf GitHub |
| 1.7.2 | Final Release, JED-Veröffentlichung (2026-04-04) |

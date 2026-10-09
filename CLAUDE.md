# JW Calendar – Joomla 6 Extension

## Projekt-Kurzinfo
- **Name:** HL Kalender (JW Calendar)
- **Typ:** Joomla 6 Package (`pkg_jwcalendar`) mit Komponente + Modul
- **Version:** 1.10.3 (Formatierungsleiste im Frontend-Dialog; enthält auch 1.9.2 = HTML in Beschreibungen)
- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Autor:** huberlabs.ch (support@huberlabs.ch)
- **Lizenz:** GPLv2+

## ⚠️ Stolperfallen (vor dem Debuggen zuerst lesen!)

- **Datumsnamen NIE mit `toLocaleDateString/toLocaleString`** – hängt am Browser-`Intl`, das z.B. kein Georgisch kennt → Browsersprache. **Immer `CAL_NAMES`** (aus Joomla `Text::_`). Gilt für Grid, Listenansicht, Detail-Popup (`formatDateRange`/`formatRange`) UND Wiederholungs-Dropdown.
- **FullCalendar-Monats-/Wochentagsnamen kommen aus Browser-`Intl`, NICHT aus der Locale-Datei** – bei unbekannter Sprache falsch. Lösung: `CAL_NAMES` per `dayHeaderContent` (Wochentage) + Titel-Override in `datesSet` (Monat).
- **„Kein Update" trotz korrektem Server? Zuerst prüfen, ob das PAKET installiert ist** – `Erweiterungen → Verwalten → Typ „Paket"` → steht `PKG_JWCALENDAR` da? Sind nur Komponente + Modul installiert (kein Paket), findet Joomla zu `<element>pkg_jwcalendar</element>`/`<type>package</type>` keine Erweiterung → `extension_id=0` → Update wird ausgeblendet. Tückisch: Der Update-Server ist trotzdem vorhanden, aktiv und mit richtiger URL (die `<updateservers>` stehen auch in den Manifesten von Komponente und Modul) – es sieht also völlig gesund aus. Fix: Paket-ZIP einmal drüberinstallieren. (goldsfitness.ch + huberlabs-extensions, 23.08.2026.)
- **Update-XML MUSS `<client>site</client>` haben** – sonst nimmt Joomla client_id=1 (admin) an, Paket ist aber client_id=0 (site) → `extension_id=0` → Update-View blendet es aus („keine Updates" trotz gültiger XML).
- **CSS-Cache:** `calendar.css` mit `['version' => filemtime]` in `registerAndUseStyle` versionieren – sonst serviert der Browser nach Update altes CSS (Joomla-Mediaversion bustet nicht zuverlässig; Symptom: Fix wirkt erst nach Strg+F5).
- **`fcDayHeader()` muss bei `view.type` `list…` das volle Datum** liefern (nicht nur Wochentag), sonst fehlt in der Listenansicht das Datum.
- **Responsive für das MODUL geht NUR über `@container`, nicht `@media`** – `@media (max-width)` misst das Browser*fenster*. Ein Modul in einer schmalen Position (Sidebar, 3:12) behält auf einem breiten Desktop das Desktop-Layout: 260px Seitenleiste frisst den 320px-Kalender, Toolbar und Kalenderwochen überlappen. Lösung: `container-type: inline-size` + `container-name: jwcal` am `.jw-calendar-wrapper`, Regeln als `@container jwcal (max-width: …)`.
- **`@container`-Regeln greifen nur auf NACHFAHREN, nie auf den Container selbst** – `flex-direction` des Wrappers lässt sich darin nicht ändern. Deshalb hat der Wrapper `flex-wrap: wrap` und die Seitenleiste bekommt im Container-Query `flex: 0 0 100%` → der Hauptkalender rutscht in die nächste Zeile.
- **`container-type` macht das Element zum Bezugsrahmen für `position: fixed/absolute`** (`contain: layout`). Modal und Popup liegen deshalb bewusst AUSSERHALB des `.jw-calendar-wrapper` – beim Umbauen der Templates unbedingt so lassen.
- **`min-height: 80vh` nur an `.jw-fullpage:not(.jw-view-list)`** – nie am nackten Wrapper, sonst wird auch Modul + Listenansicht bildschirmhoch.
- **FullCalendar `locales-all` von `@fullcalendar/core@6.1.11/locales-all.global.min.js`** (NICHT `fullcalendar@…/locales-all` → 404).
- **`gh` CLI ist nicht installiert** – GitHub-Releases via `git credential fill` (Token) + `curl` auf die API erstellen.
- **Terminbeschreibungen sind Rich-Text (HTML) und werden per `innerHTML` ausgegeben** – das ist NUR erlaubt, weil `EventService::cleanDescription()` sie serverseitig filtert (`InputFilter([], [], 1, 1)`). Beschreibungen werden ungefiltert gespeichert (`'description' => 'raw'`), also gilt: **niemals `innerHTML` ohne diesen Filter**, sonst gespeichertes XSS – jeder, der Termine anlegen darf, könnte Skripte bei allen Besuchern ausführen.
- **`.jw-popup-row` ist ein Flex-Container** – die Beschreibung braucht zusätzlich `.jw-popup-desc` (display:block), sonst stehen `<h6>` und `<p>` nebeneinander statt untereinander.
- **Überschriften brauchen im CSS eine echte Größen-Abstufung** – `.jw-editor` und `.jw-popup-desc` hatten h1–h6 alle auf dieselbe Größe gesetzt. Folge: Der Format-Umschalter funktionierte einwandfrei (das HTML wechselte), sah aber aus wie „passiert nichts". Wer die Größen anfasst: h1–h6 müssen sichtbar verschieden bleiben.
- **Dialoge schließen NICHT beim Klick daneben** (weder Termin- noch Kategorie-Dialog, Komponente wie Modul) – nur X, Abbrechen, Speichern und Esc. Ein Fehlklick hatte vorher alles Eingetippte verworfen. Nicht „aus Versehen" wieder einbauen.
- **`execCommand('removeFormat')` räumt NUR Inline-Formate weg** – Überschriften und Ausrichtung bleiben stehen. Das erwartet niemand. Der „Formatierung entfernen"-Knopf (`jwClear`) macht deshalb selbst: removeFormat + unlink + `formatBlock <p>` + `text-align`/`align` von den betroffenen Blöcken abräumen (Blöcke **nach** dem `formatBlock` neu ermitteln, weil es das alte `style`-Attribut mitnimmt).
- **Links in Beschreibung/Editor werden explizit unterstrichen** (`.jw-popup-desc a`, `.jw-editor a`) – viele Templates setzen seitenweit `a { text-decoration: none }` (auf eiwtestzone gewinnt `template.css` gegen Bootstrap). Ohne eigene Regel sieht es je nach Template anders aus, und Farbe allein darf einen Link nicht kennzeichnen (WCAG 1.4.1). Kein `!important` nötig, unsere Selektoren sind spezifischer.
- **Die LIVE-Markierung hat im Editor immer Vorrang** vor der gemerkten (`savedRange`) – letztere ist nur Notnagel, wenn der Fokus den Editor wirklich verlassen hat (Linkleiste). Sonst passiert Folgendes: Lässt man beim Markieren die Maus **außerhalb** des Feldes los, feuert kein `mouseup` im Editor, die alte gemerkte Markierung bleibt stehen und wird beim nächsten Klick über die gute lebende gelegt → der Befehl läuft ins Leere („passiert nichts"). `savedRange` wird zudem als `cloneRange()` abgelegt, nicht als lebender Range.
- **Toolbar-Knöpfe zeigen ihren Zustand** (`.jw-ed-on` via `queryCommandState`) – ohne das wirkt „fett" innerhalb einer Überschrift (schon fett) wie „passiert nichts", und man sucht den Fehler im falschen Code.
- **Der Frontend-Editor liegt EINMAL in `media/js/jweditor.js`** – nicht inline in die Templates kopieren. Beide Templates laden ihn aus `media/com_calendar/js/` und rufen nur `JwEditor.init/getHtml/setHtml`. Das Beschreibungsfeld ist kein `<textarea>` mehr, sondern ein `contenteditable` → **niemals `.value` verwenden**, immer `JwEditor.getHtml()/setHtml()`.
- **Was der Editor erzeugt, muss der Server-Filter durchlassen** – `JwEditor` (Whitelist beim Einfügen) und `EventService::cleanDescription()` (InputFilter) müssen zusammenpassen. Wer der Leiste einen Knopf hinzufügt, prüft vorher, ob der Tag den Filter überlebt, sonst verschwindet die Formatierung beim Speichern.
- **Immer Komponente UND Modul synchron ändern** – jeder Fix muss in beide `default.php`.
- **Event-/Wiederholungslogik NUR in `EventService`** (`site/src/Service/EventService.php`) – `ApiController` delegiert bloß. Wer Wiederholungen/Feiertage im Controller ändert, bringt Kalenderanzeige und „nächster Termin" auseinander.
- **Modul benutzt eine Komponenten-Klasse** (`EventService`) – immer mit `class_exists()` + `try/catch` absichern, sonst White Screen, wenn jemand nur das Modul aktualisiert hat.
- **JED hat ein eigenes Feld „Download URL" – bei jedem Release mitziehen!** Es enthält die Versionsnummer im Pfad (`…/releases/download/v<ver>/pkg_jwcalendar_v<ver>.zip`) und wird beim Versions-Update NICHT automatisch mitgeändert. Symptom: JED zeigt die neue Version, der Download-Button liefert aber die alte. Dauerhafte Alternative: `https://github.com/IvanosHuberius/HL-Calendar/releases/latest` eintragen (immer neuestes Release, nie wieder anfassen).
- **Update-XML erst NACH dem GitHub-Release auf die neue Version setzen** – sonst bietet Joomla ein Update an, dessen Download-Asset noch gar nicht existiert (404 beim Installieren).
- **PHP-CLI liegt unter `C:\Users\THE BEAST II\php\php.exe`** (nicht im PATH!) – nach jeder PHP-Änderung `& "C:\Users\THE BEAST II\php\php.exe" -l <datei>` laufen lassen.

## Technik
- **Frontend:** FullCalendar.js 6.1.11 (via CDN jsdelivr)
- **Backend:** PHP 8.1+, MySQL, Joomla 6 MVC
- **Namespace:** `Jewe\Component\Calendar` (Komponente), `Jewe\Module\JwCalendar` (Modul)
- **Sprachen:** de-DE, en-GB, ka-GE, it-IT, es-ES, fr-FR, pt-PT, ar-AA, ru-RU (Englisch = Fallback). Ab 1.8.1.
- **Plattform:** Joomla 5 & 6

## Ordnerstruktur

```
Kalender/
  pkg_jwcalendar.xml          # Package-Manifest
  pkg_jwcalendar_v1.7.2.zip   # Fertiges Installationspaket
  build.ps1                   # Build-Script (PowerShell)
  verify.ps1                  # Verifikations-Script
  CLAUDE.md                   # Diese Datei
  STATUS.md                   # Projektstatus
  packages/
    com_calendar.zip           # Komponente (gepackt)
    mod_jwcalendar.zip         # Modul (gepackt)
  temp/
    com_calendar/              # Komponente (Quellcode)
    mod_jwcalendar/            # Modul (Quellcode)
  language/                    # Package-Sprachdateien
  docs/                        # PDF-Dokumentation (DE + EN)
  update_server/               # Update-XML fuer JED
  Backup/                      # Aeltere Versionen
```

## Wichtige Quelldateien (unter temp/)

| Datei | Beschreibung |
|-------|-------------|
| `com_calendar/site/tmpl/calendar/default.php` | Frontend-Template (Hauptansicht) |
| `com_calendar/site/src/Controller/ApiController.php` | API-Controller (AJAX-Endpoints, delegiert an EventService) |
| `com_calendar/site/src/Service/EventService.php` | **Einzige** Quelle für Event-Abfrage, Wiederholungen, Feiertags-/Ausnahme-Logik, „nächster Termin" |
| `com_calendar/media/css/calendar.css` | Haupt-CSS |
| `com_calendar/administrator/config.xml` | Backend-Konfiguration |
| `com_calendar/administrator/sql/install.mysql.sql` | DB-Schema |
| `mod_jwcalendar/tmpl/default.php` | Modul-Template |
| `mod_jwcalendar/mod_jwcalendar.php` | Modul Entry-Point |

## API Endpoints

Alle Aufrufe via `index.php?option=com_calendar&task=api.<method>`:

| Endpoint | Beschreibung |
|----------|-------------|
| `api.getEvents` | Events laden (Zeitraum-Filter) |
| `api.getNextEventDate` | Datum des nächsten anstehenden Termins (`{"date":"YYYY-MM-DD"|null}`) |
| `api.saveEvent` | Event erstellen/aktualisieren |
| `api.deleteEvent` | Event loeschen |
| `api.moveEvent` | Event verschieben/resizen (Drag & Drop) |
| `api.getCategories` | Kategorien laden |
| `api.saveCategory` | Kategorie erstellen (Super User) |
| `api.deleteCategory` | Kategorie loeschen (Super User) |

## DB-Tabellen

- `#__calendar_events` – Events (title, description, location, start/end_date, all_day, color, category_id, recurrence_*, **skip_holidays, holiday_country, holiday_subdivision, exception_dates**, reminder_minutes, state, access, created_by, params)
- `#__calendar_categories` – Kategorien (title, color, description, state, access, ordering)
- `#__calendar_holidays` – Feiertags-Cache (country, subdivision, hyear, dates, fetched) – ab 1.8.0
- 5 Default-Kategorien: Allgemein, Arbeit, Persoenlich, Familie, Feiertage
- Schema-Updates: `administrator/sql/updates/mysql/<version>.sql` (z.B. `1.8.0.sql`)

## Features ab 1.8.0

- **Termin-Darstellung** (`event_display_style` in config.xml): Punkt klein/mittel/groß oder Balken. CSS-Klasse `jw-evtstyle-*` am Wrapper, `eventDisplay:'block'` für Balken, `pickTextColor()` für Kontrast.
- **Feiertags-Aussetzung:** `HolidayService` (`site/src/Service/HolidayService.php`) holt gesetzliche Feiertage von der OpenHolidays-API und cached sie in `#__calendar_holidays`. `ApiController::isExcludedDate()` prüft pro Wiederholungs-Termin (Feiertag ODER manuelle Ausnahme). Land/Bundesland-Daten liegen als `HOLIDAY_COUNTRIES` im Frontend-JS (Komponente + Modul).

## Build-Prozess

```powershell
# Packages bauen (aus temp/ Quellcode)
powershell -File build.ps1

# ZIP-Inhalt pruefen
powershell -File verify.ps1
```

Der Build erstellt drei ZIPs: `com_calendar.zip`, `mod_jwcalendar.zip` und `pkg_jwcalendar_v<version>.zip` (aktuell `_v1.8.0.zip`).

## Wichtige Regeln

1. **Immer beide Versionen pruefen:** Bei jedem Fix/Change muessen Komponente UND Modul synchron gehalten werden
2. **Quellcode liegt unter `temp/`:** Nie direkt die ZIPs bearbeiten, sondern die Quelldateien unter `temp/com_calendar/` und `temp/mod_jwcalendar/`
3. **Nach Aenderungen neu bauen:** `build.ps1` ausfuehren um die Packages zu aktualisieren
4. **CSS Custom Properties:** Farben werden als `--jw-primary` etc. in einem `<style>`-Block injiziert
5. **Modul liest Komponenten-Config:** via `ComponentHelper::getParams('com_calendar')`
6. **FullCalendar via CDN:** Nicht lokal einbetten, wird von jsdelivr geladen

## Update-Server / Auto-Update

- **Pflicht in der Update-XML:** `<client>site</client>` im `<update>`-Block! Ohne `<client>` nimmt Joomla `client_id=1` (administrator) an; das Paket `pkg_jwcalendar` hat aber `client_id=0` (site) → keine Zuordnung → Update wird mit `extension_id=0` gespeichert und in der Update-Liste **ausgeblendet** ("keine Updates" trotz gültiger, erreichbarer XML).
- Update-XML liegt unter `update_server/jwcalendar_update.xml` (GitHub-Raw = produktive Quelle; bei Versionswechsel `<version>` + Download-URL anpassen).
- Download = GitHub-Release-Asset `pkg_jwcalendar_v<version>.zip`. Übergang für Alt-Installs: XML zusätzlich auf eiwtestzone `updates/` hochladen.
- Debug bei "kein Update sichtbar": DB prüfen – `#__updates` (extension_id/client_id), `#__update_sites` (enabled/last_check_timestamp), `#__update_sites_extensions`. Update-View filtert `extension_id <> 0`.

## Lokalisierung (ab 1.8.1)

- **FullCalendar holt Monats-/Wochentagsnamen aus dem nativen `Intl` des BROWSERS**, NICHT aus seiner Locale-Datei (die liefert nur Buttons/Wocheneinstellungen). Kennt der Browser die Seitensprache nicht (z.B. Georgisch: `Intl.supportedLocalesOf(['ka'])` = `[]`), fällt er auf die **Browsersprache** zurück → falsche Sprache.
- **Lösung (browserunabhängig):** Monats-/Wochentagsnamen serverseitig aus Joomla holen (`Text::_('JUNE')`, `Text::_('MONDAY')`, `Text::_('MON')` … Standard-Joomla-Datumskonstanten) → als `CAL_NAMES` ins JS → per `dayHeaderContent` (Wochentage) + Titel-Override in `datesSet` (Monat) rendern. In `default.php` von Komponente + Modul (Haupt- + Mini-Kalender).
- `locales-all`-CDN für FullCalendar: `https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales-all.global.min.js` (registriert `globalLocales`; liefert lokalisierte Button-Texte/RTL). NICHT `fullcalendar@.../locales-all` (404).
- Dialog-/JS-Texte als `Text::_()`-Schlüssel im `L`-Objekt; Sprachdateien unter `site/language/<lang>/` (Komponente) und `language/<lang>/` (Modul). RTL via `$lang->isRtl()` → `dir="rtl"` am Wrapper.

## Feature 1.10.0/1.10.1 – Formatierungsleiste im Frontend-Dialog

- Im Frontend war die Beschreibung ein nacktes `<textarea>`; formatieren ging nur, wenn man HTML von Hand tippte. Im **Backend** gab es die Leiste längst (`event.xml`: `type="editor" filter="safehtml"`).
- Neu: eigene, abhängigkeitsfreie Mini-Leiste in `media/js/jweditor.js` (von Komponente UND Modul genutzt): Format (Normal/H1–H6), Fett, Kursiv, Unterstrichen, Durchgestrichen, Aufzählung, Nummerierung, Ausrichtung links/mitte/rechts, Link (mit „in neuem Tab" → `target="_blank" rel="noopener noreferrer"`), Link entfernen, Formatierung entfernen, **Quelltext-Umschalter** (`<>`) zum Tippen von reinem HTML.
- Beim Einfügen aus Word/Web wird clientseitig auf die erlaubten Tags reduziert (`mso-`-Müll, `<style>`, Skripte, `on*` raus); `javascript:`/`data:`-URLs werden abgewiesen, Adressen ohne Schema bekommen `https://`.
- Im Browser getestet: 14 Strukturprüfungen, alle Toolbar-Befehle, Link mit/ohne neuem Tab, abgewehrter `javascript:`-Link, 5 Einfüge-Szenarien.
- 18 neue Sprachschlüssel (`*_ED_*`) in allen 9 Sprachen, „Abbrechen" über Joomlas `JCANCEL`.
- **1.10.2 (von Ivan im Test gefunden):** Zusätzlich gehärtet: die lebende Textmarkierung hat jetzt Vorrang (siehe Stolperfalle) – erklärt sprunghafte „Formatierung geht nicht"-Fälle, die sich nicht reproduzieren ließen. „Formatierung entfernen" ließ Überschriften stehen (siehe Stolperfalle) – räumt jetzt auch Blockformat, Ausrichtung und Links weg. Zusätzlich zeigen Fett/Kursiv/Unterstrichen/Durchgestrichen/Listen/Ausrichtung ihren Aktiv-Zustand.
- **1.10.1 (von Ivan im Test gefunden):** (1) Dialoge schlossen beim Klick daneben → Eingaben weg; Overlay-Klick entfernt. (2) h1–h6 waren im CSS alle gleich groß → Format-Wechsel wirkte folgenlos; echte Abstufung (Editor 24/20/17/15/14/13px, Popup 19/17/15/14/13/12px). (3) Das Format-Dropdown sprang nach jeder Wahl auf „Normal" zurück; es spiegelt jetzt das Format an der Cursorposition.

## Fix 1.9.2 – HTML in Terminbeschreibungen

- Beschreibungen mit Markup (`<h6>`, `<p>`, Listen, Links) wurden im Detail-Popup als **roher Code** angezeigt – Komponente wie Modul nutzten `textContent`. (Von Georg gemeldet, 10.09.2026.)
- Fix: Ausgabe per `innerHTML`, dazu **serverseitige Bereinigung** in `EventService::cleanDescription()` (zentral, gilt für Basis-Termine UND Wiederholungen). Neues CSS `.jw-popup-desc` bändigt Überschriften/Listen/Bilder im schmalen Popup (max. 240px Höhe, dann scrollbar).
- Filter mit 13 Angriffsvarianten lokal getestet (script, onerror/onload/onclick, javascript:- und data:-URLs, iframe/object/embed, verschachteltes `<scr<script>ipt>`): kein ausführbarer Rest, normale Formatierung unverändert erhalten.
- Ebenfalls in 1.9.2: `pkg_jwcalendar.sys.ini` für alle 9 Sprachen ergänzt – das Paket heißt in der Erweiterungsverwaltung jetzt „HL Kalender" statt `PKG_JWCALENDAR`.

## Fix 1.9.1 – schmale Modulpositionen

- Das Modul in schmalen Positionen (Sidebar, 3:12) war unbrauchbar: bei 320px Modulbreite belegte die Seitenleiste 260px, der Kalender bekam 60px. Ohne Seitenleiste überlappten Toolbar-Buttons den Titel und die Kalenderwochen die Tageszahlen. (Von Georg gemeldet, 17.08.2026.)
- Fix rein in `calendar.css` (gilt damit automatisch für Komponente **und** Modul): Container-Queries `@container jwcal`. Zwei Stufen – **≤600px**: Seitenleiste stapelt über den Kalender, Mini-Kalender aus, Toolbar untereinander. **≤450px**: kompakte Buttons/Schriften, Kalenderwochen ausgeblendet (sie liegen absolut positioniert über den Tageszahlen).
- Verifiziert auf eiwtestzone bei 320px: Seitenleiste und Kalender je 100% Breite gestapelt, Toolbar `column`, keine Überlappung, kein horizontaler Überlauf.

## Features ab 1.9.0

- **Startdatum „nächster Termin"** (`start_date_mode`, Komponente **und** Modul – je eigene Einstellung, Default `today`): `today` | `next_event_if_empty` | `next_event`. Zweck: Bei seltenen Terminen sieht der Besucher sonst einen leeren aktuellen Monat (Wunsch von Georg, 10.08.2026).
- Ermittlung **serverseitig** in `default.php` → `EventService::resolveStartDate($user, $mode, $view, $firstDay)` → als `initialDate` an FullCalendar (Haupt- **und** Mini-Kalender). Kein zweiter HTTP-Request, kein Flackern.
- `next_event_if_empty` prüft den sichtbaren Zeitraum der Startansicht (Tag/Woche/Monat/Jahr, abhängig von `default_view` + `first_day`) und springt nur, wenn dort **gar nichts** liegt – auch vergangene Termine zählen als „nicht leer".
- Suchfenster für den nächsten Termin: 24 Monate ab heute; nichts gefunden → kein Sprung (bleibt auf heute).
- **Refactoring:** Event-Abfrage + Wiederholungen + Feiertags-/Ausnahme-Logik sind aus `ApiController` nach `EventService` gewandert (`buildEvents`, `getNextEventDate`, `hasEventsInRange`, `resolveStartDate`). Der Controller ist nur noch dünne JSON-Hülle.

## Lessons Learned

- Joomla 6 Module brauchen immer `mod_modulname.php` Entry-Point mit `module="mod_modulname"` im Manifest
- Column Alias: `$this->setColumnAlias('published', 'state')` im Table-Konstruktor noetig
- `access.xml` ist Pflicht wenn `config.xml` ein `rules`-Feld enthaelt
- `form.validate` Script via WebAssetManager laden, sonst TypeError
- `control="hue"` auf Color-Feldern ergibt visuellen Farb-Spektrum-Picker

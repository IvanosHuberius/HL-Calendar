# JW Calendar – Joomla 6 Extension

## Projekt-Kurzinfo
- **Name:** HL Kalender (JW Calendar)
- **Typ:** Joomla 6 Package (`pkg_jwcalendar`) mit Komponente + Modul
- **Version:** 1.8.0 (live & ausgeliefert; Vorgänger 1.7.2)
- **JED:** https://extensions.joomla.org/extension/calendars-a-events/hl-calendar/
- **Autor:** huberlabs.ch (support@huberlabs.ch)
- **Lizenz:** GPLv2+

## Technik
- **Frontend:** FullCalendar.js 6.1.11 (via CDN jsdelivr)
- **Backend:** PHP 8.1+, MySQL, Joomla 6 MVC
- **Namespace:** `Jewe\Component\Calendar` (Komponente), `Jewe\Module\JwCalendar` (Modul)
- **Sprachen:** Deutsch (de-DE), Englisch (en-GB)
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
| `com_calendar/site/src/Controller/ApiController.php` | API-Controller (AJAX-Endpoints) |
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

## Lessons Learned

- Joomla 6 Module brauchen immer `mod_modulname.php` Entry-Point mit `module="mod_modulname"` im Manifest
- Column Alias: `$this->setColumnAlias('published', 'state')` im Table-Konstruktor noetig
- `access.xml` ist Pflicht wenn `config.xml` ein `rules`-Feld enthaelt
- `form.validate` Script via WebAssetManager laden, sonst TypeError
- `control="hue"` auf Color-Feldern ergibt visuellen Farb-Spektrum-Picker

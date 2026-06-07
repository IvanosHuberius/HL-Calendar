#!/usr/bin/env python3
"""Generate German PDF documentation for HL Kalender v1.7.2"""

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm, mm
from reportlab.lib.colors import HexColor, black, white, Color
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    KeepTogether, HRFlowable
)
from reportlab.platypus.tableofcontents import TableOfContents
from reportlab.lib import colors
import os

OUTPUT = os.path.join(os.path.dirname(__file__), "HL_Kalender_Dokumentation_DE.pdf")

# Colors
PRIMARY = HexColor("#1a73e8")
PRIMARY_DARK = HexColor("#1557b0")
DARK_TEXT = HexColor("#202124")
GRAY_TEXT = HexColor("#5f6368")
LIGHT_BG = HexColor("#f8f9fa")
BORDER = HexColor("#dadce0")
WHITE = white

def build_styles():
    styles = getSampleStyleSheet()

    styles.add(ParagraphStyle(
        'CoverTitle', parent=styles['Title'],
        fontSize=32, leading=40, textColor=PRIMARY,
        spaceAfter=10, alignment=TA_CENTER,
        fontName='Helvetica-Bold'
    ))
    styles.add(ParagraphStyle(
        'CoverSubtitle', parent=styles['Normal'],
        fontSize=16, leading=22, textColor=GRAY_TEXT,
        spaceAfter=6, alignment=TA_CENTER,
        fontName='Helvetica'
    ))
    styles.add(ParagraphStyle(
        'ChapterTitle', parent=styles['Heading1'],
        fontSize=22, leading=28, textColor=PRIMARY,
        spaceBefore=20, spaceAfter=14,
        fontName='Helvetica-Bold',
        borderWidth=0, borderPadding=0,
    ))
    styles.add(ParagraphStyle(
        'SectionTitle', parent=styles['Heading2'],
        fontSize=15, leading=20, textColor=PRIMARY_DARK,
        spaceBefore=14, spaceAfter=8,
        fontName='Helvetica-Bold'
    ))
    styles.add(ParagraphStyle(
        'BodyText2', parent=styles['Normal'],
        fontSize=10.5, leading=15, textColor=DARK_TEXT,
        spaceAfter=8, alignment=TA_JUSTIFY,
        fontName='Helvetica'
    ))
    styles.add(ParagraphStyle(
        'BulletItem', parent=styles['Normal'],
        fontSize=10.5, leading=15, textColor=DARK_TEXT,
        spaceAfter=4, leftIndent=20, bulletIndent=8,
        fontName='Helvetica'
    ))
    styles.add(ParagraphStyle(
        'Note', parent=styles['Normal'],
        fontSize=10, leading=14, textColor=GRAY_TEXT,
        spaceAfter=8, leftIndent=12,
        fontName='Helvetica-Oblique',
        borderWidth=0, borderPadding=6,
        backColor=LIGHT_BG,
    ))
    styles.add(ParagraphStyle(
        'TableHeader', parent=styles['Normal'],
        fontSize=10, leading=13, textColor=white,
        fontName='Helvetica-Bold', alignment=TA_CENTER
    ))
    styles.add(ParagraphStyle(
        'TableCell', parent=styles['Normal'],
        fontSize=9.5, leading=13, textColor=DARK_TEXT,
        fontName='Helvetica'
    ))
    styles.add(ParagraphStyle(
        'Footer', parent=styles['Normal'],
        fontSize=8, leading=10, textColor=GRAY_TEXT,
        fontName='Helvetica', alignment=TA_CENTER
    ))
    return styles

def make_table(headers, rows, col_widths=None, styles_obj=None):
    s = styles_obj
    data = [[Paragraph(h, s['TableHeader']) for h in headers]]
    for row in rows:
        data.append([Paragraph(str(c), s['TableCell']) for c in row])

    if col_widths is None:
        col_widths = [None] * len(headers)

    t = Table(data, colWidths=col_widths, repeatRows=1)
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), PRIMARY),
        ('TEXTCOLOR', (0, 0), (-1, 0), white),
        ('ALIGN', (0, 0), (-1, 0), 'CENTER'),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, 0), 10),
        ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
        ('TOPPADDING', (0, 0), (-1, 0), 8),
        ('BACKGROUND', (0, 1), (-1, -1), white),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [white, LIGHT_BG]),
        ('TEXTCOLOR', (0, 1), (-1, -1), DARK_TEXT),
        ('FONTNAME', (0, 1), (-1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 1), (-1, -1), 9.5),
        ('TOPPADDING', (0, 1), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 1), (-1, -1), 5),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
        ('GRID', (0, 0), (-1, -1), 0.5, BORDER),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    return t

def hr():
    return HRFlowable(width="100%", thickness=1, color=BORDER, spaceAfter=10, spaceBefore=4)

def add_page_number(canvas, doc):
    canvas.saveState()
    canvas.setFont('Helvetica', 8)
    canvas.setFillColor(GRAY_TEXT)
    canvas.drawCentredString(A4[0] / 2, 20 * mm, f"HL Kalender v1.7.2 — Dokumentation  |  Seite {doc.page}")
    canvas.restoreState()

def build():
    doc = SimpleDocTemplate(
        OUTPUT, pagesize=A4,
        topMargin=2.2 * cm, bottomMargin=2.5 * cm,
        leftMargin=2.2 * cm, rightMargin=2.2 * cm,
        title="HL Kalender v1.7.2 — Dokumentation",
        author="huberlabs.ch",
        subject="Joomla 6 Kalender-Extension"
    )
    s = build_styles()
    story = []

    # ── COVER PAGE ──
    story.append(Spacer(1, 4 * cm))
    story.append(Paragraph("HL Kalender", s['CoverTitle']))
    story.append(Paragraph("Joomla 6 Kalender-Extension", s['CoverSubtitle']))
    story.append(Spacer(1, 1 * cm))
    story.append(HRFlowable(width="40%", thickness=2, color=PRIMARY, spaceAfter=12, spaceBefore=12))
    story.append(Paragraph("Version 1.7.2", s['CoverSubtitle']))
    story.append(Spacer(1, 2 * cm))
    story.append(Paragraph("Benutzerhandbuch", s['CoverSubtitle']))
    story.append(Spacer(1, 3 * cm))

    cover_info = [
        ["Autor:", "huberlabs.ch"],
        ["Lizenz:", "GNU/GPL v2 or later"],
        ["Website:", "www.eiwtestzone.ch/huberlabs-extensions"],
        ["Support:", "support@huberlabs.ch"],
        ["Datum:", "Maerz 2026"],
    ]
    ct = Table(cover_info, colWidths=[4 * cm, 10 * cm])
    ct.setStyle(TableStyle([
        ('FONTNAME', (0, 0), (0, -1), 'Helvetica-Bold'),
        ('FONTNAME', (1, 0), (1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 0), (-1, -1), 11),
        ('TEXTCOLOR', (0, 0), (-1, -1), GRAY_TEXT),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
    ]))
    story.append(ct)
    story.append(PageBreak())

    # ── INHALTSVERZEICHNIS ──
    story.append(Paragraph("Inhaltsverzeichnis", s['ChapterTitle']))
    story.append(hr())
    toc_items = [
        ("1", "Ueberblick"),
        ("2", "Systemanforderungen"),
        ("3", "Installation"),
        ("4", "Konfiguration"),
        ("5", "Kalender-Ansichten"),
        ("6", "Events verwalten"),
        ("7", "Wiederkehrende Events"),
        ("8", "Kategorien"),
        ("9", "Berechtigungen"),
        ("10", "Modul-Konfiguration"),
        ("11", "Update-Server"),
        ("12", "FAQ / Fehlerbehebung"),
    ]
    for num, title in toc_items:
        story.append(Paragraph(f"<b>{num}.</b>&nbsp;&nbsp;&nbsp;{title}", s['BodyText2']))
    story.append(PageBreak())

    # ── KAPITEL 1: ÜBERBLICK ──
    story.append(Paragraph("1. Ueberblick", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Der <b>HL Kalender</b> ist eine moderne, Google Calendar-aehnliche Kalender-Extension fuer "
        "Joomla 6. Er bietet eine intuitive Benutzeroberflaeche mit Drag &amp; Drop, farbigen Kategorien, "
        "wiederkehrenden Terminen und vollstaendiger Frontend-Bearbeitung.",
        s['BodyText2']
    ))
    story.append(Paragraph("<b>Hauptmerkmale:</b>", s['BodyText2']))
    features = [
        "Google Calendar-aehnliches Design und Bedienung",
        "4 Ansichten: Monat, Woche, Tag, Liste",
        "Drag &amp; Drop zum Verschieben und Aendern der Dauer von Events",
        "Wiederkehrende Events (taeglich, woechentlich, monatlich, jaehrlich, benutzerdefiniert)",
        "Farbige Kategorien mit 16-Farben-Palette",
        "Vollstaendige Frontend-Bearbeitung (AJAX-basiert)",
        "Responsive Design fuer alle Geraete",
        "Joomla ACL-Berechtigungssystem",
        "Deutsch und Englisch vollstaendig lokalisiert",
        "Komponente + Modul im Paket",
    ]
    for f in features:
        story.append(Paragraph(f"&bull; {f}", s['BulletItem']))
    story.append(PageBreak())

    # ── KAPITEL 2: SYSTEMANFORDERUNGEN ──
    story.append(Paragraph("2. Systemanforderungen", s['ChapterTitle']))
    story.append(hr())
    story.append(make_table(
        ["Anforderung", "Minimum"],
        [
            ["Joomla", "6.x"],
            ["PHP", "8.0 oder hoeher"],
            ["MySQL", "5.7+ (UTF-8 MB4)"],
            ["Browser", "Moderner Browser mit JavaScript (Chrome, Firefox, Safari, Edge)"],
        ],
        col_widths=[5 * cm, 11 * cm],
        styles_obj=s
    ))
    story.append(Spacer(1, 0.5 * cm))
    story.append(Paragraph(
        "<i>Hinweis: FullCalendar.js 6.1.11 wird automatisch via CDN (jsDelivr) geladen. "
        "Eine Internetverbindung ist fuer die Frontend-Darstellung erforderlich.</i>",
        s['Note']
    ))
    story.append(PageBreak())

    # ── KAPITEL 3: INSTALLATION ──
    story.append(Paragraph("3. Installation", s['ChapterTitle']))
    story.append(hr())

    story.append(Paragraph("3.1 Paket installieren", s['SectionTitle']))
    steps = [
        "Laden Sie <b>pkg_jwcalendar_v1.7.2.zip</b> herunter.",
        "Melden Sie sich im Joomla-Administrationsbereich an.",
        "Navigieren Sie zu <b>System &gt; Installieren &gt; Erweiterungen</b>.",
        "Waehlen Sie die ZIP-Datei aus und klicken Sie auf <b>Hochladen &amp; Installieren</b>.",
        "Das System erstellt automatisch die Datenbanktabellen und 5 Standard-Kategorien.",
    ]
    for i, step in enumerate(steps, 1):
        story.append(Paragraph(f"<b>{i}.</b> {step}", s['BodyText2']))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("3.2 Menuepunkt erstellen", s['SectionTitle']))
    steps2 = [
        "Gehen Sie zu <b>Menues &gt; [Ihr Menue] &gt; Neuer Menuepunkt</b>.",
        "Waehlen Sie als Typ: <b>HL Kalender &gt; Kalender</b>.",
        "Geben Sie einen Titel ein (z.B. \"Kalender\").",
        "Speichern Sie den Menuepunkt.",
    ]
    for i, step in enumerate(steps2, 1):
        story.append(Paragraph(f"<b>{i}.</b> {step}", s['BodyText2']))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("3.3 Modul einrichten (optional)", s['SectionTitle']))
    story.append(Paragraph(
        "Das Paket enthaelt auch ein Kalender-Modul, das Sie in beliebigen Modulpositionen platzieren koennen:",
        s['BodyText2']
    ))
    steps3 = [
        "Navigieren Sie zu <b>Inhalt &gt; Site Module</b>.",
        "Klicken Sie auf <b>Neu</b> und waehlen Sie <b>HL Kalender</b>.",
        "Konfigurieren Sie Position, Ansicht und Kategorie-Filter.",
        "Veroeffentlichen und speichern Sie das Modul.",
    ]
    for i, step in enumerate(steps3, 1):
        story.append(Paragraph(f"<b>{i}.</b> {step}", s['BodyText2']))
    story.append(PageBreak())

    # ── KAPITEL 4: KONFIGURATION ──
    story.append(Paragraph("4. Konfiguration", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Die Konfiguration finden Sie unter <b>Komponenten &gt; HL Kalender &gt; Optionen</b>.",
        s['BodyText2']
    ))

    story.append(Paragraph("4.1 Farbeinstellungen", s['SectionTitle']))
    story.append(Paragraph(
        "Der HL Kalender bietet 9 anpassbare Farben, die das gesamte Erscheinungsbild steuern:",
        s['BodyText2']
    ))
    story.append(make_table(
        ["Einstellung", "Standard", "Beschreibung"],
        [
            ["Primaerfarbe", "#1a73e8", "Hauptfarbe fuer Buttons und Hervorhebungen"],
            ["Primaerfarbe (Hover)", "#1765cc", "Dunklere Variante fuer Hover-Effekte"],
            ["Heute-Hervorhebung", "#fff8e1", "Hintergrundfarbe fuer das heutige Datum"],
            ["Hintergrundfarbe", "#ffffff", "Kalender-Hintergrund"],
            ["Textfarbe", "#3c4043", "Haupttextfarbe"],
            ["Textfarbe (hell)", "#70757a", "Sekundaere Textfarbe"],
            ["Rahmenfarbe", "#dadce0", "Trennlinien und Raender"],
            ["Hover-Hintergrund", "#f1f3f4", "Hintergrund bei Maus-Hover"],
            ["Sidebar-Hintergrund", "#ffffff", "Seitenleisten-Hintergrund"],
        ],
        col_widths=[4.5 * cm, 2.5 * cm, 9 * cm],
        styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("4.2 Kalender-Einstellungen", s['SectionTitle']))
    story.append(make_table(
        ["Einstellung", "Optionen", "Standard"],
        [
            ["Standardansicht", "Monat / Woche / Tag / Liste", "Monat"],
            ["Erster Wochentag", "Sonntag / Montag", "Montag"],
            ["Kalenderwochen anzeigen", "Ja / Nein", "Ja"],
            ["Seitenleiste anzeigen", "Ja / Nein", "Ja"],
            ["Geschaeftszeiten Beginn", "Uhrzeit (HH:MM)", "08:00"],
            ["Geschaeftszeiten Ende", "Uhrzeit (HH:MM)", "18:00"],
            ["Standard-Eventfarbe", "Farbauswahl", "#3788d8"],
        ],
        col_widths=[5 * cm, 5.5 * cm, 3.5 * cm],
        styles_obj=s
    ))
    story.append(PageBreak())

    # ── KAPITEL 5: KALENDER-ANSICHTEN ──
    story.append(Paragraph("5. Kalender-Ansichten", s['ChapterTitle']))
    story.append(hr())

    views = [
        ("Monatsansicht (Standard)",
         "Zeigt den gesamten Monat als Raster an. Ganztaegige Events erscheinen als farbige Balken, "
         "zeitgebundene Events werden mit Uhrzeit angezeigt. Klicken Sie auf ein Datum, um einen neuen "
         "Termin zu erstellen."),
        ("Wochenansicht",
         "Zeigt 7 Tage mit einem Zeitraster (Stundenaufloesung). Ganztaegige Events erscheinen in der "
         "oberen Leiste. Geschaeftszeiten werden hervorgehoben. Ideal fuer die detaillierte Wochenplanung."),
        ("Tagesansicht",
         "Zeigt einen einzelnen Tag mit stuendlicher Aufloesung. Perfekt fuer Tage mit vielen Terminen, "
         "um den genauen Zeitplan zu sehen."),
        ("Listenansicht",
         "Zeigt alle Termine chronologisch als Textliste. Besonders nuetzlich fuer Druckansichten und "
         "schnelle Uebersicht aller anstehenden Termine."),
    ]
    for title, desc in views:
        story.append(Paragraph(f"5.x {title}", s['SectionTitle']))
        story.append(Paragraph(desc, s['BodyText2']))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph(
        "<i>Tipp: Wechseln Sie zwischen den Ansichten ueber die Schaltflaechen oben rechts im Kalender. "
        "Mit den Pfeiltasten navigieren Sie vorwaerts und rueckwaerts.</i>",
        s['Note']
    ))
    story.append(PageBreak())

    # ── KAPITEL 6: EVENTS VERWALTEN ──
    story.append(Paragraph("6. Events verwalten", s['ChapterTitle']))
    story.append(hr())

    story.append(Paragraph("6.1 Event erstellen", s['SectionTitle']))
    story.append(Paragraph(
        "Es gibt zwei Moeglichkeiten, ein neues Event zu erstellen:",
        s['BodyText2']
    ))
    story.append(Paragraph("&bull; Klicken Sie auf den <b>+</b>-Button in der Seitenleiste", s['BulletItem']))
    story.append(Paragraph("&bull; Klicken Sie direkt auf ein Datum oder einen Zeitslot im Kalender", s['BulletItem']))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph("<b>Event-Formular:</b>", s['BodyText2']))
    story.append(make_table(
        ["Feld", "Pflicht", "Beschreibung"],
        [
            ["Titel", "Ja", "Name des Events"],
            ["Start", "Ja", "Startdatum und -uhrzeit"],
            ["Ende", "Nein", "Enddatum und -uhrzeit"],
            ["Ganztaegig", "Nein", "Schalter fuer ganztaegige Events (blendet Uhrzeiten aus)"],
            ["Kategorie", "Nein", "Kategorie-Zuordnung (bestimmt Farbe)"],
            ["Ort", "Nein", "Veranstaltungsort"],
            ["Beschreibung", "Nein", "Detaillierte Beschreibung (mit Textformatierung)"],
            ["Farbe", "Nein", "Individuelle Eventfarbe (16-Farben-Palette)"],
            ["Wiederholung", "Nein", "Wiederholungseinstellungen (siehe Kapitel 7)"],
        ],
        col_widths=[3 * cm, 1.5 * cm, 11.5 * cm],
        styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("6.2 Event bearbeiten", s['SectionTitle']))
    story.append(Paragraph(
        "Klicken Sie auf ein Event, um das Detail-Popup zu oeffnen. Klicken Sie dann auf "
        "<b>Bearbeiten</b>, um das Event-Formular zu oeffnen und Aenderungen vorzunehmen.",
        s['BodyText2']
    ))

    story.append(Paragraph("6.3 Event loeschen", s['SectionTitle']))
    story.append(Paragraph(
        "Klicken Sie auf ein Event und dann auf <b>Loeschen</b>. Ein Bestaetigungsdialog erscheint. "
        "Nur der Ersteller oder Benutzer mit Loeschberechtigung koennen Events loeschen.",
        s['BodyText2']
    ))

    story.append(Paragraph("6.4 Drag &amp; Drop", s['SectionTitle']))
    story.append(Paragraph(
        "Events koennen per Drag &amp; Drop verschoben werden. Ziehen Sie ein Event auf ein neues Datum "
        "oder eine neue Uhrzeit. In der Wochen- und Tagesansicht koennen Sie auch die Dauer aendern, "
        "indem Sie am unteren Rand des Events ziehen.",
        s['BodyText2']
    ))
    story.append(PageBreak())

    # ── KAPITEL 7: WIEDERKEHRENDE EVENTS ──
    story.append(Paragraph("7. Wiederkehrende Events", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Der HL Kalender unterstuetzt wiederkehrende Events im Google Calendar-Stil. "
        "Die Wiederholungsoptionen werden dynamisch basierend auf dem Startdatum generiert.",
        s['BodyText2']
    ))

    story.append(Paragraph("7.1 Wiederholungstypen", s['SectionTitle']))
    story.append(make_table(
        ["Option", "Beispiel", "Beschreibung"],
        [
            ["Wird nicht wiederholt", "-", "Einmaliges Event (Standard)"],
            ["Taeglich", "Jeden Tag", "Nur fuer Ein-Tages-Events verfuegbar"],
            ["Woechentlich am [Tag]", "Woechentlich am Mittwoch", "Basierend auf dem Wochentag des Startdatums"],
            ["Monatlich am [X.] [Tag]", "Monatlich am 3. Dienstag", "Intelligente Nth-Wochentag-Berechnung"],
            ["Jaehrlich am [Datum]", "Jaehrlich am 18. Maerz", "Basierend auf dem Startdatum"],
            ["Benutzerdefiniert...", "Alle 2 Wochen", "Freie Intervall- und Einheiten-Wahl"],
        ],
        col_widths=[4 * cm, 4 * cm, 8 * cm],
        styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("7.2 Benutzerdefinierte Wiederholung", s['SectionTitle']))
    story.append(Paragraph(
        "Bei Auswahl von <b>Benutzerdefiniert</b> erscheinen zusaetzliche Felder:",
        s['BodyText2']
    ))
    story.append(Paragraph("&bull; <b>Alle [X]</b> - Intervall (1-30)", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Einheit</b> - Tage, Wochen, Monate oder Jahre", s['BulletItem']))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph(
        "<i>Hinweis: Bei Mehrtages-Events ist die Einheit \"Tage\" nicht verfuegbar. "
        "Taegliche Wiederholung ist nur fuer Ein-Tages-Events moeglich.</i>",
        s['Note']
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("7.3 Wiederholungsende", s['SectionTitle']))
    story.append(Paragraph(
        "Fuer wiederkehrende Events koennen Sie festlegen, wann die Serie enden soll:",
        s['BodyText2']
    ))
    story.append(Paragraph("&bull; <b>Nie</b> - Die Serie laeuft 10 Jahre in die Zukunft (praktisch \"unendlich\")", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Am [Datum]</b> - Die Serie endet an einem bestimmten Datum", s['BulletItem']))
    story.append(PageBreak())

    # ── KAPITEL 8: KATEGORIEN ──
    story.append(Paragraph("8. Kategorien", s['ChapterTitle']))
    story.append(hr())

    story.append(Paragraph("8.1 Standard-Kategorien", s['SectionTitle']))
    story.append(Paragraph(
        "Bei der Installation werden 5 Standard-Kategorien angelegt:",
        s['BodyText2']
    ))
    story.append(make_table(
        ["Kategorie", "Farbe", "Farbcode"],
        [
            ["Allgemein", "Blau", "#3788d8"],
            ["Arbeit", "Rot", "#e67c73"],
            ["Persoenlich", "Gruen", "#33b679"],
            ["Familie", "Gelb", "#f6bf26"],
            ["Feiertage", "Violett", "#8e24aa"],
        ],
        col_widths=[5 * cm, 4 * cm, 4 * cm],
        styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("8.2 Kategorien verwalten (nur Super User)", s['SectionTitle']))
    story.append(Paragraph(
        "Super User koennen Kategorien direkt im Frontend verwalten:",
        s['BodyText2']
    ))
    story.append(Paragraph("&bull; <b>Neue Kategorie</b>: Klicken Sie auf das <b>+</b> neben \"Meine Kalender\" in der Seitenleiste", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Bearbeiten</b>: Fahren Sie mit der Maus ueber eine Kategorie und klicken Sie auf das Stift-Symbol", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Loeschen</b>: Fahren Sie mit der Maus ueber eine Kategorie und klicken Sie auf das Papierkorb-Symbol", s['BulletItem']))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph(
        "<i>Beim Loeschen einer Kategorie werden die zugehoerigen Events nicht geloescht, "
        "sondern nur von der Kategorie getrennt.</i>",
        s['Note']
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("8.3 Sichtbarkeit umschalten", s['SectionTitle']))
    story.append(Paragraph(
        "In der Seitenleiste unter \"Meine Kalender\" koennen Sie die Sichtbarkeit jeder Kategorie "
        "per Checkbox ein- und ausschalten. So koennen Sie gezielt nur bestimmte Terminarten anzeigen.",
        s['BodyText2']
    ))
    story.append(PageBreak())

    # ── KAPITEL 9: BERECHTIGUNGEN ──
    story.append(Paragraph("9. Berechtigungen", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Der HL Kalender nutzt das Standard-Joomla-ACL-System fuer die Zugriffskontrolle.",
        s['BodyText2']
    ))
    story.append(make_table(
        ["Benutzerrolle", "Rechte"],
        [
            ["Gast (nicht angemeldet)", "Kann nur oeffentliche Events ansehen. Keine Bearbeitung moeglich."],
            ["Registrierter Benutzer", "Kann eigene Events erstellen, bearbeiten und loeschen."],
            ["Super User", "Voller Zugriff: Alle Events und Kategorien verwalten, Optionen aendern."],
        ],
        col_widths=[4.5 * cm, 11.5 * cm],
        styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("9.1 Berechtigungen konfigurieren", s['SectionTitle']))
    story.append(Paragraph(
        "Unter <b>Komponenten &gt; HL Kalender &gt; Optionen &gt; Berechtigungen</b> koennen Sie "
        "folgende Rechte pro Benutzergruppe konfigurieren:",
        s['BodyText2']
    ))
    perms = [
        ("core.admin", "Zugriff auf Komponentenverwaltung"),
        ("core.options", "Optionen aendern"),
        ("core.manage", "Komponente aufrufen"),
        ("core.create", "Neue Events erstellen"),
        ("core.delete", "Beliebige Events loeschen"),
        ("core.edit", "Beliebige Events bearbeiten"),
        ("core.edit.state", "Events veroeffentlichen/zurueckziehen"),
        ("core.edit.own", "Eigene Events bearbeiten"),
    ]
    for perm, desc in perms:
        story.append(Paragraph(f"&bull; <b>{perm}</b> - {desc}", s['BulletItem']))
    story.append(PageBreak())

    # ── KAPITEL 10: MODUL-KONFIGURATION ──
    story.append(Paragraph("10. Modul-Konfiguration", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Das HL Kalender-Modul kann an beliebigen Positionen im Template platziert werden. "
        "Es unterstuetzt mehrere Instanzen mit unterschiedlichen Einstellungen.",
        s['BodyText2']
    ))

    story.append(make_table(
        ["Parameter", "Optionen", "Beschreibung"],
        [
            ["Standardansicht", "Monat/Woche/Tag/Liste", "Welche Ansicht beim Laden angezeigt wird"],
            ["Seitenleiste anzeigen", "Ja / Nein", "Mini-Kalender und Kategorien ein-/ausblenden"],
            ["Frontend-Bearbeitung", "Ja / Nein", "Events erstellen/bearbeiten im Modul erlauben"],
            ["Kalender-Hoehe", "CSS-Wert (z.B. 600px)", "Hoehe des Moduls"],
            ["Kalenderwochen", "Ja / Nein", "ISO-Kalenderwochen anzeigen"],
            ["Erster Wochentag", "Sonntag / Montag", "Wochenstart"],
            ["Kategorie-Filter", "Mehrfachauswahl", "Nur bestimmte Kategorien anzeigen"],
        ],
        col_widths=[3.5 * cm, 4 * cm, 8.5 * cm],
        styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph(
        "<i>Tipp: Verwenden Sie den Kategorie-Filter, um z.B. ein Modul nur fuer \"Arbeit\"-Termine "
        "und ein anderes nur fuer \"Familie\"-Termine einzurichten.</i>",
        s['Note']
    ))
    story.append(PageBreak())

    # ── KAPITEL 11: UPDATE-SERVER ──
    story.append(Paragraph("11. Update-Server", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Der HL Kalender unterstuetzt automatische Update-Benachrichtigungen ueber den Joomla-Update-Mechanismus.",
        s['BodyText2']
    ))
    story.append(Paragraph(
        "Wenn eine neue Version verfuegbar ist, wird dies im Joomla-Administrationsbereich "
        "unter <b>System &gt; Update &gt; Erweiterungen</b> angezeigt.",
        s['BodyText2']
    ))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph("<b>Update-Server URL:</b>", s['BodyText2']))
    story.append(Paragraph(
        "www.eiwtestzone.ch/huberlabs-extensions/updates/jwcalendar_update.xml",
        s['Note']
    ))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph(
        "Updates koennen direkt aus dem Joomla-Backend installiert werden, "
        "ohne die Extension manuell herunterladen zu muessen.",
        s['BodyText2']
    ))
    story.append(PageBreak())

    # ── KAPITEL 12: FAQ ──
    story.append(Paragraph("12. FAQ / Fehlerbehebung", s['ChapterTitle']))
    story.append(hr())

    faqs = [
        ("Der Kalender wird nicht angezeigt",
         "Stellen Sie sicher, dass JavaScript im Browser aktiviert ist und eine Internetverbindung besteht "
         "(FullCalendar.js wird via CDN geladen). Pruefen Sie die Browser-Konsole auf Fehlermeldungen."),
        ("Ich kann keine Events erstellen",
         "Pruefen Sie, ob Sie angemeldet sind und die Berechtigung <b>core.create</b> fuer Ihre "
         "Benutzergruppe aktiviert ist (Komponenten &gt; HL Kalender &gt; Optionen &gt; Berechtigungen)."),
        ("Kategorien werden nicht in der Seitenleiste angezeigt",
         "Stellen Sie sicher, dass die Seitenleiste in den Optionen aktiviert ist und mindestens eine "
         "Kategorie den Status \"Veroeffentlicht\" hat."),
        ("Wiederkehrende Events werden nicht angezeigt",
         "Pruefen Sie das Wiederholungsende-Datum. Wenn \"Nie\" gewaehlt ist, werden Events bis zu "
         "10 Jahre in die Zukunft generiert. Bei \"Am [Datum]\" werden keine Events nach diesem Datum angezeigt."),
        ("Drag &amp; Drop funktioniert nicht",
         "Drag &amp; Drop ist nur fuer angemeldete Benutzer mit Bearbeitungsrechten verfuegbar. "
         "Gast-Benutzer koennen Events nur ansehen."),
        ("Das Modul zeigt andere Events als die Komponente",
         "Pruefen Sie den Kategorie-Filter in den Modul-Einstellungen. Wenn bestimmte Kategorien "
         "ausgewaehlt sind, werden nur Events dieser Kategorien angezeigt."),
        ("Farben werden nicht korrekt dargestellt",
         "Leeren Sie den Browser-Cache. Die Farbeinstellungen werden als CSS Custom Properties geladen "
         "und koennen gecacht sein."),
    ]
    for question, answer in faqs:
        story.append(Paragraph(f"<b>F: {question}</b>", s['BodyText2']))
        story.append(Paragraph(f"A: {answer}", s['BodyText2']))
        story.append(Spacer(1, 0.2 * cm))

    # ── FOOTER / LAST PAGE ──
    story.append(Spacer(1, 1 * cm))
    story.append(HRFlowable(width="100%", thickness=1, color=PRIMARY, spaceAfter=12))
    story.append(Paragraph(
        "<b>HL Kalender v1.7.2</b> | huberlabs.ch | GNU/GPL v2 or later",
        s['Footer']
    ))
    story.append(Paragraph(
        "Support: support@huberlabs.ch | www.eiwtestzone.ch/huberlabs-extensions",
        s['Footer']
    ))

    doc.build(story, onFirstPage=add_page_number, onLaterPages=add_page_number)
    print(f"PDF erstellt: {OUTPUT}")

if __name__ == "__main__":
    build()

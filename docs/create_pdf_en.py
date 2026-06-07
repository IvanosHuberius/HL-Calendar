#!/usr/bin/env python3
"""Generate English PDF documentation for HL Calendar v1.7.2"""

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm, mm
from reportlab.lib.colors import HexColor, black, white
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    HRFlowable
)
import os

OUTPUT = os.path.join(os.path.dirname(__file__), "HL_Calendar_Documentation_EN.pdf")

PRIMARY = HexColor("#1a73e8")
PRIMARY_DARK = HexColor("#1557b0")
DARK_TEXT = HexColor("#202124")
GRAY_TEXT = HexColor("#5f6368")
LIGHT_BG = HexColor("#f8f9fa")
BORDER = HexColor("#dadce0")

def build_styles():
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle('CoverTitle', parent=styles['Title'], fontSize=32, leading=40, textColor=PRIMARY, spaceAfter=10, alignment=TA_CENTER, fontName='Helvetica-Bold'))
    styles.add(ParagraphStyle('CoverSubtitle', parent=styles['Normal'], fontSize=16, leading=22, textColor=GRAY_TEXT, spaceAfter=6, alignment=TA_CENTER, fontName='Helvetica'))
    styles.add(ParagraphStyle('ChapterTitle', parent=styles['Heading1'], fontSize=22, leading=28, textColor=PRIMARY, spaceBefore=20, spaceAfter=14, fontName='Helvetica-Bold'))
    styles.add(ParagraphStyle('SectionTitle', parent=styles['Heading2'], fontSize=15, leading=20, textColor=PRIMARY_DARK, spaceBefore=14, spaceAfter=8, fontName='Helvetica-Bold'))
    styles.add(ParagraphStyle('BodyText2', parent=styles['Normal'], fontSize=10.5, leading=15, textColor=DARK_TEXT, spaceAfter=8, alignment=TA_JUSTIFY, fontName='Helvetica'))
    styles.add(ParagraphStyle('BulletItem', parent=styles['Normal'], fontSize=10.5, leading=15, textColor=DARK_TEXT, spaceAfter=4, leftIndent=20, bulletIndent=8, fontName='Helvetica'))
    styles.add(ParagraphStyle('Note', parent=styles['Normal'], fontSize=10, leading=14, textColor=GRAY_TEXT, spaceAfter=8, leftIndent=12, fontName='Helvetica-Oblique', backColor=LIGHT_BG))
    styles.add(ParagraphStyle('TableHeader', parent=styles['Normal'], fontSize=10, leading=13, textColor=white, fontName='Helvetica-Bold', alignment=TA_CENTER))
    styles.add(ParagraphStyle('TableCell', parent=styles['Normal'], fontSize=9.5, leading=13, textColor=DARK_TEXT, fontName='Helvetica'))
    styles.add(ParagraphStyle('Footer', parent=styles['Normal'], fontSize=8, leading=10, textColor=GRAY_TEXT, fontName='Helvetica', alignment=TA_CENTER))
    return styles

def make_table(headers, rows, col_widths=None, styles_obj=None):
    s = styles_obj
    data = [[Paragraph(h, s['TableHeader']) for h in headers]]
    for row in rows:
        data.append([Paragraph(str(c), s['TableCell']) for c in row])
    t = Table(data, colWidths=col_widths, repeatRows=1)
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), PRIMARY),
        ('TEXTCOLOR', (0, 0), (-1, 0), white),
        ('ALIGN', (0, 0), (-1, 0), 'CENTER'),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, 0), 10),
        ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
        ('TOPPADDING', (0, 0), (-1, 0), 8),
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
    canvas.drawCentredString(A4[0] / 2, 20 * mm, f"HL Calendar v1.7.2 — Documentation  |  Page {doc.page}")
    canvas.restoreState()

def build():
    doc = SimpleDocTemplate(
        OUTPUT, pagesize=A4,
        topMargin=2.2 * cm, bottomMargin=2.5 * cm,
        leftMargin=2.2 * cm, rightMargin=2.2 * cm,
        title="HL Calendar v1.7.2 — Documentation",
        author="huberlabs.ch",
        subject="Joomla 6 Calendar Extension"
    )
    s = build_styles()
    story = []

    # ── COVER PAGE ──
    story.append(Spacer(1, 4 * cm))
    story.append(Paragraph("HL Calendar", s['CoverTitle']))
    story.append(Paragraph("Joomla 6 Calendar Extension", s['CoverSubtitle']))
    story.append(Spacer(1, 1 * cm))
    story.append(HRFlowable(width="40%", thickness=2, color=PRIMARY, spaceAfter=12, spaceBefore=12))
    story.append(Paragraph("Version 1.7.2", s['CoverSubtitle']))
    story.append(Spacer(1, 2 * cm))
    story.append(Paragraph("User Manual", s['CoverSubtitle']))
    story.append(Spacer(1, 3 * cm))

    cover_info = [
        ["Author:", "huberlabs.ch"],
        ["License:", "GNU/GPL v2 or later"],
        ["Website:", "www.eiwtestzone.ch/huberlabs-extensions"],
        ["Support:", "support@huberlabs.ch"],
        ["Date:", "March 2026"],
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

    # ── TABLE OF CONTENTS ──
    story.append(Paragraph("Table of Contents", s['ChapterTitle']))
    story.append(hr())
    toc_items = [
        ("1", "Overview"),
        ("2", "System Requirements"),
        ("3", "Installation"),
        ("4", "Configuration"),
        ("5", "Calendar Views"),
        ("6", "Managing Events"),
        ("7", "Recurring Events"),
        ("8", "Categories"),
        ("9", "Permissions"),
        ("10", "Module Configuration"),
        ("11", "Update Server"),
        ("12", "FAQ / Troubleshooting"),
    ]
    for num, title in toc_items:
        story.append(Paragraph(f"<b>{num}.</b>&nbsp;&nbsp;&nbsp;{title}", s['BodyText2']))
    story.append(PageBreak())

    # ── CHAPTER 1: OVERVIEW ──
    story.append(Paragraph("1. Overview", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "The <b>HL Calendar</b> is a modern, Google Calendar-style extension for Joomla 6. "
        "It provides an intuitive user interface with drag &amp; drop, color-coded categories, "
        "recurring events, and full frontend editing capabilities.",
        s['BodyText2']
    ))
    story.append(Paragraph("<b>Key Features:</b>", s['BodyText2']))
    features = [
        "Google Calendar-inspired design and usability",
        "4 views: Month, Week, Day, List",
        "Drag &amp; drop to move events and change their duration",
        "Recurring events (daily, weekly, monthly, yearly, custom)",
        "Color-coded categories with 16-color palette",
        "Full frontend editing (AJAX-based)",
        "Responsive design for all devices",
        "Joomla ACL permission system",
        "Fully localized in German and English",
        "Component + Module included in package",
    ]
    for f in features:
        story.append(Paragraph(f"&bull; {f}", s['BulletItem']))
    story.append(PageBreak())

    # ── CHAPTER 2: SYSTEM REQUIREMENTS ──
    story.append(Paragraph("2. System Requirements", s['ChapterTitle']))
    story.append(hr())
    story.append(make_table(
        ["Requirement", "Minimum"],
        [
            ["Joomla", "6.x"],
            ["PHP", "8.0 or higher"],
            ["MySQL", "5.7+ (UTF-8 MB4)"],
            ["Browser", "Modern browser with JavaScript (Chrome, Firefox, Safari, Edge)"],
        ],
        col_widths=[5 * cm, 11 * cm], styles_obj=s
    ))
    story.append(Spacer(1, 0.5 * cm))
    story.append(Paragraph(
        "<i>Note: FullCalendar.js 6.1.11 is automatically loaded via CDN (jsDelivr). "
        "An internet connection is required for the frontend display.</i>",
        s['Note']
    ))
    story.append(PageBreak())

    # ── CHAPTER 3: INSTALLATION ──
    story.append(Paragraph("3. Installation", s['ChapterTitle']))
    story.append(hr())

    story.append(Paragraph("3.1 Install Package", s['SectionTitle']))
    for i, step in enumerate([
        "Download <b>pkg_jwcalendar_v1.7.2.zip</b>.",
        "Log in to the Joomla administration area.",
        "Navigate to <b>System &gt; Install &gt; Extensions</b>.",
        "Select the ZIP file and click <b>Upload &amp; Install</b>.",
        "The system will automatically create database tables and 5 default categories.",
    ], 1):
        story.append(Paragraph(f"<b>{i}.</b> {step}", s['BodyText2']))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("3.2 Create Menu Item", s['SectionTitle']))
    for i, step in enumerate([
        "Go to <b>Menus &gt; [Your Menu] &gt; New Menu Item</b>.",
        "Select type: <b>HL Calendar &gt; Calendar</b>.",
        "Enter a title (e.g., \"Calendar\").",
        "Save the menu item.",
    ], 1):
        story.append(Paragraph(f"<b>{i}.</b> {step}", s['BodyText2']))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("3.3 Set Up Module (Optional)", s['SectionTitle']))
    story.append(Paragraph(
        "The package also includes a calendar module that you can place in any module position:",
        s['BodyText2']
    ))
    for i, step in enumerate([
        "Navigate to <b>Content &gt; Site Modules</b>.",
        "Click <b>New</b> and select <b>HL Calendar</b>.",
        "Configure position, view, and category filter.",
        "Publish and save the module.",
    ], 1):
        story.append(Paragraph(f"<b>{i}.</b> {step}", s['BodyText2']))
    story.append(PageBreak())

    # ── CHAPTER 4: CONFIGURATION ──
    story.append(Paragraph("4. Configuration", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "Configuration is found under <b>Components &gt; HL Calendar &gt; Options</b>.",
        s['BodyText2']
    ))

    story.append(Paragraph("4.1 Color Settings", s['SectionTitle']))
    story.append(Paragraph(
        "HL Calendar offers 9 customizable colors that control the entire appearance:",
        s['BodyText2']
    ))
    story.append(make_table(
        ["Setting", "Default", "Description"],
        [
            ["Primary Color", "#1a73e8", "Main color for buttons and highlights"],
            ["Primary Color (Hover)", "#1765cc", "Darker variant for hover effects"],
            ["Today Highlight", "#fff8e1", "Background color for today's date"],
            ["Background Color", "#ffffff", "Calendar background"],
            ["Text Color", "#3c4043", "Main text color"],
            ["Text Color (Light)", "#70757a", "Secondary text color"],
            ["Border Color", "#dadce0", "Dividers and borders"],
            ["Hover Background", "#f1f3f4", "Background on mouse hover"],
            ["Sidebar Background", "#ffffff", "Sidebar background"],
        ],
        col_widths=[4.5 * cm, 2.5 * cm, 9 * cm], styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("4.2 Calendar Settings", s['SectionTitle']))
    story.append(make_table(
        ["Setting", "Options", "Default"],
        [
            ["Default View", "Month / Week / Day / List", "Month"],
            ["First Day of Week", "Sunday / Monday", "Monday"],
            ["Show Week Numbers", "Yes / No", "Yes"],
            ["Show Sidebar", "Yes / No", "Yes"],
            ["Business Hours Start", "Time (HH:MM)", "08:00"],
            ["Business Hours End", "Time (HH:MM)", "18:00"],
            ["Default Event Color", "Color picker", "#3788d8"],
        ],
        col_widths=[5 * cm, 5.5 * cm, 3.5 * cm], styles_obj=s
    ))
    story.append(PageBreak())

    # ── CHAPTER 5: CALENDAR VIEWS ──
    story.append(Paragraph("5. Calendar Views", s['ChapterTitle']))
    story.append(hr())
    views = [
        ("Month View (Default)",
         "Displays the entire month as a grid. All-day events appear as colored bars, "
         "timed events are shown with their time. Click on a date to create a new event."),
        ("Week View",
         "Shows 7 days with a time grid (hourly resolution). All-day events appear in the "
         "top bar. Business hours are highlighted. Ideal for detailed weekly planning."),
        ("Day View",
         "Shows a single day with hourly resolution. Perfect for days with many events "
         "to see the exact schedule."),
        ("List View",
         "Shows all events chronologically as a text list. Especially useful for print views "
         "and a quick overview of upcoming events."),
    ]
    for title, desc in views:
        story.append(Paragraph(f"5.x {title}", s['SectionTitle']))
        story.append(Paragraph(desc, s['BodyText2']))
    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph(
        "<i>Tip: Switch between views using the buttons in the top right corner of the calendar. "
        "Use the arrow buttons to navigate forward and backward.</i>",
        s['Note']
    ))
    story.append(PageBreak())

    # ── CHAPTER 6: MANAGING EVENTS ──
    story.append(Paragraph("6. Managing Events", s['ChapterTitle']))
    story.append(hr())

    story.append(Paragraph("6.1 Create Event", s['SectionTitle']))
    story.append(Paragraph("There are two ways to create a new event:", s['BodyText2']))
    story.append(Paragraph("&bull; Click the <b>+</b> button in the sidebar", s['BulletItem']))
    story.append(Paragraph("&bull; Click directly on a date or time slot in the calendar", s['BulletItem']))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph("<b>Event Form:</b>", s['BodyText2']))
    story.append(make_table(
        ["Field", "Required", "Description"],
        [
            ["Title", "Yes", "Name of the event"],
            ["Start", "Yes", "Start date and time"],
            ["End", "No", "End date and time"],
            ["All Day", "No", "Toggle for all-day events (hides time fields)"],
            ["Category", "No", "Category assignment (determines color)"],
            ["Location", "No", "Event location"],
            ["Description", "No", "Detailed description (with text formatting)"],
            ["Color", "No", "Individual event color (16-color palette)"],
            ["Recurrence", "No", "Recurrence settings (see Chapter 7)"],
        ],
        col_widths=[3 * cm, 1.5 * cm, 11.5 * cm], styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("6.2 Edit Event", s['SectionTitle']))
    story.append(Paragraph(
        "Click on an event to open the detail popup. Then click <b>Edit</b> to open the "
        "event form and make changes.",
        s['BodyText2']
    ))
    story.append(Paragraph("6.3 Delete Event", s['SectionTitle']))
    story.append(Paragraph(
        "Click on an event and then <b>Delete</b>. A confirmation dialog will appear. "
        "Only the creator or users with delete permissions can delete events.",
        s['BodyText2']
    ))
    story.append(Paragraph("6.4 Drag &amp; Drop", s['SectionTitle']))
    story.append(Paragraph(
        "Events can be moved via drag &amp; drop. Drag an event to a new date or time. "
        "In week and day views, you can also change the duration by dragging the bottom edge of the event.",
        s['BodyText2']
    ))
    story.append(PageBreak())

    # ── CHAPTER 7: RECURRING EVENTS ──
    story.append(Paragraph("7. Recurring Events", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "HL Calendar supports recurring events in Google Calendar style. "
        "Recurrence options are dynamically generated based on the start date.",
        s['BodyText2']
    ))

    story.append(Paragraph("7.1 Recurrence Types", s['SectionTitle']))
    story.append(make_table(
        ["Option", "Example", "Description"],
        [
            ["Does not repeat", "-", "Single event (default)"],
            ["Daily", "Every day", "Only available for single-day events"],
            ["Weekly on [day]", "Weekly on Wednesday", "Based on the weekday of the start date"],
            ["Monthly on the [Nth] [day]", "Monthly on the 3rd Tuesday", "Smart Nth weekday calculation"],
            ["Yearly on [date]", "Yearly on March 18", "Based on the start date"],
            ["Custom...", "Every 2 weeks", "Free interval and unit selection"],
        ],
        col_widths=[4 * cm, 4 * cm, 8 * cm], styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("7.2 Custom Recurrence", s['SectionTitle']))
    story.append(Paragraph(
        "When selecting <b>Custom</b>, additional fields appear:",
        s['BodyText2']
    ))
    story.append(Paragraph("&bull; <b>Every [X]</b> - Interval (1-30)", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Unit</b> - Days, Weeks, Months, or Years", s['BulletItem']))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph(
        "<i>Note: For multi-day events, the \"Days\" unit is not available. "
        "Daily recurrence is only possible for single-day events.</i>",
        s['Note']
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("7.3 Recurrence End", s['SectionTitle']))
    story.append(Paragraph("For recurring events, you can specify when the series should end:", s['BodyText2']))
    story.append(Paragraph("&bull; <b>Never</b> - The series runs 10 years into the future (practically \"infinite\")", s['BulletItem']))
    story.append(Paragraph("&bull; <b>On [date]</b> - The series ends on a specific date", s['BulletItem']))
    story.append(PageBreak())

    # ── CHAPTER 8: CATEGORIES ──
    story.append(Paragraph("8. Categories", s['ChapterTitle']))
    story.append(hr())

    story.append(Paragraph("8.1 Default Categories", s['SectionTitle']))
    story.append(Paragraph("5 default categories are created during installation:", s['BodyText2']))
    story.append(make_table(
        ["Category", "Color", "Color Code"],
        [
            ["General", "Blue", "#3788d8"],
            ["Work", "Red", "#e67c73"],
            ["Personal", "Green", "#33b679"],
            ["Family", "Yellow", "#f6bf26"],
            ["Holidays", "Purple", "#8e24aa"],
        ],
        col_widths=[5 * cm, 4 * cm, 4 * cm], styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("8.2 Manage Categories (Super User Only)", s['SectionTitle']))
    story.append(Paragraph("Super Users can manage categories directly in the frontend:", s['BodyText2']))
    story.append(Paragraph("&bull; <b>New Category</b>: Click the <b>+</b> next to \"My Calendars\" in the sidebar", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Edit</b>: Hover over a category and click the pencil icon", s['BulletItem']))
    story.append(Paragraph("&bull; <b>Delete</b>: Hover over a category and click the trash icon", s['BulletItem']))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph(
        "<i>When deleting a category, associated events are not deleted but only unassigned from the category.</i>",
        s['Note']
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("8.3 Toggle Visibility", s['SectionTitle']))
    story.append(Paragraph(
        "In the sidebar under \"My Calendars\", you can toggle the visibility of each category "
        "using checkboxes. This allows you to show only specific event types.",
        s['BodyText2']
    ))
    story.append(PageBreak())

    # ── CHAPTER 9: PERMISSIONS ──
    story.append(Paragraph("9. Permissions", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph("HL Calendar uses the standard Joomla ACL system for access control.", s['BodyText2']))
    story.append(make_table(
        ["User Role", "Rights"],
        [
            ["Guest (not logged in)", "Can only view public events. No editing possible."],
            ["Registered User", "Can create, edit, and delete own events."],
            ["Super User", "Full access: Manage all events and categories, change options."],
        ],
        col_widths=[4.5 * cm, 11.5 * cm], styles_obj=s
    ))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("9.1 Configure Permissions", s['SectionTitle']))
    story.append(Paragraph(
        "Under <b>Components &gt; HL Calendar &gt; Options &gt; Permissions</b> you can configure "
        "the following rights per user group:",
        s['BodyText2']
    ))
    for perm, desc in [
        ("core.admin", "Access component administration"),
        ("core.options", "Change options"),
        ("core.manage", "Access component"),
        ("core.create", "Create new events"),
        ("core.delete", "Delete any event"),
        ("core.edit", "Edit any event"),
        ("core.edit.state", "Publish/unpublish events"),
        ("core.edit.own", "Edit own events"),
    ]:
        story.append(Paragraph(f"&bull; <b>{perm}</b> - {desc}", s['BulletItem']))
    story.append(PageBreak())

    # ── CHAPTER 10: MODULE CONFIGURATION ──
    story.append(Paragraph("10. Module Configuration", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "The HL Calendar module can be placed in any template position. "
        "It supports multiple instances with different settings.",
        s['BodyText2']
    ))
    story.append(make_table(
        ["Parameter", "Options", "Description"],
        [
            ["Default View", "Month/Week/Day/List", "Which view is shown on load"],
            ["Show Sidebar", "Yes / No", "Show/hide mini calendar and categories"],
            ["Frontend Editing", "Yes / No", "Allow creating/editing events in the module"],
            ["Calendar Height", "CSS value (e.g., 600px)", "Height of the module"],
            ["Week Numbers", "Yes / No", "Show ISO week numbers"],
            ["First Day of Week", "Sunday / Monday", "Week start day"],
            ["Category Filter", "Multi-select", "Show only specific categories"],
        ],
        col_widths=[3.5 * cm, 4 * cm, 8.5 * cm], styles_obj=s
    ))
    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph(
        "<i>Tip: Use the category filter to set up one module for \"Work\" events only "
        "and another for \"Family\" events only.</i>",
        s['Note']
    ))
    story.append(PageBreak())

    # ── CHAPTER 11: UPDATE SERVER ──
    story.append(Paragraph("11. Update Server", s['ChapterTitle']))
    story.append(hr())
    story.append(Paragraph(
        "HL Calendar supports automatic update notifications through the Joomla update mechanism.",
        s['BodyText2']
    ))
    story.append(Paragraph(
        "When a new version is available, it will be shown in the Joomla administration area "
        "under <b>System &gt; Update &gt; Extensions</b>.",
        s['BodyText2']
    ))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph("<b>Update Server URL:</b>", s['BodyText2']))
    story.append(Paragraph(
        "www.eiwtestzone.ch/huberlabs-extensions/updates/jwcalendar_update.xml",
        s['Note']
    ))
    story.append(Spacer(1, 0.2 * cm))
    story.append(Paragraph(
        "Updates can be installed directly from the Joomla backend without manually downloading the extension.",
        s['BodyText2']
    ))
    story.append(PageBreak())

    # ── CHAPTER 12: FAQ ──
    story.append(Paragraph("12. FAQ / Troubleshooting", s['ChapterTitle']))
    story.append(hr())
    faqs = [
        ("The calendar is not displayed",
         "Make sure JavaScript is enabled in the browser and there is an internet connection "
         "(FullCalendar.js is loaded via CDN). Check the browser console for error messages."),
        ("I cannot create events",
         "Check if you are logged in and the <b>core.create</b> permission is enabled for your "
         "user group (Components &gt; HL Calendar &gt; Options &gt; Permissions)."),
        ("Categories are not shown in the sidebar",
         "Make sure the sidebar is enabled in the options and at least one category has "
         "\"Published\" status."),
        ("Recurring events are not displayed",
         "Check the recurrence end date. If \"Never\" is selected, events are generated up to "
         "10 years into the future. With \"On [date]\", no events are shown after that date."),
        ("Drag &amp; drop does not work",
         "Drag &amp; drop is only available for logged-in users with editing permissions. "
         "Guest users can only view events."),
        ("The module shows different events than the component",
         "Check the category filter in the module settings. If specific categories are selected, "
         "only events from those categories are displayed."),
        ("Colors are not displayed correctly",
         "Clear the browser cache. Color settings are loaded as CSS Custom Properties "
         "and may be cached."),
    ]
    for question, answer in faqs:
        story.append(Paragraph(f"<b>Q: {question}</b>", s['BodyText2']))
        story.append(Paragraph(f"A: {answer}", s['BodyText2']))
        story.append(Spacer(1, 0.2 * cm))

    # ── FOOTER ──
    story.append(Spacer(1, 1 * cm))
    story.append(HRFlowable(width="100%", thickness=1, color=PRIMARY, spaceAfter=12))
    story.append(Paragraph("<b>HL Calendar v1.7.2</b> | huberlabs.ch | GNU/GPL v2 or later", s['Footer']))
    story.append(Paragraph("Support: support@huberlabs.ch | www.eiwtestzone.ch/huberlabs-extensions", s['Footer']))

    doc.build(story, onFirstPage=add_page_number, onLaterPages=add_page_number)
    print(f"PDF created: {OUTPUT}")

if __name__ == "__main__":
    build()

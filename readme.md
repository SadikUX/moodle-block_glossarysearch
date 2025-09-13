# Glossary Search Block (block_glossarysearch)

A modern, lightweight Moodle block for searching glossary entries directly from a block region.  
Results are displayed **inside the block** with keyword highlighting, paging, and a responsive card layout.

---

## Features
- Modern card design with Mustache templating
- Search form and results inside the block, full width, mobile-friendly
- Search by keyword across **concepts** and **definitions**
- Works across all glossaries in a course, or restrict to one glossary in settings
- Match highlighting with `<mark>`
- Paging (10 results per page)
- Per-instance custom title
- No custom database tables required, no install.xml needed
- Portable: search uses only SQL `LIKE` (no regex/DB-specific quirks)

---

## Requirements
- Moodle **4.5** (tested on 4.5)
- PHP 8.1 / 8.2
- Tested with the **Adaptable theme**

---

## Installation
1. Copy the folder to:
	`blocks/glossarysearch/`
2. Log in as admin → **Site administration → Notifications** to complete installation.
3. In a course: **Turn editing on** → **Add a block** → *Glossary search*.
4. (Optional) Configure the block: set a custom title or restrict to one glossary.

---

## Usage
- Type a search term in the input box and press **Search**.
- Results appear as a list (concept, definition, glossary name).
- Matches are highlighted; a paging bar appears if there are more than 10 results.

---

## Capabilities
- `block/glossarysearch:addinstance` — add to course pages (default: teachers & managers)
- `block/glossarysearch:myaddinstance` — add to Dashboard (default: all users)

---

## Notes
- Only **approved** glossary entries are searched.
- On the Dashboard/front page, the block must be configured to use a specific glossary or be placed inside a course.
- The search is portable for all databases (LIKE only, no regex).
- For very large glossaries, consider Moodle **Global Search (Solr)**.

---

## Accessibility
- Form controls have proper labels.
- Highlights use `<mark>`, which is screen-reader friendly.

---

## Privacy
This plugin stores no personal data. It only displays glossary entries the user already has permission to view.

---

## Changelog (Key Changes)
- Complete redesign: modern card layout, responsive, Mustache template
- Results list is full width, improved spacing and readability
- Only portable LIKE search, no regex/DB-specific quirks
- Modernized CSS and UX, linter issues fixed
- Code cleaned up and documented

---

## Maintainer
Currently maintained by **Sadik Mert** (Fork & Rewrite, 2025)
Original version: Alan Chadwick (Moodle Forum, 2025)

---

## License
GPL v3 or later — see the LICENSE file for details.

---


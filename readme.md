# Glossary Search Block (block_glossarysearch)

A lightweight Moodle block that lets users search glossary entries directly from a block region.  
Results are displayed **inside the block** with keyword highlighting and paging.

---

## Features
- Search by keyword across **concepts** and **definitions**
- Works across all glossaries in a course, or restrict to one glossary in settings
- Results shown directly in the block (no page navigation)
- Match highlighting with `<mark>`
- Paging (default 10 results per page)
- Per-instance custom title

---

## Requirements
- Moodle **4.5** (tested only on 4.5)
- PHP 8.1 / 8.2
- Tested with the **Adaptable theme**

---

## Installation
1. Copy the folder to:
blocks/glossarysearch/

2. Log in as admin → **Site administration → Notifications** to complete installation.
3. In a course, **Turn editing on** → **Add a block** → *Glossary search*.
4. (Optional) Configure the block: set a custom title or restrict to one glossary.

---

## Usage
- Type a search term in the block’s input box and press **Search**.
- Results show as a list (concept, definition, glossary name).
- Matches are highlighted; a paging bar appears if there are more than 10 results.

---

## Capabilities
- `block/glossarysearch:addinstance` — add to course pages (default: teachers & managers).
- `block/glossarysearch:myaddinstance` — add to Dashboard (default: all users).

---

## Notes
- Only searches **approved** glossary entries.
- On the Dashboard/front page, the block has no course context. You must either:
- Configure it to use a specific glossary, or
- Add it inside a course.
- Matching uses SQL `LIKE`. For very large glossaries, consider Moodle **Global Search (Solr)**.

---

## Accessibility
- Form controls have proper labels.
- Highlights use `<mark>`, which is screen-reader friendly.

---

## Privacy
This block stores no personal data. It only displays glossary entries the user already has permission to view.

---

## Disclaimer
I am **not a developer or Moodle expert**. This block was created for personal use and shared in case it helps others.  
If anyone with more expertise would like to take over maintenance, improve the code, or extend the functionality, please feel free to do so.  
I cannot promise support or fixes for issues.

---

## License
GPL v3 or later — see the LICENSE file for details.

---


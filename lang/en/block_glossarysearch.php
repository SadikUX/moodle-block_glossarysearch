<?php
// Language strings for the Glossary search block.
// File: blocks/glossarysearch/lang/en/block_glossarysearch.php
//
// Notes:
// - Keep this file UTF-8 without BOM.
// - Capability names must match those declared in db/access.php.
// - Strings are referenced throughout the block (UI labels, help, etc.).

// Plugin name (shown in block chooser and headings).
$string['pluginname'] = 'Glossary search';

// Capabilities (shown on the permissions UI).
$string['glossarysearch:addinstance']   = 'Add a new Glossary search block';
$string['glossarysearch:myaddinstance'] = 'Add a new Glossary search block to the Dashboard';

// UI labels for the inline search form and results.
$string['search']             = 'Search';
$string['searchplaceholder']  = 'Search glossary…';
$string['allcourseglossaries']= 'All course glossaries';
$string['enterquery']         = 'Type a word or phrase and press Search.';
$string['noresults']          = 'No entries matched your search.';

// Settings shown on the block instance configuration form (edit_form.php).
$string['configtitle']    = 'Custom block title';

// Clearer wording (keeps behaviour the same).
$string['configglossary'] = 'Limit to a specific glossary';

// Help text for the glossary selector (appears in a help popup).
$string['configglossary_help'] =
    'Choose a single glossary in this course to search. '
  . 'Leave it set to “All course glossaries” to search every glossary in the course.';

// NEW: label for the “Whole word only” checkbox added to the block UI.
$string['wholewordonly'] = 'Whole word only';

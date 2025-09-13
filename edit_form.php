<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit form for the Glossary Search block
 *
 * @package    block_glossarysearch
 * @copyright  2025 Alan Chadwick (original author, released in Moodle forum)
 * @copyright  2025 Sadik Mert (rewrite & further development)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/edit_form.php');

/**
 * Class block_glossarysearch_edit_form
 *
 * Provides the form for configuring instances of the Glossary Search block.
 *
 * @package    block_glossarysearch
 */
class block_glossarysearch_edit_form extends block_edit_form {
    /**
     * Adds per-instance settings shown when configuring this block.
     */
    protected function specific_definition($mform) {
        global $DB, $COURSE;

        // Custom block title.
        // Text shown as the block title on the page (can be left blank).
        $mform->addElement(
            'text',
            'config_customtitle',
            get_string('configtitle', 'block_glossarysearch')
        );
        $mform->setType('config_customtitle', PARAM_TEXT);
        // Provide a sensible default (plugin name) if editors don’t set one.
        $mform->setDefault(
            'config_customtitle',
            get_string('pluginname', 'block_glossarysearch')
        );

        // Glossary scope (optional).
        // Allow the editor to pin this block instance to a single glossary.
        // If 0 (the default), the block searches all glossaries in the course.
        // NOTE: Use integer 0 instead of '' because the element type is PARAM_INT.
        $options = [0 => get_string('allcourseglossaries', 'block_glossarysearch')];

        if (!empty($COURSE->id) && $COURSE->id != SITEID) {
            // On a course page: list glossaries in that course (alphabetical).
            if ($glossaries = $DB->get_records('glossary', ['course' => $COURSE->id], 'name')) {
                foreach ($glossaries as $g) {
                    $options[(int)$g->id] = format_string($g->name);
                }
            }
        }
        // Select element to choose a specific glossary (or "All course glossaries").
        $mform->addElement(
            'select',
            'config_glossaryid',
            get_string('configglossary', 'block_glossarysearch'),
            $options
        );
        $mform->setType('config_glossaryid', PARAM_INT);
        $mform->setDefault('config_glossaryid', 0);
        // Add a help icon explaining the setting (string defined in lang pack).
        if ($mform->elementExists('config_glossaryid')) {
            $mform->addHelpButton('config_glossaryid', 'configglossary', 'block_glossarysearch');
        }
    }
}

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
 * Settings for the Glossary Search block
 *
 * @package    block_glossarysearch
 * @copyright  2025 Alan Chadwick (original author, released in Moodle forum)
 * @copyright  2025 Sadik Mert (rewrite & further development)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'block_glossarysearch/perpage_heading',
        get_string('perpage_heading', 'block_glossarysearch'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'block_glossarysearch/perpage',
        get_string('perpage', 'block_glossarysearch'),
        get_string('perpage_desc', 'block_glossarysearch'),
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'block_glossarysearch/colors_heading',
        get_string('colors_heading', 'block_glossarysearch'),
        ''
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'block_glossarysearch/primarycolor',
        get_string('primarycolor', 'block_glossarysearch'),
        get_string('primarycolor_desc', 'block_glossarysearch'),
        '#0073e6'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'block_glossarysearch/secondarycolor',
        get_string('secondarycolor', 'block_glossarysearch'),
        get_string('secondarycolor_desc', 'block_glossarysearch'),
        '#005bb5'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'block_glossarysearch/highlightcolor',
        get_string('highlightcolor', 'block_glossarysearch'),
        get_string('highlightcolor_desc', 'block_glossarysearch'),
        '#222'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'block_glossarysearch/highlightbg',
        get_string('highlightbg', 'block_glossarysearch'),
        get_string('highlightbg_desc', 'block_glossarysearch'),
        '#ffe082'
    ));
}

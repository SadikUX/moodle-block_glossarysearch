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
 * Glossary search block
 *
 * @package    block_glossarysearch
 * @copyright  2025 Alan Chadwick (original author, released in Moodle forum)
 * @copyright  2025 Sadik Mert (rewrite & further development)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Bring in our helper that builds the WHERE clause for whole-word / substring.
require_once(__DIR__ . '/locallib.php');

/**
 * Glossary search block class
 *
 * @package    block_glossarysearch
 */
class block_glossarysearch extends block_base {
    /**
     * Initialize the block
     */
    public function init() {
        // Default block title (overridden per instance in specialization()).
        $this->title = get_string('pluginname', 'block_glossarysearch');
    }

    /**
     * Specify where this block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        // Allow this block on course pages, dashboard (My), and front page.
        return [
            'course-view' => true,
            'my' => true,
            'site' => true,
        ];
    }

    /**
     * Allow multiple instances of this block.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        // Allow multiple instances per page if desired.
        return true;
    }

    /**
     * No global config for this block.
     *
     * @return bool
     */
    public function has_config() {
        // No site-level admin settings (we use per-instance config via edit_form.php).
        return false;
    }

    /**
     * Set the block title to a custom title if configured.
     */
    public function specialization() {
        // If a custom title is set on this block instance, use it.
        if (!empty($this->config) && !empty($this->config->customtitle)) {
            $this->title = format_string($this->config->customtitle);
        }
    }

    /**
     * Returns the content of the block.
     *
     * This method generates the HTML content displayed within the block.
     * If the content has already been generated, the cached version is returned.
     *
     * @return stdClass The block content object.
     */
    public function get_content() {
        global $OUTPUT, $DB, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Read request parameters (namespaced with gs_ to avoid collisions).
        $q         = optional_param('gs_q', '', PARAM_RAW_TRIMMED);
        $page      = optional_param('gs_page', 0, PARAM_INT);
        $wholeword = optional_param('gs_wholeword', 0, PARAM_BOOL);
        $perpage   = 10;

        // Scope: glossary selection (from block config dropdown) and course.
        $glossaryid = !empty($this->config->glossaryid) ? (int)$this->config->glossaryid : 0;
        $courseid   = isset($COURSE->id) ? (int)$COURSE->id : SITEID;

        // Build glossary dropdown (when on a course) to narrow search.
        $glossaries = [];
        if ($courseid && $courseid != SITEID) {
            $glossaries = $DB->get_records('glossary', ['course' => $courseid], 'name');
        } else {
            if ($glossaryid) {
                $glossaries = $DB->get_records('glossary', ['id' => $glossaryid]);
            }
        }

        $selectoptions = [0 => get_string('allcourseglossaries', 'block_glossarysearch')];
        foreach ($glossaries as $g) {
            $selectoptions[(int)$g->id] = format_string($g->name);
        }
        $currentgid = $glossaryid ? $glossaryid : optional_param('gs_gid', 0, PARAM_INT);

        // Render the small search form inside the block (stacked layout).
        $formurl = new moodle_url($this->page->url, ['blockid' => $this->instance->id]);
        $formhtml = html_writer::start_tag('form', ['method' => 'get', 'action' => $formurl->out(false)]);
        foreach ($this->page->url->params() as $k => $v) {
            $formhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => s($k), 'value' => s($v)]);
        }
        $formhtml .= html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'text', 'name' => 'gs_q', 'value' => s($q),
                'placeholder' => get_string('searchplaceholder', 'block_glossarysearch'),
                'aria-label' => get_string('search', 'block_glossarysearch'),
            ]),
            'glossarysearch-input'
        );
        if (!empty($glossaries)) {
            $formhtml .= html_writer::div(
                html_writer::select($selectoptions, 'gs_gid', $currentgid, null),
                'glossarysearch-select'
            );
        }
        $cbattrs = [
            'type'  => 'checkbox',
            'name'  => 'gs_wholeword',
            'value' => 1,
            'id'    => 'id_gs_wholeword',
        ];
        if ($wholeword) {
            $cbattrs['checked'] = 'checked';
        }
        $checkboxhtml  = html_writer::empty_tag('input', $cbattrs);
        $checkboxhtml .= html_writer::tag(
            'label',
            get_string('wholewordonly', 'block_glossarysearch'),
            ['for' => 'id_gs_wholeword']
        );
        $formhtml .= html_writer::div($checkboxhtml, 'glossarysearch-checkbox');
        $formhtml .= html_writer::div(
            html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search', 'block_glossarysearch')]),
            'glossarysearch-submit'
        );
        $formhtml .= html_writer::end_tag('form');

        // Prepare template context for results (if any).
        $templatecontext = [
            'formhtml' => $formhtml,
        ];

        if ($q !== '') {
            [$textwhere, $textparams] = block_glossarysearch_build_where($q, (bool)$wholeword);
            $wheres = [];
            $params = [];
            $wheres[] = '(' . $textwhere . ')';
            $params   = $params + $textparams;
            $wheres[] = 'ge.approved = :approved';
            $params['approved'] = 1;
            if (!empty($currentgid)) {
                $wheres[] = 'ge.glossaryid = :gid';
                $params['gid'] = $currentgid;
            } else if ($courseid && $courseid != SITEID) {
                $wheres[] = 'g.course = :courseid';
                $params['courseid'] = $courseid;
            } else if (!empty($glossaryid)) {
                $wheres[] = 'ge.glossaryid = :gid2';
                $params['gid2'] = $glossaryid;
            }
            $where = 'WHERE ' . implode(' AND ', $wheres);
            $countsql = "SELECT COUNT(1)
                           FROM {glossary_entries} ge
                           JOIN {glossary} g ON g.id = ge.glossaryid
                         $where";
            $total = $DB->count_records_sql($countsql, $params);
            $sql = "SELECT ge.id, ge.concept, ge.definition, ge.glossaryid, g.name AS glossaryname
                      FROM {glossary_entries} ge
                      JOIN {glossary} g ON g.id = ge.glossaryid
                    $where
                  ORDER BY ge.concept ASC";
            $entries = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
            if ($entries) {
                $list = html_writer::start_tag('ul', ['class' => 'glossarysearch-results']);
                foreach ($entries as $e) {
                    $concept = format_string($e->concept);
                    $def     = format_text($e->definition, FORMAT_HTML, ['filter' => true]);
                    if ($wholeword) {
                        $pattern = '/(?<![A-Za-z0-9_])(' . preg_quote($q, '/') . ')(?![A-Za-z0-9_])/i';
                    } else {
                        $pattern = '/(' . preg_quote($q, '/') . ')/i';
                    }
                    $concept = preg_replace($pattern, '<mark>$1</mark>', $concept);
                    $def     = preg_replace($pattern, '<mark>$1</mark>', $def);
                    $item  = html_writer::tag('strong', $concept);
                    $item .= html_writer::tag('div', $def, ['class' => 'glossarysearch-def']);
                    $item .= html_writer::tag('div', s($e->glossaryname), ['class' => 'glossarysearch-meta']);
                    $list .= html_writer::tag('li', $item);
                }
                $list .= html_writer::end_tag('ul');
                $templatecontext['results'] = $list;
                $base = new moodle_url($this->page->url, [
                    'gs_q'         => $q,
                    'gs_gid'       => $currentgid,
                    'gs_wholeword' => (int)$wholeword,
                ]);
                $paging = $OUTPUT->paging_bar($total, $page, $perpage, $base);
                $templatecontext['paging'] = $paging;
            } else {
                $templatecontext['results'] = html_writer::div(
                    get_string('noresults', 'block_glossarysearch'),
                    'glossarysearch-empty'
                );
            }
        } else {
            $templatecontext['help'] = get_string('enterquery', 'block_glossarysearch');
        }

        $this->content->text = $OUTPUT->render_from_template('block_glossarysearch/content', $templatecontext);
        return $this->content;
    }
}

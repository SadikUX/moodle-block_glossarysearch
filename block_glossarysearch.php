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

defined('MOODLE_INTERNAL') || die();

// Bring in our helpers for building WHERE clauses.
require_once(__DIR__ . '/locallib.php');

class block_glossarysearch extends block_base {

    public function init() {
        // Default block title (overridden per instance in specialization()).
        $this->title = get_string('pluginname', 'block_glossarysearch');
    }

    public function applicable_formats() {
        // Allow this block on course pages, dashboard (My), and front page.
        return [
            'course-view' => true,
            'my' => true,
            'site' => true
        ];
    }

    public function instance_allow_multiple() {
        // Allow multiple instances per page if desired.
        return true;
    }

    public function has_config() {
        // No site-level admin settings (we use per-instance config via edit_form.php).
        return false;
    }

    public function specialization() {
        // If a custom title is set on this block instance, use it.
        if (!empty($this->config) && !empty($this->config->customtitle)) {
            $this->title = format_string($this->config->customtitle);
        }
    }

    public function get_content() {
        global $OUTPUT, $PAGE, $DB, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // ---------------------------
        // Read request parameters (namespaced with gs_ to avoid collisions).
        // ---------------------------
        $q         = optional_param('gs_q', '', PARAM_RAW_TRIMMED); // search query
        $page      = optional_param('gs_page', 0, PARAM_INT);       // paging
        $wholeword = optional_param('gs_wholeword', 0, PARAM_BOOL); // Whole word only
        $perpage   = 10;

        // Scope: glossary selection (from block config dropdown) and course.
        $glossaryid = !empty($this->config->glossaryid) ? (int)$this->config->glossaryid : 0;
        $courseid   = isset($COURSE->id) ? (int)$COURSE->id : SITEID;

        // ---------------------------
        // Build glossary dropdown (when on a course) to narrow search.
        // ---------------------------
        $glossaries = [];
        if ($courseid && $courseid != SITEID) {
            // On a course page: list the course's glossaries by name.
            $glossaries = $DB->get_records('glossary', ['course' => $courseid], 'name');
        } else {
            // On dashboard / front page: if instance is pre-configured to one glossary, expose only that.
            if ($glossaryid) {
                $glossaries = $DB->get_records('glossary', ['id' => $glossaryid]);
            }
        }

        // Dropdown options (first option = "All course glossaries"). Use 0 as the key (PARAM_INT safe).
        $selectoptions = [0 => get_string('allcourseglossaries', 'block_glossarysearch')];
        foreach ($glossaries as $g) {
            $selectoptions[(int)$g->id] = format_string($g->name);
        }
        $currentgid = $glossaryid ? $glossaryid : optional_param('gs_gid', 0, PARAM_INT);

        // ---------------------------
        // Render the small search form inside the block (stacked layout).
        // ---------------------------
        $formurl = new moodle_url($PAGE->url, ['blockid' => $this->instance->id]);
        $formhtml = html_writer::start_tag('form', ['method' => 'get', 'action' => $formurl->out(false)]);

        // Preserve existing URL params (e.g. course view vars) so we don't lose context.
        foreach ($PAGE->url->params() as $k => $v) {
            $formhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => s($k), 'value' => s($v)]);
        }

        // Search input (own line).
        $formhtml .= html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'text', 'name' => 'gs_q', 'value' => s($q),
                'placeholder' => get_string('searchplaceholder', 'block_glossarysearch'),
                'aria-label' => get_string('search', 'block_glossarysearch')
            ]),
            'glossarysearch-input'
        );

        // Glossary dropdown (own line, only if we actually have a list).
        if (!empty($glossaries)) {
            $formhtml .= html_writer::div(
                html_writer::select($selectoptions, 'gs_gid', $currentgid, null),
                'glossarysearch-select'
            );
        }

        // Whole word only checkbox (own line, checkbox grouped with its label).
        $cbattrs = [
            'type'  => 'checkbox',
            'name'  => 'gs_wholeword',
            'value' => 1,
            'id'    => 'id_gs_wholeword'
        ];
        if ($wholeword) { $cbattrs['checked'] = 'checked'; }

        $checkboxhtml  = html_writer::empty_tag('input', $cbattrs);
        $checkboxhtml .= html_writer::tag('label',
            get_string('wholewordonly', 'block_glossarysearch'),
            ['for' => 'id_gs_wholeword']
        );
        $formhtml .= html_writer::div($checkboxhtml, 'glossarysearch-checkbox');

        // Submit button (own line).
        $formhtml .= html_writer::div(
            html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search', 'block_glossarysearch')]),
            'glossarysearch-submit'
        );

        $formhtml .= html_writer::end_tag('form');
        $this->content->text .= html_writer::div($formhtml, 'glossarysearch-form');

        // ---------------------------
        // If there's a query, run the search and render results.
        // ---------------------------
        if ($q !== '') {

            // Text WHERE (concept/definition) and alias WHERE (keywords).
            list($textwhere,  $textparams)  = block_glossarysearch_build_where($q, (bool)$wholeword);
            list($aliaswhere, $aliasparams) = block_glossarysearch_build_where_alias($q, (bool)$wholeword);

            // Combine (match either text or alias).
            $wheres = [];
            $params = [];

            $wheres[] = '((' . $textwhere . ') OR (' . $aliaswhere . '))';
            $params   = $params + $textparams + $aliasparams;

            // Only approved entries.
            $wheres[] = 'ge.approved = :approved';
            $params['approved'] = 1;

            // Scope by glossary/course.
            if (!empty($currentgid)) {
                // Specific glossary (dropdown selection or instance pre-config).
                $wheres[] = 'ge.glossaryid = :gid';
                $params['gid'] = $currentgid;

            } else if ($courseid && $courseid != SITEID) {
                // Course page and no specific glossary chosen: limit to that course's glossaries.
                $wheres[] = 'g.course = :courseid';
                $params['courseid'] = $courseid;

            } else if (!empty($glossaryid)) {
                // Dashboard/front page with an instance-configured glossary.
                $wheres[] = 'ge.glossaryid = :gid2';
                $params['gid2'] = $glossaryid;
            }

            $where = 'WHERE ' . implode(' AND ', $wheres);

            // Count total for paging (DISTINCT to avoid duplicates when multiple aliases match).
            $countsql = "SELECT COUNT(DISTINCT ge.id)
                           FROM {glossary_entries} ge
                           JOIN {glossary} g ON g.id = ge.glossaryid
                      LEFT JOIN {glossary_alias} ga ON ga.entryid = ge.id
                         $where";
            $total = $DB->count_records_sql($countsql, $params);

            // Fetch page of results (DISTINCT to avoid dupes).
            $sql = "SELECT DISTINCT ge.id, ge.concept, ge.definition, ge.glossaryid, g.name AS glossaryname
                      FROM {glossary_entries} ge
                      JOIN {glossary} g ON g.id = ge.glossaryid
                 LEFT JOIN {glossary_alias} ga ON ga.entryid = ge.id
                    $where
                  ORDER BY ge.concept ASC";

            $entries = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

            // ---------------------------
            // Render results.
            // ---------------------------
            if ($entries) {
                $list = html_writer::start_tag('ul', ['class' => 'glossarysearch-results']);

                foreach ($entries as $e) {
                    $concept = format_string($e->concept);
                    $def     = format_text($e->definition, FORMAT_HTML, ['filter' => true]);

                    // Simple highlight (case-insensitive).
                    if ($wholeword) {
                        // Whole-word highlighting: conservative boundary on letters/numbers underscore.
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
                $this->content->text .= $list;

                // Paging bar (preserve filters).
                $base = new moodle_url($PAGE->url, [
                    'gs_q'         => $q,
                    'gs_gid'       => $currentgid,
                    'gs_wholeword' => (int)$wholeword,
                ]);
                $paging = $OUTPUT->paging_bar($total, $page, $perpage, $base);
                $this->content->text .= html_writer::div($paging, 'glossarysearch-paging');

            } else {
                $this->content->text .= html_writer::div(get_string('noresults', 'block_glossarysearch'), 'glossarysearch-empty');
            }

        } else {
            // Hint text when no query yet.
            $this->content->text .= html_writer::div(get_string('enterquery', 'block_glossarysearch'), 'glossarysearch-help');
        }

        return $this->content;
    }
}

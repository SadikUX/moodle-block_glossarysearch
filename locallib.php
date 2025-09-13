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
 * Library of interface functions and constants for block_glossarysearch
 *
 * @package    block_glossarysearch
 * @copyright  2025 Alan Chadwick (original author, released in Moodle forum)
 * @copyright  2025 Sadik Mert (rewrite & further development)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Build a WHERE fragment and params for glossary search.
 *
 * @param string $q          The raw search text (already trimmed).
 * @param bool   $wholeword  True = enforce whole-word match.
 * @return array [$where, $params]
 */
function block_glossarysearch_build_where(string $q, bool $wholeword): array {
    global $DB, $CFG;

    $q = trim($q);
    if ($q === '') {
        return ['1=1', []];
    }

    // Escape regex metacharacters safely (for DB regex branches).
    $quoted = preg_quote($q, '/');

    // Default substring search (portable LIKE on concept/definition)
    // Use distinct param names for each placeholder.
    $like1      = $DB->sql_like('ge.concept', ':q1', false);
    $like2      = $DB->sql_like('ge.definition', ':q2', false);
    $likewhere  = "($like1 OR $like2)";
    $likeparams = ['q1' => "%$q%", 'q2' => "%$q%"];

    if (!$wholeword) {
        return [$likewhere, $likeparams];
    }

    // WHOLE WORD MODE.
    $dbtype = $CFG->dbtype ?? '';

    // Einheitlicher, portabler LIKE-Fallback für alle DBs (simuliert Wortgrenzen).
    $lhs = $DB->sql_compare_text($DB->sql_concat("' '", 'ge.concept', "' '"));
    $rhs = $DB->sql_compare_text($DB->sql_concat("' '", 'ge.definition', "' '"));
    $where = "(
        $lhs LIKE :w1 OR $lhs LIKE :w2 OR $lhs LIKE :w3 OR $lhs LIKE :w4 OR
        $rhs LIKE :w5 OR $rhs LIKE :w6 OR $rhs LIKE :w7 OR $rhs LIKE :w8
    )";
    $params = [
        'w1' => '% ' . $q . ' %',
        'w2' => $q . ' %',
        'w3' => '% ' . $q,
        'w4' => ' ' . $q . ' ',
        'w5' => '% ' . $q . ' %',
        'w6' => $q . ' %',
        'w7' => '% ' . $q,
        'w8' => ' ' . $q . ' ',
    ];
    return [$where, $params];
}

<?php
// This file is part of the Glossary Search block.
//
// Local library functions for block_glossarysearch.

defined('MOODLE_INTERNAL') || die();

/**
 * Build a WHERE fragment and params for concept/definition matching.
 *
 * @param string $q          The raw search text (already trimmed).
 * @param bool   $wholeword  True = enforce whole-word match.
 * @return array [$where, $params]
 */
function block_glossarysearch_build_where(string $q, bool $wholeword): array {
    global $DB, $CFG;

    $q = trim($q);
    if ($q === '') {
        return ['1=1', []]; // no filtering
    }

    // Escape regex metacharacters safely (for DB regex branches).
    $quoted = preg_quote($q, '/');

    // ---------- Default substring search (portable LIKE on concept/definition) ----------
    // Use distinct param names for each placeholder.
    $like1      = $DB->sql_like('ge.concept', ':q1', false);
    $like2      = $DB->sql_like('ge.definition', ':q2', false);
    $likewhere  = "($like1 OR $like2)";
    $likeparams = ['q1' => "%$q%", 'q2' => "%$q%"];

    if (!$wholeword) {
        return [$likewhere, $likeparams];
    }

    // ---------- WHOLE WORD MODE ----------
    $dbtype = $CFG->dbtype ?? '';

    // MySQL / MariaDB: POSIX word boundaries [[:<:]] … [[:>:]]
    if (strpos($dbtype, 'mysqli') !== false || strpos($dbtype, 'mariadb') !== false) {
        $re    = '[[:<:]]' . $quoted . '[[:>:]]';
        $where = "(ge.concept REGEXP :re1 OR ge.definition REGEXP :re2)";
        return [$where, ['re1' => $re, 're2' => $re]];
    }

    // PostgreSQL: case-insensitive regex with \y word boundaries.
    if (strpos($dbtype, 'pgsql') !== false) {
        // Double-escaped in PHP so the DB receives \y...\y.
        $re    = "\\y" . $quoted . "\\y";
        $where = "(ge.concept ~* :re1 OR ge.definition ~* :re2)";
        return [$where, ['re1' => $re, 're2' => $re]];
    }

    // ---------- Fallback (no regex): approximate whole word with space-padding + LIKE ----------
    // Use DB-portable concatenation via $DB->sql_concat(), then compare as text.
    $lhs = $DB->sql_compare_text($DB->sql_concat("' '", 'ge.concept', "' '"));
    $rhs = $DB->sql_compare_text($DB->sql_concat("' '", 'ge.definition', "' '"));

    // We check a few crude patterns to simulate word boundaries.
    $where = "(
        $lhs LIKE :w1 OR $lhs LIKE :w2 OR $lhs LIKE :w3 OR $lhs LIKE :w4 OR
        $rhs LIKE :w5 OR $rhs LIKE :w6 OR $rhs LIKE :w7 OR $rhs LIKE :w8
    )";

    $params = [
        'w1' => '% ' . $q . ' %', // middle
        'w2' => $q . ' %',        // start-ish
        'w3' => '% ' . $q,        // end-ish
        'w4' => ' ' . $q . ' ',   // exact (very short strings)
        'w5' => '% ' . $q . ' %',
        'w6' => $q . ' %',
        'w7' => '% ' . $q,
        'w8' => ' ' . $q . ' ',
    ];

    return [$where, $params];
}

/**
 * Build a WHERE fragment and params for alias/keyword matching (ga.alias).
 *
 * @param string $q
 * @param bool   $wholeword
 * @return array [$where, $params]
 */
function block_glossarysearch_build_where_alias(string $q, bool $wholeword): array {
    global $DB, $CFG;

    $q = trim($q);
    if ($q === '') {
        return ['1=1', []]; // no filtering
    }

    $quoted = preg_quote($q, '/');

    // ---------- Default substring search (portable LIKE on alias) ----------
    $like       = $DB->sql_like('ga.alias', ':qa', false);
    $likewhere  = "($like)";
    $likeparams = ['qa' => "%$q%"];

    if (!$wholeword) {
        return [$likewhere, $likeparams];
    }

    // ---------- WHOLE WORD MODE ----------
    $dbtype = $CFG->dbtype ?? '';

    // MySQL/MariaDB: POSIX word boundaries
    if (strpos($dbtype, 'mysqli') !== false || strpos($dbtype, 'mariadb') !== false) {
        $re = '[[:<:]]' . $quoted . '[[:>:]]';
        return ["(ga.alias REGEXP :rea)", ['rea' => $re]];
    }

    // PostgreSQL: case-insensitive regex with \y boundaries
    if (strpos($dbtype, 'pgsql') !== false) {
        $re = "\\y" . $quoted . "\\y";
        return ["(ga.alias ~* :rea)", ['rea' => $re]];
    }

    // Fallback: approximate with space-padded LIKE.
    $aliascmp = $DB->sql_compare_text($DB->sql_concat("' '", 'ga.alias', "' '"));
    $where = "(
        $aliascmp LIKE :w9 OR
        $aliascmp LIKE :w10 OR
        $aliascmp LIKE :w11 OR
        $aliascmp LIKE :w12
    )";

    $params = [
        'w9'  => '% ' . $q . ' %',
        'w10' => $q . ' %',
        'w11' => '% ' . $q,
        'w12' => ' ' . $q . ' ',
    ];

    return [$where, $params];
}

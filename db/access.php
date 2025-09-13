<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Capabilities for the Glossary search block.
 *
 * Conventions:
 * - block/xyz:addinstance   => who can add the block to course/front page (COURSE context)
 * - block/xyz:myaddinstance => who can add the block to their Dashboard (SYSTEM context)
 */
$capabilities = [

    // Allow any logged-in user to add the block to their own Dashboard.
    'block/glossarysearch:myaddinstance' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'user' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/my:manageblocks',
    ],

    // Allow managers, editing teachers (and optionally course creators) to add
    // the block to course pages (and the site front page which is a special course).
    'block/glossarysearch:addinstance' => [
        'riskbitmask'  => RISK_SPAM | RISK_XSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE, // <-- important: COURSE, not BLOCK
        'archetypes'   => [
            'manager'         => CAP_ALLOW,
            'editingteacher'  => CAP_ALLOW,
            'coursecreator'   => CAP_ALLOW, // optional but typical
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ],
];

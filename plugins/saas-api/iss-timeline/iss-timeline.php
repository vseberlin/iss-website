<?php
/**
 * Timeline module for SuperSaaS calendar items.
 *
 * Uses the existing `iss_calendar_item` CPT and augments it with
 * public-facing timeline fields + rendering helpers.
 */

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/timeline-meta.php';
require_once __DIR__ . '/includes/timeline-query.php';
require_once __DIR__ . '/includes/timeline-render.php';
require_once __DIR__ . '/includes/blocks.php';
require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/timeline-editor.php';


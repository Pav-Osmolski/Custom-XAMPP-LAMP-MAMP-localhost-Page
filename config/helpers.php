<?php
/**
 * Helpers loader
 *
 * Central include file that loads all helper modules in a stable order.
 * Keep this path referenced by config/config.php.
 *
 * @author  Pawel Osmolski
 * @version 2.0
 */

// Load polyfills first
require_once __DIR__ . '/helpers/polyfills.php';

// Security
require_once __DIR__ . '/helpers/security.php';

// Core utilities
require_once __DIR__ . '/helpers/paths.php';
require_once __DIR__ . '/helpers/io.php';
require_once __DIR__ . '/helpers/json.php';
require_once __DIR__ . '/helpers/filesystem.php';
require_once __DIR__ . '/helpers/templates.php';
require_once __DIR__ . '/helpers/booleans.php';

// System + UI helpers
require_once __DIR__ . '/helpers/system.php';
require_once __DIR__ . '/helpers/ui.php';
require_once __DIR__ . '/helpers/export.php';
require_once __DIR__ . '/helpers/apache.php';
require_once __DIR__ . '/helpers/mysql.php';

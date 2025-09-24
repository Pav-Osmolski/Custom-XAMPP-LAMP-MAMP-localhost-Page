<?php
/**
 * Read-only JSON config endpoint for the UI.
 * Exposes only: folders.json, link_templates.json, dock.json.
 * Uses same-origin checks. No auth tokens required for GET.
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */
require_once __DIR__ . '/../config/security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!function_exists('request_is_same_origin') || !request_is_same_origin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$map = [
    'folders'         => __DIR__ . '/../config/folders.json',
    'link_templates'  => __DIR__ . '/../config/link_templates.json',
    'dock'            => __DIR__ . '/../config/dock.json',
];

$key = $_GET['file'] ?? '';
if (!isset($map[$key])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown file']);
    exit;
}

$path = $map[$key];
if (!is_readable($path)) {
    // Return an empty array rather than leaking filesystem details
    echo "[]";
    exit;
}

$raw = file_get_contents($path);
echo ($raw !== false && $raw !== '') ? $raw : "[]";

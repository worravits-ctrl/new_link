<?php
// Simple JSON file-based API for links storage
// Endpoints:
// GET    /api/links.php           -> list all
// POST   /api/links.php           -> create one (JSON body)
// PUT    /api/links.php           -> replace all (JSON array body)
// DELETE /api/links.php           -> delete by ids (JSON body: { ids: [] })

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$baseDir = dirname(__DIR__);
$dataDir = $baseDir . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}
$file = $dataDir . DIRECTORY_SEPARATOR . 'links.json';
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

function read_links($file) {
    $json = @file_get_contents($file);
    if ($json === false) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function write_links($file, $links) {
    $tmp = $file . '.tmp';
    file_put_contents($tmp, json_encode($links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    rename($tmp, $file);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $links = read_links($file);
        echo json_encode($links, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if ($method === 'POST') {
        if (!is_array($data)) throw new Exception('Invalid JSON');
        $links = read_links($file);
        // sanitize and append one link
        $link = [
            'id' => $data['id'] ?? round(microtime(true) * 1000),
            'url' => $data['url'] ?? '',
            'title' => $data['title'] ?? '',
            'favicon' => $data['favicon'] ?? '',
            'dateAdded' => $data['dateAdded'] ?? date(DATE_ATOM)
        ];
        $links[] = $link;
        write_links($file, $links);
        echo json_encode($link, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'PUT') {
        if (!is_array($data)) throw new Exception('Invalid JSON');
        // replace all
        write_links($file, $data);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        if (!isset($data['ids']) || !is_array($data['ids'])) throw new Exception('Invalid JSON');
        $ids = array_map('strval', $data['ids']);
        $links = read_links($file);
        $filtered = array_values(array_filter($links, function ($l) use ($ids) {
            return !in_array(strval($l['id']), $ids, true);
        }));
        write_links($file, $filtered);
        echo json_encode(['removed' => count($links) - count($filtered)]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

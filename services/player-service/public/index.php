<?php

declare(strict_types=1);

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Http.php';

use PDO;
use PlayerService\Database;
use PlayerService\Http;

$pdo = Database::connection();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?? '/';
$query = [];
parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);

if ($path === '/health') {
    Http::json(['status' => 'ok']);
    return;
}

switch (true) {
    case $method === 'GET' && $path === '/players':
        Http::json(Http::paginatePlayers($pdo, $query));
        return;

    case $method === 'GET' && preg_match('#^/players/(\d+)$#', $path, $matches):
        $stmt = $pdo->prepare('SELECT id, team_id, number, name FROM players WHERE id = :id');
        $stmt->bindValue(':id', (int) $matches[1], PDO::PARAM_INT);
        $stmt->execute();
        $player = $stmt->fetch();
        if (!$player) {
            Http::json(['error' => 'not_found'], 404);
            return;
        }
        Http::json($player);
        return;

    case $method === 'POST' && $path === '/players':
        $body = Http::getJsonBody();
        $name = isset($body['name']) ? trim((string) $body['name']) : '';
        $teamId = isset($body['teamId']) ? (int) $body['teamId'] : 0;
        $number = isset($body['number']) ? ($body['number'] === '' ? null : (int) $body['number']) : null;

        if ($name === '' || $teamId <= 0) {
            Http::json(['error' => 'invalid_payload', 'message' => 'name and teamId are required'], 400);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO players (team_id, number, name) VALUES (:teamId, :number, :name)');
        $stmt->bindValue(':teamId', $teamId, PDO::PARAM_INT);
        $stmt->bindValue(':number', $number, $number === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $pdo->lastInsertId();

        Http::json(['id' => $id, 'teamId' => $teamId, 'number' => $number, 'name' => $name], 201);
        return;

    case $method === 'PUT' && preg_match('#^/players/(\d+)$#', $path, $matches):
        $id = (int) $matches[1];
        $body = Http::getJsonBody();
        $name = isset($body['name']) ? trim((string) $body['name']) : '';
        $teamId = isset($body['teamId']) ? (int) $body['teamId'] : 0;
        $number = isset($body['number']) ? ($body['number'] === '' ? null : (int) $body['number']) : null;

        if ($name === '' || $teamId <= 0) {
            Http::json(['error' => 'invalid_payload', 'message' => 'name and teamId are required'], 400);
            return;
        }

        $stmt = $pdo->prepare('UPDATE players SET team_id = :teamId, number = :number, name = :name WHERE id = :id');
        $stmt->bindValue(':teamId', $teamId, PDO::PARAM_INT);
        $stmt->bindValue(':number', $number, $number === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            Http::json(['error' => 'not_found'], 404);
            return;
        }

        Http::json(['id' => $id, 'teamId' => $teamId, 'number' => $number, 'name' => $name]);
        return;

    case $method === 'DELETE' && preg_match('#^/players/(\d+)$#', $path, $matches):
        $stmt = $pdo->prepare('DELETE FROM players WHERE id = :id');
        $stmt->bindValue(':id', (int) $matches[1], PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            Http::json(['error' => 'not_found'], 404);
            return;
        }
        http_response_code(204);
        return;

    case $method === 'DELETE' && preg_match('#^/players/by-team/(\d+)$#', $path, $matches):
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM players WHERE team_id = :team');
        $stmt->bindValue(':team', (int) $matches[1], PDO::PARAM_INT);
        $stmt->execute();
        $pdo->commit();
        http_response_code(204);
        return;

    case $method === 'POST' && $path === '/players/bulk':
        $body = Http::getJsonBody();
        $teamId = isset($body['teamId']) ? (int) $body['teamId'] : 0;
        $players = isset($body['players']) && is_array($body['players']) ? $body['players'] : [];

        if ($teamId <= 0) {
            Http::json(['error' => 'invalid_payload', 'message' => 'teamId is required'], 400);
            return;
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO players (team_id, number, name) VALUES (:teamId, :number, :name)');
        foreach ($players as $player) {
            $name = isset($player['name']) ? trim((string) $player['name']) : '';
            $number = isset($player['number']) ? ($player['number'] === '' ? null : (int) $player['number']) : null;
            if ($name === '') {
                continue;
            }
            $stmt->bindValue(':teamId', $teamId, PDO::PARAM_INT);
            $stmt->bindValue(':number', $number, $number === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
        }
        $pdo->commit();
        Http::json(['status' => 'created'], 201);
        return;

    case $method === 'PUT' && $path === '/players/bulk':
        $body = Http::getJsonBody();
        $teamId = isset($body['teamId']) ? (int) $body['teamId'] : 0;
        $players = isset($body['players']) && is_array($body['players']) ? $body['players'] : [];

        if ($teamId <= 0) {
            Http::json(['error' => 'invalid_payload', 'message' => 'teamId is required'], 400);
            return;
        }

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM players WHERE team_id = :team')
            ->execute([':team' => $teamId]);

        $stmt = $pdo->prepare('INSERT INTO players (team_id, number, name) VALUES (:teamId, :number, :name)');
        foreach ($players as $player) {
            $name = isset($player['name']) ? trim((string) $player['name']) : '';
            $number = isset($player['number']) ? ($player['number'] === '' ? null : (int) $player['number']) : null;
            if ($name === '') {
                continue;
            }
            $stmt->bindValue(':teamId', $teamId, PDO::PARAM_INT);
            $stmt->bindValue(':number', $number, $number === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
        }
        $pdo->commit();
        Http::json(['status' => 'updated']);
        return;
}

Http::json(['error' => 'not_found'], 404);

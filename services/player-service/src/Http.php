<?php

declare(strict_types=1);

namespace PlayerService;

use PDO;

final class Http
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (!is_array($decoded)) {
            self::json(['error' => 'invalid_json'], 400);
            exit;
        }

        return $decoded;
    }

    public static function paginatePlayers(PDO $pdo, array $query): array
    {
        $page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $pageSize = isset($query['pageSize']) ? max(1, min(200, (int) $query['pageSize'])) : 10;
        $offset = ($page - 1) * $pageSize;

        $conditions = [];
        $params = [];

        if (isset($query['teamId']) && $query['teamId'] !== '') {
            $conditions[] = 'team_id = :teamId';
            $params[':teamId'] = (int) $query['teamId'];
        }

        if (isset($query['search']) && trim((string) $query['search']) !== '') {
            $conditions[] = '(LOWER(name) LIKE :search OR CAST(number AS CHAR) LIKE :search)';
            $params[':search'] = '%' . strtolower(trim((string) $query['search'])) . '%';
        }

        $whereSql = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM players ' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT id, team_id, number, name FROM players ' . $whereSql . ' ORDER BY number IS NULL, number ASC, name ASC LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'totalCount' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }
}

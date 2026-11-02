<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Service;

use OCP\IDBConnection;

class AccessLogService {

    private const TABLE = 'dlhg_shows_accesslog';

    public function __construct(
        private readonly IDBConnection $db,
    ) {}

    /**
     * Protokolliert einen Zugriff auf die App.
     * Pro user_id + session_id nur einmal speichern (UNIQUE Constraint).
     */
    public function logAccess(string $userId, string $sessionId): void {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::TABLE)
               ->values([
                   'user_id'    => $qb->createNamedParameter($userId),
                   'session_id' => $qb->createNamedParameter($sessionId),
                   'timestamp'  => $qb->createNamedParameter(time()),
               ]);
            $qb->executeStatement();
        } catch (\Exception) {
            // Duplicate entry ist ok (UNIQUE Constraint), wird ignoriert
        }
    }

    /**
     * Gibt die Zugriffstatistiken pro Benutzer zurück.
     * Zählt nur eindeutige Sessions (user_id + session_id Kombination).
     * @return array<int, array{user_id: string, count: int, last_access: int}>
     */
    public function getStatistics(): array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('user_id')
               ->selectAlias($qb->createFunction('COUNT(DISTINCT session_id)'), 'count')
               ->selectAlias($qb->createFunction('MAX(timestamp)'), 'last_access')
               ->from(self::TABLE)
               ->where($qb->expr()->isNotNull('session_id'))
               ->groupBy('user_id')
               ->orderBy('count', 'DESC');

            $result = $qb->executeQuery();
            $stats = [];
            while ($row = $result->fetch()) {
                $stats[] = [
                    'user_id'     => (string)$row['user_id'],
                    'count'       => (int)$row['count'],
                    'last_access' => (int)$row['last_access'],
                ];
            }
            $result->closeCursor();
            return $stats;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Gibt die Gesamtanzahl der eindeutigen Sessions zurück.
     */
    public function getTotalAccesses(): int {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->selectAlias($qb->createFunction('COUNT(DISTINCT session_id)'), 'total')
               ->from(self::TABLE)
               ->where($qb->expr()->isNotNull('session_id'));

            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();
            return $row ? (int)$row['total'] : 0;
        } catch (\Exception) {
            return 0;
        }
    }
}

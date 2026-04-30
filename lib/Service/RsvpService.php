<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Service;

use OCP\IDBConnection;

class RsvpService {

    private const TABLE = 'dlhg_shows_rsvp';

    public function __construct(
        private readonly IDBConnection $db,
    ) {}

    /**
     * Gibt alle RSVPs des Users zurück, indexiert nach calendarobject_id.
     * [ calendarobject_id => 'accepted'|'declined' ]
     */
    public function getForUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('calendarobject_id', 'response')
           ->from(self::TABLE)
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $map    = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['calendarobject_id']] = $row['response'];
        }
        $result->closeCursor();
        return $map;
    }

    /**
     * Speichert oder aktualisiert die Antwort eines Users für einen Termin.
     */
    public function upsert(int $calendarObjectId, string $calendarObjectUid, string $userId, string $response): void {
        // Prüfen ob bereits ein Eintrag existiert
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
           ->from(self::TABLE)
           ->where($qb->expr()->eq('calendarobject_id', $qb->createNamedParameter($calendarObjectId)))
           ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row    = $result->fetch();
        $result->closeCursor();

        if ($row) {
            // Update
            $qb = $this->db->getQueryBuilder();
            $qb->update(self::TABLE)
               ->set('response',     $qb->createNamedParameter($response))
               ->set('responded_at', $qb->createNamedParameter(time()))
               ->where($qb->expr()->eq('calendarobject_id', $qb->createNamedParameter($calendarObjectId)))
               ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            $qb->executeStatement();
        } else {
            // Insert
            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::TABLE)
               ->values([
                   'calendarobject_id'  => $qb->createNamedParameter($calendarObjectId),
                   'calendarobject_uid' => $qb->createNamedParameter($calendarObjectUid),
                   'user_id'            => $qb->createNamedParameter($userId),
                   'response'           => $qb->createNamedParameter($response),
                   'responded_at'       => $qb->createNamedParameter(time()),
               ]);
            $qb->executeStatement();
        }
    }

    /**
     * Löscht die Antwort eines Users für einen Termin (Antwort zurückziehen).
     */
    public function delete(int $calendarObjectId, string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TABLE)
           ->where($qb->expr()->eq('calendarobject_id', $qb->createNamedParameter($calendarObjectId)))
           ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $qb->executeStatement();
    }


    /**
     * Gibt je calendarobject_id die Summe der Zusagen und Absagen zurück.
     * [ calendarobject_id => ['accepted' => int, 'declined' => int] ]
     */
    public function getTotalsPerEvent(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('calendarobject_id', 'response')
        ->selectAlias($qb->createFunction('COUNT(*)'), 'total')
        ->from(self::TABLE)
        ->groupBy('calendarobject_id', 'response');

        $result = $qb->executeQuery();
        $totals = [];
        while ($row = $result->fetch()) {
            $id = (int)$row['calendarobject_id'];
            if (!isset($totals[$id])) {
                $totals[$id] = ['accepted' => 0, 'declined' => 0];
            }
            $totals[$id][$row['response']] = (int)$row['total'];
        }
        $result->closeCursor();
        return $totals;
    }




}
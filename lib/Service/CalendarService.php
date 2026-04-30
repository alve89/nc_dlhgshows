<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Service;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUserManager;
use Sabre\VObject\Reader;
use OCP\IDBConnection;


class CalendarService {

    public function __construct(
        private readonly CalDavBackend $calDavBackend,
        private readonly IUserManager $userManager,
        private readonly IDBConnection $db,
    ) {}

    /**
     * Returns all calendars for the given user.
     */
    public function getCalendars(string $userId): array {
        $calendars = $this->calDavBackend->getCalendarsForUser("principals/users/{$userId}");
        return array_map(fn($cal) => [
            'id'          => $cal['id'],
            'uri'         => $cal['uri'],
            'displayname' => $cal['{DAV:}displayname'] ?? $cal['uri'],
            'color'       => $cal['{http://apple.com/ns/ical/}calendar-color'] ?? null,
        ], $calendars);
    }

    /**
     * Returns all VEVENT objects from a specific calendar, optionally
     * filtered to a date range.
     *
     * @return array<int, array{
     *   uid: string,
     *   summary: string,
     *   start: string,
     *   end: string,
     *   allDay: bool,
     *   location: string,
     *   description: string,
     *   calendarId: int|string,
     * }>
     */
    public function getEvents(string $userId, int|string $calendarId, ?string $start = null, ?string $end = null): array {
        $calendarInfo = $this->calDavBackend->getCalendarById((int)$calendarId);
        if (!$calendarInfo) {
            throw new \RuntimeException('Calendar not found.');
        }

        // Owner und Kalender-URI für die Calendar-App-URL
        $principalUri = $calendarInfo['principaluri'] ?? '';
        $ownerUid     = str_replace('principals/users/', '', $principalUri);
        $calendarUri  = $calendarInfo['uri'] ?? '';

        $objects = $this->calDavBackend->getCalendarObjects((int)$calendarId);
        // Timestamps direkt aus DB holen
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'firstoccurence')
        ->from('calendarobjects')
        ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter((int)$calendarId)));

        $result = $qb->executeQuery();
        $timestamps = [];
        while ($row = $result->fetch()) {
            $timestamps[(int)$row['id']] = (int)$row['firstoccurence'];
        }
        $result->closeCursor();

        $events = [];
        foreach ($objects as $object) {
            $data = $this->calDavBackend->getCalendarObject((int)$calendarId, $object['uri']);
            if (!$data || empty($data['calendardata'])) {
                continue;
            }

            try {
                $vCalendar = Reader::read($data['calendardata']);
            } catch (\Exception) {
                continue;
            }

            foreach ($vCalendar->select('VEVENT') as $vEvent) {
                $dtStart = $vEvent->DTSTART;
                $dtEnd   = $vEvent->DTEND ?? $vEvent->DURATION ?? null;

                $tz = new \DateTimeZone('Europe/Berlin');

                $allDay   = $dtStart && !$dtStart->hasTime();
                $startStr = $dtStart
                    ? ($allDay
                        ? $dtStart->getDateTime()->format('Y-m-d')
                        : $dtStart->getDateTime()->setTimezone($tz)->format(\DateTimeInterface::ATOM))
                    : '';
                $endStr = $dtEnd
                    ? ($allDay
                        ? $dtEnd->getDateTime()->format('Y-m-d')
                        : $dtEnd->getDateTime()->setTimezone($tz)->format(\DateTimeInterface::ATOM))
                    : $startStr;

                // Datum für die URL
                $startDate = $dtStart
                ? $dtStart->getDateTime()->format('Y-m-01')
                : 'now';
                
                $firstOccurence = $timestamps[(int)$object['id']] ?? 0;


                // Calendar-App-URL bauen
                $davPath        = '/remote.php/dav/calendars/' . $ownerUid . '/' . $calendarUri . '/' . $object['uri'];
                $calendarAppUrl = '/index.php/apps/calendar/dayGridMonth/'
                                . $startDate . '/edit/popover/'
                                . base64_encode($davPath) . '/'
                                . $firstOccurence;

                $events[] = [
                    'calendarObjectId' => (int)$object['id'],
                    'uid'              => (string)($vEvent->UID ?? uniqid()),
                    'summary'          => (string)($vEvent->SUMMARY ?? '(kein Titel)'),
                    'start'            => $startStr,
                    'end'              => $endStr,
                    'allDay'           => $allDay,
                    'location'         => (string)($vEvent->LOCATION ?? ''),
                    'description'      => (string)($vEvent->DESCRIPTION ?? ''),
                    'calendarId'       => $calendarId,
                    'calendarAppUrl'   => $calendarAppUrl,
                ];
            }
        }
//var_dump($object['firstoccurence'] ?? 'NULL');

        usort($events, fn($a, $b) => strcmp($a['start'], $b['start']));

        return $events;
    }
}

<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Controller;

use OCA\Dlhgshows\AppInfo\Application;
use OCA\Dlhgshows\Service\CalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class CalendarController extends Controller {

    public function __construct(
        IRequest $request,
        private readonly CalendarService $calendarService,
        private readonly ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * GET /apps/dlhgshows/api/calendars
     * Returns all calendars for the logged-in user.
     */
    #[NoAdminRequired]
    #[ApiRoute(verb: 'GET', url: '/api/calendars')]
    public function index(): DataResponse {
        try {
            $calendars = $this->calendarService->getCalendars($this->userId ?? '');
            return new DataResponse($calendars);
        } catch (\Throwable $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /apps/dlhgshows/api/calendars/{calendarId}/events
     * Returns all events in a calendar. Optionally pass ?start=&end= (ISO 8601).
     */
    #[NoAdminRequired]
    #[ApiRoute(verb: 'GET', url: '/api/calendars/{calendarId}/events')]
    public function events(int $calendarId): DataResponse {
        $start = $this->request->getParam('start');
        $end   = $this->request->getParam('end');

        try {
            $events = $this->calendarService->getEvents(
                $this->userId ?? '',
                $calendarId,
                $start,
                $end,
            );
            return new DataResponse($events);
        } catch (\Throwable $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }
}

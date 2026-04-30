<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Controller;

use OCA\Dlhgshows\AppInfo\Application;
use OCA\Dlhgshows\Service\CalendarService;
use OCA\Dlhgshows\Service\RsvpService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;


class PageController extends Controller {

    public function __construct(
        IRequest $request,
        private readonly CalendarService $calendarService,
        private readonly RsvpService $rsvpService,
        private readonly IGroupManager $groupManager,
        private readonly IAppConfig $appConfig,
        private readonly ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse {
        $userId       = $this->userId ?? '';
        $calendarId    = $this->appConfig->getValueInt(Application::APP_ID, 'calendar_id', 33);
        $calendarName  = $this->appConfig->getValueString(Application::APP_ID, 'calendar_name', 'Teamkalender');
        $statsGroupRaw = $this->appConfig->getValueString(Application::APP_ID, 'stats_groups', '');
        $statsGroups   = $statsGroupRaw !== '' ? json_decode($statsGroupRaw, true) ?? [] : [];

        $events = $this->calendarService->getEvents($userId, $calendarId);
        $rsvps  = $this->rsvpService->getForUser($userId);
        $totals = $this->rsvpService->getTotalsPerEvent();

        $canSeeStats = false;
        foreach ($statsGroups as $group) {
            if ($this->groupManager->isInGroup($userId, $group)) {
                $canSeeStats = true;
                break;
            }
        }
        
        return new TemplateResponse(Application::APP_ID, 'index', [
            'calendarName' => $calendarName,
            'events'       => $events,
            'rsvps'        => $rsvps,
            'totals'       => $totals,
            'userId'       => $userId,
            'canSeeStats'  => $canSeeStats,
        ]);
    }
}
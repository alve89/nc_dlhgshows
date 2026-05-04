<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Settings;

use OCA\Dlhgshows\AppInfo\Application;
use OCA\Dlhgshows\Service\CalendarService;
use OCA\Dlhgshows\Service\RsvpService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\IGroupManager;


class Admin implements ISettings {

    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly RsvpService $rsvpService,
        private readonly IUserSession $userSession,
        private readonly IAppConfig $appConfig,
        private readonly IGroupManager $groupManager,
    ) {}

    public function getForm(): TemplateResponse {
        $user   = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : '';

        $calendarId      = $this->appConfig->getValueInt(Application::APP_ID, 'calendar_id', 33);
        $calendarName    = $this->appConfig->getValueString(Application::APP_ID, 'calendar_name', 'Teamkalender');
        $statsGroupRaw   = $this->appConfig->getValueString(Application::APP_ID, 'stats_groups', '');
        $statsGroups     = $statsGroupRaw !== '' ? json_decode($statsGroupRaw, true) ?? [] : [];
        $membersGroupRaw = $this->appConfig->getValueString(Application::APP_ID, 'members_groups', '');
        $membersGroups   = $membersGroupRaw !== '' ? json_decode($membersGroupRaw, true) ?? [] : [];

        // Alle Gruppen laden
        $groups = [];
        foreach ($this->groupManager->search('') as $group) {
            $groups[] = [
                'id'          => $group->getGID(),
                'displayName' => $group->getDisplayName(),
            ];
        }
        usort($groups, fn($a, $b) => strcasecmp($a['displayName'], $b['displayName']));

        $events = $this->calendarService->getEvents($userId, $calendarId);
        $totals = $this->rsvpService->getTotalsPerEvent();

        return new TemplateResponse(Application::APP_ID, 'admin', [
            'calendarName'  => $calendarName,
            'events'        => $events,
            'totals'        => $totals,
            'calendarId'    => $calendarId,
            'statsGroups'   => $statsGroups,
            'membersGroups' => $membersGroups,
            'groups'        => $groups,
        ], 'blank');
    }

    public function getSection(): string {
        return 'additional';
    }

    public function getPriority(): int {
        return 50;
    }
}
<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Settings;

use OCA\Dlhgshows\AppInfo\Application;
use OCA\Dlhgshows\Service\CalendarService;
use OCA\Dlhgshows\Service\AccessLogService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\Settings\ISettings;
use OCP\IGroupManager;


class Admin implements ISettings {

    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly AccessLogService $accessLogService,
        private readonly IAppConfig $appConfig,
        private readonly IGroupManager $groupManager,
    ) {}

    public function getForm(): TemplateResponse {

        $calendarName    = $this->appConfig->getValueString(Application::APP_ID, 'calendar_name', 'Teamkalender');
        $statsGroupRaw   = $this->appConfig->getValueString(Application::APP_ID, 'stats_groups', '');
        $statsGroups     = $statsGroupRaw !== '' ? json_decode($statsGroupRaw, true) ?? [] : [];
        $membersGroupRaw = $this->appConfig->getValueString(Application::APP_ID, 'members_groups', '');
        $membersGroups   = $membersGroupRaw !== '' ? json_decode($membersGroupRaw, true) ?? [] : [];

        $calendarIdsRaw = $this->appConfig->getValueString(Application::APP_ID, 'calendar_ids', '');
        $calendarIds    = $calendarIdsRaw !== '' ? json_decode($calendarIdsRaw, true) ?? [] : [];
        // Fallback auf alte Einzel-ID-Einstellung
        if (empty($calendarIds)) {
            $oldId = $this->appConfig->getValueInt(Application::APP_ID, 'calendar_id', 0);
            if ($oldId > 0) {
                $calendarIds = [$oldId];
            }
        }

        // Alle Gruppen laden
        $groups = [];
        foreach ($this->groupManager->search('') as $group) {
            $groups[] = [
                'id'          => $group->getGID(),
                'displayName' => $group->getDisplayName(),
            ];
        }
        usort($groups, fn($a, $b) => strcasecmp($a['displayName'], $b['displayName']));

        // Alle Kalender von allen Benutzern laden
        $calendars = $this->calendarService->getAllCalendars();

        // Zugriffstatistiken laden
        $accessStats = $this->accessLogService->getStatistics();
        $totalAccesses = $this->accessLogService->getTotalAccesses();

        return new TemplateResponse(Application::APP_ID, 'admin', [
            'calendarName'  => $calendarName,
            'calendarIds'   => $calendarIds,
            'statsGroups'   => $statsGroups,
            'membersGroups' => $membersGroups,
            'groups'        => $groups,
            'calendars'     => $calendars,
            'accessStats'   => $accessStats,
            'totalAccesses' => $totalAccesses,
        ], 'blank');
    }

    public function getSection(): string {
        return 'additional';
    }

    public function getPriority(): int {
        return 50;
    }
}
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
use OCP\IDBConnection;


class PageController extends Controller {

    public function __construct(
        IRequest $request,
        private readonly CalendarService $calendarService,
        private readonly RsvpService $rsvpService,
        private readonly IGroupManager $groupManager,
        private readonly IAppConfig $appConfig,
        private readonly IDBConnection $db,
        private readonly ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse {
        $userId        = $this->userId ?? '';
        $calendarId    = $this->appConfig->getValueInt(Application::APP_ID, 'calendar_id', 33);
        $calendarName  = $this->appConfig->getValueString(Application::APP_ID, 'calendar_name', 'Teamkalender');
        $statsGroupRaw = $this->appConfig->getValueString(Application::APP_ID, 'stats_groups', '');
        $statsGroups   = $statsGroupRaw !== '' ? json_decode($statsGroupRaw, true) ?? [] : [];
        $membersGroupRaw = $this->appConfig->getValueString(Application::APP_ID, 'members_groups', '');
        $membersGroups   = $membersGroupRaw !== '' ? json_decode($membersGroupRaw, true) ?? [] : [];

        $events        = $this->calendarService->getEvents($userId, $calendarId);
        $rsvps         = $this->rsvpService->getForUser($userId);
        $totals        = $this->rsvpService->getTotalsPerEvent();
        $usersPerEvent = $this->rsvpService->getUsersPerEvent();

        // Mitglieder aller konfigurierten Mitgliedergruppen laden
        $allUserIds = [];
        foreach ($membersGroups as $group) {
            $groupObj = $this->groupManager->get($group);
            if ($groupObj) {
                foreach ($groupObj->getUsers() as $user) {
                    $uid = $user->getUID();
                    // displayname aus oc_accounts_data abrufen
                    $query = $this->db->getQueryBuilder();
                    $query->select('value')
                        ->from('accounts_data')
                        ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
                        ->andWhere($query->expr()->eq('name', $query->createNamedParameter('displayname')));
                    $result = $query->executeQuery();
                    $row = $result->fetch();
                    $result->closeCursor();
                    $displayName = $row ? $row['value'] : $uid;
                    $allUserIds[$uid] = $displayName;
                }
            }
        }

        $canSeeStats = false;
        foreach ($statsGroups as $group) {
            if ($this->groupManager->isInGroup($userId, $group)) {
                $canSeeStats = true;
                break;
            }
        }

        return new TemplateResponse(Application::APP_ID, 'index', [
            'calendarName'  => $calendarName,
            'events'        => $events,
            'rsvps'         => $rsvps,
            'totals'        => $totals,
            'usersPerEvent' => $usersPerEvent,
            'allUserIds'    => $allUserIds,
            'userId'        => $userId,
            'canSeeStats'   => $canSeeStats,
        ]);
    }
}
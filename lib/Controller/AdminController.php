<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Controller;

use OCA\Dlhgshows\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\IAppConfig;
use OCP\IRequest;

class AdminController extends Controller {

    public function __construct(
        IRequest $request,
        private readonly IAppConfig $appConfig,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[AuthorizedAdminSetting(settings: \OCA\Dlhgshows\Settings\Admin::class)]
    public function save(): DataResponse {
        $calendarName  = (string)$this->request->getParam('calendar_name');
        $calendarIds   = $this->request->getParam('calendar_ids');
        $statsGroups   = $this->request->getParam('stats_groups');
        $membersGroups = $this->request->getParam('members_groups');

        if (!is_array($calendarIds)) {
            $calendarIds = $calendarIds !== null ? [(int)$calendarIds] : [];
        } else {
            $calendarIds = array_map('intval', $calendarIds);
        }
        $calendarIds = array_values(array_unique(array_filter($calendarIds, fn($id) => $id > 0)));

        if (!is_array($statsGroups)) {
            $statsGroups = $statsGroups !== null ? [$statsGroups] : [];
        }
        if (!is_array($membersGroups)) {
            $membersGroups = $membersGroups !== null ? [$membersGroups] : [];
        }

        $this->appConfig->setValueString(Application::APP_ID, 'calendar_ids',   json_encode($calendarIds));
        if ($calendarName !== '') {
            $this->appConfig->setValueString(Application::APP_ID, 'calendar_name', $calendarName);
        }
        $this->appConfig->setValueString(Application::APP_ID, 'stats_groups',   json_encode(array_values($statsGroups)));
        $this->appConfig->setValueString(Application::APP_ID, 'members_groups', json_encode(array_values($membersGroups)));

        return new DataResponse(['status' => 'ok']);
    }
}
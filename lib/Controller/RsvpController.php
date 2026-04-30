<?php

declare(strict_types=1);

namespace OCA\Dlhgshows\Controller;

use OCA\Dlhgshows\AppInfo\Application;
use OCA\Dlhgshows\Service\RsvpService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class RsvpController extends Controller {

    public function __construct(
        IRequest $request,
        private readonly RsvpService $rsvpService,
        private readonly ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * POST /apps/dlhgshows/api/rsvp
     * Body: calendarobject_id, calendarobject_uid, response (accepted|declined)
     */
    #[NoAdminRequired]
    public function upsert(): DataResponse {
        $calendarObjectId  = (int)$this->request->getParam('calendarobject_id');
        $calendarObjectUid = (string)$this->request->getParam('calendarobject_uid');
        $response          = (string)$this->request->getParam('response');

        if (!$calendarObjectId || !in_array($response, ['accepted', 'declined'], true)) {
            return new DataResponse(['error' => 'Ungültige Parameter'], 400);
        }

        $this->rsvpService->upsert(
            $calendarObjectId,
            $calendarObjectUid,
            $this->userId ?? '',
            $response,
        );

        return new DataResponse(['status' => 'ok']);
    }
}
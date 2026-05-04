<?php
/** @var array $_ */
\OCP\Util::addStyle('dlhgshows', 'main');
\OCP\Util::addScript('dlhgshows', 'dlhgshows-main');

$events        = $_['events'];
$calendarName  = $_['calendarName'];
$rsvps         = $_['rsvps'];
$totals        = $_['totals'];
$usersPerEvent = $_['usersPerEvent'] ?? [];
$allUserIds    = $_['allUserIds'] ?? [];
$canSeeStats   = $_['canSeeStats'];

$submitUrl    = \OC::$server->getURLGenerator()->linkToRoute('dlhgshows.rsvp.upsert');
$requestToken = \OCP\Util::callRegister();
$nonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
?>
<script nonce="<?php p($nonce); ?>">
var hwAllUsers      = <?php echo json_encode($allUserIds); ?>;
var hwUsersPerEvent = <?php echo json_encode($usersPerEvent); ?>;
</script>

<div id="app">
    <div id="app-content" class="app-content-list">
        <div class="hw-app">

            <div class="hw-header">
                <h2 class="hw-title">Kalendereinträge — <?php p($calendarName); ?></h2>
            </div>

            <?php if (empty($events)): ?>
                <div class="hw-empty">Keine Termine in diesem Kalender.</div>

            <?php else: ?>
                <div class="hw-main-wrap">
                <div class="hw-table-wrap">

                    <div class="hw-legend">
                        <div class="hw-legend-item" data-type="auftritt">
                            <span class="hw-legend-dot hw-legend-dot-auftritt"></span>
                            <span>Auftritt</span>
                        </div>
                        <div class="hw-legend-item" data-type="anfrage">
                            <span class="hw-legend-dot hw-legend-dot-anfrage"></span>
                            <span>Anfrage</span>
                        </div>
                        <div class="hw-legend-item" data-type="absage">
                            <span class="hw-legend-dot hw-legend-dot-absage"></span>
                            <span>Absage</span>
                        </div>
                        <?php if ($canSeeStats): ?>
                        <div class="hw-legend-item hw-legend-item-stats" data-type="stats">
                            <span class="hw-legend-dot hw-legend-dot-stats hw-legend-dot-empty"></span>
                            <span>Auswertung</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="hw-table-scroll">
                    <table class="hw-table">
                        <thead>
                            <tr>
                                <th class="hw-th">Titel</th>
                                <th class="hw-th">Beginn</th>
                                <th class="hw-th">Ende</th>
                                <th class="hw-th">Ort</th>
                                <th class="hw-th">Beschreibung</th>
                                <th class="hw-th hw-th-rsvp" colspan="1">Zu-/Absage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev):
                                $objId    = (int)$ev['calendarObjectId'];
                                $objUid   = $ev['uid'];
                                $current  = $rsvps[$objId] ?? null;
                                $accepted = $totals[$objId]['accepted'] ?? 0;
                                $declined = $totals[$objId]['declined'] ?? 0;

                                $titleClass = '';
                                $rowType    = '';
                                if (str_starts_with($ev['summary'], 'DLHG Auftritt')) {
                                    $titleClass = 'hw-title-auftritt';
                                    $rowType    = 'auftritt';
                                } elseif (str_starts_with($ev['summary'], 'DLHG Anfrage')) {
                                    $titleClass = 'hw-title-anfrage';
                                    $rowType    = 'anfrage';
                                } elseif (str_starts_with($ev['summary'], 'DLHG Absage')) {
                                    $titleClass = 'hw-title-absage';
                                    $rowType    = 'absage';
                                }
                            ?>
                                <tr class="hw-tr" data-type="<?php p($rowType); ?>" data-objid="<?php p($objId); ?>">
                                    <td class="hw-td hw-td-title" data-label="Titel">
                                        <?php if ($ev['allDay']): ?>
                                            <span class="hw-badge hw-badge-allday">Ganztägig</span>
                                        <?php endif; ?>
                                        <?php if ($canSeeStats && !empty($ev['calendarAppUrl'])): ?>
                                            <a href="<?php p($ev['calendarAppUrl']); ?>" class="hw-cal-link <?php p($titleClass); ?>">
                                                <?php p($ev['summary']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="<?php p($titleClass); ?>"><?php p($ev['summary']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="hw-td hw-td-date" data-label="Beginn">
                                        <?php
                                            $d = new \DateTime($ev['start']);
                                            echo $ev['allDay'] ? $d->format('d.m.Y') : $d->format('d.m.Y H:i');
                                        ?>
                                    </td>
                                    <td class="hw-td hw-td-date" data-label="Ende">
                                        <?php
                                            $d = new \DateTime($ev['end']);
                                            echo $ev['allDay'] ? $d->format('d.m.Y') : $d->format('d.m.Y H:i');
                                        ?>
                                    </td>
                                    <td class="hw-td hw-td-loc" data-label="Ort">
                                        <?php p($ev['location'] ?: '—'); ?>
                                    </td>
                                    <td class="hw-td hw-td-desc" data-label="Beschreibung">
                                        <?php p($ev['description'] ?: '—'); ?>
                                    </td>
<td class="hw-td hw-td-rsvp" data-label="Zu-/Absage">
    <div class="hw-rsvp-btn-wrap">
        <form method="post" action="<?php p($submitUrl); ?>" class="hw-rsvp-form">
            <input type="hidden" name="requesttoken"       value="<?php p($requestToken); ?>">
            <input type="hidden" name="calendarobject_id"  value="<?php p($objId); ?>">
            <input type="hidden" name="calendarobject_uid" value="<?php p($objUid); ?>">
            <input type="hidden" name="response"           value="accepted">
            <button type="submit" class="hw-btn hw-btn-accept <?php if ($current === 'accepted') echo 'hw-btn-active'; ?>">✓</button>
        </form>
        <form method="post" action="<?php p($submitUrl); ?>" class="hw-rsvp-form">
            <input type="hidden" name="requesttoken"       value="<?php p($requestToken); ?>">
            <input type="hidden" name="calendarobject_id"  value="<?php p($objId); ?>">
            <input type="hidden" name="calendarobject_uid" value="<?php p($objUid); ?>">
            <input type="hidden" name="response"           value="declined">
            <button type="submit" class="hw-btn hw-btn-decline <?php if ($current === 'declined') echo 'hw-btn-active'; ?>">✗</button>
        </form>
    </div>
    <div class="hw-rsvp-count" style="display:none;">
        <span class="hw-rsvp-total hw-rsvp-total-accepted"><?php p($accepted ?: '0'); ?></span>
        <span class="hw-rsvp-divider"> / </span>
        <span class="hw-rsvp-total hw-rsvp-total-declined"><?php p($declined ?: '0'); ?></span>
    </div>
</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div><!-- /.hw-table-scroll -->

                    <div class="hw-footer">
                        <?php count($events) > 1 ? p(count($events)) . p(' Einträge') : p(count($events)) . p(' Eintrag') ?>
                    </div>
                </div><!-- /.hw-table-wrap -->

                <aside class="app-sidebar hw-sidebar" style="display:none;" aria-label="Auswertung">
                    <div class="app-sidebar-header app-sidebar-header--without-figure">
                        <div class="app-sidebar-header__info">
                            <div class="app-sidebar-header__desc">
                                <div class="app-sidebar-header__name-container">
                                    <h2 class="app-sidebar-header__mainname">Auswertung</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="app-sidebar-tabs">
                        <div class="app-sidebar-tabs__content">
                            <section class="app-sidebar__tab app-sidebar__tab--active">
                                <div class="hw-stats-top">
                                    <div class="hw-stats-col" id="hw-stats-accepted">
                                        <h3 class="hw-stats-heading">Zusagen</h3>
                                        <div class="hw-stats-avatars"></div>
                                    </div>
                                    <div class="hw-stats-col" id="hw-stats-declined">
                                        <h3 class="hw-stats-heading">Absagen</h3>
                                        <div class="hw-stats-avatars"></div>
                                    </div>
                                </div>
                                <hr class="hw-stats-divider">
                                <div class="hw-stats-bottom" id="hw-stats-none">
                                    <h3 class="hw-stats-heading">Keine Rückmeldung</h3>
                                    <div class="hw-stats-avatars"></div>
                                </div>
                            </section>
                        </div>
                    </div>
                </aside>

                </div><!-- /.hw-main-wrap -->
            <?php endif; ?>

        </div>
    </div>
</div>
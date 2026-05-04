<?php
/** @var array $_ */
\OCP\Util::addStyle('dlhgshows', 'admin');
\OCP\Util::addScript('dlhgshows', 'dlhgshows-admin');

\OCP\Util::addScript('core', 'select2');
\OCP\Util::addStyle('core', 'select2');

$events        = $_['events'];
$calendarName  = $_['calendarName'];
$totals        = $_['totals'];
$calendarId    = $_['calendarId'];
$statsGroups   = $_['statsGroups']  ?? [];
$membersGroups = $_['membersGroups'] ?? [];
$groups        = $_['groups']       ?? [];
$calendars     = $_['calendars']    ?? [];
$accessStats   = $_['accessStats']  ?? [];
$totalAccesses = $_['totalAccesses'] ?? 0;

$saveUrl      = \OC::$server->getURLGenerator()->linkToRoute('dlhgshows.admin.save');
$requestToken = \OCP\Util::callRegister();
?>

<script nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()); ?>">
(function () {
    function initSelect2() {
        if (typeof jQuery === 'undefined') return;
        jQuery('#calendar_id').select2({
            width: 'off',
            placeholder: 'Kalender auswählen…',
            allowClear: true,
        });
        jQuery('#stats_groups').select2({
            width: 'off',
            placeholder: 'Gruppen auswählen…',
            allowClear: true,
        });
        jQuery('#members_groups').select2({
            width: 'off',
            placeholder: 'Gruppe auswählen…',
            allowClear: true,
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSelect2);
    } else {
        initSelect2();
    }
})();
</script>

<div class="hw-app">

    <div class="hw-header">
        <h2 class="hw-title">Einstellungen</h2>
    </div>

    <form class="hw-settings-form" action="<?php p($saveUrl); ?>" method="post">
        <input type="hidden" name="requesttoken" value="<?php p($requestToken); ?>">
        <div class="hw-settings-row">
            <label for="calendar_id">Kalender</label>
            <select id="calendar_id" name="calendar_id" class="hw-settings-select2">
                <option value="">-- Kalender auswählen --</option>
                <?php foreach ($calendars as $calendar): ?>
                    <option value="<?php p($calendar['id']); ?>"
                        <?php if ($calendar['id'] === $calendarId) echo 'selected'; ?>>
                        <?php p($calendar['displayname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="hw-settings-row">
            <label for="calendar_name">Kalender-Anzeigename</label>
            <input type="text" id="calendar_name" name="calendar_name"
                   value="<?php p($calendarName); ?>" class="hw-settings-input">
        </div>
        <div class="hw-settings-row hw-settings-row-multiselect">
            <label for="stats_groups">Gruppen für Auswertung</label>
            <select id="stats_groups" name="stats_groups[]"
                    class="hw-settings-select2" multiple>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php p($group['id']); ?>"
                        <?php if (in_array($group['id'], $statsGroups, true)) echo 'selected'; ?>>
                        <?php p($group['displayName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="hw-settings-row hw-settings-row-multiselect">
            <label for="members_groups">Mitgliedergruppe (Auswertung)</label>
            <select id="members_groups" name="members_groups[]"
                    class="hw-settings-select2" multiple>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php p($group['id']); ?>"
                        <?php if (in_array($group['id'], $membersGroups, true)) echo 'selected'; ?>>
                        <?php p($group['displayName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="hw-settings-row">
            <button type="submit" class="hw-settings-btn">Speichern</button>
        </div>
        <div id="hw-settings-message" class="hw-settings-message" style="display:none;"></div>
    </form>

    <!-- Statistik-Sektion -->
    <div class="hw-stats-section">
        <h3 class="hw-stats-title">Zugriffstatistiken</h3>
        <div class="hw-stats-summary">
            <div class="hw-stats-card">
                <div class="hw-stats-label">Gesamtzugriffe</div>
                <div class="hw-stats-value"><?php p($totalAccesses); ?></div>
            </div>
        </div>
        
        <?php if (!empty($accessStats)): ?>
            <div class="hw-stats-table">
                <table>
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>Zugriffe</th>
                            <th>Letzter Zugriff</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accessStats as $stat): ?>
                            <tr>
                                <td><?php p($stat['user_id']); ?></td>
                                <td class="hw-stats-count"><?php p($stat['count']); ?></td>
                                <td class="hw-stats-date"><?php p(date('d.m.Y H:i', $stat['last_access'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="hw-stats-empty">Noch keine Zugriffe protokolliert.</div>
        <?php endif; ?>
    </div>

</div>
<?php
/** @var array $_ */
\OCP\Util::addStyle('dlhgshows', 'main');

\OCP\Util::addScript('core', 'select2');
\OCP\Util::addStyle('core', 'select2');

$events       = $_['events'];
$calendarName = $_['calendarName'];
$totals       = $_['totals'];
$calendarId   = $_['calendarId'];
$statsGroups  = $_['statsGroups'] ?? [];
$groups       = $_['groups'] ?? [];

$saveUrl      = \OC::$server->getURLGenerator()->linkToRoute('dlhgshows.admin.save');
$requestToken = \OCP\Util::callRegister();
?>

<script nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()); ?>">
(function () {
    function initSelect2() {
        var el = document.getElementById('stats_groups');
        if (!el || typeof jQuery === 'undefined') return;
        jQuery('#stats_groups').select2({
            width: 'off',
            placeholder: 'Gruppen auswählen…',
            allowClear: true,
        });
        // Select2 mit width:'off' setzt kein inline-style – CSS übernimmt vollständig.
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
            <label for="calendar_id">Kalender-ID</label>
            <input type="number" id="calendar_id" name="calendar_id"
                   value="<?php p($calendarId); ?>" class="hw-settings-input">
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
        <div class="hw-settings-row">
            <button type="submit" class="hw-settings-btn">Speichern</button>
        </div>
    </form>

</div>
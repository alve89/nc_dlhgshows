document.addEventListener('DOMContentLoaded', function () {

    // ── RSVP ────────────────────────────────────────────────────────────────
    document.querySelectorAll('.hw-rsvp-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Keine Abstimmung für abgesagte Einträge
            var row = form.closest('tr');
            if (row && row.dataset.type === 'absage') {
                return;
            }

            var data = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: data,
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);

                var row = form.closest('tr');
                row.querySelectorAll('.hw-btn').forEach(function (btn) {
                    btn.classList.remove('hw-btn-active');
                });
form.querySelector('.hw-btn').classList.add('hw-btn-active');
setTimeout(function () {
    form.querySelector('.hw-btn').blur();
}, 50);

                // Sidebar aktualisieren falls sichtbar
                if (document.querySelector('.hw-sidebar') &&
                    document.querySelector('.hw-sidebar').style.display !== 'none') {
                    var activeRow = document.querySelector('tr.hw-tr--active');
                    if (activeRow) { hwFillSidebar(activeRow); }
                }
            }).catch(function (err) {
                console.error('RSVP fehlgeschlagen:', err);
            });
        });
    });

    // ── Zeilen-Klick → Sidebar befüllen ────────────────────────────────────
    document.querySelectorAll('.hw-tr').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form')) return;
            var sidebar = document.querySelector('.hw-sidebar');
            if (!sidebar || sidebar.style.display === 'none') return;
            document.querySelectorAll('.hw-tr').forEach(function (r) {
                r.classList.remove('hw-tr--active');
            });
            row.classList.add('hw-tr--active');
            hwFillSidebar(row);
        });
    });

    function hwFillSidebar(row) {
        var objId    = parseInt(row.dataset.objid, 10);
        var allUsers = (typeof hwAllUsers !== 'undefined') ? hwAllUsers : {};
        var perEvent = (typeof hwUsersPerEvent !== 'undefined') ? hwUsersPerEvent : {};

        var eventData = perEvent[objId] || { accepted: [], declined: [] };
        var accepted  = eventData.accepted  || [];
        var declined  = eventData.declined  || [];
        var responded = accepted.concat(declined);
        var noReply   = Object.keys(allUsers).filter(function (u) { return responded.indexOf(u) === -1; });

        hwRenderAvatars('#hw-stats-accepted .hw-stats-avatars', accepted);
        hwRenderAvatars('#hw-stats-declined .hw-stats-avatars', declined);
        hwRenderAvatars('#hw-stats-none    .hw-stats-avatars', noReply);
    }

    function hwInitials(displayName) {
        var parts = displayName.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return displayName.substring(0, 2).toUpperCase();
    }

    function hwRenderAvatars(selector, users) {
        var container = document.querySelector(selector);
        if (!container) return;
        container.innerHTML = '';
        users.forEach(function (userId) {
            var displayName = (hwAllUsers && hwAllUsers[userId]) ? hwAllUsers[userId] : userId;
            var span = document.createElement('span');
            span.className   = 'hw-avatar';
            span.textContent = hwInitials(displayName);
            span.title       = displayName;
            container.appendChild(span);
        });
        if (!users.length) {
            var empty = document.createElement('span');
            empty.className   = 'hw-avatar-empty';
            empty.textContent = '—';
            container.appendChild(empty);
        }
    }

    // ── Legende Filter ──────────────────────────────────────────────────────
    var hidden = { auftritt: false, anfrage: false, absage: false };
    var statsActive = false;

    document.querySelectorAll('.hw-legend-item').forEach(function (item) {
        item.style.cursor = 'pointer';

        item.addEventListener('click', function () {
            var type = item.dataset.type;

            // Auswertungs-Toggle
            if (type === 'stats') {
                statsActive = !statsActive;
                var dot = item.querySelector('.hw-legend-dot');
                if (statsActive) {
                    dot.classList.remove('hw-legend-dot-empty');
                } else {
                    dot.classList.add('hw-legend-dot-empty');
                }

                // Buttons aus-/einblenden, Zahlen ein-/ausblenden
                document.querySelectorAll('.hw-rsvp-btn-wrap').forEach(function (el) {
                    el.style.display = statsActive ? 'none' : '';
                });
                document.querySelectorAll('.hw-rsvp-count').forEach(function (el) {
                    el.style.display = statsActive ? '' : 'none';
                });

                // Sidebar ein-/ausblenden
                var sidebar = document.querySelector('.hw-sidebar');
                if (sidebar) {
                    sidebar.style.display = statsActive ? '' : 'none';
                    if (!statsActive) {
                        document.querySelectorAll('.hw-tr--active').forEach(function (r) {
                            r.classList.remove('hw-tr--active');
                        });
                    }
                }
                return;
            }

            // Zeilen-Filter
            hidden[type] = !hidden[type];
            var dot = item.querySelector('.hw-legend-dot');
            if (hidden[type]) {
                dot.classList.add('hw-legend-dot-empty');
            } else {
                dot.classList.remove('hw-legend-dot-empty');
            }
            document.querySelectorAll('tr[data-type="' + type + '"]').forEach(function (row) {
                row.style.display = hidden[type] ? 'none' : '';
            });
        });
    });
});
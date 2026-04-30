document.addEventListener('DOMContentLoaded', function () {

    // ── RSVP ────────────────────────────────────────────────────────────────
    document.querySelectorAll('.hw-rsvp-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

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

            }).catch(function (err) {
                console.error('RSVP fehlgeschlagen:', err);
            });
        });
    });

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
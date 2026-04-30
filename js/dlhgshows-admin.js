document.addEventListener('DOMContentLoaded', function () {

    // ── Settings Form ────────────────────────────────────────────────────────
    var form = document.querySelector('.hw-settings-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var messageEl = document.getElementById('hw-settings-message');
        messageEl.style.display = 'none';

        var data = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: data,
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (result) {
            if (result.status === 'ok') {
                messageEl.textContent = 'Einstellungen gespeichert!';
                messageEl.classList.add('hw-success');
                messageEl.classList.remove('hw-error');
                messageEl.style.display = 'block';

                setTimeout(function () {
                    messageEl.style.display = 'none';
                }, 3000);
            } else {
                throw new Error(result.error || 'Unbekannter Fehler');
            }
        }).catch(function (err) {
            messageEl.textContent = 'Fehler beim Speichern: ' + err.message;
            messageEl.classList.add('hw-error');
            messageEl.classList.remove('hw-success');
            messageEl.style.display = 'block';
            console.error('Fehler:', err);
        });
    });

});
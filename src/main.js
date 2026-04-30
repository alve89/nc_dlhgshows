/**
 * dlhgshows – Calendar Events Viewer
 * Vue 3 (CDN), no build step required for Nextcloud development.
 *
 * Loaded via OCP\Util::addScript — wrap in IIFE to avoid global pollution.
 */
(async () => {
    const { createApp, ref, computed, watch, onMounted } = Vue;

    // ── Nextcloud CSRF token helper ──────────────────────────────────────────
    const requestToken = () =>
        document.querySelector('head[data-requesttoken]')?.dataset?.requesttoken ?? '';

    // ── API helpers ──────────────────────────────────────────────────────────
    const baseUrl = OC.generateUrl('/apps/dlhgshows');

    async function apiFetch(path) {
        const res = await fetch(baseUrl + path, {
            headers: {
                'Accept': 'application/json',
                'requesttoken': requestToken(),
            },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }

    // ── Root component ───────────────────────────────────────────────────────
    const App = {
        setup() {
            const calendars      = ref([]);
            const selectedCalId  = ref(null);
            const events         = ref([]);
            const loadingCals    = ref(false);
            const loadingEvents  = ref(false);
            const errorMsg       = ref('');
            const search         = ref('');
            const sortKey        = ref('start');
            const sortDir        = ref('asc');

            // ── Load calendars on mount ──────────────────────────────────────
            onMounted(async () => {
                loadingCals.value = true;
                try {
                    calendars.value = await apiFetch('/api/calendars');
                    if (calendars.value.length) {
                        selectedCalId.value = calendars.value[0].id;
                    }
                } catch (e) {
                    errorMsg.value = 'Kalender konnten nicht geladen werden: ' + e.message;
                } finally {
                    loadingCals.value = false;
                }
            });

            // ── Load events when calendar selection changes ──────────────────
            watch(selectedCalId, async (id) => {
                if (!id) return;
                loadingEvents.value = true;
                errorMsg.value = '';
                try {
                    events.value = await apiFetch(`/api/calendars/${id}/events`);
                } catch (e) {
                    errorMsg.value = 'Termine konnten nicht geladen werden: ' + e.message;
                    events.value = [];
                } finally {
                    loadingEvents.value = false;
                }
            });

            // ── Sorting ──────────────────────────────────────────────────────
            function toggleSort(key) {
                if (sortKey.value === key) {
                    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
                } else {
                    sortKey.value = key;
                    sortDir.value = 'asc';
                }
            }

            function sortIcon(key) {
                if (sortKey.value !== key) return '⇅';
                return sortDir.value === 'asc' ? '↑' : '↓';
            }

            // ── Filtered + sorted events ─────────────────────────────────────
            const filteredEvents = computed(() => {
                const q = search.value.toLowerCase();
                let list = q
                    ? events.value.filter(e =>
                        e.summary.toLowerCase().includes(q) ||
                        e.location.toLowerCase().includes(q) ||
                        e.description.toLowerCase().includes(q))
                    : [...events.value];

                list.sort((a, b) => {
                    let va = a[sortKey.value] ?? '';
                    let vb = b[sortKey.value] ?? '';
                    if (typeof va === 'string') va = va.toLowerCase();
                    if (typeof vb === 'string') vb = vb.toLowerCase();
                    if (va < vb) return sortDir.value === 'asc' ? -1 : 1;
                    if (va > vb) return sortDir.value === 'asc' ? 1 : -1;
                    return 0;
                });
                return list;
            });

            // ── Date formatting ──────────────────────────────────────────────
            function formatDate(iso, allDay) {
                if (!iso) return '—';
                const d = new Date(iso);
                if (allDay) {
                    return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
                }
                return d.toLocaleString('de-DE', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit',
                });
            }

            // ── Selected calendar label ──────────────────────────────────────
            const selectedCalLabel = computed(() =>
                calendars.value.find(c => c.id === selectedCalId.value)?.displayname ?? '');

            return {
                calendars, selectedCalId, selectedCalLabel,
                filteredEvents, loadingCals, loadingEvents, errorMsg,
                search, sortKey, sortDir, toggleSort, sortIcon, formatDate,
            };
        },

        template: `
<div class="hw-app">

    <!-- Header ----------------------------------------------------------- -->
    <div class="hw-header">
        <h2 class="hw-title">Kalendereinträge</h2>
        <div class="hw-controls">
            <select v-if="!loadingCals" v-model="selectedCalId" class="hw-select">
                <option v-for="cal in calendars" :key="cal.id" :value="cal.id">
                    {{ cal.displayname }}
                </option>
            </select>
            <span v-else class="hw-loading-text">Kalender laden…</span>

            <input
                v-model="search"
                class="hw-search"
                type="search"
                placeholder="Suchen…"
            />
        </div>
    </div>

    <!-- Error ------------------------------------------------------------ -->
    <div v-if="errorMsg" class="hw-error">{{ errorMsg }}</div>

    <!-- Loading spinner -------------------------------------------------- -->
    <div v-if="loadingEvents" class="hw-spinner-wrap">
        <span class="hw-spinner"></span>
        <span class="hw-loading-text">Termine laden…</span>
    </div>

    <!-- Empty state ------------------------------------------------------ -->
    <div v-else-if="!loadingEvents && filteredEvents.length === 0 && selectedCalId" class="hw-empty">
        Keine Termine gefunden.
    </div>

    <!-- Table ------------------------------------------------------------ -->
    <div v-else-if="filteredEvents.length" class="hw-table-wrap">
        <table class="hw-table">
            <thead>
                <tr>
                    <th @click="toggleSort('summary')"   class="hw-th hw-th-sortable">
                        Titel <span class="hw-sort-icon">{{ sortIcon('summary') }}</span>
                    </th>
                    <th @click="toggleSort('start')"     class="hw-th hw-th-sortable">
                        Beginn <span class="hw-sort-icon">{{ sortIcon('start') }}</span>
                    </th>
                    <th @click="toggleSort('end')"       class="hw-th hw-th-sortable">
                        Ende <span class="hw-sort-icon">{{ sortIcon('end') }}</span>
                    </th>
                    <th @click="toggleSort('location')"  class="hw-th hw-th-sortable">
                        Ort <span class="hw-sort-icon">{{ sortIcon('location') }}</span>
                    </th>
                    <th class="hw-th">Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="ev in filteredEvents" :key="ev.uid" class="hw-tr">
                    <td class="hw-td hw-td-title">
                        <span class="hw-badge hw-badge-allday" v-if="ev.allDay">Ganztägig</span>
                        {{ ev.summary }}
                    </td>
                    <td class="hw-td hw-td-date">{{ formatDate(ev.start, ev.allDay) }}</td>
                    <td class="hw-td hw-td-date">{{ formatDate(ev.end, ev.allDay) }}</td>
                    <td class="hw-td hw-td-loc">{{ ev.location || '—' }}</td>
                    <td class="hw-td hw-td-desc">{{ ev.description || '—' }}</td>
                </tr>
            </tbody>
        </table>
        <div class="hw-footer">{{ filteredEvents.length }} Eintrag/Einträge</div>
    </div>

</div>
`,
    };

    // ── Mount only after DOM is ready ────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('dlhgshows-root');
        if (root) {
            createApp(App).mount(root);
        }
    });
})();

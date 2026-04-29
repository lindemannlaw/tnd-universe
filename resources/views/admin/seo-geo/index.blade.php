@extends('admin.layouts.base')

@section('title', __('admin.seo_geo_overview') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.seo_geo_overview')">
        <span class="text-muted small me-3">{{ $total }} Einträge</span>
        <button type="button" class="btn btn-sm btn-outline-primary me-2" id="btnBulkGenerate">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            Alle Felder neu generieren
        </button>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnTriggerCrawl">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe2"/></svg>
            Suchmaschinen-Crawl anstoßen
        </button>
    </x-admin.main-panel>
@endsection

@section('content')
    @php
    $statusConfig = [
        'all'      => ['label' => 'Alle',        'badge' => 'bg-secondary',         'badgeText' => 'text-secondary', 'count' => $total],
        'empty'    => ['label' => 'Leer',         'badge' => 'bg-danger',            'badgeText' => 'text-danger',    'count' => $empty],
        'partial'  => ['label' => 'Teilweise',    'badge' => 'bg-warning text-dark', 'badgeText' => 'text-warning',   'count' => $partial],
        'complete' => ['label' => 'Vollständig',  'badge' => 'bg-success',           'badgeText' => 'text-success',   'count' => $complete],
    ];
    @endphp

    <div id="twoPanelLayout" class="d-flex px-3 px-sm-4" style="overflow:hidden; gap:1.25rem;">

            {{-- ── Sidebar ───────────────────────────────────────────────────── --}}
            <div style="width:220px; flex-shrink:0; overflow-y:auto; padding-right:2px;">

                    {{-- Status --}}
                    <div class="border rounded mb-2">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 user-select-none sidebar-section-toggle"
                             style="cursor:pointer; background:var(--bs-light); border-radius:inherit;"
                             data-bs-toggle="collapse" data-bs-target="#sidebarStatus" aria-expanded="true">
                            <span class="text-uppercase fw-semibold text-muted" style="font-size:.7rem;letter-spacing:.05em;">Status</span>
                            <svg class="bi sidebar-chevron" width="11" height="11" fill="currentColor" style="transition:transform .2s;flex-shrink:0;"><use xlink:href="/img/icons/bootstrap-icons.svg#chevron-up"/></svg>
                        </div>
                        <div class="collapse show" id="sidebarStatus">
                            <div class="px-2 py-2">
                                <div class="d-flex justify-content-between align-items-center mb-1 px-1" style="font-size:.8rem;">
                                    <span class="text-muted">Filter:</span>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="text-primary text-decoration-none" id="selectAllStatus">Alle</a>
                                        <span class="text-muted">/</span>
                                        <a href="#" class="text-secondary text-decoration-none" id="deselectAllStatus">Keine</a>
                                    </div>
                                </div>
                                <div class="d-flex flex-column" style="gap:2px;">
                                    @foreach($statusConfig as $key => $cfg)
                                        @php $active = in_array($key, $statusFilter); @endphp
                                        <label class="d-flex align-items-center gap-2 px-2 py-1 rounded small status-filter-label"
                                               style="cursor:pointer;{{ $active ? 'background:rgba(0,0,0,.06);' : '' }}">
                                            <input type="checkbox"
                                                   class="form-check-input flex-shrink-0 status-check"
                                                   data-status="{{ $key }}"
                                                   style="cursor:pointer;margin-top:0;"
                                                   {{ $active ? 'checked' : '' }}>
                                            <span class="flex-grow-1 {{ $active ? 'fw-semibold ' . ($cfg['badgeText'] ?? '') : 'text-body' }}">
                                                {{ $cfg['label'] }}
                                            </span>
                                            <span class="badge {{ $cfg['badge'] }} ms-auto"
                                                  style="font-size:.7em;">{{ $cfg['count'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Inhalt --}}
                    <div class="border rounded mb-2">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 user-select-none sidebar-section-toggle"
                             style="cursor:pointer; background:var(--bs-light); border-radius:inherit;"
                             data-bs-toggle="collapse" data-bs-target="#sidebarContent" aria-expanded="true">
                            <span class="text-uppercase fw-semibold text-muted" style="font-size:.7rem;letter-spacing:.05em;">Inhalt</span>
                            <svg class="bi sidebar-chevron" width="11" height="11" fill="currentColor" style="transition:transform .2s;flex-shrink:0;"><use xlink:href="/img/icons/bootstrap-icons.svg#chevron-up"/></svg>
                        </div>
                        <div class="collapse show" id="sidebarContent">
                            @include('admin.partials.content-nav', [
                                'dashboard'   => 'seo-geo',
                                'typeFilter'  => $typeFilter,
                                'idFilter'    => $idFilter,
                                'navPages'    => $navPages,
                                'navSections' => $navSections,
                                'extraParams' => ['status' => $statusFilter],
                                'inner'       => true,
                            ])
                        </div>
                    </div>

            </div>{{-- /sidebar --}}

            {{-- ── Main content ───────────────────────────────────────────────── --}}
            <div style="flex:1; overflow-y:auto; min-width:0;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 120px;">TYP</th>
                                <th>TITEL</th>
                                <th>META TITLE</th>
                                <th>META DESCRIPTION</th>
                                <th>GEO TEXT</th>
                                <th style="width: 80px;">STATUS</th>
                                <th style="width: 100px;">AKTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-25 text-body">
                                            {{ ucfirst(str_replace('_', ' ', $item['type'])) }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($item['title'], 40) }}</td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($item['seo']['seo_title'], 40) }}</td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($item['seo']['seo_description'], 50) }}</td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($item['seo']['geo_text'], 40) }}</td>
                                    <td>
                                        <span class="badge {{ $item['status'] === 'complete' ? 'bg-success' : ($item['status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                            {{ $item['percent'] }}%
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.seo-geo.show', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Anzeigen
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">Keine Einträge gefunden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>{{-- /content --}}
        </div>{{-- /twoPanelLayout --}}
@endsection

@push('modals')
{{-- Bulk Generate Progress Modal --}}
<div class="modal fade" id="bulkGenerateModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <svg class="bi me-2" width="18" height="18" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
                    Felder werden generiert…
                </h5>
            </div>
            <div class="modal-body pt-2">
                <div id="bulkGenStatus" class="text-muted small mb-3">Initialisierung…</div>
                <div class="progress mb-2" style="height: 8px;">
                    <div id="bulkGenProgressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         role="progressbar"
                         style="width: 0%"></div>
                </div>
                <div id="bulkGenItemStatus" class="text-muted" style="font-size:.8em; min-height: 1.4em;"></div>
            </div>
            <div class="modal-footer border-0 pt-0" id="bulkGenFooter" style="display:none;">
                <button type="button" class="btn btn-sm btn-success" data-bs-dismiss="modal" onclick="location.reload()">
                    Fertig – Seite neu laden
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Crawl Trigger Modal --}}
<div class="modal fade" id="crawlTriggerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <svg class="bi me-2" width="18" height="18" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe2"/></svg>
                    Suchmaschinen-Crawl
                </h5>
            </div>
            <div class="modal-body pt-2">
                <div id="crawlStatusList" class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2 small" data-service="sitemap">
                        <span class="badge bg-secondary" style="min-width:74px;">…</span>
                        <span class="fw-semibold">Sitemap-Cache</span>
                        <span class="text-muted ms-auto crawl-msg" style="font-size:.8em;"></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small" data-service="indexnow">
                        <span class="badge bg-secondary" style="min-width:74px;">…</span>
                        <span class="fw-semibold">IndexNow (Bing/Yandex)</span>
                        <span class="text-muted ms-auto crawl-msg" style="font-size:.8em;"></span>
                    </div>
                </div>
                <hr class="my-3">
                <div class="small text-muted">
                    <div class="fw-semibold text-body mb-1">Google Search Console</div>
                    Google muss einmalig manuell die Sitemap zugewiesen bekommen — danach holt Google sie automatisch regelmäßig:
                    <a href="https://search.google.com/search-console/sitemaps?resource_id=sc-domain:tnduniverse.com"
                       target="_blank" rel="noopener"
                       class="d-inline-block mt-1">
                        Sitemap in Search Console verwalten →
                    </a>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('footer-scripts')
@php
    $itemsForJs = array_values(array_map(function($i) {
        return ['type' => $i['type'], 'id' => $i['id'], 'title' => $i['title']];
    }, $items));
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ITEMS        = @json($itemsForJs);
    const GENERATE_URL = @json(route('admin.seo-geo.generate'));
    const SAVE_URL     = @json(route('admin.seo-geo.save-field'));
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content;
    const ALL_FIELDS   = ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'];

    // ── Two-panel layout height ───────────────────────────────────────────
    const layout = document.getElementById('twoPanelLayout');
    function resizeLayout() {
        if (!layout) return;
        layout.style.height = (window.innerHeight - layout.getBoundingClientRect().top - 8) + 'px';
    }
    resizeLayout();
    window.addEventListener('resize', resizeLayout);

    // ── Sidebar collapse chevron rotation ─────────────────────────────────
    document.querySelectorAll('.sidebar-section-toggle').forEach(toggle => {
        const targetEl = document.querySelector(toggle.dataset.bsTarget);
        const chevron  = toggle.querySelector('.sidebar-chevron');
        if (!targetEl || !chevron) return;
        targetEl.addEventListener('hide.bs.collapse', () => chevron.style.transform = 'rotate(180deg)');
        targetEl.addEventListener('show.bs.collapse', () => chevron.style.transform = 'rotate(0deg)');
    });

    // ── Status checkboxes → URL navigation ───────────────────────────────
    function navigateWithStatus(statuses) {
        const url = new URL(window.location.href);
        url.searchParams.delete('status');
        url.searchParams.delete('status[]');
        if (statuses.length === 0 || statuses.includes('all')) {
            url.searchParams.set('status', 'all');
        } else {
            statuses.forEach(s => url.searchParams.append('status[]', s));
        }
        window.location.href = url.toString();
    }

    document.getElementById('selectAllStatus')?.addEventListener('click', (e) => {
        e.preventDefault();
        navigateWithStatus(['all']);
    });
    document.getElementById('deselectAllStatus')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.status-check:not([data-status="all"])').forEach(c => c.checked = false);
        const allCb = document.querySelector('.status-check[data-status="all"]');
        if (allCb) allCb.checked = true;
    });

    document.querySelectorAll('.status-check').forEach(cb => {
        cb.addEventListener('change', () => {
            const allCb = document.querySelector('.status-check[data-status="all"]');
            if (cb.dataset.status === 'all' && cb.checked) {
                document.querySelectorAll('.status-check:not([data-status="all"])').forEach(c => c.checked = false);
            } else if (cb.dataset.status !== 'all') {
                if (allCb) allCb.checked = false;
            }
            const now = Array.from(document.querySelectorAll('.status-check:not([data-status="all"]):checked'))
                             .map(c => c.dataset.status);
            navigateWithStatus(now.length === 0 ? ['all'] : now);
        });
    });

    // ── Trigger Crawl button ─────────────────────────────────────────────
    const TRIGGER_CRAWL_URL = @json(route('admin.seo-geo.trigger-crawl'));
    const btnCrawl   = document.getElementById('btnTriggerCrawl');
    const crawlModal = new bootstrap.Modal(document.getElementById('crawlTriggerModal'));

    function setCrawlRow(service, statusKey, message) {
        const row = document.querySelector(`#crawlStatusList [data-service="${service}"]`);
        if (!row) return;
        const badge = row.querySelector('.badge');
        const msg   = row.querySelector('.crawl-msg');

        const map = {
            success:        ['bg-success',         'OK'],
            error:          ['bg-danger',          'Fehler'],
            not_configured: ['bg-warning text-dark', 'Nicht konfig.'],
            pending:        ['bg-secondary',       '…'],
        };
        const [cls, label] = map[statusKey] ?? map.pending;
        badge.className = 'badge ' + cls;
        badge.style.minWidth = '74px';
        badge.textContent = label;
        msg.textContent = message ?? '';
    }

    btnCrawl?.addEventListener('click', async () => {
        ['sitemap', 'indexnow'].forEach(s => setCrawlRow(s, 'pending', 'läuft…'));
        crawlModal.show();
        btnCrawl.disabled = true;

        try {
            const res = await fetch(TRIGGER_CRAWL_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            ['sitemap', 'indexnow'].forEach(s => {
                const r = data[s] ?? { status: 'error', message: 'Keine Antwort' };
                setCrawlRow(s, r.status, r.message ?? '');
            });
        } catch (err) {
            ['sitemap', 'indexnow'].forEach(s => setCrawlRow(s, 'error', err.message));
        } finally {
            btnCrawl.disabled = false;
        }
    });

    const btn          = document.getElementById('btnBulkGenerate');
    const modal        = new bootstrap.Modal(document.getElementById('bulkGenerateModal'));
    const statusEl     = document.getElementById('bulkGenStatus');
    const progressBar  = document.getElementById('bulkGenProgressBar');
    const itemStatusEl = document.getElementById('bulkGenItemStatus');
    const footerEl     = document.getElementById('bulkGenFooter');

    btn?.addEventListener('click', async () => {
        if (!ITEMS.length) {
            alert('Keine Einträge in der aktuellen Ansicht.');
            return;
        }

        statusEl.textContent      = 'Starte…';
        progressBar.style.width   = '0%';
        progressBar.className     = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
        itemStatusEl.textContent  = '';
        footerEl.style.display    = 'none';
        modal.show();

        const total = ITEMS.length;
        let done    = 0;
        let errors  = 0;

        for (const item of ITEMS) {
            done++;
            const pct = Math.round((done - 1) / total * 100);
            progressBar.style.width = pct + '%';
            statusEl.textContent    = `Generiere ${done} / ${total}: ${item.title}`;
            itemStatusEl.textContent = 'Generiere EN via KI…';

            try {
                const genRes = await fetch(GENERATE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ type: item.type, id: item.id, locale: 'en' }),
                });
                const genData = await genRes.json();
                if (genData.error) throw new Error(genData.error);

                for (let fi = 0; fi < ALL_FIELDS.length; fi++) {
                    const field = ALL_FIELDS[fi];
                    const value = genData[field];
                    if (!value) continue;

                    itemStatusEl.textContent = `Übersetze Feld ${fi + 1} / ${ALL_FIELDS.length}: ${field.replace('_', ' ')}…`;

                    const saveRes = await fetch(SAVE_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ type: item.type, id: item.id, field, locale: 'en', value, translate: true }),
                    });
                    const saveData = await saveRes.json();
                    if (saveData.error) throw new Error(saveData.error);
                }

                itemStatusEl.textContent = '✓ Fertig';
            } catch (err) {
                errors++;
                itemStatusEl.textContent = '✗ Fehler: ' + err.message;
                await new Promise(r => setTimeout(r, 1500));
            }
        }

        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-animated');
        if (errors > 0) {
            progressBar.classList.replace('bg-primary', 'bg-warning');
            statusEl.textContent = `Abgeschlossen mit ${errors} Fehler(n).`;
        } else {
            progressBar.classList.replace('bg-primary', 'bg-success');
            statusEl.textContent = `Alle ${total} Einträge erfolgreich generiert!`;
        }
        itemStatusEl.textContent = '';
        footerEl.style.display   = 'flex';
    });
});
</script>
@endpush

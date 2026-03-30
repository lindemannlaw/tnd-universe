@extends('admin.layouts.base')

@section('title', __('admin.seo_geo_overview') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.seo_geo_overview')">
        <span class="text-muted small me-3">{{ $total }} Einträge</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnBulkGenerate">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            Alle Felder neu generieren
        </button>
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.seo-geo.index') }}" class="d-flex gap-2 mb-4 flex-wrap align-items-center">
            {{-- Type filter --}}
            <a href="{{ route('admin.seo-geo.index', ['status' => $statusFilter]) }}"
               class="btn btn-sm {{ $typeFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                Alle Typen
            </a>
            @foreach($types as $type)
                <a href="{{ route('admin.seo-geo.index', ['type' => $type, 'status' => $statusFilter]) }}"
                   class="btn btn-sm {{ $typeFilter === $type ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                </a>
            @endforeach

            <span class="mx-1 text-muted">|</span>

            {{-- Status filter with counts --}}
            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter]) }}"
                   class="btn {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Alle <span class="badge bg-secondary ms-1">{{ $total }}</span>
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'empty']) }}"
                   class="btn {{ $statusFilter === 'empty' ? 'btn-danger' : 'btn-outline-secondary' }}">
                    Leer <span class="badge bg-danger ms-1">{{ $empty }}</span>
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'partial']) }}"
                   class="btn {{ $statusFilter === 'partial' ? 'btn-warning' : 'btn-outline-secondary' }}">
                    Teilweise <span class="badge bg-warning text-dark ms-1">{{ $partial }}</span>
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'complete']) }}"
                   class="btn {{ $statusFilter === 'complete' ? 'btn-success' : 'btn-outline-secondary' }}">
                    Vollständig <span class="badge bg-success ms-1">{{ $complete }}</span>
                </a>
            </div>
        </form>

        {{-- Table --}}
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
    </x-admin.container>
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

        // Reset modal state
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
                // 1. Generate EN
                const genRes = await fetch(GENERATE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ type: item.type, id: item.id, locale: 'en' }),
                });
                const genData = await genRes.json();
                if (genData.error) throw new Error(genData.error);

                // 2. Save each field with translate=true
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

        // Done
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

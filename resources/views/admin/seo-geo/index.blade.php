@extends('admin.layouts.base')

@section('title', __('admin.seo_geo_overview') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.seo_geo_overview')">
        <span class="text-muted small">{{ $total }} Einträge</span>
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>

        {{-- Summary cards --}}
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <div class="card border-0 text-center" style="min-width: 120px;">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-success">{{ $complete }}</div>
                        <div class="small text-muted">Vollständig</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card border-0 text-center" style="min-width: 120px;">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-warning">{{ $partial }}</div>
                        <div class="small text-muted">Teilweise</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card border-0 text-center" style="min-width: 120px;">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-danger">{{ $empty }}</div>
                        <div class="small text-muted">Leer</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="card border-0 text-center" style="min-width: 120px;">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold">{{ $total }}</div>
                        <div class="small text-muted">Gesamt</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.seo-geo.index') }}" class="d-flex gap-2 mb-4 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Filter:</span>

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

                <span class="mx-2 text-muted">|</span>

                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter]) }}"
                   class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Alle
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'empty']) }}"
                   class="btn btn-sm {{ $statusFilter === 'empty' ? 'btn-danger' : 'btn-outline-secondary' }}">
                    Leer
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'partial']) }}"
                   class="btn btn-sm {{ $statusFilter === 'partial' ? 'btn-warning' : 'btn-outline-secondary' }}">
                    Teilweise
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'complete']) }}"
                   class="btn btn-sm {{ $statusFilter === 'complete' ? 'btn-success' : 'btn-outline-secondary' }}">
                    Vollständig
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

@extends('admin.layouts.base')

@section('title', __('admin.seo_geo_overview') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.seo_geo_overview')">
        <span class="text-muted small">{{ $total }} Einträge</span>
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
                    Alle <span class="badge bg-light text-dark ms-1">{{ $total }}</span>
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'empty']) }}"
                   class="btn {{ $statusFilter === 'empty' ? 'btn-danger' : 'btn-outline-secondary' }}">
                    Leer <span class="badge bg-light text-dark ms-1">{{ $empty }}</span>
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'partial']) }}"
                   class="btn {{ $statusFilter === 'partial' ? 'btn-warning' : 'btn-outline-secondary' }}">
                    Teilweise <span class="badge bg-light text-dark ms-1">{{ $partial }}</span>
                </a>
                <a href="{{ route('admin.seo-geo.index', ['type' => $typeFilter, 'status' => 'complete']) }}"
                   class="btn {{ $statusFilter === 'complete' ? 'btn-success' : 'btn-outline-secondary' }}">
                    Vollständig <span class="badge bg-light text-dark ms-1">{{ $complete }}</span>
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

@foreach ($articles as $article)
    <div
        class="d-flex flex-column flex-sm-row align-items-sm-center px-3 px-sm-4 py-2 border-bottom border-dark border-opacity-25">
        {{-- <div class="col-12 col-md-3 col-hd-2 fw-semibold">{{ $article->title }}</div>
        @if ($article->short_description ?: $article->description)
            <div class="col-12 col-sm line-clamp-2 mt-2 mt-sm-0 px-0 px-sm-3">{!! $article->short_description ?: $article->description !!}</div>
        @endif --}}
        <div class="col-12 col-sm pe-0 pe-sm-3">
            <div class="fw-semibold">{{ $article->title }}</div>
            @if ($article->short_description ?: $article->description)
                <div
                    style="font-size: 14px;"
                    class="line-clamp-1 text-gray"
                >{!! $article->short_description ?: $article->description !!}</div>
            @endif
        </div>
        <div class="col-12 col-sm-auto d-flex align-items-center justify-content-end gap-3 mt-2 mt-sm-0">
            <div class="me-auto pe-1">
                <time>{{ $article->created_at->translatedFormat('d M Y') }}</time>
                <div
                    style="font-size: 14px;"
                    class="text-gray"
                >{{ $article->categories?->first()->name }}</div>
            </div>

            @if (!$article->active)
                <x-admin.icon
                    :name="'eye-slash'"
                    :width="'30'"
                    :height="'30'"
                />
            @endif

            <x-admin.ajax.delete-modal-button
                :subtitle="$article->title"
                :deleteAction="route('admin.news.article.delete', $article->id)"
                :updateIdSection="'articles-list'"
            />

            <x-admin.ajax.view-modal-button
                class="btn-sm p-2"
                :action="route('admin.news.article.edit', $article->id)"
                :modal_id="'article-control-modal'"
                :iconName="'pen'"
            />
        </div>
    </div>
@endforeach

@if ($articles->isEmpty())
    <x-admin.empty-message />
@endif

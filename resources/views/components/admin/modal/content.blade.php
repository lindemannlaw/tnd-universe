@props([
	'size' => null, // sm, lg, xl
	'title' => null,
])

<div class="modal-dialog modal-dialog-centered {{ $size ? 'modal-' . $size : null }}">
    <div class="modal-content">
        <div class="modal-header gap-2">
            <h1 class="modal-title fs-5 me-auto">{{ $title }}</h1>
            @if(isset($headerActions))
                {{ $headerActions }}
            @endif
            <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            {{ $body }}
        </div>
        <div class="modal-footer">
            {{ $footer }}
        </div>
    </div>
</div>

@props([
	'placeholder' => null,
	'name' => '',
	'value' => '',
	'required' => true,
	'buttons' => null, // image|video|blockquote|fontColor|underline|italic|strike|subscript|superscript|align|list
	'charLimit' => null,
	'height' => null,
	'defaultWysiwygTag' => null,
])

<div data-wysiwyg-wrapper {{ $attributes->merge(['class' => 'position-relative', 'style' => 'z-index: 11']) }}>
    <textarea
        data-wysiwyg
        data-button-list="undo,redo|formatBlock|bold|{{ $buttons ? $buttons . '|' : null }}link|fullScreen,showBlocks,codeView|removeFormat"
        data-char-limit="{{ $charLimit }}"
        data-default-tag="{{ $defaultWysiwygTag }}"
        {!! $height ? 'data-height='.$height : null !!}
        name="{{ $name }}"
        {{ $required ? 'required' : null }}
    >
        {{ $value }}
    </textarea>

    @if($placeholder)
        <span class="form-control-placeholder glued">{{ $placeholder }} {!! $required ? '<span class="text-danger opacity-75"> *</span>' : null !!}</span>
    @endif
</div>

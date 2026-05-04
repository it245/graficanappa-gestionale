@props([
    'name' => '',
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'id' => null,
])

@php
    $id = $id ?? $name;
    $hasError = !empty($error);
    $describedBy = $hasError ? $id . '-err' : ($hint ? $id . '-hint' : null);
@endphp

<div class="mes-field" style="margin-bottom:18px;">
    @if($label)
        <label for="{{ $id }}" style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px;font-family:inherit;">
            {{ $label }}
            @if($required)<span style="color:var(--mes-danger);margin-left:2px;" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input id="{{ $id }}"
           name="{{ $name }}"
           type="{{ $type }}"
           value="{{ $value ?? old($name) }}"
           placeholder="{{ $placeholder }}"
           @required($required)
           @disabled($disabled)
           @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
           @if($hasError) aria-invalid="true" @endif
           style="width:100%;border:1px solid {{ $hasError ? 'var(--mes-danger)' : 'var(--mes-border)' }};border-radius:var(--mes-radius-md);padding:11px 14px;font-size:15px;background:{{ $hasError ? '#fef2f2' : 'var(--mes-bg-input)' }};font-family:inherit;color:var(--mes-text-primary);transition:border-color var(--mes-duration-base) var(--mes-ease-standard),background var(--mes-duration-base),box-shadow var(--mes-duration-base);"
           onfocus="this.style.borderColor='var(--mes-primary)';this.style.background='#fff';this.style.boxShadow='var(--mes-ring-primary)';this.style.outline='none';"
           onblur="this.style.borderColor='{{ $hasError ? 'var(--mes-danger)' : 'var(--mes-border)' }}';this.style.background='{{ $hasError ? '#fef2f2' : 'var(--mes-bg-input)' }}';this.style.boxShadow='none';"
           {{ $attributes }}>

    @if($hasError)
        <p id="{{ $id }}-err" style="font-size:12px;color:var(--mes-danger);margin-top:4px;font-family:inherit;">{{ $error }}</p>
    @elseif($hint)
        <p id="{{ $id }}-hint" style="font-size:12px;color:var(--mes-text-secondary);margin-top:4px;font-family:inherit;">{{ $hint }}</p>
    @endif
</div>

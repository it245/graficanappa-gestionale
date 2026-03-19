@props([
    'id' => 'mes-table',
    'width' => '100%',
    'scrollable' => false,
])

<div class="mes-table-container" @if($scrollable) style="overflow-x:auto" @endif>
    <table class="mes-table" id="{{ $id }}" style="width:{{ $width }}">
        {{ $slot }}
    </table>
</div>

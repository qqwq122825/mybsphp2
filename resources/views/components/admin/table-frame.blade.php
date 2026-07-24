@props(['frameClass' => '', 'scrollClass' => ''])

{{-- Standard table frame. Reuse this component for every administration table. --}}
<div {{ $attributes->class(['admin-data-table', $frameClass]) }}>
    @isset($toolbar)<div class="admin-data-table-toolbar">{{ $toolbar }}</div>@endisset
    <div class="admin-data-table-scroll {{ $scrollClass }}">{{ $slot }}</div>
    @isset($footer)<footer class="admin-data-table-footer">{{ $footer }}</footer>@endisset
</div>

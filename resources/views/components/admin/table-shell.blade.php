@props(['title', 'subtitle' => null, 'badge' => null, 'panelClass' => ''])

<section {{ $attributes->class(['dashboard-panel', 'admin-table-shell', $panelClass]) }}>
    <header class="admin-table-shell-header">
        <div>
            <h2>{{ $title }}</h2>
            @if($subtitle)<small>{{ $subtitle }}</small>@endif
            @isset($headingMeta)
                <div class="admin-table-shell-heading-meta">{{ $headingMeta }}</div>
            @endisset
        </div>
        <div class="admin-table-shell-heading-actions">
            @if($badge)<span class="admin-table-shell-badge">{{ $badge }}</span>@endif
            {{ $headingActions ?? '' }}
        </div>
    </header>
    @isset($search)<div class="admin-table-shell-search">{{ $search }}</div>@endisset
    <x-admin.table-frame frame-class="admin-table-shell-frame">
        @isset($toolbar)
            <x-slot:toolbar>{{ $toolbar }}</x-slot:toolbar>
        @endisset
        {{ $slot }}
        @isset($footer)
            <x-slot:footer>{{ $footer }}</x-slot:footer>
        @endisset
    </x-admin.table-frame>
</section>

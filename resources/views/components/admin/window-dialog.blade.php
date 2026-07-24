@props([
    'id' => null,
    'title' => '',
    'eyebrow' => null,
    'maximized' => true,
    'autoOpen' => false,
    'contentClass' => '',
    'workspace' => true,
    'minimizable' => false,
])

{{-- Shared administration window. All new dialogs must reuse this component. --}}
<dialog @if($id) id="{{ $id }}" @endif
        {{ $attributes->class([
            'admin-window-dialog',
            'is-maximized' => $maximized,
            'is-workspace-window' => $workspace,
        ]) }}
        data-window-dialog
        data-window-scope="{{ $workspace ? 'workspace' : 'modal' }}"
        @if($autoOpen) data-window-auto-open @endif>
    <div class="admin-window-dialog__window">
        <header class="admin-window-dialog__titlebar">
            <div class="admin-window-dialog__heading">
                @isset($heading)
                    {{ $heading }}
                @else
                    @if($eyebrow)<small>{{ $eyebrow }}</small>@endif
                    <h3>{{ $title }}</h3>
                @endisset
            </div>
            <div class="admin-window-dialog__controls" aria-label="窗口控制">
                @if($minimizable)
                    <button type="button" data-window-action="minimize" aria-label="最小化" title="最小化"><span aria-hidden="true">—</span></button>
                @endif
                <button type="button" data-window-action="maximize" aria-label="切换最大化" title="切换最大化"><svg aria-hidden="true" viewBox="0 0 20 20"><rect x="4" y="4" width="12" height="12" rx="1" /></svg></button>
                <button type="button" data-window-action="close" aria-label="关闭" title="关闭"><svg aria-hidden="true" viewBox="0 0 20 20"><path d="M5 5l10 10M15 5L5 15" /></svg></button>
            </div>
        </header>
        <div class="admin-window-dialog__content {{ $contentClass }}" tabindex="-1" autofocus>{{ $slot }}</div>
    </div>
</dialog>

@props([
    'method' => 'get',
    'action' => null,
    'fieldLabel' => '搜索字段',
    'keywordLabel' => '关键字：',
    'sortLabel' => '排序',
    'buttonLabel' => '搜索',
    'buttonAriaLabel' => '搜索',
    'iconOnly' => false,
])

<form method="{{ $method }}" @if($action) action="{{ $action }}" @endif
      {{ $attributes->class(['admin-filter-bar', 'is-icon-submit' => $iconOnly]) }}>
    {{ $hidden ?? '' }}

    @isset($field)
        <label class="admin-filter-bar__item admin-filter-bar__field">
            <strong>{{ $fieldLabel }}</strong>
            <span class="admin-filter-bar__control">{{ $field }}</span>
        </label>
    @endisset

    @isset($keyword)
        <label class="admin-filter-bar__item admin-filter-bar__keyword">
            <strong>{{ $keywordLabel }}</strong>
            <span class="admin-filter-bar__control">{{ $keyword }}</span>
        </label>
    @endisset

    @isset($sort)
        <label class="admin-filter-bar__item admin-filter-bar__sort">
            <strong>{{ $sortLabel }}</strong>
            <span class="admin-filter-bar__control">{{ $sort }}</span>
        </label>
    @endisset

    <button class="button button-primary admin-filter-bar__submit" type="submit" aria-label="{{ $buttonAriaLabel }}">
        @if($iconOnly)
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg>
        @else
            {{ $buttonLabel }}
        @endif
    </button>

    @isset($extra)
        <div class="admin-filter-bar__extra">{{ $extra }}</div>
    @endisset
</form>

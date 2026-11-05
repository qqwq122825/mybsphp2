@php
    $ascending = $sort === $field.'_asc';
    $descending = $sort === $field.'_desc';
    $labelAction = '按'.$label.($ascending ? '降序' : '升序');
@endphp
<th aria-sort="{{ $ascending ? 'ascending' : ($descending ? 'descending' : 'none') }}">
    <span data-agent-sort-label>{{ $label }}</span>
    <a data-agent-sort href="{{ request()->fullUrlWithQuery(['sort' => $field.($ascending ? '_desc' : '_asc'), 'page' => 1]) }}"
       aria-label="{{ $labelAction }}" title="{{ $labelAction }}">{{ $ascending ? '↑' : ($descending ? '↓' : '↕') }}</a>
</th>

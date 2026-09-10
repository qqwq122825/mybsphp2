@props(['tone' => 'primary', 'type' => 'button'])

<button type="{{ $type }}" {{ $attributes->class(['admin-table-action', 'admin-table-action--'.$tone]) }}>{{ $slot }}</button>

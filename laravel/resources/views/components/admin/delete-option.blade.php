@props([
    'label',
    'name',
    'description',
    'checked' => true,
    'master' => false,
])

<div class="application-delete-option">
    <strong>{{ $label }}</strong>
    <label class="application-delete-switch">
        <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) @if($master) data-delete-application-master @endif>
        <span>删除</span><i aria-hidden="true">✓</i>
    </label>
    <small>{{ $description }}</small>
</div>

@php($selectedCharset = old($name, $selected ?? 'mixed'))
<select name="{{ $name }}">
    <option value="mixed" @selected($selectedCharset === 'mixed')>默认数字+大写+小写组合</option>
    <option value="digits" @selected($selectedCharset === 'digits')>仅数字</option>
    <option value="uppercase" @selected($selectedCharset === 'uppercase')>仅大写字母</option>
    <option value="lowercase" @selected($selectedCharset === 'lowercase')>仅小写字母</option>
    <option value="digits_uppercase" @selected($selectedCharset === 'digits_uppercase')>数字+大写字母</option>
    <option value="digits_lowercase" @selected($selectedCharset === 'digits_lowercase')>数字+小写字母</option>
</select>

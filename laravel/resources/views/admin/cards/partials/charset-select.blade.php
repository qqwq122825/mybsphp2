<select name="{{ $name }}">
    <option value="mixed" @selected(old($name, 'mixed') === 'mixed')>默认数字+大写+小写组合</option>
    <option value="digits" @selected(old($name) === 'digits')>仅数字</option>
    <option value="uppercase" @selected(old($name) === 'uppercase')>仅大写字母</option>
    <option value="lowercase" @selected(old($name) === 'lowercase')>仅小写字母</option>
    <option value="digits_uppercase" @selected(old($name) === 'digits_uppercase')>数字+大写字母</option>
    <option value="digits_lowercase" @selected(old($name) === 'digits_lowercase')>数字+小写字母</option>
</select>

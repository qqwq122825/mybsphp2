<script setup>
import { ElButton, ElDatePicker, ElFormItem, ElInput, ElInputNumber, ElOption, ElSelect } from 'element-plus';
import { DocumentCopy } from '@element-plus/icons-vue';
import { copyText } from './forms';

defineProps({ fields: Array, model: Object, errors: { type: Object, default: () => ({}) } });
function rules(control, label) {
    if (control.readonly || control.disabled) return [];
    const rules = [];
    if (control.required) rules.push({ required: true, message: `请填写${label}`, trigger: 'change' });
    if (control.type === 'number') rules.push({ type: 'integer', min: control.min, max: control.max, message: `请输入 ${control.min ?? '有效'} 至 ${control.max ?? '有效'} 的整数`, trigger: 'change' });
    if (control.maxlength) rules.push({ max: control.maxlength, message: `最多 ${control.maxlength} 个字符`, trigger: 'blur' });
    if (control.pattern) rules.push({ pattern: new RegExp(`^(?:${control.pattern})$`), message: `${label}格式不正确`, trigger: 'blur' });
    return rules;
}
</script>

<template>
    <div v-for="field in fields" :key="field.controls[0].key" class="ep-config-field" :class="{ 'ep-config-field-wide': field.controls.some(control => control.type === 'textarea') }">
        <div class="ep-field-controls">
            <el-form-item v-for="(control, index) in field.controls" :key="control.key" :label="index === 0 ? field.label : `${field.label}单位`" :prop="control.key" :error="errors[control.key]?.[0] || ''" :rules="rules(control, field.label)">
                <el-select v-if="control.type === 'select'" v-model="model[control.key]" :disabled="control.disabled" :aria-label="index ? `${field.label}单位` : field.label" @change="delete errors[control.key]">
                    <el-option v-for="option in control.options" :key="option.value" :label="option.label" :value="option.value" :disabled="option.disabled" />
                </el-select>
                <el-input-number v-else-if="control.type === 'number'" v-model="model[control.key]" :min="control.min" :max="control.max" :precision="0" controls-position="right" :disabled="control.disabled" :aria-label="field.label" @change="delete errors[control.key]" />
                <el-date-picker v-else-if="control.type === 'datetime-local'" v-model="model[control.key]" type="datetime" format="YYYY-MM-DD HH:mm:ss" value-format="YYYY-MM-DDTHH:mm:ss" :placeholder="control.placeholder || '选择日期与时间'" :aria-label="field.label" @change="delete errors[control.key]" />
                <el-input v-else v-model="model[control.key]" :type="['textarea', 'password'].includes(control.type) ? control.type : 'text'" :show-password="control.type === 'password'" :autocomplete="control.autocomplete" :inputmode="control.inputmode" :autosize="control.type === 'textarea' ? { minRows: Math.min(control.rows || 3, 5), maxRows: 10 } : undefined" :maxlength="control.maxlength" :readonly="control.readonly" :disabled="control.disabled" :placeholder="control.placeholder" :aria-label="field.label" @input="delete errors[control.key]" />
            </el-form-item>
        </div>
        <div v-if="field.help" class="ep-field-help">{{ field.help }}</div>
        <el-button v-if="field.copy !== undefined" :icon="DocumentCopy" class="ep-copy-button" @click="copyText(field.copy)">复制{{ field.label }}</el-button>
    </div>
</template>

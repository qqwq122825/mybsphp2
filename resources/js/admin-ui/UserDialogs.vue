<script setup>
import { computed, nextTick, ref } from 'vue';
import { ElAlert, ElButton, ElDatePicker, ElDialog, ElForm, ElFormItem, ElInput, ElInputNumber, ElOption, ElSelect, ElTag } from 'element-plus';
import FormFields from './FormFields.vue';
import { submitForm } from './forms';
import { userBulkFields, userDetailModel } from './read-users';

const props = defineProps({ page: Object, selected: Array, preview: Boolean });
const detail = ref(null);
const detailVisible = ref(false);
const model = ref({});
const errors = ref({});
const detailForm = ref();
const bulkVisible = ref(false);
const bulkForm = ref();
const bulkModel = ref({ action: '', value: null });
const busy = ref(false);
const needsValue = computed(() => ['add_time', 'subtract_time', 'set_expiry', 'bind_feature'].includes(bulkModel.value.action));
const timeAction = computed(() => ['add_time', 'subtract_time'].includes(bulkModel.value.action));
function openDetail(value, old = null) {
    detail.value = value;
    model.value = userDetailModel(value);
    errors.value = old ? { ...props.page.errors } : {};
    if (old) Object.keys(model.value).forEach(key => { if (Object.hasOwn(old, key)) model.value[key] = key.includes('concurrency') ? Number(old[key]) : old[key] ?? ''; });
    detailVisible.value = true;
    if (!old) nextTick(() => detailForm.value?.clearValidate());
}
function openBulk() { bulkModel.value = { action: '', value: null }; bulkVisible.value = true; nextTick(() => bulkForm.value?.clearValidate()); }
async function saveDetail() {
    if (busy.value || !await detailForm.value.validate().catch(() => false)) return;
    busy.value = true;
    const done = await submitForm({ action: detail.value.action, method: 'post' }, [...props.page.detail.fields, ...Object.entries(model.value), ['_ui_detail_target', detail.value.action]], { preview: props.preview });
    busy.value = false;
    if (done) detailVisible.value = false;
}
async function saveBulk() {
    if (busy.value || !props.selected.length || !await bulkForm.value.validate().catch(() => false)) return;
    busy.value = true;
    const label = props.page.bulk.options.find(option => option.value === bulkModel.value.action)?.label;
    const done = await submitForm(props.page.bulk, userBulkFields(props.page.bulk, props.selected, bulkModel.value.action, bulkModel.value.value), { preview: props.preview, tone: 'warning', confirm: `将对选中的 ${props.selected.length} 个用户执行「${label}」，请确认。` });
    busy.value = false;
    if (done) bulkVisible.value = false;
}
defineExpose({ openDetail, openBulk });
</script>

<template>
    <el-dialog v-model="detailVisible" title="用户详情" width="min(760px, calc(100vw - 32px))" append-to-body :close-on-click-modal="false" class="ep-admin-dialog ep-user-detail" destroy-on-close>
        <div v-if="detail" class="ep-user-title">{{ detail.title }}</div>
        <el-form ref="detailForm" :model="model" label-position="top" @submit.prevent.stop="saveDetail"><div class="ep-config-grid"><form-fields :fields="page.detail.inputs" :model="model" :errors="errors" /></div></el-form>
        <template #footer><el-button @click="detailVisible = false">取消</el-button><el-button type="primary" :loading="busy" @click="saveDetail">确认保存</el-button></template>
    </el-dialog>
    <el-dialog v-model="bulkVisible" title="软件用户批量维护" width="min(560px, calc(100vw - 32px))" append-to-body :close-on-click-modal="false" class="ep-admin-dialog">
        <el-alert :title="`已选择 ${selected.length} 个用户`" description="仅处理当前勾选的用户。批量维护前请备份相关数据。" type="warning" :closable="false" show-icon />
        <el-form ref="bulkForm" :model="bulkModel" label-position="top" class="ep-add-form" @submit.prevent.stop="saveBulk">
            <el-form-item label="维护功能" prop="action" :rules="[{ required: true, message: '请选择维护功能', trigger: 'change' }]"><el-select v-model="bulkModel.action" placeholder="选择维护功能" aria-label="维护功能" @change="bulkModel.value = null; bulkForm?.clearValidate()"><el-option v-for="option in page.bulk.options" :key="option.value" :value="option.value" :label="option.label" /></el-select></el-form-item>
            <el-form-item v-if="needsValue" :label="timeAction ? '时长（秒）' : bulkModel.action === 'set_expiry' ? '到期时间' : '绑定特征'" prop="value" :rules="[{ required: true, message: '请填写维护值', trigger: 'change' }]">
                <el-input-number v-if="timeAction" v-model="bulkModel.value" :min="1" :max="315360000" :precision="0" controls-position="right" aria-label="时长（秒）" />
                <el-date-picker v-else-if="bulkModel.action === 'set_expiry'" v-model="bulkModel.value" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="选择到期时间" />
                <el-input v-else v-model="bulkModel.value" :maxlength="160" aria-label="绑定特征" />
            </el-form-item>
        </el-form>
        <template #footer><el-button @click="bulkVisible = false">取消</el-button><el-button type="primary" :disabled="!selected.length" :loading="busy" @click="saveBulk">确认操作</el-button></template>
    </el-dialog>
</template>

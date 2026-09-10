<script setup>
import { computed, onMounted, ref } from 'vue';
import {
    ElAlert, ElButton, ElCard, ElCol, ElConfigProvider,
    ElDescriptions, ElDescriptionsItem, ElDialog, ElEmpty, ElForm, ElFormItem,
    ElInput, ElMessage, ElMessageBox, ElOption, ElPagination, ElRow,
    ElSelect, ElSpace, ElStatistic, ElTable, ElTableColumn, ElTabPane,
    ElTabs, ElTag, ElTooltip,
} from 'element-plus';
import { Search, Plus, Download, Printer, Operation, DocumentCopy } from '@element-plus/icons-vue';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import ServerContent from './ServerContent';
import UserDialogs from './UserDialogs.vue';
import { csvCell, decodePayload, submissionFields } from './read-list';

const props = defineProps({ page: { type: Object, required: true }, preview: Boolean });
const userDialogs = ref();
onMounted(() => {
    const old = props.page.userDialogs?.old;
    if (!old?._ui_detail_target) return;
    const target = props.page.rows.flatMap(row => row.cells.flatMap(cell => cell.content)).find(node => node.action?.userDetail?.action === old._ui_detail_target)?.action.userDetail;
    if (target) userDialogs.value.openDetail(target, old);
});
const filters = ref(Object.fromEntries(props.page.filter.fields.map(field => [field.key, field.value])));
const appliedFilters = ref({ ...filters.value });
const selected = ref([]);
const compact = ref(false);
const submitting = ref(false);
const currentPage = ref(props.page.pagination.page);
const addVisible = ref(props.page.add?.autoOpen || false);
const addModel = ref(Object.fromEntries((props.page.add?.inputs || []).map(input => [input.name, input.value])));
const addErrors = ref(Object.fromEntries((props.page.add?.inputs || []).map(input => [input.name, input.error])));
const addForm = ref();
const detail = ref(null);
const payloadTab = ref('request');
const detailVisible = computed({ get: () => !!detail.value, set: value => { if (!value) detail.value = null; } });
const requestPayload = computed(() => decodePayload(detail.value?.requestPayload));
const responsePayload = computed(() => decodePayload(detail.value?.responsePayload));
const previewRows = computed(() => {
    let rows = [...props.page.rows];
    const keyword = (appliedFilters.value.keyword || '').trim().toLowerCase();
    const subject = appliedFilters.value.subject_type;
    if (keyword) rows = rows.filter(row => row.cells.some(cell => cell.text.toLowerCase().includes(keyword)));
    if (subject && subject !== 'all') rows = rows.filter(row => subject === 'device' ? row.cells[1]?.text === '设备' : row.cells[1]?.text === 'IP' || (row.cells[3]?.text && row.cells[3].text !== '—'));
    if (appliedFilters.value.sort === 'oldest') rows.reverse();
    return rows;
});
const rows = computed(() => props.preview ? previewRows.value.slice((currentPage.value - 1) * props.page.pagination.perPage, currentPage.value * props.page.pagination.perPage) : props.page.rows);
const total = computed(() => props.preview ? previewRows.value.length : props.page.pagination.total);
const needsSelection = action => action.bulk && (!action.name || action.value === 'delete_selected');
const hasActionColumn = props.page.columns.some(column => column.action);

function query(page = 1) {
    if (props.preview) {
        appliedFilters.value = { ...filters.value };
        currentPage.value = page;
        selected.value = [];
        return;
    }
    const url = new URL(props.page.filter.action || window.location.href);
    props.page.filter.hidden.forEach(([key, value]) => url.searchParams.set(key, value));
    props.page.filter.fields.forEach(field => { if (field.name) url.searchParams.set(field.name, filters.value[field.key]); });
    url.searchParams.set('page', String(page));
    window.location.assign(url.href);
}

async function submit(action, fields) {
    if (submitting.value) return;
    if (action.confirm) {
        try {
            await ElMessageBox.confirm(action.confirm, '确认操作', { confirmButtonText: '确认', cancelButtonText: '取消', type: action.tone === 'danger' ? 'warning' : 'info', closeOnClickModal: false });
        } catch { return; }
    }
    if (props.preview) {
        ElMessage.success('组件交互预览完成，业务数据保持原状');
        return;
    }
    const url = new URL(action.action, window.location.href);
    if (url.origin !== window.location.origin) {
        ElMessage.error('提交地址异常，请刷新页面后重试');
        return;
    }
    submitting.value = true;
    const form = document.createElement('form');
    form.method = action.method || 'post';
    form.action = url.href;
    fields.forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value;
        form.append(input);
    });
    document.body.append(form);
    form.submit();
}

function runAction(action) {
    if (action.userDetail) { userDialogs.value.openDetail(action.userDetail); return; }
    if (action.userBulk) { userDialogs.value.openBulk(); return; }
    if (action.detail) { detail.value = action.detail; payloadTab.value = 'request'; return; }
    if (action.opens) { addVisible.value = true; return; }
    if (!action.action || (needsSelection(action) && !selected.value.length)) return;
    // Single-row actions intentionally submit only their own key, not the bulk selection.
    return submit(action, submissionFields(action, selected.value));
}

async function addBlock() {
    if (!await addForm.value.validate().catch(() => false)) return;
    if (!addModel.value.device_serial?.trim() && !addModel.value.ip?.trim()) {
        ElMessage.warning('请填写设备序列号或 IP 地址');
        return;
    }
    await submit({ action: props.page.add.action, method: 'post', tone: 'danger', confirm: '确定拉黑所填设备或 IP 吗？' }, [...props.page.add.fields, ...Object.entries(addModel.value)]);
}

function exportRows() {
    const columns = props.page.columns.filter(column => !column.selection && !column.action && !column.detail);
    const csv = [columns.map(column => csvCell(column.label)).join(','), ...rows.value.map(row => columns.map(column => csvCell(row.cells[column.key].text)).join(','))].join('\r\n');
    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
    const link = document.createElement('a'); link.href = url; link.download = `${props.page.title}-${new Date().toISOString().slice(0, 10)}.csv`; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function copyPayload() {
    try { await navigator.clipboard.writeText(payloadTab.value === 'request' ? requestPayload.value : responsePayload.value); ElMessage.success('已复制'); }
    catch { ElMessage.warning('请选中内容复制'); }
}
function printTable() { window.print(); }
</script>

<template>
    <el-config-provider :locale="zhCn" :z-index="5000">
        <el-card class="ep-admin-list" shadow="never">
            <template #header>
                <div class="ep-page-heading">
                    <div><h2>{{ page.title }}</h2><p>{{ page.subtitle }}</p></div>
                    <el-space>
                        <el-button v-for="(action, index) in page.headingActions" :key="index" type="primary" :icon="Plus" @click="runAction(action)">{{ action.label }}</el-button>
                    </el-space>
                </div>
                <el-row v-if="page.metrics.length" :gutter="24" class="ep-metrics">
                    <el-col v-for="metric in page.metrics" :key="metric.label" :span="8">
                        <el-statistic :title="metric.label" :value="metric.value"><template #suffix><small>{{ metric.suffix }}</small></template></el-statistic>
                    </el-col>
                </el-row>
            </template>

            <el-alert v-if="page.userDialogs && Object.keys(page.userDialogs.errors).length && !page.userDialogs.old._ui_detail_target" title="请检查操作参数" :description="Object.values(page.userDialogs.errors).flat().join('；')" type="error" show-icon :closable="false" class="ep-encryption-note" />
            <el-form :model="filters" label-position="top" class="ep-filter-grid" @submit.prevent.stop="query()">
                <el-form-item v-for="field in page.filter.fields" :key="field.key" :label="field.label">
                    <el-select v-if="field.options" v-model="filters[field.key]" :aria-label="field.label">
                        <el-option v-for="option in field.options" :key="option.value" :label="option.label" :value="option.value" :disabled="option.disabled" />
                    </el-select>
                    <el-input v-else v-model="filters[field.key]" :placeholder="field.placeholder" :aria-label="field.label" clearable />
                </el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit">搜索</el-button>
            </el-form>

            <div class="ep-table-toolbar">
                <el-space wrap>
                    <el-button v-for="(action, index) in page.actions" :key="index" :type="action.tone" plain :disabled="submitting || (needsSelection(action) && !selected.length)" @click="runAction(action)">{{ action.label }}</el-button>
                    <el-tag v-if="selected.length" type="info" effect="plain">已选 {{ selected.length }} 项</el-tag>
                </el-space>
                <el-space>
                    <el-tooltip content="切换表格密度"><el-button :icon="Operation" :type="compact ? 'primary' : ''" :plain="compact" aria-label="切换表格密度" :aria-pressed="compact" @click="compact = !compact" /></el-tooltip>
                    <el-tooltip content="导出当前页"><el-button :icon="Download" aria-label="导出当前表格" @click="exportRows" /></el-tooltip>
                    <el-tooltip content="打印"><el-button :icon="Printer" aria-label="打印" @click="printTable" /></el-tooltip>
                </el-space>
            </div>

            <el-table :data="rows" row-key="id" border stripe :size="compact ? 'small' : 'default'" :max-height="560" @selection-change="selected = $event">
                <template v-for="column in page.columns" :key="column.key">
                    <el-table-column v-if="column.selection" type="selection" :width="48" :selectable="row => !row.disabled" />
                    <el-table-column v-else :label="column.label" :width="column.width" :min-width="column.minWidth" :fixed="column.action || (column.detail && !hasActionColumn) ? 'right' : false" :align="column.action || column.detail ? 'center' : 'left'">
                        <template #default="scope">
                            <div class="ep-cell" :class="{ 'ep-cell-mono': scope.row.cells[column.key].mono }"><server-content :nodes="scope.row.cells[column.key].content" @action="runAction" /></div>
                        </template>
                    </el-table-column>
                </template>
                <template #empty><el-empty :description="page.emptyText" :image-size="72" /></template>
            </el-table>
            <div class="ep-pagination">
                <span>共 {{ total }} 条记录<span v-if="preview"> · 示例数据</span></span>
                <el-pagination :current-page="currentPage" :page-size="page.pagination.perPage" :total="total" :pager-count="5" layout="prev, pager, next" background @current-change="query" />
            </div>
        </el-card>

        <el-dialog v-if="page.add" v-model="addVisible" :title="page.add.title" width="min(600px, calc(100vw - 32px))" append-to-body :close-on-click-modal="false" class="ep-admin-dialog">
            <el-alert :title="page.add.note" type="info" :closable="false" show-icon />
            <el-form ref="addForm" :model="addModel" label-position="top" class="ep-add-form" @submit.prevent.stop="addBlock">
                <el-form-item v-for="input in page.add.inputs" :key="input.name" :label="input.label" :prop="input.name" :error="addErrors[input.name]" :rules="input.maxlength ? [{ max: input.maxlength, message: `最多 ${input.maxlength} 个字符`, trigger: 'blur' }] : []">
                    <el-input v-model="addModel[input.name]" :placeholder="input.placeholder" :maxlength="input.maxlength" clearable @input="addErrors[input.name] = ''" />
                    <div class="ep-field-help">{{ input.help }}</div>
                </el-form-item>
                <el-space class="ep-form-actions"><el-button @click="addVisible = false">取消</el-button><el-button type="primary" native-type="submit" :loading="submitting">确认拉黑</el-button></el-space>
            </el-form>
        </el-dialog>

        <el-dialog v-model="detailVisible" title="客户端请求详情" width="min(1000px, calc(100vw - 32px))" append-to-body class="ep-admin-dialog">
            <template v-if="detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="日志编号">{{ detail.logId }}</el-descriptions-item>
                    <el-descriptions-item label="请求时间">{{ detail.createdAt }}</el-descriptions-item>
                    <el-descriptions-item label="来源 IP">{{ detail.ip }}</el-descriptions-item>
                    <el-descriptions-item label="软件">{{ detail.application }}</el-descriptions-item>
                    <el-descriptions-item label="卡密">{{ detail.credential }}</el-descriptions-item>
                    <el-descriptions-item label="处理结果"><el-tag :type="detail.resultTone">{{ detail.result }}</el-tag></el-descriptions-item>
                    <el-descriptions-item label="响应">{{ detail.responseCode }} · HTTP {{ detail.httpStatus }}</el-descriptions-item>
                    <el-descriptions-item label="请求设备">{{ detail.requestDevice }}</el-descriptions-item>
                    <el-descriptions-item label="绑定设备">{{ detail.boundDevice }}</el-descriptions-item>
                    <el-descriptions-item label="返回设备">{{ detail.returnedDevice }}</el-descriptions-item>
                </el-descriptions>
                <el-tabs v-model="payloadTab" class="ep-payload-tabs">
                    <el-tab-pane label="解密请求" name="request"><pre class="ep-payload">{{ requestPayload }}</pre></el-tab-pane>
                    <el-tab-pane label="实际返回" name="response"><pre class="ep-payload">{{ responsePayload }}</pre></el-tab-pane>
                </el-tabs>
            </template>
            <template #footer><el-button :icon="DocumentCopy" @click="copyPayload">复制当前内容</el-button><el-button type="primary" @click="detailVisible = false">关闭</el-button></template>
        </el-dialog>
        <user-dialogs v-if="page.userDialogs" ref="userDialogs" :page="page.userDialogs" :selected="selected" :preview="preview" />
    </el-config-provider>
</template>

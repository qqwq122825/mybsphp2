<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { ElAlert, ElAside, ElButton, ElCard, ElCol, ElConfigProvider, ElContainer, ElDescriptions, ElDescriptionsItem, ElDialog, ElDivider, ElEmpty, ElForm, ElHeader, ElIcon, ElInput, ElMain, ElMenu, ElMenuItem, ElMessage, ElPagination, ElRow, ElSpace, ElStatistic, ElTable, ElTableColumn, ElTabPane, ElTabs, ElTag, ElText, ElTooltip } from 'element-plus';
import { DataBoard, Tickets, CreditCard, Wallet, Fold, Expand, Refresh, SwitchButton, Search, Star, StarFilled, Download, DocumentCopy } from '@element-plus/icons-vue';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import FormFields from '../admin-ui/FormFields.vue';
import ServerContent from '../admin-ui/ServerContent';
import { formValues } from '../admin-ui/read-software';
import { csvCell, submissionFields } from '../admin-ui/read-list';
import { copyText, submitForm } from '../admin-ui/forms';
import { purchaseTotal } from './read-agent';

const props = defineProps({ page: Object, preview: Boolean });
const media = window.matchMedia('(max-width: 800px)');
const mobile = ref(media.matches);
const updateMedia = event => { mobile.value = event.matches; };
media.addEventListener('change', updateMedia);
onBeforeUnmount(() => media.removeEventListener('change', updateMedia));
const collapsed = ref(false);
const icons = [DataBoard, Tickets, CreditCard, Wallet];
const activeNav = computed(() => props.page.navigation.find(item => item.active)?.href || '');
const model = ref(formValues(props.page.form?.controls || []));
const errors = ref({ ...props.page.errors });
const busy = ref(false);
const formRef = ref();
const keyword = ref(props.page.table?.keyword || '');
const applied = ref(keyword.value);
const currentPage = ref(props.page.table?.page || 1);
const density = ref(false);
const pendingStars = ref(new Set());
const dialog = ref(props.page.modal);
const dialogVisible = computed({ get: () => !!dialog.value, set: value => { if (!value) dialog.value = null; } });
const price = computed(() => props.page.form?.prices[model.value.card_type_id] ?? 0);
const totalPrice = computed(() => purchaseTotal(price.value, model.value.quantity));
const insufficient = computed(() => totalPrice.value > props.page.balance);
const filteredRows = computed(() => {
    const rows = props.page.table?.rows || [];
    if (!props.preview || !applied.value.trim()) return rows;
    const terms = applied.value.trim().toLowerCase().split(/\s+/);
    return rows.filter(row => terms.some(term => row.cells.some(cell => cell.text.toLowerCase().includes(term))));
});
const tableColumns = computed(() => (props.page.table?.columns || []).filter(column => !mobile.value || (!column.star && column.label !== '编号')));
const tableRows = computed(() => props.preview ? filteredRows.value.slice((currentPage.value - 1) * props.page.table.perPage, currentPage.value * props.page.table.perPage) : filteredRows.value);
const money = cents => (Number(cents) / 100).toFixed(2);
function navigate(href) { if (href && href !== activeNav.value) window.location.assign(href); }
function refresh() { window.location.reload(); }
function query(page = 1) {
    if (props.preview) { applied.value = keyword.value; currentPage.value = page; return; }
    const url = new URL(props.page.table.action || window.location.href);
    if (props.page.table.filter) url.searchParams.set('keyword', keyword.value);
    url.searchParams.set('page', page);
    window.location.assign(url.href);
}
async function submit(action, fields, confirm = '') {
    if (busy.value) return;
    busy.value = true;
    const done = await submitForm(action, fields, { preview: props.preview, confirm, tone: 'warning' });
    busy.value = false;
    return done;
}
async function save() {
    try { await formRef.value.validate(); }
    catch (invalid) { errors.value = Object.fromEntries(Object.entries(invalid || {}).map(([key, messages]) => [key, messages.map(message => message.message)])); return; }
    if (props.page.kind === 'purchase' && insufficient.value) return;
    const fields = [...props.page.form.fields, ...props.page.form.controls.flatMap(field => field.controls.filter(control => control.name).map(control => [control.name, model.value[control.key] ?? '']))];
    await submit(props.page.form, fields, props.page.kind === 'purchase' ? `确认制作 ${model.value.quantity} 张卡？预计扣款 ¥ ${money(totalPrice.value)}，实际金额由服务端核定。` : '');
}
async function runAction(action) {
    const values = Object.fromEntries(action.fields);
    if (props.preview && values.action === 'delete_card' && !values.confirmed) {
        dialog.value = { title: '确认删除', description: '预览删除确认流程，退款金额以服务端核算结果为准。业务数据保持原状。', card: '当前选中的示例卡', actions: [{ ...action, label: '确认删除并退款', fields: [...action.fields, ['confirmed', '1']] }] };
        return;
    }
    if (props.preview && values.action === 'unbind' && !action.name) {
        dialog.value = { title: '确认解绑', description: '请选择是否扣除解绑时间。本地预览保持原有到期时间和绑定数据。', card: '当前选中的示例卡', actions: ['1','0'].map(value => ({ ...action, label: value === '1' ? '扣除时间' : '不扣时间', name: 'deduct_time', value })) };
        return;
    }
    const done = await submit(action, submissionFields(action), ['freeze', 'unfreeze'].includes(values.action) ? `确认${action.label}这张卡？` : '');
    if (done) dialog.value = null;
}
async function star(row) {
    if (pendingStars.value.has(row.id)) return;
    if (props.preview) { row.star.on = !row.star.on; ElMessage.success('已更新示例标记'); return; }
    pendingStars.value.add(row.id);
    try {
        const url = new URL(row.star.url, location.href);
        if (url.origin !== location.origin) throw new Error();
        const response = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': props.page.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ action: 'star' }) });
        if (!response.ok) throw new Error();
        const result = await response.json();
        if (typeof result.starred !== 'boolean') throw new Error();
        row.star.on = result.starred;
    } catch { ElMessage.error('标记更新失败，请重试'); }
    finally { pendingStars.value.delete(row.id); }
}
function exportRows() {
    const columns = props.page.table.columns.filter(column => !column.star && !column.action);
    const csv = [columns.map(column => csvCell(column.label)).join(','), ...tableRows.value.map(row => columns.map(column => csvCell(row.cells[column.key].text)).join(','))].join('\r\n');
    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
    const link = document.createElement('a'); link.href = url; link.download = `${props.page.title}.csv`; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
function width(column) { if (mobile.value && column.label === '卡号') return 164; if (mobile.value && column.action) return 152; return column.star ? 64 : column.action ? 160 : column.label.includes('卡号') ? 240 : ['时间','制作时间'].includes(column.label) ? 176 : 132; }
</script>

<template>
    <el-config-provider :locale="zhCn" :z-index="5000">
        <el-container direction="vertical" style="min-height:100vh;background:var(--el-bg-color-page)">
            <el-header style="display:flex;align-items:center;justify-content:space-between;gap:12px;background:var(--el-bg-color);border-bottom:1px solid var(--el-border-color-light)">
                <el-space><el-button v-if="!page.login && !mobile" :icon="collapsed ? Expand : Fold" aria-label="切换侧栏" @click="collapsed = !collapsed" /><el-text tag="b" size="large">{{ page.appName }} · 代理端</el-text></el-space>
                <el-space v-if="!page.login"><el-tag v-if="!mobile" type="info">{{ page.account }}</el-tag><el-button :icon="Refresh" aria-label="刷新" @click="refresh" /><el-button :icon="SwitchButton" :loading="busy" @click="submit(page.logout, page.logout.fields)">退出</el-button></el-space>
            </el-header>
            <el-container style="min-width:0">
                <el-aside v-if="!page.login && !mobile && !collapsed" width="200px" style="background:var(--el-bg-color)"><el-menu :default-active="activeNav" @select="navigate"><el-menu-item v-for="(item,index) in page.navigation" :key="item.href" :index="item.href"><el-icon><component :is="icons[index]" /></el-icon><span>{{ item.label }}</span></el-menu-item></el-menu></el-aside>
                <el-main :style="{padding: mobile ? '16px' : '24px', minWidth:0}">
                    <el-space direction="vertical" alignment="stretch" :size="20" style="width:100%">
                        <el-alert v-if="preview" title="本地验收预览 · 示例数据" description="本页操作仅用于组件验收，余额和业务数据保持原状。" type="info" show-icon :closable="false" />
                        <el-tabs v-if="!page.login" :model-value="activeNav" @tab-change="navigate"><el-tab-pane v-for="item in page.navigation" :key="item.href" :label="item.label" :name="item.href" /></el-tabs>
                        <el-alert v-if="page.success" :title="page.success" type="success" show-icon />
                        <el-alert v-if="Object.keys(page.errors).length" title="请检查输入" :description="Object.values(page.errors).flat().join('；')" type="error" show-icon :closable="false" />
                        <template v-if="page.kind === 'dashboard'">
                            <el-card shadow="never"><template #header><el-text tag="b" size="large">代理公告</el-text></template><el-text>{{ page.announcement }}</el-text></el-card>
                            <el-row :gutter="16"><el-col v-for="metric in page.metrics" :key="metric.label" :xs="12" :sm="8" :lg="6" style="margin-bottom:16px"><el-card shadow="never"><el-statistic :title="metric.label" :value="metric.value" :precision="metric.money ? 2 : 0"><template #suffix><el-text size="small" type="info">{{ metric.unit }}</el-text></template></el-statistic></el-card></el-col></el-row>
                        </template>
                        <el-card v-if="page.form" shadow="never" :style="{maxWidth: page.login ? '480px' : '900px', width:'100%', margin:'0 auto'}">
                            <template #header><el-text tag="b" size="large">{{ page.title }}</el-text></template>
                            <el-form ref="formRef" :model="model" label-position="top" @submit.prevent.stop="save">
                                <el-row :gutter="20"><el-col v-for="field in page.form.controls" :key="field.controls[0].key" :span="24" style="margin-bottom:24px"><form-fields :fields="[field]" :model="model" :errors="errors" /></el-col></el-row>
                                <el-space v-if="page.form.captcha" style="margin-bottom:20px"><el-text>图形验证码</el-text><el-tag size="large">{{ page.form.captcha }}</el-tag><el-button :icon="Refresh" @click="refresh">刷新验证码</el-button></el-space>
                                <el-descriptions v-if="page.kind === 'purchase'" :column="mobile ? 1 : 3" border><el-descriptions-item label="当前余额">¥ {{ money(page.balance) }}</el-descriptions-item><el-descriptions-item label="制卡单价">¥ {{ money(price) }}</el-descriptions-item><el-descriptions-item label="预计扣款">¥ {{ money(totalPrice) }}</el-descriptions-item></el-descriptions>
                                <el-alert v-if="page.kind === 'purchase' && !Object.keys(page.form.prices).length" title="暂无可用卡种，请联系管理员配置。" type="info" :closable="false" style="margin-top:16px" />
                                <el-alert v-if="insufficient" title="账户余额不足，请调整数量或联系管理员充值。" type="warning" :closable="false" style="margin-top:16px" />
                                <el-divider />
                                <el-button type="primary" native-type="submit" :loading="busy" :disabled="page.kind === 'purchase' && (!model.card_type_id || insufficient)" style="width:100%">{{ page.form.submit }}</el-button>
                            </el-form>
                            <el-button v-if="preview && page.login" tag="a" href="/agent-dashboard.html" text type="primary" style="margin-top:16px">进入代理端预览</el-button>
                        </el-card>
                        <el-card v-if="page.result" shadow="never"><template #header><el-space><el-tag type="success">制卡成功</el-tag><el-text>{{ page.resultDescription }}</el-text><el-button :icon="DocumentCopy" @click="copyText(page.result)">复制全部</el-button></el-space></template><el-input :model-value="page.result" type="textarea" :rows="6" readonly aria-label="生成的卡密" /></el-card>
                        <el-card v-if="page.table" shadow="never">
                            <template #header><el-space wrap><el-text tag="b" size="large">{{ page.title }}</el-text><el-tag v-if="page.table.financial" type="info">当前余额 ¥ {{ money(page.balance) }}</el-tag></el-space></template>
                            <el-form v-if="page.table.filter" @submit.prevent.stop="query()" style="margin-bottom:20px"><el-row :gutter="12"><el-col :span="mobile ? 18 : 20"><el-input v-model="keyword" :type="page.table.multiline ? 'textarea' : 'text'" :rows="3" clearable placeholder="搜索完整卡号或后4位" aria-label="卡密搜索" /></el-col><el-col :span="mobile ? 6 : 4"><el-button type="primary" :icon="Search" native-type="submit">搜索</el-button></el-col></el-row></el-form>
                            <el-space wrap style="margin-bottom:16px"><el-button v-if="page.table.create" type="primary" tag="a" :href="page.table.create" :icon="CreditCard">余额制卡</el-button><el-button :icon="Download" @click="exportRows">导出当前页</el-button><el-button @click="density = !density">{{ density ? '默认密度' : '紧凑密度' }}</el-button></el-space>
                            <el-table :data="tableRows" row-key="id" border stripe :size="density ? 'small' : 'default'" :max-height="560">
                                <el-table-column v-for="column in tableColumns" :key="column.key" :label="column.label || '标记'" :width="column.star || column.action ? width(column) : undefined" :min-width="width(column)" :fixed="column.action ? 'right' : false" :align="column.star || column.action ? 'center' : 'left'">
                                    <template #default="scope"><el-button v-if="column.star && scope.row.star" :icon="scope.row.star.on ? StarFilled : Star" :type="scope.row.star.on ? 'warning' : ''" text :loading="pendingStars.has(scope.row.id)" :aria-label="scope.row.star.on ? '取消标记' : '标记'" @click="star(scope.row)" /><el-space v-else-if="mobile && column.label === '卡号'" direction="vertical" alignment="start" :size="4"><el-space><el-text size="small" type="info">#{{ scope.row.recordId }}</el-text><el-button v-if="scope.row.star" :icon="scope.row.star.on ? StarFilled : Star" :type="scope.row.star.on ? 'warning' : ''" text size="small" :loading="pendingStars.has(scope.row.id)" :aria-label="scope.row.star.on ? '取消标记' : '标记'" @click="star(scope.row)" /></el-space><div class="ep-cell"><server-content :nodes="scope.row.cells[column.key].content" /></div></el-space><el-text v-else-if="scope.row.cells[column.key].money" :type="scope.row.cells[column.key].money">{{ scope.row.cells[column.key].text }}</el-text><el-space v-else-if="column.action" wrap><el-button v-for="(action,index) in scope.row.actions" :key="index" :type="action.tone" plain :disabled="busy" @click="runAction(action)">{{ action.label }}</el-button></el-space><div v-else class="ep-cell"><server-content :nodes="scope.row.cells[column.key].content" @action="runAction" /></div></template>
                                </el-table-column>
                                <template #empty><el-empty :description="page.table.empty" :image-size="72" /></template>
                            </el-table>
                            <el-space wrap style="margin-top:20px"><el-text type="info">共 {{ preview ? filteredRows.length : page.table.total }} 条</el-text><el-pagination :current-page="currentPage" :total="preview ? filteredRows.length : page.table.total" :page-size="page.table.perPage" :pager-count="5" layout="prev, pager, next" background @current-change="query" /></el-space>
                        </el-card>
                    </el-space>
                </el-main>
            </el-container>
        </el-container>
        <el-dialog v-model="dialogVisible" :title="dialog?.title || '确认操作'" width="min(560px, calc(100vw - 32px))" append-to-body :close-on-click-modal="false" class="ep-admin-dialog"><template v-if="dialog"><el-alert :title="dialog.description" type="warning" show-icon :closable="false" /><el-divider /><el-text>{{ dialog.card }}</el-text></template><template #footer><el-space wrap><el-button @click="dialog = null">取消</el-button><el-button v-for="(action,index) in dialog?.actions" :key="index" :type="action.tone" :loading="busy" @click="runAction(action)">{{ action.label }}</el-button></el-space></template></el-dialog>
    </el-config-provider>
</template>

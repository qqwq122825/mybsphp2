<script setup>
import { ref } from 'vue';
import { ElAlert, ElButton, ElCard, ElCheckbox, ElConfigProvider, ElDialog, ElEmpty, ElIcon, ElPagination, ElSpace, ElStatistic, ElTag } from 'element-plus';
import { Box, Delete, Plus, Setting, Lock, User, Tickets, Collection, CreditCard } from '@element-plus/icons-vue';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import { deleteSubmission } from './read-software';
import { submitForm } from './forms';

const props = defineProps({ page: Object, preview: Boolean });
const deleting = ref(null);
const deleteVisible = ref(false);
const selected = ref([]);
const busy = ref(false);
const icons = { '软件配置': Setting, '封控设置': Lock, '用户管理': User, '充值卡列表': Tickets, '卡串类型': Collection, '现卡制作': CreditCard };
function openDelete(application) {
    deleting.value = application;
    selected.value = []; // Explicit choice instead of preselecting destructive actions.
    deleteVisible.value = true;
}
function toggle(name, checked) {
    if (name === 'delete_application' && checked) selected.value = deleting.value.deletion.options.map(option => option.name);
    else if (checked) selected.value.push(name);
    else selected.value = selected.value.filter(value => value !== name && value !== 'delete_application');
}
async function remove() {
    if (!selected.value.length || busy.value) return;
    busy.value = true;
    const done = await submitForm(deleting.value.deletion, deleteSubmission(deleting.value.deletion, selected.value), { preview: props.preview, tone: 'warning', confirm: `确认删除「${deleting.value.name}」所选的 ${selected.value.length} 类数据？请确认已备份相关数据。` });
    busy.value = false;
    if (done) deleteVisible.value = false;
}
function paginate(page) { const url = new URL(location.href); url.searchParams.set('page', page); location.assign(url); }
</script>

<template>
    <el-config-provider :locale="zhCn" :z-index="5000">
        <div class="ep-software-page">
            <header class="ep-page-heading">
                <div><h2>{{ page.title }}</h2><p>{{ page.subtitle }}</p></div>
                <el-button v-if="page.create" type="primary" :icon="Plus" tag="a" :href="page.create.href">{{ page.create.label }}</el-button>
            </header>
            <div class="ep-summary-grid">
                <el-card v-for="metric in page.metrics" :key="metric.label" shadow="never">
                    <el-statistic :title="metric.label" :value="metric.value" />
                    <p class="ep-field-help">{{ metric.help }}</p>
                </el-card>
            </div>
            <el-card v-if="!page.applications.length" shadow="never"><el-empty description="还没有软件，先添加软件，再建立卡种并生成卡密。" /></el-card>
            <el-card v-for="application in page.applications" :key="application.code" shadow="never" class="ep-software-card">
                <template #header>
                    <div class="ep-page-heading">
                        <div class="ep-software-identity"><el-icon :size="24" color="var(--el-color-primary)"><Box /></el-icon><div><h2>{{ application.name }}</h2><p>软件代号 <code>{{ application.code }}</code></p></div></div>
                        <el-space><el-tag :type="application.active ? 'success' : 'info'">{{ application.status }}</el-tag><el-button v-if="application.deletion" type="danger" plain :icon="Delete" @click="openDelete(application)">删除软件</el-button></el-space>
                    </div>
                </template>
                <div class="ep-software-mode"><span>软件模式</span><el-tag type="info" effect="plain">{{ application.mode }}</el-tag></div>
                <div class="ep-software-stats"><el-statistic v-for="stat in application.stats" :key="stat.label" :title="stat.label" :value="stat.value" /></div>
                <template #footer>
                    <el-space wrap><el-button v-for="link in application.links" :key="link.href" :type="link.tone" :plain="!!link.tone" :icon="icons[link.label]" tag="a" :href="link.href" :data-workspace-open="link.workspace ? '' : null" :data-workspace-title="link.title">{{ link.label }}</el-button></el-space>
                </template>
            </el-card>
            <div class="ep-pagination"><span>共 {{ page.pagination.total }} 个软件</span><el-pagination :total="page.pagination.total" :page-size="page.pagination.perPage" :current-page="page.pagination.page" layout="prev, pager, next" background @current-change="paginate" /></div>
        </div>
        <el-dialog v-model="deleteVisible" title="删除软件数据" width="min(560px, calc(100vw - 32px))" append-to-body :close-on-click-modal="false" class="ep-admin-dialog">
            <template v-if="deleting">
                <el-alert :title="`${deleting.name} · ${deleting.code}`" description="请选择删除范围。勾选软件本身将同时选中全部关联数据。" type="warning" :closable="false" show-icon />
                <div class="ep-delete-options"><div v-for="option in deleting.deletion.options" :key="option.name"><el-checkbox :model-value="selected.includes(option.name)" @change="toggle(option.name, $event)">{{ option.label }}</el-checkbox><p class="ep-field-help">{{ option.help }}</p></div></div>
            </template>
            <template #footer><el-button @click="deleteVisible = false">取消</el-button><el-button type="danger" :disabled="!selected.length" :loading="busy" @click="remove">确认删除</el-button></template>
        </el-dialog>
    </el-config-provider>
</template>

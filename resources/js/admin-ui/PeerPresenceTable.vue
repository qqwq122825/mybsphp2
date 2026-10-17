<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { ElAlert, ElButton, ElPagination, ElTable, ElTableColumn, ElTag } from 'element-plus';
import { Refresh } from '@element-plus/icons-vue';
const props = defineProps({ page: Object, preview: Boolean });
const data = ref({ ...props.page });
const busy = ref(false);
const error = ref(props.page.error || '');
let controller;
async function refresh(page = data.value.page) {
    if (busy.value || props.preview) return;
    busy.value = true; error.value = ''; controller = new AbortController();
    try {
        const url = new URL(props.page.url, location.href); url.searchParams.set('peer_page', page);
        const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('同行名单刷新失败，请稍后重试或重新登录。');
        const payload = await response.json();
        if (!Array.isArray(payload.rows) || payload.error) throw new Error(payload.error || '同行名单响应异常，请重试。');
        data.value = payload;
    } catch (problem) { if (problem.name !== 'AbortError') error.value = problem.message; }
    finally { busy.value = false; }
}
onMounted(() => refresh());
onBeforeUnmount(() => controller?.abort());
</script>
<template>
    <section class="ep-peer-presence" aria-label="在线同行名单">
        <div class="ep-table-toolbar"><h3>在线同行 <el-tag type="info" size="small">共 {{ data.total }} 个昵称</el-tag></h3><el-button :icon="Refresh" :loading="busy" native-type="button" @click="refresh()">刷新名单</el-button></div>
        <p class="ep-field-help">仅显示当前软件，按最近心跳倒序；同名合并。10 分钟心跳，20 分钟未更新自动下线。状态以已保存的开关为准。</p>
        <el-alert v-if="error" :title="error" type="error" :closable="false" />
        <p v-if="data.pending_names" class="ep-field-help">有 {{ data.pending_names }} 条旧记录等待下次心跳补全昵称。</p>
        <el-table :data="data.rows" border stripe max-height="460" :empty-text="data.enabled ? '暂无在线同行，进入对局上报后显示。' : '同行显示已关闭，开启并保存后接收上报。'" aria-label="同行名字和最近心跳时间">
            <el-table-column prop="game_name" label="同行名字" min-width="150" />
            <el-table-column prop="last_seen_at" label="最近心跳时间（北京时间）" min-width="220" />
        </el-table>
        <div class="ep-pagination"><span>名单刷新于 {{ data.refreshed_at }}（北京时间）</span><el-pagination layout="total, prev, pager, next" :pager-count="5" :page-size="data.per_page" :total="data.total" :current-page="data.page" :disabled="busy" @current-change="refresh" /></div>
    </section>
</template>

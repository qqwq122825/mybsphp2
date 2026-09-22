<script setup>
import { ref } from 'vue';
import { ElAlert, ElButton, ElInput, ElSelect, ElSwitch, ElOption, ElTable, ElTableColumn, ElMessageBox } from 'element-plus';
import { copyText, submitForm } from './forms';

const props = defineProps({ page: Object, preview: Boolean });
const enabled = ref(props.page.enabled);
const permission = ref(props.page.permission);
const search = ref(props.page.search);
const busy = ref(false);
async function save(action = 'save', nextEnabled = enabled.value) {
    if (busy.value) return false;
    if (action !== 'save') {
        try {
            await ElMessageBox.confirm(action === 'rotate_path'
                ? '生成新地址，旧地址保留 10 分钟，请同步更新 CDN 白名单和 DFMProxy。'
                : action === 'rotate_token' ? '旧密钥立即失效并解除节点绑定，请更新 DFMProxy。'
                    : '旧地址立即失效，请同步更新 CDN 白名单和 DFMProxy。', '确认重置', { type: 'warning' });
        } catch { return false; }
    }
    busy.value = true;
    try {
        return await submitForm(props.page, [...props.page.fields, ['enabled', nextEnabled], ['permission', permission.value], ['action', action]], { preview: props.preview });
    } finally { busy.value = false; }
}
function toggle() {
    // Persist first: failed requests leave the switch at the server-confirmed value.
    return save('save', enabled.value === '1' ? '0' : '1');
}
function query() {
    const url = new URL(props.page.searchAction);
    url.searchParams.set('config_tab', 'proxy');
    url.searchParams.set('proxy_search', search.value);
    if (!props.preview) window.location.assign(url.href);
}
</script>

<template>
    <section class="ep-config-section">
        <h3>代理联动 · 单应用单节点</h3>
        <el-alert title="专属随机入口，独立节点密钥" description="开启后自动保存并生成专属地址；激活卡密随下一次同步到代理，账号和密码都填卡密。原客户端登录入口保持独立。" type="info" :closable="false" />
        <div class="ep-config-grid" style="margin: 20px 0">
            <div><p>启用代理联动</p><el-switch v-model="enabled" active-value="1" inactive-value="0" active-text="开启" inactive-text="关闭" aria-label="启用代理联动" :loading="busy" :disabled="busy" :before-change="toggle" /><p class="ep-field-help">开启即保存并生成地址，无需另点保存。关闭后保留地址，再次开启沿用。</p></div>
            <div><p>同步用户权限</p><el-select v-model="permission" aria-label="同步用户权限" :disabled="busy"><el-option label="仅重放" value="replay" /><el-option label="仅录制" value="record" /><el-option label="录制＋重放" value="both" /></el-select></div>
        </div>
        <el-alert v-if="!page.address" title="尚未生成联动地址" description="打开上方开关后，这里将显示联动地址、CDN 白名单路径和节点接入密钥。" type="info" :closable="false" />
        <div v-if="page.address" class="ep-connection-list">
            <div v-for="item in [{ label: '联动地址', value: page.address, copy: page.address }, { label: 'CDN 精确匹配路径', value: page.path, copy: page.path }, { label: '节点接入密钥', value: '••••••••' + page.token.slice(-4), copy: page.token }]" :key="item.label" class="ep-connection-item">
                <span>{{ item.label }}</span><el-input :model-value="item.value" :aria-label="item.label" readonly><template #append><el-button @click="copyText(item.copy)">复制</el-button></template></el-input>
            </div>
        </div>
        <p>节点：{{ page.node }}｜最近拉取：{{ page.lastSync }}｜上次拉取用户数：{{ page.count }}</p>
        <p v-if="page.previousPath">过渡路径：{{ page.previousPath }}，有效至 {{ page.previousUntil }}</p>
        <p class="ep-field-help">CDN 仅对完整路径配置 POST 白名单、免浏览器挑战、禁用缓存；保留 DDoS 防护及限流。首次成功同步绑定节点。网络宽限最多 300 秒，仍受用户原到期时间限制。</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin:20px 0">
            <el-button type="primary" :loading="busy" @click="save()">保存权限配置</el-button>
            <template v-if="page.address">
                <el-button :disabled="busy" @click="save('rotate_path')">轮换地址（10 分钟过渡）</el-button>
                <el-button type="warning" plain :disabled="busy" @click="save('rotate_path_now')">立即重置地址</el-button>
                <el-button type="danger" plain :disabled="busy" @click="save('rotate_token')">重置密钥 / 更换节点</el-button>
            </template>
        </div>
        <h3>代理账号</h3>
        <el-input v-model="search" placeholder="按授权唯一 ID 精确查询（请勿填写卡密）" aria-label="查询代理账号" @keydown.enter.prevent.stop="query"><template #append><el-button @click="query">查询</el-button></template></el-input>
        <el-table :data="page.credentials" empty-text="暂无可显示的代理账号，请先激活卡密并同步" style="margin:16px 0">
            <el-table-column prop="subject" label="授权类型 / 唯一 ID" min-width="280" show-overflow-tooltip />
            <el-table-column prop="username" label="代理用户名" min-width="230"><template #default="{ row }"><el-button link @click="copyText(row.username)">{{ row.username }}</el-button></template></el-table-column>
            <el-table-column label="代理密码" width="130"><template #default="{ row }"><el-button @click="copyText(row.password)">复制密码</el-button></template></el-table-column>
        </el-table>
        <el-button v-if="page.previousUrl" tag="a" :href="page.previousUrl">上一页</el-button><el-button v-if="page.nextUrl" tag="a" :href="page.nextUrl">下一页</el-button><span> 共 {{ page.total }} 个账号</span>
        <p class="ep-field-help">卡密模式：代理账号＝密码＝卡密；账号充值模式使用独立代理码，账号与密码相同。到期用户从 DFMProxy 自动删除，后台卡密与历史记录保留；续期后同步恢复。出现账号不代表当前有效，空到期时间、积分卡暂不授予代理访问资格。</p>
    </section>
</template>

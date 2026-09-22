<script setup>
import { ref } from 'vue';
import { ElButton, ElInput, ElSwitch } from 'element-plus';
import { copyText, submitForm } from './forms';

const props = defineProps({ page: Object, preview: Boolean });
const enabled = ref(props.page.enabled);
const busy = ref(false);
async function toggle() {
    if (busy.value) return false;
    busy.value = true;
    try {
        // Commit first so a failed request never displays an unsaved switch state.
        return await submitForm(props.page, [...props.page.fields, ['enabled', enabled.value === '1' ? '0' : '1'], ['action', 'save']], { preview: props.preview });
    } finally { busy.value = false; }
}
</script>

<template>
    <section class="ep-config-section">
        <div style="margin: 20px 0">
            <p>启用代理联动</p>
            <el-switch v-model="enabled" active-value="1" inactive-value="0" active-text="开启" inactive-text="关闭" aria-label="启用代理联动" :loading="busy" :disabled="busy" :before-change="toggle" />
            <p class="ep-field-help">开启后自动生成同步地址。</p>
        </div>
        <template v-if="page.address">
            <div class="ep-connection-list">
                <div class="ep-connection-item">
                    <span>同步地址</span>
                    <el-input :model-value="page.address" aria-label="同步地址" readonly><template #append><el-button @click="copyText(page.address)">复制地址</el-button></template></el-input>
                </div>
            </div>
            <p class="ep-field-help">将此地址填入 DFMProxy，每 30 秒自动同步账号。此地址包含同步凭证，请勿公开。</p>
        </template>
        <p v-else class="ep-field-help">尚未生成联动地址。开启上方开关即可生成。</p>
    </section>
</template>

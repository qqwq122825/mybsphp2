<script setup>
import { ref } from 'vue';
import { ElAlert, ElButton, ElCard, ElCollapse, ElCollapseItem, ElConfigProvider, ElForm, ElInput, ElTabPane, ElTabs, ElTag } from 'element-plus';
import { ArrowLeft, Check, DocumentCopy } from '@element-plus/icons-vue';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import FormFields from './FormFields.vue';
import ProxyLink from './ProxyLink.vue';
import { formSubmission, formValues } from './read-software';
import { copyText, submitForm } from './forms';

const props = defineProps({ page: Object, preview: Boolean });
const activeTab = ref(props.page.activeTab);
const model = ref(formValues(props.page.groups.flatMap(group => group.fields)));
const errors = ref({ ...props.page.errors });
const formRef = ref();
const busy = ref(false);
async function save() {
    if (busy.value) return;
    try { await formRef.value.validate(); }
    catch (invalid) {
        errors.value = Object.fromEntries(Object.entries(invalid || {}).map(([key, messages]) => [key, messages.map(message => message.message)]));
        const key = Object.keys(invalid || {})[0];
        const group = props.page.groups.find(group => group.fields.some(field => field.controls.some(control => control.key === key)));
        if (group) activeTab.value = group.tab;
        return;
    }
    busy.value = true;
    await submitForm(props.page, formSubmission(props.page, model.value), { preview: props.preview });
    busy.value = false;
}
</script>

<template>
    <el-config-provider :locale="zhCn" :z-index="5000">
        <el-card shadow="never" class="ep-software-form">
            <template #header><div class="ep-page-heading"><div><h2>{{ page.title }}</h2><p>{{ page.subtitle }}</p></div><el-button v-if="page.back" tag="a" :href="page.back.href" :icon="ArrowLeft">返回列表</el-button></div></template>
            <el-form ref="formRef" :model="model" label-position="top" @submit.prevent.stop="save">
                <el-tabs v-model="activeTab">
                    <el-tab-pane v-for="tab in page.tabs" :key="tab.name" :label="tab.label" :name="tab.name">
                        <proxy-link v-if="tab.name === 'proxy' && page.proxy" :page="page.proxy" :preview="preview" />
                        <section v-for="(group, groupIndex) in page.groups.filter(group => group.tab === tab.name)" :key="groupIndex" class="ep-config-section">
                            <h3>{{ group.title }}</h3>
                            <div v-if="group.connections.length" class="ep-connection-list">
                                <div v-for="connection in group.connections" :key="connection.label" class="ep-connection-item"><span>{{ connection.label }}</span><div><el-input :model-value="connection.value" readonly :aria-label="connection.label"><template v-if="connection.copy !== undefined" #append><el-button :icon="DocumentCopy" :aria-label="`复制${connection.label}`" @click="copyText(connection.copy)" /></template></el-input><p v-if="connection.help" class="ep-field-help">{{ connection.help }}</p></div></div>
                            </div>
                            <el-alert v-if="group.encryption" :title="group.encryption" description="通信密钥和服务器公钥由系统生成，请妥善保存。" type="info" :closable="false" show-icon class="ep-encryption-note" />
                            <div class="ep-config-grid"><form-fields :fields="group.fields.filter(field => !field.certificate)" :model="model" :errors="errors" /></div>
                            <el-collapse v-if="group.fields.some(field => field.certificate)"><el-collapse-item title="证书与客户端配置" name="certificate"><div class="ep-config-grid"><form-fields :fields="group.fields.filter(field => field.certificate)" :model="model" :errors="errors" /></div></el-collapse-item></el-collapse>
                        </section>
                    </el-tab-pane>
                </el-tabs>
                <div v-show="activeTab !== 'proxy'" class="ep-settings-footer"><span class="ep-field-help">{{ page.submitHelp }}</span><el-button type="primary" native-type="submit" :icon="Check" :loading="busy">{{ page.submitLabel }}</el-button></div>
            </el-form>
        </el-card>
    </el-config-provider>
</template>

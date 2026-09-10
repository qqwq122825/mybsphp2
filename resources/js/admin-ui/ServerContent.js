import { h } from 'vue';
import { ElButton, ElTag } from 'element-plus';

// Only trusted structure + text nodes cross the Blade/Vue boundary. No v-html,
// runtime templates, eval, or forwarding of inline event-handler source.
export default {
    props: { nodes: { type: Array, required: true } },
    emits: ['action'],
    setup(props, { emit }) {
        const renderNode = (node, index) => {
            if ('text' in node) return node.text;
            if (node.action) return h(ElButton, { key: index, type: node.action.tone, plain: true, size: 'small', disabled: node.action.disabled, onClick: () => emit('action', node.action) }, () => node.action.label);
            if ('tag' in node) return h(ElTag, { key: index, type: node.tone, effect: 'light', disableTransitions: true }, () => node.tag);
            return h(node.element, { key: index, class: node.deviceLine ? 'ep-device-line' : undefined }, node.children.map(renderNode));
        };
        return () => props.nodes.map(renderNode);
    },
};

import { defineComponent, ref, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderStyle, ssrInterpolate, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import Image from '@tiptap/extension-image';
import Youtube from '@tiptap/extension-youtube';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import { Highlight } from '@tiptap/extension-highlight';
import { Subscript } from '@tiptap/extension-subscript';
import { Superscript } from '@tiptap/extension-superscript';
import { TaskList } from '@tiptap/extension-task-list';
import { TaskItem } from '@tiptap/extension-task-item';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { CodeBlockLowlight } from '@tiptap/extension-code-block-lowlight';
import { createLowlight, common } from 'lowlight';
import { Icon } from '@iconify/vue';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "RichTextEditor",
  __ssrInlineRender: true,
  props: {
    modelValue: { default: "" },
    placeholder: { default: "\u0E40\u0E02\u0E35\u0E22\u0E19\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48..." },
    editable: { type: Boolean, default: true },
    minHeight: { default: "200px" }
  },
  emits: ["update:modelValue"],
  setup(__props, { emit: __emit }) {
    const lowlight = createLowlight(common);
    const props = __props;
    const emit = __emit;
    const textColors = [
      "#000000",
      "#374151",
      "#DC2626",
      "#EA580C",
      "#D97706",
      "#16A34A",
      "#0891B2",
      "#2563EB",
      "#7C3AED",
      "#DB2777"
    ];
    const highlightColors = [
      "#FEF08A",
      "#BBF7D0",
      "#A5F3FC",
      "#C7D2FE",
      "#FBCFE8",
      "#FED7AA",
      "#E5E7EB",
      "#FCA5A5",
      "#86EFAC",
      "#67E8F9"
    ];
    const showTextColorPicker = ref(false);
    const showHighlightPicker = ref(false);
    const showTableMenu = ref(false);
    const editor = useEditor({
      content: props.modelValue,
      editable: props.editable,
      extensions: [
        StarterKit.configure({
          heading: { levels: [1, 2, 3] },
          codeBlock: false,
          // Use CodeBlockLowlight instead
          // Disable built-in extensions that we're adding separately
          link: false,
          underline: false
        }),
        Link.configure({
          openOnClick: false,
          HTMLAttributes: { class: "text-blue-500 underline hover:text-blue-700" }
        }),
        TextAlign.configure({ types: ["heading", "paragraph"] }),
        Underline,
        Placeholder.configure({ placeholder: props.placeholder }),
        Image.configure({ HTMLAttributes: { class: "max-w-full rounded-lg" } }),
        Youtube.configure({ width: 640, height: 360 }),
        TextStyle,
        Color,
        Highlight.configure({ multicolor: true }),
        Subscript,
        Superscript,
        TaskList,
        TaskItem.configure({ nested: true }),
        Table.configure({ resizable: true }),
        TableRow,
        TableCell,
        TableHeader,
        CodeBlockLowlight.configure({ lowlight })
      ],
      onUpdate: ({ editor: editor2 }) => {
        emit("update:modelValue", editor2.getHTML());
      }
    });
    watch(() => props.modelValue, (newValue) => {
      if (editor.value && newValue !== editor.value.getHTML()) {
        editor.value.commands.setContent(newValue, false, { emitUpdate: false });
      }
    });
    watch(() => props.editable, (newValue) => {
      if (editor.value) editor.value.setEditable(newValue);
    });
    const showTemplateMenu = ref(false);
    const templates = [
      {
        name: "\u{1F4DA} \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B",
        icon: "fluent:book-24-regular",
        content: `
<h2>\u{1F4CB} \u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h2>
<p>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E21\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E41\u0E25\u0E30\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E17\u0E31\u0E01\u0E29\u0E30\u0E17\u0E35\u0E48\u0E08\u0E33\u0E40\u0E1B\u0E47\u0E19...</p>

<h2>\u{1F3AF} \u0E27\u0E31\u0E15\u0E16\u0E38\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</h2>
<ul>
  <li>\u2705 \u0E40\u0E02\u0E49\u0E32\u0E43\u0E08\u0E2B\u0E25\u0E31\u0E01\u0E01\u0E32\u0E23\u0E41\u0E25\u0E30\u0E41\u0E19\u0E27\u0E04\u0E34\u0E14\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19</li>
  <li>\u2705 \u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E19\u0E33\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49\u0E44\u0E1B\u0E1B\u0E23\u0E30\u0E22\u0E38\u0E01\u0E15\u0E4C\u0E43\u0E0A\u0E49\u0E44\u0E14\u0E49\u0E08\u0E23\u0E34\u0E07</li>
  <li>\u2705 \u0E1E\u0E31\u0E12\u0E19\u0E32\u0E17\u0E31\u0E01\u0E29\u0E30\u0E01\u0E32\u0E23\u0E04\u0E34\u0E14\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C</li>
  <li>\u2705 \u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E17\u0E33\u0E07\u0E32\u0E19\u0E23\u0E48\u0E27\u0E21\u0E01\u0E31\u0E1A\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E44\u0E14\u0E49</li>
</ul>

<h2>\u{1F4D6} \u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19</h2>
<ol>
  <li><strong>\u0E1A\u0E17\u0E17\u0E35\u0E48 1:</strong> \u0E1A\u0E17\u0E19\u0E33\u0E41\u0E25\u0E30\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19</li>
  <li><strong>\u0E1A\u0E17\u0E17\u0E35\u0E48 2:</strong> \u0E2B\u0E25\u0E31\u0E01\u0E01\u0E32\u0E23\u0E2A\u0E33\u0E04\u0E31\u0E0D</li>
  <li><strong>\u0E1A\u0E17\u0E17\u0E35\u0E48 3:</strong> \u0E01\u0E32\u0E23\u0E1B\u0E23\u0E30\u0E22\u0E38\u0E01\u0E15\u0E4C\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</li>
  <li><strong>\u0E1A\u0E17\u0E17\u0E35\u0E48 4:</strong> \u0E01\u0E23\u0E13\u0E35\u0E28\u0E36\u0E01\u0E29\u0E32</li>
  <li><strong>\u0E1A\u0E17\u0E17\u0E35\u0E48 5:</strong> \u0E42\u0E04\u0E23\u0E07\u0E07\u0E32\u0E19\u0E41\u0E25\u0E30\u0E2A\u0E23\u0E38\u0E1B</li>
</ol>

<h2>\u{1F465} \u0E40\u0E2B\u0E21\u0E32\u0E30\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A</h2>
<p>\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E19\u0E43\u0E08\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49\u0E41\u0E25\u0E30\u0E17\u0E31\u0E01\u0E29\u0E30\u0E43\u0E19\u0E14\u0E49\u0E32\u0E19\u0E19\u0E35\u0E49 \u0E44\u0E21\u0E48\u0E08\u0E33\u0E40\u0E1B\u0E47\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E21\u0E32\u0E01\u0E48\u0E2D\u0E19</p>

<h2>\u23F0 \u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</h2>
<p>\u0E1B\u0E23\u0E30\u0E21\u0E32\u0E13 <strong>10-15 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</strong> \u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49\u0E15\u0E32\u0E21\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E30\u0E14\u0E27\u0E01</p>
`
      },
      {
        name: "\u{1F4BB} \u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E02\u0E35\u0E22\u0E19\u0E42\u0E1B\u0E23\u0E41\u0E01\u0E23\u0E21",
        icon: "fluent:code-24-regular",
        content: `
<h2>\u{1F680} \u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E19\u0E35\u0E49</h2>
<p>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E01\u0E32\u0E23\u0E40\u0E02\u0E35\u0E22\u0E19\u0E42\u0E1B\u0E23\u0E41\u0E01\u0E23\u0E21\u0E15\u0E31\u0E49\u0E07\u0E41\u0E15\u0E48\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E08\u0E19\u0E16\u0E36\u0E07\u0E02\u0E31\u0E49\u0E19\u0E2A\u0E39\u0E07 \u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E42\u0E1B\u0E23\u0E40\u0E08\u0E01\u0E15\u0E4C\u0E08\u0E23\u0E34\u0E07!</p>

<h2>\u{1F4A1} \u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E08\u0E30\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</h2>
<ul>
  <li>\u{1F539} \u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E40\u0E02\u0E35\u0E22\u0E19\u0E42\u0E1B\u0E23\u0E41\u0E01\u0E23\u0E21</li>
  <li>\u{1F539} \u0E42\u0E04\u0E23\u0E07\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E25\u0E01\u0E2D\u0E23\u0E34\u0E17\u0E36\u0E21</li>
  <li>\u{1F539} \u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E42\u0E1B\u0E23\u0E40\u0E08\u0E01\u0E15\u0E4C\u0E08\u0E23\u0E34\u0E07</li>
  <li>\u{1F539} Best Practices \u0E41\u0E25\u0E30 Clean Code</li>
  <li>\u{1F539} \u0E01\u0E32\u0E23\u0E17\u0E33\u0E07\u0E32\u0E19\u0E23\u0E48\u0E27\u0E21\u0E01\u0E31\u0E1A\u0E17\u0E35\u0E21 (Git/GitHub)</li>
</ul>

<h2>\u{1F6E0}\uFE0F \u0E40\u0E04\u0E23\u0E37\u0E48\u0E2D\u0E07\u0E21\u0E37\u0E2D\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49</h2>
<ul>
  <li>\u{1F4CC} VS Code / IDE</li>
  <li>\u{1F4CC} Git & GitHub</li>
  <li>\u{1F4CC} Terminal / Command Line</li>
</ul>

<h2>\u{1F4CB} \u0E02\u0E49\u0E2D\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E40\u0E1A\u0E37\u0E49\u0E2D\u0E07\u0E15\u0E49\u0E19</h2>
<ul>
  <li>\u2714\uFE0F \u0E04\u0E2D\u0E21\u0E1E\u0E34\u0E27\u0E40\u0E15\u0E2D\u0E23\u0E4C\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49</li>
  <li>\u2714\uFE0F \u0E04\u0E27\u0E32\u0E21\u0E01\u0E23\u0E30\u0E15\u0E37\u0E2D\u0E23\u0E37\u0E2D\u0E23\u0E49\u0E19\u0E43\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</li>
  <li>\u2714\uFE0F \u0E44\u0E21\u0E48\u0E08\u0E33\u0E40\u0E1B\u0E47\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E21\u0E32\u0E01\u0E48\u0E2D\u0E19</li>
</ul>

<h2>\u{1F3C6} \u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A</h2>
<p>\u2728 \u0E43\u0E1A\u0E23\u0E31\u0E1A\u0E23\u0E2D\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A | \u{1F4C1} Source Code \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 | \u{1F4AC} \u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E15\u0E25\u0E2D\u0E14\u0E04\u0E2D\u0E23\u0E4C\u0E2A</p>
`
      },
      {
        name: "\u{1F3A8} \u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A/\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E2A\u0E23\u0E23\u0E04\u0E4C",
        icon: "fluent:design-ideas-24-regular",
        content: `
<h2>\u2728 \u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E19\u0E35\u0E49\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E2D\u0E30\u0E44\u0E23?</h2>
<p>\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E17\u0E31\u0E01\u0E29\u0E30\u0E01\u0E32\u0E23\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E41\u0E25\u0E30\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E2A\u0E23\u0E23\u0E04\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E01\u0E49\u0E32\u0E27\u0E44\u0E1B\u0E2D\u0E35\u0E01\u0E02\u0E31\u0E49\u0E19!</p>

<h2>\u{1F3AF} \u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E08\u0E30\u0E44\u0E14\u0E49</h2>
<ul>
  <li>\u{1F31F} \u0E2B\u0E25\u0E31\u0E01\u0E01\u0E32\u0E23\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49\u0E44\u0E14\u0E49\u0E08\u0E23\u0E34\u0E07</li>
  <li>\u{1F31F} \u0E40\u0E17\u0E04\u0E19\u0E34\u0E04\u0E01\u0E32\u0E23\u0E43\u0E0A\u0E49\u0E2A\u0E35 \u0E23\u0E39\u0E1B\u0E17\u0E23\u0E07 \u0E41\u0E25\u0E30 Layout</li>
  <li>\u{1F31F} \u0E01\u0E32\u0E23\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1C\u0E25\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E42\u0E14\u0E14\u0E40\u0E14\u0E48\u0E19</li>
  <li>\u{1F31F} Portfolio \u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25\u0E07\u0E32\u0E19</li>
</ul>

<h2>\u{1F6E0}\uFE0F \u0E40\u0E04\u0E23\u0E37\u0E48\u0E2D\u0E07\u0E21\u0E37\u0E2D\u0E17\u0E35\u0E48\u0E08\u0E30\u0E43\u0E0A\u0E49</h2>
<p>Figma | Adobe XD | Photoshop | Illustrator (\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 1 \u0E42\u0E1B\u0E23\u0E41\u0E01\u0E23\u0E21)</p>

<h2>\u{1F468}\u200D\u{1F3A8} \u0E40\u0E2B\u0E21\u0E32\u0E30\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A</h2>
<ul>
  <li>\u{1F538} \u0E1C\u0E39\u0E49\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E19\u0E43\u0E08\u0E07\u0E32\u0E19\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A</li>
  <li>\u{1F538} \u0E19\u0E31\u0E01\u0E2D\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E1D\u0E35\u0E21\u0E37\u0E2D</li>
  <li>\u{1F538} \u0E1C\u0E39\u0E49\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E2A\u0E23\u0E49\u0E32\u0E07 Portfolio</li>
</ul>

<h2>\u{1F381} \u0E42\u0E1A\u0E19\u0E31\u0E2A\u0E1E\u0E34\u0E40\u0E28\u0E29</h2>
<p>\u{1F4E6} Template \u0E1F\u0E23\u0E35 | \u{1F3A8} Color Palette | \u{1F4D0} UI Kit | \u{1F4A1} \u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</p>
`
      },
      {
        name: "\u{1F4C8} \u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08/\u0E01\u0E32\u0E23\u0E15\u0E25\u0E32\u0E14",
        icon: "fluent:briefcase-24-regular",
        content: `
<h2>\u{1F4BC} \u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E19\u0E35\u0E49</h2>
<p>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E01\u0E25\u0E22\u0E38\u0E17\u0E18\u0E4C\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E15\u0E25\u0E32\u0E14\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E1C\u0E25\u0E08\u0E23\u0E34\u0E07 \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p>

<h2>\u{1F4CA} \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E04\u0E23\u0E2D\u0E1A\u0E04\u0E25\u0E38\u0E21</h2>
<ol>
  <li>\u{1F4CC} <strong>\u0E01\u0E32\u0E23\u0E27\u0E32\u0E07\u0E41\u0E1C\u0E19\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08</strong> - Business Model Canvas</li>
  <li>\u{1F4CC} <strong>\u0E01\u0E32\u0E23\u0E15\u0E25\u0E32\u0E14\u0E14\u0E34\u0E08\u0E34\u0E17\u0E31\u0E25</strong> - Social Media, SEO, SEM</li>
  <li>\u{1F4CC} <strong>\u0E01\u0E32\u0E23\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</strong> - Data-Driven Decision</li>
  <li>\u{1F4CC} <strong>\u0E01\u0E32\u0E23\u0E1A\u0E23\u0E34\u0E2B\u0E32\u0E23\u0E17\u0E35\u0E21</strong> - Leadership & Management</li>
</ol>

<h2>\u{1F3AF} \u0E40\u0E1B\u0E49\u0E32\u0E2B\u0E21\u0E32\u0E22\u0E2B\u0E25\u0E31\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A</h2>
<ul>
  <li>\u2705 \u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E27\u0E32\u0E07\u0E41\u0E1C\u0E19\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08\u0E44\u0E14\u0E49\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E23\u0E30\u0E1A\u0E1A</li>
  <li>\u2705 \u0E40\u0E02\u0E49\u0E32\u0E43\u0E08\u0E01\u0E25\u0E22\u0E38\u0E17\u0E18\u0E4C\u0E01\u0E32\u0E23\u0E15\u0E25\u0E32\u0E14\u0E22\u0E38\u0E04\u0E43\u0E2B\u0E21\u0E48</li>
  <li>\u2705 \u0E19\u0E33\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49\u0E44\u0E1B\u0E43\u0E0A\u0E49\u0E01\u0E31\u0E1A\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08\u0E08\u0E23\u0E34\u0E07\u0E44\u0E14\u0E49</li>
</ul>

<h2>\u{1F464} \u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19</h2>
<p>\u0E1C\u0E39\u0E49\u0E40\u0E0A\u0E35\u0E48\u0E22\u0E27\u0E0A\u0E32\u0E0D\u0E14\u0E49\u0E32\u0E19\u0E18\u0E38\u0E23\u0E01\u0E34\u0E08\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E15\u0E25\u0E32\u0E14\u0E17\u0E35\u0E48\u0E21\u0E35\u0E1B\u0E23\u0E30\u0E2A\u0E1A\u0E01\u0E32\u0E23\u0E13\u0E4C\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 10 \u0E1B\u0E35</p>

<h2>\u{1F4B0} \u0E01\u0E32\u0E23\u0E25\u0E07\u0E17\u0E38\u0E19\u0E17\u0E35\u0E48\u0E04\u0E38\u0E49\u0E21\u0E04\u0E48\u0E32</h2>
<p>\u{1F525} \u0E40\u0E23\u0E35\u0E22\u0E19\u0E04\u0E23\u0E31\u0E49\u0E07\u0E40\u0E14\u0E35\u0E22\u0E27 \u0E43\u0E0A\u0E49\u0E44\u0E14\u0E49\u0E15\u0E25\u0E2D\u0E14\u0E0A\u0E35\u0E27\u0E34\u0E15 | \u{1F4F1} \u0E40\u0E02\u0E49\u0E32\u0E16\u0E36\u0E07\u0E44\u0E14\u0E49\u0E17\u0E38\u0E01\u0E2D\u0E38\u0E1B\u0E01\u0E23\u0E13\u0E4C | \u{1F504} \u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E1F\u0E23\u0E35</p>
`
      },
      {
        name: "\u{1F4DD} \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E2A\u0E31\u0E49\u0E19",
        icon: "fluent:document-bullet-list-24-regular",
        content: `
<h2>\u{1F4CB} \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h2>
<p>\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E2A\u0E31\u0E49\u0E19\u0E46 \u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49...</p>

<h2>\u{1F3AF} \u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</h2>
<ul>
  <li>\u2713 \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 1</li>
  <li>\u2713 \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 2</li>
  <li>\u2713 \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 3</li>
</ul>

<h2>\u{1F465} \u0E40\u0E2B\u0E21\u0E32\u0E30\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A</h2>
<p>\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E1B\u0E49\u0E32\u0E2B\u0E21\u0E32\u0E22\u0E02\u0E2D\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49...</p>
`
      }
    ];
    const closeDropdowns = () => {
      showTextColorPicker.value = false;
      showHighlightPicker.value = false;
      showTableMenu.value = false;
      showTemplateMenu.value = false;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "rich-text-editor border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden" }, _attrs))}>`);
      if (__props.editable && unref(editor)) {
        _push(`<div class="flex flex-wrap items-center gap-1 p-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600"><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("bold") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E15\u0E31\u0E27\u0E2B\u0E19\u0E32">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-bold-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("italic") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E35\u0E22\u0E07">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-italic-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("underline") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E02\u0E35\u0E14\u0E40\u0E2A\u0E49\u0E19\u0E43\u0E15\u0E49">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-underline-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("strike") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E02\u0E35\u0E14\u0E06\u0E48\u0E32">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-strikethrough-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("subscript") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E15\u0E31\u0E27\u0E2B\u0E49\u0E2D\u0E22">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-subscript-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("superscript") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E15\u0E31\u0E27\u0E22\u0E01">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-superscript-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><div class="relative"><button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="\u0E2A\u0E35\u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-color-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button>`);
        if (unref(showTextColorPicker)) {
          _push(`<div class="absolute top-full left-0 mt-1 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 grid grid-cols-5 gap-1"><!--[-->`);
          ssrRenderList(textColors, (color) => {
            _push(`<button type="button" class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform" style="${ssrRenderStyle({ backgroundColor: color })}"></button>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="relative"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("highlight") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E44\u0E2E\u0E44\u0E25\u0E17\u0E4C">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:highlight-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button>`);
        if (unref(showHighlightPicker)) {
          _push(`<div class="absolute top-full left-0 mt-1 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 grid grid-cols-5 gap-1"><!--[-->`);
          ssrRenderList(highlightColors, (color) => {
            _push(`<button type="button" class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform" style="${ssrRenderStyle({ backgroundColor: color })}"></button>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("heading", { level: 1 }) }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D 1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-header-1-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("heading", { level: 2 }) }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D 2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-header-2-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("heading", { level: 3 }) }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D 3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-header-3-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("bulletList") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E41\u0E1A\u0E1A\u0E08\u0E38\u0E14">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-bullet-list-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("orderedList") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E41\u0E1A\u0E1A\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-number-list-ltr-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("taskList") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 Checklist">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:task-list-square-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive({ textAlign: "left" }) }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E0A\u0E34\u0E14\u0E0B\u0E49\u0E32\u0E22">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-align-left-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive({ textAlign: "center" }) }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E01\u0E36\u0E48\u0E07\u0E01\u0E25\u0E32\u0E07">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-align-center-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive({ textAlign: "right" }) }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E0A\u0E34\u0E14\u0E02\u0E27\u0E32">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-align-right-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><div class="relative"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("table") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E15\u0E32\u0E23\u0E32\u0E07">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:table-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button>`);
        if (unref(showTableMenu)) {
          _push(`<div class="absolute top-full left-0 mt-1 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 min-w-[160px]"><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:table-add-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E41\u0E17\u0E23\u0E01\u0E15\u0E32\u0E23\u0E32\u0E07 </button>`);
          if (unref(editor).isActive("table")) {
            _push(`<!--[--><hr class="my-1 border-gray-200 dark:border-gray-600"><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E2D\u0E25\u0E31\u0E21\u0E19\u0E4C\u0E0B\u0E49\u0E32\u0E22</button><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E2D\u0E25\u0E31\u0E21\u0E19\u0E4C\u0E02\u0E27\u0E32</button><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E16\u0E27\u0E1A\u0E19</button><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E16\u0E27\u0E25\u0E48\u0E32\u0E07</button><hr class="my-1 border-gray-200 dark:border-gray-600"><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">\u0E25\u0E1A\u0E04\u0E2D\u0E25\u0E31\u0E21\u0E19\u0E4C</button><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">\u0E25\u0E1A\u0E41\u0E16\u0E27</button><button class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">\u0E25\u0E1A\u0E15\u0E32\u0E23\u0E32\u0E07</button><!--]-->`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("link") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E25\u0E34\u0E07\u0E01\u0E4C">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:link-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button>`);
        if (unref(editor).isActive("link")) {
          _push(`<button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="\u0E25\u0E1A\u0E25\u0E34\u0E07\u0E01\u0E4C">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:link-dismiss-24-regular",
            class: "w-4 h-4 text-red-500"
          }, null, _parent));
          _push(`</button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:image-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="\u0E40\u0E1E\u0E34\u0E48\u0E21 YouTube">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "logos:youtube-icon",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div><div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1"><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("blockquote") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-quote-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("code") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E42\u0E04\u0E49\u0E14 inline">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:code-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass([{ "bg-gray-200 dark:bg-gray-600": unref(editor).isActive("codeBlock") }, "p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"])}" title="\u0E42\u0E04\u0E49\u0E14 block">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:code-block-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="\u0E40\u0E2A\u0E49\u0E19\u0E41\u0E1A\u0E48\u0E07">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:line-horizontal-1-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button></div><div class="relative"><button type="button" class="flex items-center gap-1 px-2 py-1.5 rounded bg-gradient-to-r from-purple-500 to-pink-500 text-white hover:from-purple-600 hover:to-pink-600 transition-all text-xs font-medium shadow-sm" title="\u0E40\u0E25\u0E37\u0E2D\u0E01 Template">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:document-bullet-list-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span class="hidden sm:inline">Template</span></button>`);
        if (unref(showTemplateMenu)) {
          _push(`<div class="absolute top-full right-0 mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-xl z-50 overflow-hidden"><div class="p-2 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700"><p class="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:sparkle-24-filled",
            class: "w-4 h-4 text-yellow-500"
          }, null, _parent));
          _push(` \u0E40\u0E25\u0E37\u0E2D\u0E01 Template \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08\u0E23\u0E39\u0E1B </p></div><div class="max-h-64 overflow-y-auto"><!--[-->`);
          ssrRenderList(templates, (template) => {
            _push(`<button type="button" class="w-full px-3 py-2.5 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: template.icon,
              class: "w-5 h-5 text-blue-500 flex-shrink-0"
            }, null, _parent));
            _push(`<span class="text-sm text-gray-700 dark:text-gray-300">${ssrInterpolate(template.name)}</span></button>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex items-center gap-0.5 ml-auto"><button type="button"${ssrIncludeBooleanAttr(!unref(editor).can().undo()) ? " disabled" : ""} class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-30" title="\u0E22\u0E49\u0E2D\u0E19\u0E01\u0E25\u0E31\u0E1A">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-undo-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button><button type="button"${ssrIncludeBooleanAttr(!unref(editor).can().redo()) ? " disabled" : ""} class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-30" title="\u0E17\u0E33\u0E0B\u0E49\u0E33">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-redo-24-regular",
          class: "w-4 h-4 text-gray-700 dark:text-gray-300"
        }, null, _parent));
        _push(`</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(unref(EditorContent), {
        editor: unref(editor),
        class: "prose dark:prose-invert max-w-none p-4",
        style: { minHeight: __props.minHeight },
        onClick: closeDropdowns
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Common/RichTextEditor.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=RichTextEditor-J-3c3zks.mjs.map

<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import Placeholder from '@tiptap/extension-placeholder'
import Image from '@tiptap/extension-image'
import Youtube from '@tiptap/extension-youtube'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { Highlight } from '@tiptap/extension-highlight'
import { Subscript } from '@tiptap/extension-subscript'
import { Superscript } from '@tiptap/extension-superscript'
import { TaskList } from '@tiptap/extension-task-list'
import { TaskItem } from '@tiptap/extension-task-item'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { CodeBlockLowlight } from '@tiptap/extension-code-block-lowlight'
import { common, createLowlight } from 'lowlight'
import { Icon } from '@iconify/vue'

const lowlight = createLowlight(common)

interface Props {
  modelValue: string
  placeholder?: string
  editable?: boolean
  minHeight?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'เขียนเนื้อหาที่นี่...',
  editable: true,
  minHeight: '200px'
})

const emit = defineEmits(['update:modelValue'])

// Color options
const textColors = [
  '#000000', '#374151', '#DC2626', '#EA580C', '#D97706', 
  '#16A34A', '#0891B2', '#2563EB', '#7C3AED', '#DB2777'
]
const highlightColors = [
  '#FEF08A', '#BBF7D0', '#A5F3FC', '#C7D2FE', '#FBCFE8',
  '#FED7AA', '#E5E7EB', '#FCA5A5', '#86EFAC', '#67E8F9'
]

// Dropdown states
const showTextColorPicker = ref(false)
const showHighlightPicker = ref(false)
const showTableMenu = ref(false)

const editor = useEditor({
  content: props.modelValue,
  editable: props.editable,
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3] },
      codeBlock: false, // Use CodeBlockLowlight instead
      // Disable built-in extensions that we're adding separately
    }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: { class: 'text-blue-500 underline hover:text-blue-700' }
    }),
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Underline,
    Placeholder.configure({ placeholder: props.placeholder }),
    Image.configure({ HTMLAttributes: { class: 'max-w-full rounded-lg' } }),
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
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (editor.value && newValue !== editor.value.getHTML()) {
    editor.value.commands.setContent(newValue, false)
  }
})

watch(() => props.editable, (newValue) => {
  if (editor.value) editor.value.setEditable(newValue)
})

// Actions
const addLink = () => {
  const url = window.prompt('ใส่ URL:')
  if (url && editor.value) {
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }
}

const removeLink = () => {
  if (editor.value) editor.value.chain().focus().unsetLink().run()
}

const addImage = () => {
  const url = window.prompt('ใส่ URL รูปภาพ:')
  if (url && editor.value) {
    editor.value.chain().focus().setImage({ src: url }).run()
  }
}

const addYoutube = () => {
  const url = window.prompt('ใส่ URL YouTube:')
  if (url && editor.value) {
    editor.value.chain().focus().setYoutubeVideo({ src: url }).run()
  }
}

const setTextColor = (color: string) => {
  if (editor.value) {
    editor.value.chain().focus().setColor(color).run()
  }
  showTextColorPicker.value = false
}

const setHighlight = (color: string) => {
  if (editor.value) {
    editor.value.chain().focus().toggleHighlight({ color }).run()
  }
  showHighlightPicker.value = false
}

const insertTable = () => {
  if (editor.value) {
    editor.value.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
  }
  showTableMenu.value = false
}

const addColumnBefore = () => {
  if (editor.value) editor.value.chain().focus().addColumnBefore().run()
  showTableMenu.value = false
}

const addColumnAfter = () => {
  if (editor.value) editor.value.chain().focus().addColumnAfter().run()
  showTableMenu.value = false
}

const addRowBefore = () => {
  if (editor.value) editor.value.chain().focus().addRowBefore().run()
  showTableMenu.value = false
}

const addRowAfter = () => {
  if (editor.value) editor.value.chain().focus().addRowAfter().run()
  showTableMenu.value = false
}

const deleteColumn = () => {
  if (editor.value) editor.value.chain().focus().deleteColumn().run()
  showTableMenu.value = false
}

const deleteRow = () => {
  if (editor.value) editor.value.chain().focus().deleteRow().run()
  showTableMenu.value = false
}

const deleteTable = () => {
  if (editor.value) editor.value.chain().focus().deleteTable().run()
  showTableMenu.value = false
}

// Template Presets
const showTemplateMenu = ref(false)

const templates = [
  {
    name: '📚 รายวิชาทั่วไป',
    icon: 'fluent:book-24-regular',
    content: `
<h2>📋 ภาพรวมรายวิชา</h2>
<p>รายวิชานี้ออกแบบมาเพื่อให้ผู้เรียนได้เรียนรู้และพัฒนาทักษะที่จำเป็น...</p>

<h2>🎯 วัตถุประสงค์การเรียนรู้</h2>
<ul>
  <li>✅ เข้าใจหลักการและแนวคิดพื้นฐาน</li>
  <li>✅ สามารถนำความรู้ไปประยุกต์ใช้ได้จริง</li>
  <li>✅ พัฒนาทักษะการคิดวิเคราะห์</li>
  <li>✅ สามารถทำงานร่วมกับผู้อื่นได้</li>
</ul>

<h2>📖 เนื้อหาที่จะได้เรียน</h2>
<ol>
  <li><strong>บทที่ 1:</strong> บทนำและพื้นฐาน</li>
  <li><strong>บทที่ 2:</strong> หลักการสำคัญ</li>
  <li><strong>บทที่ 3:</strong> การประยุกต์ใช้งาน</li>
  <li><strong>บทที่ 4:</strong> กรณีศึกษา</li>
  <li><strong>บทที่ 5:</strong> โครงงานและสรุป</li>
</ol>

<h2>👥 เหมาะสำหรับ</h2>
<p>ผู้เรียนที่สนใจพัฒนาความรู้และทักษะในด้านนี้ ไม่จำเป็นต้องมีพื้นฐานมาก่อน</p>

<h2>⏰ ระยะเวลาเรียน</h2>
<p>ประมาณ <strong>10-15 ชั่วโมง</strong> สามารถเรียนได้ตามความสะดวก</p>
`
  },
  {
    name: '💻 คอร์สเขียนโปรแกรม',
    icon: 'fluent:code-24-regular',
    content: `
<h2>🚀 เกี่ยวกับคอร์สนี้</h2>
<p>เรียนรู้การเขียนโปรแกรมตั้งแต่พื้นฐานจนถึงขั้นสูง พร้อมโปรเจกต์จริง!</p>

<h2>💡 สิ่งที่คุณจะได้เรียนรู้</h2>
<ul>
  <li>🔹 พื้นฐานการเขียนโปรแกรม</li>
  <li>🔹 โครงสร้างข้อมูลและอัลกอริทึม</li>
  <li>🔹 การพัฒนาโปรเจกต์จริง</li>
  <li>🔹 Best Practices และ Clean Code</li>
  <li>🔹 การทำงานร่วมกับทีม (Git/GitHub)</li>
</ul>

<h2>🛠️ เครื่องมือที่ใช้</h2>
<ul>
  <li>📌 VS Code / IDE</li>
  <li>📌 Git & GitHub</li>
  <li>📌 Terminal / Command Line</li>
</ul>

<h2>📋 ข้อกำหนดเบื้องต้น</h2>
<ul>
  <li>✔️ คอมพิวเตอร์ที่ใช้งานได้</li>
  <li>✔️ ความกระตือรือร้นในการเรียนรู้</li>
  <li>✔️ ไม่จำเป็นต้องมีพื้นฐานมาก่อน</li>
</ul>

<h2>🏆 สิ่งที่จะได้รับ</h2>
<p>✨ ใบรับรองเมื่อเรียนจบ | 📁 Source Code ทั้งหมด | 💬 การสนับสนุนตลอดคอร์ส</p>
`
  },
  {
    name: '🎨 คอร์สออกแบบ/สร้างสรรค์',
    icon: 'fluent:design-ideas-24-regular',
    content: `
<h2>✨ คอร์สนี้เกี่ยวกับอะไร?</h2>
<p>พัฒนาทักษะการออกแบบและความคิดสร้างสรรค์ของคุณให้ก้าวไปอีกขั้น!</p>

<h2>🎯 สิ่งที่คุณจะได้</h2>
<ul>
  <li>🌟 หลักการออกแบบที่ใช้ได้จริง</li>
  <li>🌟 เทคนิคการใช้สี รูปทรง และ Layout</li>
  <li>🌟 การสร้างผลงานที่โดดเด่น</li>
  <li>🌟 Portfolio สำหรับแสดงผลงาน</li>
</ul>

<h2>🛠️ เครื่องมือที่จะใช้</h2>
<p>Figma | Adobe XD | Photoshop | Illustrator (เลือกอย่างน้อย 1 โปรแกรม)</p>

<h2>👨‍🎨 เหมาะสำหรับ</h2>
<ul>
  <li>🔸 ผู้เริ่มต้นที่สนใจงานออกแบบ</li>
  <li>🔸 นักออกแบบที่ต้องการพัฒนาฝีมือ</li>
  <li>🔸 ผู้ที่ต้องการสร้าง Portfolio</li>
</ul>

<h2>🎁 โบนัสพิเศษ</h2>
<p>📦 Template ฟรี | 🎨 Color Palette | 📐 UI Kit | 💡 แหล่งเรียนรู้เพิ่มเติม</p>
`
  },
  {
    name: '📈 คอร์สธุรกิจ/การตลาด',
    icon: 'fluent:briefcase-24-regular',
    content: `
<h2>💼 เกี่ยวกับคอร์สนี้</h2>
<p>เรียนรู้กลยุทธ์ธุรกิจและการตลาดที่ได้ผลจริง เพื่อพัฒนาธุรกิจของคุณ</p>

<h2>📊 หัวข้อที่ครอบคลุม</h2>
<ol>
  <li>📌 <strong>การวางแผนธุรกิจ</strong> - Business Model Canvas</li>
  <li>📌 <strong>การตลาดดิจิทัล</strong> - Social Media, SEO, SEM</li>
  <li>📌 <strong>การวิเคราะห์ข้อมูล</strong> - Data-Driven Decision</li>
  <li>📌 <strong>การบริหารทีม</strong> - Leadership & Management</li>
</ol>

<h2>🎯 เป้าหมายหลังเรียนจบ</h2>
<ul>
  <li>✅ สามารถวางแผนธุรกิจได้อย่างเป็นระบบ</li>
  <li>✅ เข้าใจกลยุทธ์การตลาดยุคใหม่</li>
  <li>✅ นำความรู้ไปใช้กับธุรกิจจริงได้</li>
</ul>

<h2>👤 ผู้สอน</h2>
<p>ผู้เชี่ยวชาญด้านธุรกิจและการตลาดที่มีประสบการณ์มากกว่า 10 ปี</p>

<h2>💰 การลงทุนที่คุ้มค่า</h2>
<p>🔥 เรียนครั้งเดียว ใช้ได้ตลอดชีวิต | 📱 เข้าถึงได้ทุกอุปกรณ์ | 🔄 อัพเดทเนื้อหาฟรี</p>
`
  },
  {
    name: '📝 คำอธิบายสั้น',
    icon: 'fluent:document-bullet-list-24-regular',
    content: `
<h2>📋 รายละเอียดรายวิชา</h2>
<p>คำอธิบายสั้นๆ เกี่ยวกับรายวิชานี้...</p>

<h2>🎯 สิ่งที่จะได้เรียนรู้</h2>
<ul>
  <li>✓ หัวข้อที่ 1</li>
  <li>✓ หัวข้อที่ 2</li>
  <li>✓ หัวข้อที่ 3</li>
</ul>

<h2>👥 เหมาะสำหรับ</h2>
<p>กลุ่มเป้าหมายของรายวิชานี้...</p>
`
  }
]

const insertTemplate = (template: typeof templates[0]) => {
  if (editor.value) {
    editor.value.chain().focus().setContent(template.content).run()
    emit('update:modelValue', template.content)
  }
  showTemplateMenu.value = false
}

// Close dropdowns when clicking outside
const closeDropdowns = () => {
  showTextColorPicker.value = false
  showHighlightPicker.value = false
  showTableMenu.value = false
  showTemplateMenu.value = false
}

onUnmounted(() => {
  editor.value?.destroy()
})
</script>

<template>
  <div class="rich-text-editor border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden" @click.stop>
    <!-- Toolbar -->
    <div v-if="editable && editor" class="flex flex-wrap items-center gap-1 p-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600">
      <!-- Text Style -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <button type="button" @click="editor.chain().focus().toggleBold().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('bold') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ตัวหนา">
          <Icon icon="fluent:text-bold-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleItalic().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('italic') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ตัวเอียง">
          <Icon icon="fluent:text-italic-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('underline') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ขีดเส้นใต้">
          <Icon icon="fluent:text-underline-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleStrike().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('strike') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ขีดฆ่า">
          <Icon icon="fluent:text-strikethrough-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleSubscript().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('subscript') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ตัวห้อย">
          <Icon icon="fluent:text-subscript-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleSuperscript().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('superscript') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ตัวยก">
          <Icon icon="fluent:text-superscript-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
      </div>

      <!-- Colors -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <!-- Text Color -->
        <div class="relative">
          <button type="button" @click.stop="showTextColorPicker = !showTextColorPicker; showHighlightPicker = false; showTableMenu = false"
            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="สีตัวอักษร">
            <Icon icon="fluent:text-color-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
          </button>
          <div v-if="showTextColorPicker" class="absolute top-full left-0 mt-1 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 grid grid-cols-5 gap-1">
            <button v-for="color in textColors" :key="color" type="button" @click="setTextColor(color)"
              class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
              :style="{ backgroundColor: color }"></button>
          </div>
        </div>
        <!-- Highlight -->
        <div class="relative">
          <button type="button" @click.stop="showHighlightPicker = !showHighlightPicker; showTextColorPicker = false; showTableMenu = false"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('highlight') }"
            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ไฮไลท์">
            <Icon icon="fluent:highlight-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
          </button>
          <div v-if="showHighlightPicker" class="absolute top-full left-0 mt-1 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 grid grid-cols-5 gap-1">
            <button v-for="color in highlightColors" :key="color" type="button" @click="setHighlight(color)"
              class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
              :style="{ backgroundColor: color }"></button>
          </div>
        </div>
      </div>

      <!-- Headings -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('heading', { level: 1 }) }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="หัวข้อ 1">
          <Icon icon="fluent:text-header-1-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('heading', { level: 2 }) }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="หัวข้อ 2">
          <Icon icon="fluent:text-header-2-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('heading', { level: 3 }) }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="หัวข้อ 3">
          <Icon icon="fluent:text-header-3-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
      </div>

      <!-- Lists -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('bulletList') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="รายการแบบจุด">
          <Icon icon="fluent:text-bullet-list-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('orderedList') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="รายการแบบตัวเลข">
          <Icon icon="fluent:text-number-list-ltr-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleTaskList().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('taskList') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="รายการ Checklist">
          <Icon icon="fluent:task-list-square-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
      </div>

      <!-- Alignment -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive({ textAlign: 'left' }) }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ชิดซ้าย">
          <Icon icon="fluent:text-align-left-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive({ textAlign: 'center' }) }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="กึ่งกลาง">
          <Icon icon="fluent:text-align-center-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive({ textAlign: 'right' }) }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ชิดขวา">
          <Icon icon="fluent:text-align-right-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
      </div>

      <!-- Table -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <div class="relative">
          <button type="button" @click.stop="showTableMenu = !showTableMenu; showTextColorPicker = false; showHighlightPicker = false"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('table') }"
            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ตาราง">
            <Icon icon="fluent:table-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
          </button>
          <div v-if="showTableMenu" class="absolute top-full left-0 mt-1 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 min-w-[160px]">
            <button @click="insertTable" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
              <Icon icon="fluent:table-add-24-regular" class="w-4 h-4" /> แทรกตาราง
            </button>
            <template v-if="editor.isActive('table')">
              <hr class="my-1 border-gray-200 dark:border-gray-600">
              <button @click="addColumnBefore" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">เพิ่มคอลัมน์ซ้าย</button>
              <button @click="addColumnAfter" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">เพิ่มคอลัมน์ขวา</button>
              <button @click="addRowBefore" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">เพิ่มแถวบน</button>
              <button @click="addRowAfter" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">เพิ่มแถวล่าง</button>
              <hr class="my-1 border-gray-200 dark:border-gray-600">
              <button @click="deleteColumn" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">ลบคอลัมน์</button>
              <button @click="deleteRow" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">ลบแถว</button>
              <button @click="deleteTable" class="w-full px-3 py-1.5 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">ลบตาราง</button>
            </template>
          </div>
        </div>
      </div>

      <!-- Media & Links -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <button type="button" @click="addLink"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('link') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="เพิ่มลิงก์">
          <Icon icon="fluent:link-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button v-if="editor.isActive('link')" type="button" @click="removeLink"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="ลบลิงก์">
          <Icon icon="fluent:link-dismiss-24-regular" class="w-4 h-4 text-red-500" />
        </button>
        <button type="button" @click="addImage"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="เพิ่มรูปภาพ">
          <Icon icon="fluent:image-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="addYoutube"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="เพิ่ม YouTube">
          <Icon icon="logos:youtube-icon" class="w-4 h-4" />
        </button>
      </div>

      <!-- Code & Quote -->
      <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
        <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('blockquote') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="อ้างอิง">
          <Icon icon="fluent:text-quote-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleCode().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('code') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="โค้ด inline">
          <Icon icon="fluent:code-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()"
          :class="{ 'bg-gray-200 dark:bg-gray-600': editor.isActive('codeBlock') }"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="โค้ด block">
          <Icon icon="fluent:code-block-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().setHorizontalRule().run()"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="เส้นแบ่ง">
          <Icon icon="fluent:line-horizontal-1-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
      </div>

      <!-- Templates -->
      <div class="relative">
        <button type="button" 
          @click.stop="showTemplateMenu = !showTemplateMenu; showTextColorPicker = false; showHighlightPicker = false; showTableMenu = false"
          class="flex items-center gap-1 px-2 py-1.5 rounded bg-gradient-to-r from-purple-500 to-pink-500 text-white hover:from-purple-600 hover:to-pink-600 transition-all text-xs font-medium shadow-sm" 
          title="เลือก Template">
          <Icon icon="fluent:document-bullet-list-24-regular" class="w-4 h-4" />
          <span class="hidden sm:inline">Template</span>
        </button>
        <div v-if="showTemplateMenu" class="absolute top-full right-0 mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-xl z-50 overflow-hidden">
          <div class="p-2 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1">
              <Icon icon="fluent:sparkle-24-filled" class="w-4 h-4 text-yellow-500" />
              เลือก Template สำเร็จรูป
            </p>
          </div>
          <div class="max-h-64 overflow-y-auto">
            <button 
              v-for="template in templates" 
              :key="template.name"
              type="button"
              @click="insertTemplate(template)"
              class="w-full px-3 py-2.5 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
            >
              <Icon :icon="template.icon" class="w-5 h-5 text-blue-500 flex-shrink-0" />
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ template.name }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Undo/Redo -->
      <div class="flex items-center gap-0.5 ml-auto">
        <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-30" title="ย้อนกลับ">
          <Icon icon="fluent:arrow-undo-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
        <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()"
          class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-30" title="ทำซ้ำ">
          <Icon icon="fluent:arrow-redo-24-regular" class="w-4 h-4 text-gray-700 dark:text-gray-300" />
        </button>
      </div>
    </div>

    <!-- Editor Content -->
    <EditorContent 
      :editor="editor" 
      class="prose dark:prose-invert max-w-none p-4"
      :style="{ minHeight: minHeight }"
      @click="closeDropdowns"
    />
  </div>
</template>

<style>
/* TipTap Editor Styles */
.ProseMirror {
  outline: none;
  min-height: inherit;
}

.ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  pointer-events: none;
  height: 0;
}

.ProseMirror h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.ProseMirror h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.ProseMirror h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.ProseMirror p {
  margin-bottom: 0.75rem;
}

.ProseMirror ul {
  list-style-type: disc;
  list-style-position: inside;
  margin-bottom: 0.75rem;
}

.ProseMirror ol {
  list-style-type: decimal;
  list-style-position: inside;
  margin-bottom: 0.75rem;
}

.ProseMirror blockquote {
  border-left-width: 4px;
  border-color: #d1d5db;
  padding-left: 1rem;
  font-style: italic;
  margin: 1rem 0;
}

.dark .ProseMirror blockquote {
  border-color: #4b5563;
}

.ProseMirror code {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-family: monospace;
}

.dark .ProseMirror code {
  background-color: #374151;
}

.ProseMirror pre {
  background-color: #1f2937;
  color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1rem 0;
  font-family: monospace;
}

.ProseMirror pre code {
  background: none;
  padding: 0;
  font-size: 0.875rem;
  color: inherit;
}

.ProseMirror hr {
  border-color: #d1d5db;
  margin: 1rem 0;
}

.dark .ProseMirror hr {
  border-color: #4b5563;
}

.ProseMirror a {
  color: #3b82f6;
  text-decoration: underline;
}

.ProseMirror a:hover {
  color: #1d4ed8;
}

.ProseMirror img {
  max-width: 100%;
  border-radius: 0.5rem;
  margin: 1rem 0;
}

.ProseMirror iframe {
  max-width: 100%;
  border-radius: 0.5rem;
  margin: 1rem 0;
}

/* Task List */
.ProseMirror ul[data-type="taskList"] {
  list-style: none;
  padding: 0;
}

.ProseMirror ul[data-type="taskList"] li {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.ProseMirror ul[data-type="taskList"] li > label {
  flex-shrink: 0;
  margin-top: 0.25rem;
}

.ProseMirror ul[data-type="taskList"] li > label input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  accent-color: #3b82f6;
  cursor: pointer;
}

.ProseMirror ul[data-type="taskList"] li > div {
  flex: 1;
}

/* Table Styles */
.ProseMirror table {
  border-collapse: collapse;
  width: 100%;
  margin: 1rem 0;
  overflow: hidden;
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
}

.dark .ProseMirror table {
  border-color: #4b5563;
}

.ProseMirror th,
.ProseMirror td {
  border: 1px solid #d1d5db;
  padding: 0.5rem 0.75rem;
  text-align: left;
  min-width: 100px;
  vertical-align: top;
}

.dark .ProseMirror th,
.dark .ProseMirror td {
  border-color: #4b5563;
}

.ProseMirror th {
  background-color: #f3f4f6;
  font-weight: 600;
}

.dark .ProseMirror th {
  background-color: #374151;
}

.ProseMirror .selectedCell {
  background-color: #dbeafe;
}

.dark .ProseMirror .selectedCell {
  background-color: #1e3a5f;
}

/* Code Block with Syntax Highlighting */
.ProseMirror pre .hljs-comment,
.ProseMirror pre .hljs-quote {
  color: #6b7280;
}

.ProseMirror pre .hljs-variable,
.ProseMirror pre .hljs-template-variable,
.ProseMirror pre .hljs-attr,
.ProseMirror pre .hljs-tag,
.ProseMirror pre .hljs-name,
.ProseMirror pre .hljs-selector-id,
.ProseMirror pre .hljs-selector-class,
.ProseMirror pre .hljs-regexp,
.ProseMirror pre .hljs-deletion {
  color: #f87171;
}

.ProseMirror pre .hljs-number,
.ProseMirror pre .hljs-built_in,
.ProseMirror pre .hljs-literal,
.ProseMirror pre .hljs-type,
.ProseMirror pre .hljs-params,
.ProseMirror pre .hljs-meta,
.ProseMirror pre .hljs-link {
  color: #fbbf24;
}

.ProseMirror pre .hljs-attribute {
  color: #facc15;
}

.ProseMirror pre .hljs-string,
.ProseMirror pre .hljs-symbol,
.ProseMirror pre .hljs-bullet,
.ProseMirror pre .hljs-addition {
  color: #4ade80;
}

.ProseMirror pre .hljs-title,
.ProseMirror pre .hljs-section {
  color: #60a5fa;
}

.ProseMirror pre .hljs-keyword,
.ProseMirror pre .hljs-selector-tag {
  color: #c084fc;
}
</style>

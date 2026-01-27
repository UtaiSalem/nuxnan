# Date Utilities - คู่มือการใช้งาน

## ปัญหาที่แก้ไข

ปัญหาหลักคือการแสดงวันที่จากฐานข้อมูลที่คลาดเคลื่อน 1 วัน เกิดจาก:

-   การใช้ `new Date()` กับ string ในรูปแบบ `YYYY-MM-DD` จะถูกตีความเป็น UTC timezone
-   เมื่อแปลงกลับในเขตเวลาไทย (UTC+7) จะเกิดการเลื่อนวันที่

## โครงสร้างไฟล์

```
resources/js/
├── utils/
│   └── dateUtils.js          # ฟังก์ชันพื้นฐานจัดการวันที่
├── composables/
│   └── useDate.js             # Vue 3 Composables
└── docs/
    └── DATE_UTILITIES.md      # คู่มือนี้
```

## วิธีใช้งาน

### 1. ใช้งานผ่าน Utility Functions (แนะนำสำหรับ Non-Vue files)

```javascript
import {
    formatDateForInput,
    formatDateThai,
    calculateAge,
    formatDate,
} from "@/utils/dateUtils";

// ตัวอย่างการใช้งาน
const birthDate = "2010-01-08";

// แปลงสำหรับ input[type="date"]
const inputValue = formatDateForInput(birthDate); // "2010-01-08"

// แปลงเป็นภาษาไทย
const thaiDate = formatDateThai(birthDate); // "8 มกราคม 2553"

// คำนวณอายุ
const age = calculateAge(birthDate); // 15 (ปี 2025)

// แปลงตามรูปแบบที่ต้องการ
const shortDate = formatDate(birthDate, "short"); // "8/1/2553"
const fullDate = formatDate(birthDate, "full"); // "วันศุกร์ที่ 8 มกราคม พ.ศ. 2553"
```

### 2. ใช้งานใน Vue 3 Components (แนะนำ)

#### 2.1 ใช้ `useDate` Composable

```vue
<script setup>
import { useDate } from "@/composables/useDate";

// สร้าง date object
const studentBirthDate = useDate("2010-01-08");

// ใช้งาน computed properties
console.log(studentBirthDate.dateForInput.value); // "2010-01-08"
console.log(studentBirthDate.dateThai.value); // "8 มกราคม 2553"
console.log(studentBirthDate.age.value); // 15

// Methods
studentBirthDate.setDate("2012-05-20");
studentBirthDate.setToday();
studentBirthDate.clear();
</script>

<template>
    <div>
        <p>วันเกิด: {{ studentBirthDate.dateThai }}</p>
        <p>อายุ: {{ studentBirthDate.age }} ปี</p>
        <input type="date" v-model="studentBirthDate.dateForInput" />
    </div>
</template>
```

#### 2.2 ใช้ `useBirthDate` Composable (สำหรับวันเกิดโดยเฉพาะ)

```vue
<script setup>
import { useBirthDate } from "@/composables/useDate";

const birthDate = useBirthDate("2010-01-08");

// Properties พิเศษสำหรับวันเกิด
console.log(birthDate.ageInYears.value); // 15
console.log(birthDate.isMinor.value); // true
console.log(birthDate.isTeen.value); // true
console.log(birthDate.nextBirthday.value); // { date: Date, daysUntil: 50, text: "อีก 50 วัน" }
console.log(birthDate.isBirthdayToday.value); // false
</script>

<template>
    <div>
        <p>วันเกิด: {{ birthDate.dateThai }}</p>
        <p>อายุ: {{ birthDate.detailedAge.text }}</p>
        <p v-if="birthDate.isBirthdayToday">🎉 วันเกิดวันนี้!</p>
        <p v-else>{{ birthDate.nextBirthday.text }} จะถึงวันเกิด</p>
    </div>
</template>
```

#### 2.3 ใช้ `useDateFormatter` (สำหรับฟังก์ชันเดี่ยวๆ)

```vue
<script setup>
import { useDateFormatter } from "@/composables/useDate";

const formatter = useDateFormatter();

const birthDate = "2010-01-08";
const thaiDate = formatter.toThai(birthDate);
const inputDate = formatter.toInput(birthDate);
</script>
```

## API Reference

### Utility Functions

#### `formatDateForInput(dateString)`

แปลงวันที่สำหรับ `input[type="date"]`

**Parameters:**

-   `dateString` (string): วันที่ในรูปแบบ ISO หรือ YYYY-MM-DD

**Returns:** (string) วันที่ในรูปแบบ YYYY-MM-DD

**Example:**

```javascript
formatDateForInput("2010-01-08T00:00:00.000Z"); // "2010-01-08"
formatDateForInput("2010-01-08"); // "2010-01-08"
```

---

#### `formatDateThai(dateString, options)`

แปลงวันที่เป็นรูปแบบไทย

**Parameters:**

-   `dateString` (string): วันที่
-   `options` (object): ตัวเลือก
    -   `format` (string): 'short' | 'full' | default
    -   `shortMonth` (boolean): ใช้ชื่อเดือนแบบย่อ
    -   `defaultText` (string): ข้อความเมื่อไม่มีวันที่

**Returns:** (string) วันที่ภาษาไทย

**Examples:**

```javascript
formatDateThai("2010-01-08"); // "8 มกราคม 2553"
formatDateThai("2010-01-08", { format: "short" }); // "8/1/2553"
formatDateThai("2010-01-08", { format: "full" }); // "วันศุกร์ที่ 8 มกราคม พ.ศ. 2553"
formatDateThai("2010-01-08", { shortMonth: true }); // "8 ม.ค. 2553"
```

---

#### `calculateAge(dateString)`

คำนวณอายุจากวันเกิด

**Parameters:**

-   `dateString` (string): วันเกิด

**Returns:** (number | string) อายุเป็นปี หรือ '-'

**Example:**

```javascript
calculateAge("2010-01-08"); // 15 (ในปี 2025)
```

---

#### `calculateDetailedAge(dateString)`

คำนวณอายุแบบละเอียด

**Parameters:**

-   `dateString` (string): วันเกิด

**Returns:** (object) `{ years, months, days, text }`

**Example:**

```javascript
calculateDetailedAge("2010-01-08");
// { years: 15, months: 10, days: 12, text: "15 ปี 10 เดือน 12 วัน" }
```

---

#### `formatTimeAgo(dateString)`

แสดงระยะเวลาที่ผ่านไป

**Parameters:**

-   `dateString` (string): วันที่

**Returns:** (string) เช่น "5 นาทีที่แล้ว", "2 วันที่แล้ว"

**Example:**

```javascript
formatTimeAgo("2025-11-20T10:30:00"); // "5 นาทีที่แล้ว"
```

---

#### `isValidDate(dateString)`

ตรวจสอบความถูกต้องของวันที่

**Parameters:**

-   `dateString` (string): วันที่

**Returns:** (boolean)

**Example:**

```javascript
isValidDate("2010-01-08"); // true
isValidDate("2010-02-30"); // false (ไม่มี 30 กุมภาพันธ์)
isValidDate("invalid"); // false
```

---

#### `getCurrentDate()`

ดึงวันที่ปัจจุบัน

**Returns:** (string) YYYY-MM-DD

**Example:**

```javascript
getCurrentDate(); // "2025-11-20"
```

---

#### `getCurrentDateThai()`

ดึงวันที่ปัจจุบันในรูปแบบไทย

**Returns:** (string)

**Example:**

```javascript
getCurrentDateThai(); // "20 พฤศจิกายน 2568"
```

## การใช้งานในโปรเจคจริง

### ตัวอย่าง: StudentsCard.vue

```vue
<script setup>
import { reactive, computed } from "vue";
import {
    formatDateForInput,
    formatDateThai,
    calculateAge,
} from "@/utils/dateUtils";

const form = reactive({
    date_of_birth: formatDateForInput(props.student?.date_of_birth) || "",
});

// แสดงวันเกิดภาษาไทย
const birthDateThai = computed(() => formatDateThai(form.date_of_birth));

// คำนวณอายุ
const age = computed(() => calculateAge(form.date_of_birth));
</script>

<template>
    <input type="date" v-model="form.date_of_birth" />
    <div>
        <span>{{ birthDateThai }}</span>
        <span>อายุ: {{ age }} ปี</span>
    </div>
</template>
```

### ตัวอย่าง: Dashboard.vue

```vue
<script setup>
import { useDateFormatter } from "@/composables/useDate";

const formatter = useDateFormatter();

const activities = computed(() => {
    return props.allVisits.map((visit) => ({
        ...visit,
        timeAgo: formatter.toTimeAgo(visit.visit_date),
        dateThai: formatter.toThai(visit.visit_date),
    }));
});
</script>

<template>
    <div v-for="activity in activities" :key="activity.id">
        <p>{{ activity.timeAgo }}</p>
        <small>{{ activity.dateThai }}</small>
    </div>
</template>
```

## Best Practices

### 1. ใช้ Composables ใน Vue Components

```vue
<script setup>
import { useBirthDate } from "@/composables/useDate";

// ดีกว่า
const birthDate = useBirthDate(props.student.date_of_birth);

// แทนที่จะเขียน helper functions เอง
</script>
```

### 2. ใช้ Computed Properties

```vue
<script setup>
const birthDateThai = computed(() => formatDateThai(form.date_of_birth));
// ดีกว่าการเรียกฟังก์ชันใน template
</script>

<template>
    <!-- ดี -->
    <span>{{ birthDateThai }}</span>

    <!-- หลีกเลี่ยง -->
    <span>{{ formatDateThai(form.date_of_birth) }}</span>
</template>
```

### 3. จัดการ Error อย่างเหมาะสม

```javascript
const age = computed(() => {
    try {
        return calculateAge(form.date_of_birth) || "-";
    } catch (error) {
        console.error("Age calculation error:", error);
        return "-";
    }
});
```

### 4. Validate ก่อนบันทึกข้อมูล

```javascript
const saveData = async () => {
    if (!isValidDate(form.date_of_birth)) {
        alert("วันเกิดไม่ถูกต้อง");
        return;
    }

    // บันทึกข้อมูล...
};
```

## Migration Guide

### แปลงโค้ดเดิมเป็นใช้ Date Utilities

**Before:**

```vue
<script setup>
const formatBirthDate = (dateString) => {
    if (!dateString) return "ไม่ระบุ";
    const date = new Date(dateString);
    // ... lots of code
};

const calculateAge = (dateString) => {
    if (!dateString) return "-";
    const birthDate = new Date(dateString);
    // ... lots of code
};
</script>
```

**After:**

```vue
<script setup>
import { formatDateThai, calculateAge } from "@/utils/dateUtils";

// ใช้ฟังก์ชันที่มีอยู่แล้ว
</script>
```

หรือใช้ Composable:

```vue
<script setup>
import { useBirthDate } from "@/composables/useDate";

const birthDate = useBirthDate(props.student.date_of_birth);
// ใช้งาน birthDate.dateThai, birthDate.age โดยตรง
</script>
```

## Testing

```javascript
import {
    formatDateForInput,
    formatDateThai,
    calculateAge,
} from "@/utils/dateUtils";

describe("Date Utilities", () => {
    test("formatDateForInput handles timezone correctly", () => {
        expect(formatDateForInput("2010-01-08")).toBe("2010-01-08");
        expect(formatDateForInput("2010-01-08T00:00:00.000Z")).toBe(
            "2010-01-08"
        );
    });

    test("formatDateThai formats correctly", () => {
        expect(formatDateThai("2010-01-08")).toBe("8 มกราคม 2553");
    });

    test("calculateAge returns correct age", () => {
        // Mock current date to 2025-11-20
        expect(calculateAge("2010-01-08")).toBe(15);
    });
});
```

## Troubleshooting

### ปัญหา: วันที่ยังคลาดเคลื่อน 1 วัน

**สาเหตุ:** ใช้ `new Date()` โดยตรงแทนฟังก์ชัน utilities

**วิธีแก้:** ใช้ `formatDateForInput()` แทน

### ปัญหา: Import error

**สาเหตุ:** Path alias ไม่ถูกต้อง

**วิธีแก้:** ตรวจสอบ `vite.config.js` หรือใช้ relative path

```javascript
// ถ้า @ ไม่ work ให้ใช้
import { formatDateThai } from "../utils/dateUtils";
```

### ปัญหา: ไม่แสดงผล

**สาเหตุ:** ข้อมูลเป็น null หรือ undefined

**วิธีแก้:** ตรวจสอบข้อมูลก่อนใช้งาน

```vue
<template>
    <div v-if="birthDate.date.value">
        {{ birthDate.dateThai }}
    </div>
    <div v-else>ไม่มีข้อมูลวันเกิด</div>
</template>
```

## Support & Contribution

หากพบปัญหาหรือต้องการเพิ่มฟีเจอร์:

1. เปิด issue ใน repository
2. แนบตัวอย่างโค้ดและ error message
3. ระบุ expected behavior vs actual behavior

## License

Copyright © 2025 Plearnd Project

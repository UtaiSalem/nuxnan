---
name: nuxnan-fullstack-workflow
description: Use for any nuxnan feature or bug that may cross Nuxt frontend and Laravel backend. Triggers on requests mentioning course, academy, marketplace, settings, dashboard, auth, API, page, component, endpoint, validation, or "แก้ให้", "ทำต่อ", "เพิ่ม feature".
---

# Nuxnan Fullstack Workflow

Use this skill to move carefully from user request to verified implementation.

## Steps

1. Read `.agents/rules/project.md`.
2. Classify scope: frontend-only, backend-only, database, or cross-stack.
3. Search for existing implementation before creating new files.
4. Trace the flow:
   - Page/component
   - Store/composable/service
   - API route
   - Controller/request/resource/model
   - Migration or relationship, if needed
5. Make the smallest coherent change.
6. Verify with focused checks.
7. Summarize changed files and verification.

## Cross-Stack Contract Checklist

- Field names match between API resources and UI consumers.
- Create/update validation supports the same domain rules.
- Nullable fields are handled in both PHP and TypeScript.
- Filter/sort enum values match.
- Authorization behavior is preserved.
- List endpoints eager-load fields shown in cards/tables.

## Handoff Template

```markdown
ปรับเรียบร้อย:
- [what changed]

ตรวจแล้ว:
- [command or manual check]

หมายเหตุ:
- [risk or blocked verification, if any]
```

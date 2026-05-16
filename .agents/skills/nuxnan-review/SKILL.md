---
name: nuxnan-review
description: Use before commits, PRs, handoffs, or whenever the user asks to review/check nuxnan changes. Focuses on bugs, security, API contract drift, migrations, tests, and risky diffs.
---

# Nuxnan Review Skill

## Review Procedure

1. Run `git status --short`.
2. Inspect changed filenames.
3. Read diffs for touched files only.
4. Check frontend/backend contracts where both sides changed.
5. Check validation, authorization, null handling, and N+1 risk.
6. Check whether tests or verification are missing for risky changes.

## Findings Format

Lead with findings:

```markdown
Findings
- P1 [file:line] Problem. Impact. Suggested fix.

Open questions
- ...

Verification gaps
- ...
```

If no findings:

```markdown
ไม่พบ issue สำคัญจาก diff ที่ตรวจแล้ว

Verification gaps:
- ...
```

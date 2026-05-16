# Review Rules

Use this for final review, PR review, and pre-commit checks.

## Priorities

1. Runtime bugs and regressions.
2. Security, auth, authorization, and data exposure.
3. Data loss or migration risk.
4. Frontend/backend contract drift.
5. Missing tests for risky behavior.
6. Maintainability issues that materially increase future risk.

## Review Format

Lead with findings. Each finding should include:

- Severity: `P0`, `P1`, `P2`, or `P3`.
- File and line reference.
- Concrete problem and user-visible impact.
- Suggested fix.

If there are no findings, say so and mention residual verification gaps.

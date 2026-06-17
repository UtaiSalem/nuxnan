# Classroom Attendance Simulator - Implementation Checklist

## Phase 0 — Fix bugs before building
- [x] Confirm analysis from code (all findings verified)
- [x] 0.1: Fix `authAtttendanceStatus` in CourseAttendanceResource.php — use correct course_member_id lookup (cache per course)
- [x] 0.2: Clean up dead route `getMemberJoinStatus` — confirmed no frontend usage, removed route + method reference
- [x] 0.3: Save `title` in store() and update() — added validation + save, falls back to description

## Phase 1 — Simulator API
- [x] 1.1: Create `AttendanceSimulatorController` with show() — dense seat mapping, proper auth, server_time
- [x] 1.2: Register route at `/attendances/{attendance}/simulator` with proper import
- [ ] 1.3: Run lint + verify endpoint works (pending server start)

## Phase 2 — Simulator UI
- [x] 2.1: Create `useAttendanceStatus.ts` composable — status enum, arriving calc, summary builder, types
- [x] 2.2: Create `AttendanceSimulatorShell.vue` — data loading, polling (5/30s), state, optimistic updates
- [x] 2.3: Create `ClassroomSeatGrid.vue` — dual-column grid with center aisle, status-colored seats
- [x] 2.4: Create `SeatCard.vue` — individual desk with avatar, time-in, arriving animation
- [x] 2.5: Create `AttendanceSidebar.vue` — summary bars, sorted member list, inline status badges
- [x] 2.6: Add view toggle (table/simulator) to AttendancesList.vue with import
- [x] 2.7: Responsive — sidebar hidden < 1024px, inline summary shown

## Phase 3 — Verification
- [ ] 3.1: Manual verification — boot dev server, navigate, test table→simulator toggle
- [ ] 3.2: Check admin status update works from simulator sidebar
- [ ] 3.3: Test student check-in flow

## Student Master Profile UI (Nuxt)
- [x] Phase 11: Unified Data Store — `useStudentMasterStore.ts`
- [x] Phase 12: Admin List & Search — Refactor `pages/admin/students/index.vue`
- [x] Phase 13: Admin Detail View — Refactor `pages/admin/students/[id].vue`
- [x] Phase 14: Student Self-Service — Refactor `pages/student/profile.vue`
- [x] Phase 15: Approval Dashboard — New page `pages/admin/students/requests.vue`

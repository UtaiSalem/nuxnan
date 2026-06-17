# Student Master Profile Unified API (v7)

## Base URL: `/api/student`

### 1. Master Data Index
`GET /api/student/master`
- **Auth**: Required (Staff/Admin)
- **Params**: `academy_id`, `class_level`, `class_section`, `status`, `search`, `per_page`
- **Response**: Paginated `StudentResource` collection.

### 2. Master Data Detail
`GET /api/student/master/{student}`
- **Auth**: Required (Staff/Admin)
- **Response**: Full `StudentResource` with all sub-tables eager loaded.

### 3. Self Profile
`GET /api/student/me`
- **Auth**: Required (Student)
- **Response**: Same as Master Data Detail for the authenticated student.

### 4. Update Profile
`POST /api/student/update-info`
- **Auth**: Required (Student/Staff)
- **Body**: Basic info fields (title, name, nickname, etc.)
- **Logic**: Respects `academies.student_editable_fields` configuration.
- **Response**: `StudentResource` or indication of pending approval.

### 5. Change Requests
`GET /api/student/requests`
- **Auth**: Required (Staff/Admin)
- **Params**: `status` (pending|approved|rejected), `academy_id`
- **Response**: Paginated list of `StudentChangeRequest`.

### 6. Process Request
`PATCH /api/student/requests/{id}/approve`
`PATCH /api/student/requests/{id}/reject`
- **Auth**: Required (Staff/Admin)
- **Response**: Success message.

## Models & Tables
- `students`: Central master table.
- `student_cards`: Legacy table, linked via `student_id` FK.
- `student_addresses`, `student_contacts`, `student_guardians`, `student_health_info`, `student_academic_info`: Normalized sub-tables.
- `student_change_requests`: Tracks proposed changes awaiting approval.

# Course Management System v2

A modern, SPA-based course management system built with Vue 3, Pinia, and TailwindCSS.

## 🚀 Features

- **Modern Architecture**: Vue 3 Composition API with Pinia state management
- **Component-Based**: Modular, reusable components with clear separation of concerns
- **API Services**: Centralized API layer with error handling and caching
- **Performance Optimized**: Lazy loading, caching, and efficient state management
- **Responsive Design**: Mobile-first design with TailwindCSS
- **TypeScript Ready**: Structured for easy TypeScript migration

## 📁 Project Structure

```
resources/js/v2/
├── api/                    # API services
│   ├── memberService.js
│   ├── groupService.js
│   └── courseService.js
├── components/
│   ├── course/            # Course-specific components
│   │   ├── CourseHeader.vue
│   │   ├── CourseSidebar.vue
│   │   ├── MemberList.vue
│   │   └── MemberInviteModal.vue
│   └── common/            # Reusable components
│       └── Pagination.vue
├── layouts/
│   └── CourseLayout.vue   # Main course layout
├── pages/
│   └── Course/           # Course pages
│       ├── Dashboard.vue
│       ├── Members.vue
│       ├── Groups.vue
│       ├── Announcements.vue
│       ├── Attendance.vue
│       ├── Lessons.vue
│       ├── Assignments.vue
│       ├── Exams.vue
│       ├── Settings.vue
│       └── Summary.vue
├── router/
│   └── courseRoutes.js   # Vue Router configuration
├── store/
│   └── modules/
│       ├── course/
│       │   └── courseStore.js
│       ├── group/
│       │   └── groupStore.js
│       └── member/
│           └── memberStore.js
├── stores/
│   └── composables/       # Reusable store composables
│       ├── useApi.js
│       ├── useCache.js
│       └── useLoading.js
└── test/
    └── integration-test.js # Integration tests
```

## 🔧 Setup

### Prerequisites
- Node.js 16+
- Vue 3
- Pinia
- TailwindCSS

### Installation
1. Ensure all dependencies are installed
2. Import the router configuration
3. Set up Pinia stores
4. Configure API endpoints

### Basic Usage

```javascript
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import courseRoutes from '@/v2/router/courseRoutes';

const app = createApp({});
const pinia = createPinia();

app.use(pinia);
app.use(courseRoutes);

app.mount('#app');
```

## 🏪 Store Architecture

### Course Store
Manages course data, metadata, and settings.

```javascript
import { useCourseStore } from '@/v2/store/modules/course/courseStore';

const courseStore = useCourseStore();

// Initialize course
await courseStore.init(courseId);

// Get course data
const course = courseStore.currentCourse;
```

### Member Store
Manages course members, roles, and permissions.

```javascript
import { useMemberStore } from '@/v2/store/modules/member/memberStore';

const memberStore = useMemberStore();

// Fetch members
await memberStore.fetchMembers(courseId);

// Update member role
await memberStore.updateMemberRole(memberId, 'teacher');
```

### Group Store
Manages course groups and member assignments.

```javascript
import { useGroupStore } from '@/v2/store/modules/group/groupStore';

const groupStore = useGroupStore();

// Fetch groups
await groupStore.fetchGroups(courseId);

// Create new group
await groupStore.createGroup(courseId, groupData);
```

## 🔌 API Services

### Member Service
Handles all member-related API calls.

```javascript
import { memberService } from '@/v2/api/memberService';

// Get course members
const members = await memberService.getCourseMembers(courseId, { page: 1 });

// Invite member
const result = await memberService.inviteMember(courseId, { email: 'user@example.com' });
```

### Group Service
Handles all group-related API calls.

```javascript
import { groupService } from '@/v2/api/groupService';

// Get course groups
const groups = await groupService.getCourseGroups(courseId);

// Add member to group
await groupService.addMemberToGroup(groupId, memberId);
```

### Course Service
Handles all course-related API calls.

```javascript
import { courseService } from '@/v2/api/courseService';

// Get course details
const course = await courseService.getCourse(courseId);

// Update course
await courseService.updateCourse(courseId, updates);
```

## 🧩 Components

### CourseLayout
Main layout component for all course pages.

```vue
<template>
  <CourseLayout :course-id="courseId">
    <router-view />
  </CourseLayout>
</template>
```

### MemberList
Displays a list of course members with actions.

```vue
<template>
  <MemberList 
    :members="members"
    :selected-members="selectedMembers"
    @select="handleSelect"
    @update-role="handleUpdateRole"
    @remove="handleRemove"
  />
</template>
```

### Pagination
Reusable pagination component.

```vue
<template>
  <Pagination 
    :pagination="pagination"
    @page-change="handlePageChange"
    @per-page-change="handlePerPageChange"
  />
</template>
```

## 🧪 Testing

Run the integration tests to verify everything is working:

```javascript
import { runAllTests } from '@/v2/test/integration-test';

// Run all tests
const results = await runAllTests();
console.log(results);
```

## 🔄 Data Flow

1. **Component Action** → **Store Method** → **API Service** → **Backend**
2. **Backend Response** → **API Service** → **Store Update** → **Component Reactivity**

## 🚀 Performance Features

- **Lazy Loading**: Components loaded on-demand
- **Caching**: Intelligent caching with TTL
- **Pagination**: Server-side pagination for large datasets
- **Debounced Search**: Reduces API calls
- **Optimistic Updates**: Better UX for immediate feedback

## 🔐 Security

- **Authentication**: Token-based auth with interceptors
- **Authorization**: Role-based access control
- **Input Validation**: Client-side and server-side validation
- **XSS Protection**: Proper data sanitization

## 🌐 Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 📝 Development

### Code Style
- ESLint for code quality
- Prettier for formatting
- Vue 3 Composition API
- Pinia for state management

### Git Workflow
- Feature branches for new features
- Pull requests for code review
- Semantic versioning

## 🚀 Deployment

### Build
```bash
npm run build
```

### Environment Variables
```bash
VITE_API_BASE_URL=/api/v2
VITE_APP_TITLE=Plearnd LMS
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

MIT License - see LICENSE file for details

## 🆘 Support

For issues and questions:
- Create an issue in the repository
- Check the documentation
- Review the test files for examples

---

**Version**: 2.0.0  
**Last Updated**: 2025-11-03
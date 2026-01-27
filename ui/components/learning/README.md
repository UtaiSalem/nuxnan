# Learning Dashboard Components

This directory contains reusable components for the Learning Dashboard in Nuxnan application.

## Components

### LearningStatsCard.vue
A reusable stats card component for displaying learning metrics on the dashboard.

**Props:**
- `type` (required): Type of stats card - 'progress' | 'courses' | 'lessons' | 'assignments' | 'quizzes' | 'attendance' | 'average'
- `value` (required): The numeric or string value to display
- `subtitle` (required): Subtitle text
- `description` (optional): Additional description text
- `progress` (optional): Progress percentage (0-100)
- `progressLabel` (optional): Label for progress bar
- `progressText` (optional): Text showing current/max values

**Features:**
- Gradient backgrounds based on card type
- Progress bar with gradient colors
- Responsive design
- Hover animations
- Dark mode support

**Type Options:**
- `progress`: Overall learning progress (blue gradient)
- `courses`: Total courses enrolled (emerald gradient)
- `lessons`: Total lessons (violet gradient)
- `assignments`: Total assignments (amber gradient)
- `quizzes`: Total quizzes (rose gradient)
- `attendance`: Attendance rate (cyan gradient)
- `average`: Average score (yellow gradient)

### RecentCourses.vue
Displays recent courses with progress tracking.

**Props:**
- `courses` (required): Array of course objects

**Course Object Structure:**
```typescript
{
  id: number
  name: string
  category: string
  lessons_count: number
  hours: number
  progress: number
}
```

**Features:**
- Course cover images with gradient backgrounds
- Course category display
- Lessons and hours count
- Progress bar with gradient colors
- Hover effects
- Empty state handling

### UpcomingAssignments.vue
Displays upcoming assignments with due dates and points.

**Props:**
- `assignments` (required): Array of assignment objects

**Assignment Object Structure:**
```typescript
{
  id: number
  title: string
  course: string
  due_date: string
  points: number
  status: 'pending' | 'submitted' | 'graded'
}
```

**Features:**
- Assignment icons with gradient backgrounds
- Due date display in Thai format
- Points display
- Status badges
- Hover effects
- Empty state handling
- Date formatting utilities

## Usage Example

```vue
<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Learning Stats Cards -->
    <LearningStatsCard
      type="courses"
      :value="stats.totalCourses"
      subtitle="คอร์สทั้งหมด"
      description="Active: {{ stats.activeCourses }}"
    />
    
    <LearningStatsCard
      type="progress"
      :value="overallProgress"
      subtitle="ความสำเร็จโดยรวม"
      :progress="overallProgress"
      progressLabel="Progress"
      progressText="40 / 53"
    />
    
    <LearningStatsCard
      type="attendance"
      :value="stats.attendanceRate"
      subtitle="เข้าเรียน"
    />
    
    <LearningStatsCard
      type="average"
      :value="stats.averageScore"
      subtitle="เกรดเฉลี่ย"
    />
  </div>

  <!-- Recent Courses -->
  <RecentCourses :courses="recentCourses" />

  <!-- Upcoming Assignments -->
  <UpcomingAssignments :assignments="upcomingAssignments" />
</template>
```

## Styling

All components use:
- **Tailwind CSS** for utility classes
- **Gradient backgrounds** for visual appeal
- **Smooth transitions** and animations
- **Dark mode support** throughout
- **Responsive design** patterns
- **Fluent Icons** from @iconify/vue

## Color Scheme

- **Progress**: Blue to Indigo gradient
- **Courses**: Emerald to Teal gradient
- **Lessons**: Violet to Purple gradient
- **Assignments**: Amber to Orange gradient
- **Quizzes**: Rose to Pink gradient
- **Attendance**: Cyan to Blue gradient
- **Average**: Yellow to Amber gradient

## Animations

Components include subtle animations:
- Fade-in on load
- Hover scale effects
- Smooth transitions
- Progress bar animations

## Integration

These components are designed to work with:
- Vue 3 Composition API
- Nuxt 3 framework
- TypeScript for type safety
- Existing learning composables (useMemberProgress, etc.)

## Responsive Design

- Mobile-first approach
- Breakpoints:
  - Mobile: Single column
  - Tablet: 2 columns
  - Desktop: 3-4 columns
- Flexible grid layouts
- Touch-friendly interactions

## Accessibility

- Semantic HTML elements
- ARIA labels where needed
- Keyboard navigation support
- High contrast colors
- Screen reader friendly

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Future Enhancements

### Potential Improvements
1. **Course Filtering**: Filter courses by category or status
2. **Assignment Sorting**: Sort by due date or priority
3. **Progress Charts**: Visual progress charts over time
4. **Notifications**: Real-time assignment reminders
5. **Quick Actions**: Quick access to course actions
6. **Calendar View**: Calendar view of assignments and due dates
7. **Search**: Search functionality for courses and assignments
8. **Export**: Export progress reports

### Performance Optimizations
1. **Virtual Scrolling**: For long course lists
2. **Lazy Loading**: Load data on scroll
3. **Image Optimization**: Optimize course covers
4. **Code Splitting**: Lazy load dashboard components
5. **Caching**: Implement API response caching

## Tips for Usage

1. **Data Loading**: Always show loading states while fetching data
2. **Empty States**: Provide helpful messages and illustrations when no data
3. **Error Handling**: Gracefully handle API errors with user-friendly messages
4. **Progress Tracking**: Use accurate progress calculations from backend
5. **Date Formatting**: Always use Thai locale for dates
6. **Accessibility**: Ensure all interactive elements are keyboard accessible
7. **Performance**: Use computed properties for derived data
8. **Responsive Testing**: Test on multiple screen sizes

## Support

For component-specific issues, refer to:
- Component inline comments
- Type definitions in TypeScript interfaces
- Props documentation above

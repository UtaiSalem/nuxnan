# Plan: Move Search and Filters to Left Sidebar Widget

## Overview
Move the search input and all filter controls from the main content area to a dedicated widget in the left sidebar of the courses listing page.

## Current State
- Search input and 5 filter dropdowns (Category, Level, Semester, Year, Sort) are in the main content area
- Left sidebar contains: RecentlyViewedCoursesWidget, FavoriteCoursesWidget
- Main content area has the filters row followed by course cards

## Proposed Changes

### 1. Create CourseSearchFilterWidget Component
**File:** `ui/components/widgets/CourseSearchFilterWidget.vue`

**Features:**
- Search input with icon
- Category dropdown
- Level dropdown
- Semester dropdown
- Year dropdown
- Sort dropdown
- Styled as a sidebar widget with consistent design

**Props to Accept:**
- `searchQuery` (ref)
- `selectedCategory` (ref)
- `selectedLevel` (ref)
- `selectedSemester` (ref)
- `selectedYear` (ref)
- `sortBy` (ref)
- `categories` (ref)
- `levels` (ref)
- `semesters` (ref)
- `years` (ref)
- `sortOptions` (array)
- `handleSearch` (function)

**Design:**
- White/dark gray background card
- Header section with title "ค้นหาและกรอง"
- Vertical layout for all controls
- Consistent spacing and styling with other widgets

### 2. Update courses/index.vue
**Changes:**
- Import CourseSearchFilterWidget
- Add CourseSearchFilterWidget to left sidebar (above RecentlyViewedCoursesWidget)
- Remove the entire "Filters Row" section (lines 250-316)
- Keep all state management and logic in the parent component

**New Left Sidebar Structure:**
```
Left Sidebar:
  - CourseSearchFilterWidget (NEW)
  - RecentlyViewedCoursesWidget
  - FavoriteCoursesWidget
```

### 3. Layout Adjustments
- The left sidebar will now have 3 widgets stacked vertically
- Main content area will show only course cards (no filters)
- Right sidebar remains unchanged

## Implementation Steps

### Step 1: Create CourseSearchFilterWidget Component
- Create new component file at `ui/components/widgets/CourseSearchFilterWidget.vue`
- Implement search input with icon
- Implement all 5 filter dropdowns
- Style as sidebar widget
- Accept props for all reactive values and functions

### Step 2: Update courses/index.vue
- Import the new widget component
- Add widget to left sidebar template
- Remove filters row from main content
- Ensure all props are passed correctly

### Step 3: Testing
- Verify search functionality works
- Verify all filters work correctly
- Check responsive design on mobile/tablet/desktop
- Ensure widget matches design of other sidebar widgets

## Design Considerations

### Widget Styling
- Use same card style as other widgets (bg-white dark:bg-gray-800, rounded-xl, shadow-lg)
- Header with border-bottom
- Vertical spacing between controls
- Full-width inputs for better usability

### Responsive Design
- On mobile, widget may need to be collapsible or moved to top
- Consider sticky positioning for better UX on desktop

### User Experience
- Clear visual hierarchy
- Easy to understand labels in Thai
- Smooth transitions when filters change

## Files to Modify
1. **Create:** `ui/components/widgets/CourseSearchFilterWidget.vue`
2. **Modify:** `ui/pages/Learn/Courses/index.vue`

## Success Criteria
- [ ] Search and all filters are in left sidebar widget
- [ ] Widget matches design of other sidebar widgets
- [ ] All functionality remains intact
- [ ] Layout looks clean and professional
- [ ] Responsive design works properly

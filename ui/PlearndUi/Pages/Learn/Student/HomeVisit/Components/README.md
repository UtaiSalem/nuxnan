# HomeVisit Shared Components

This folder contains shared Vue components that can be used by both Student and Teacher interfaces in the HomeVisit system.

## Components

### Student Information Management
- **StudentsCard.vue** - Basic student information with photo upload
- **AcademicInfoCard.vue** - Academic information management
- **AddressCard.vue** - Address information management
- **ContactCard.vue** - Contact information management
- **HealthInfoCard.vue** - Health information management
- **GuardianCard.vue** - Guardian information management
- **DocumentsCard.vue** - Document management (future development)

## Usage

### Import individual components:
```javascript
import StudentsCard from '../Components/StudentsCard.vue'
import AcademicInfoCard from '../Components/AcademicInfoCard.vue'
```

### Import multiple components using named imports:
```javascript
import {
  StudentsCard,
  AcademicInfoCard,
  AddressCard,
  ContactCard,
  HealthInfoCard,
  GuardianCard
} from '../Components'
```

## Features

All components support:
- ✅ Real-time data editing and saving
- ✅ Form validation and error handling
- ✅ Responsive design for mobile and desktop
- ✅ Event emission for parent component handling
- ✅ Loading states and success/error messages

## Used By

- **Student Profile** (`/Student/Profile.vue`) - Student can view and edit their own information
- **Teacher Dashboard** (`/Teacher/Dashboard.vue`) - Teachers can view and edit student information

## Event Handling

Each component emits standard events:
- `@save` - When data is successfully saved
- `@update` - When data needs to be updated in parent component
- `@error` - When an error occurs during operations

## Dependencies

Components require proper API endpoints and models:
- Student model with relationships (academicInfo, addresses, contacts, etc.)
- Proper authentication and authorization
- File upload capabilities for images/documents
import { computed } from 'vue';
import { a as usePage } from './inertia-vue3-CWdJjaLG.mjs';
import './server.mjs';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'vue-router';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';
import 'pinia';
import 'vue/server-renderer';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

function useStudentRoutes(context = null) {
  const page = usePage();
  const isTeacherContext = computed(() => {
    if (context) {
      return context === "teacher";
    }
    return page.url.includes("/teacher/") || page.component.includes("Teacher");
  });
  const isStudentContext = computed(() => {
    return !isTeacherContext.value;
  });
  const academicInfoRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        index: (studentId) => route("homevisit.teacher.academic-info.index", studentId),
        store: (studentId) => route("homevisit.teacher.academic-info.store", studentId),
        update: (studentId, academicInfoId) => route("homevisit.teacher.academic-info.update", [studentId, academicInfoId]),
        destroy: (studentId, academicInfoId) => route("homevisit.teacher.academic-info.destroy", [studentId, academicInfoId]),
        setCurrent: (studentId, academicInfoId) => route("homevisit.teacher.academic-info.set-current", [studentId, academicInfoId])
      };
    } else {
      return {
        index: (studentId) => route("homevisit.student.academic-info.index", studentId),
        store: (studentId) => route("homevisit.student.academic-info.store", studentId),
        update: (studentId, academicInfoId) => route("homevisit.student.academic-info.update", [studentId, academicInfoId]),
        destroy: (studentId, academicInfoId) => route("homevisit.student.academic-info.destroy", [studentId, academicInfoId]),
        setCurrent: (studentId, academicInfoId) => route("homevisit.student.academic-info.set-current", [studentId, academicInfoId])
      };
    }
  });
  const addressRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        index: (studentId) => route("homevisit.teacher.addresses.index", studentId),
        store: (studentId) => route("homevisit.teacher.addresses.store", studentId),
        update: (studentId, addressId) => route("homevisit.teacher.addresses.update", [studentId, addressId]),
        destroy: (studentId, addressId) => route("homevisit.teacher.addresses.destroy", [studentId, addressId]),
        setCurrent: (studentId, addressId) => route("homevisit.teacher.addresses.set-current", [studentId, addressId])
      };
    } else {
      return {
        index: (studentId) => route("homevisit.student.addresses.index", studentId),
        store: (studentId) => route("homevisit.student.addresses.store", studentId),
        update: (studentId, addressId) => route("homevisit.student.addresses.update", [studentId, addressId]),
        destroy: (studentId, addressId) => route("homevisit.student.addresses.destroy", [studentId, addressId]),
        setCurrent: (studentId, addressId) => route("homevisit.student.addresses.set-current", [studentId, addressId])
      };
    }
  });
  const contactRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        index: (studentId) => route("homevisit.teacher.contacts.index", studentId),
        store: (studentId) => route("homevisit.teacher.contacts.store", studentId),
        update: (studentId, contactId) => route("homevisit.teacher.contacts.update", [studentId, contactId]),
        destroy: (studentId, contactId) => route("homevisit.teacher.contacts.destroy", [studentId, contactId]),
        setPrimary: (studentId, contactId) => route("homevisit.teacher.contacts.set-primary", [studentId, contactId])
      };
    } else {
      return {
        index: (studentId) => route("homevisit.student.contacts.index", studentId),
        store: (studentId) => route("homevisit.student.contacts.store", studentId),
        update: (studentId, contactId) => route("homevisit.student.contacts.update", [studentId, contactId]),
        destroy: (studentId, contactId) => route("homevisit.student.contacts.destroy", [studentId, contactId]),
        setPrimary: (studentId, contactId) => route("homevisit.student.contacts.set-primary", [studentId, contactId])
      };
    }
  });
  const healthRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        show: (studentId) => route("homevisit.teacher.health.show", studentId),
        store: (studentId) => route("homevisit.teacher.health.store", studentId),
        update: (studentId, healthId) => route("homevisit.teacher.health.update", [studentId, healthId])
      };
    } else {
      return {
        show: (studentId) => route("homevisit.student.health.show", studentId),
        store: (studentId) => route("homevisit.student.health.store", studentId),
        update: (studentId, healthId) => route("homevisit.student.health.update", [studentId, healthId])
      };
    }
  });
  const guardianRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        show: (studentId) => route("homevisit.teacher.guardian.show", studentId),
        store: (studentId) => route("homevisit.teacher.guardian.store", studentId),
        update: (studentId) => route("homevisit.teacher.guardian.update", studentId)
      };
    } else {
      return {
        show: (studentId) => route("homevisit.student.guardian.show", studentId),
        store: (studentId) => route("homevisit.student.guardian.store", studentId),
        update: (studentId) => route("homevisit.student.guardian.update", studentId)
      };
    }
  });
  return {
    isTeacherContext,
    isStudentContext,
    academicInfoRoutes,
    addressRoutes,
    contactRoutes,
    healthRoutes,
    guardianRoutes
  };
}

export { useStudentRoutes };
//# sourceMappingURL=useStudentRoutes-BWKsyL_y.mjs.map

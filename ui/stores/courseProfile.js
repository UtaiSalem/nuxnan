import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import Swal from 'sweetalert2';

export const useCourseProfileStore = defineStore('courseProfile', () => {
    // State
    const courseProfiles = ref({});
    const loadingStates = ref({});
    const errorStates = ref({});
    const cacheTimestamps = ref({});
    
    // UI State
    const showOptionGroups = ref();
    const showAcceptMemberOption = ref({});
    const inputHeaderEditing = ref({});
    const inputSubheaderEditing = ref({});
    
    // Temporary state for image previews
    const tempImages = ref({});
    
    // Cache duration in milliseconds (5 minutes)
    const CACHE_DURATION = 5 * 60 * 1000;
    
    // Getters
    const getCourseProfile = computed(() => (courseId) => {
        return courseProfiles.value[courseId] || null;
    });
    
    const isLoading = computed(() => (key) => {
        return loadingStates.value[key] || false;
    });
    
    const getError = computed(() => (key) => {
        return errorStates.value[key] || null;
    });
    
    const isCacheValid = computed(() => (key) => {
        const timestamp = cacheTimestamps.value[key];
        if (!timestamp) return false;
        return Date.now() - timestamp < CACHE_DURATION;
    });
    
    const getShowOptionGroups = computed(() => () => {
        const result = showOptionGroups.value || false;
        // Dev-only logging
        return result;
    });
    
    const getShowAcceptMemberOption = computed(() => (courseId) => {
        return showAcceptMemberOption.value[courseId] || false;
    });
    
    const getInputHeaderEditing = computed(() => (courseId) => {
        return inputHeaderEditing.value[courseId] || false;
    });
    
    const getInputSubheaderEditing = computed(() => (courseId) => {
        return inputSubheaderEditing.value[courseId] || false;
    });
    
    const getTempImage = computed(() => (courseId, imageType) => {
        return tempImages.value[courseId]?.[imageType] || null;
    });
    
    // Actions
    const setLoading = (key, status) => {
        loadingStates.value[key] = status;
    };
    
    const setError = (key, error) => {
        errorStates.value[key] = error;
    };
    
    const clearError = (key) => {
        delete errorStates.value[key];
    };
    
    const setCacheTimestamp = (key) => {
        cacheTimestamps.value[key] = Date.now();
    };
    
    const setCourseProfile = (courseId, data) => {
        courseProfiles.value[courseId] = data;
        setCacheTimestamp(`course_${courseId}`);
    };
    
    const updateCourseProfile = (courseId, updatedData) => {
        if (courseProfiles.value[courseId]) {
            courseProfiles.value[courseId] = { 
                ...courseProfiles.value[courseId], 
                ...updatedData 
            };
            setCacheTimestamp(`course_${courseId}`);
        }
    };
    
    // UI State Actions
    const setShowOptionGroups = (status) => {
        showOptionGroups.value = status;
    };
    
    const setShowAcceptMemberOption = (courseId, status) => {
        showAcceptMemberOption.value[courseId] = status;
    };
    
    const setInputHeaderEditing = (courseId, status) => {
        inputHeaderEditing.value[courseId] = status;
    };
    
    const setInputSubheaderEditing = (courseId, status) => {
        inputSubheaderEditing.value[courseId] = status;
    };
    
    // Image Actions
    const setTempImage = (courseId, imageType, imageUrl) => {
        if (!tempImages.value[courseId]) {
            tempImages.value[courseId] = {};
        }
        // Store the new URL (don't revoke yet - let component handle it)
        tempImages.value[courseId][imageType] = imageUrl;
    };
    
    const clearTempImage = (courseId, imageType) => {
        if (tempImages.value[courseId] && tempImages.value[courseId][imageType]) {
            // Just delete the reference (component will handle URL.revokeObjectURL)
            delete tempImages.value[courseId][imageType];
        }
    };
    
    const clearAllTempImages = (courseId) => {
        if (tempImages.value[courseId]) {
            // Just delete all references (component will handle URL.revokeObjectURL)
            delete tempImages.value[courseId];
        }
    };
    
    // API Actions
    const fetchCourseProfile = async (courseId, forceRefresh = false) => {
        const cacheKey = `course_${courseId}`;
        const api = useApi();

        // Return cached data if valid and not forcing refresh
        if (!forceRefresh && isCacheValid.value(cacheKey)) {
            return courseProfiles.value[courseId];
        }

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            const response = await api.get(`/courses/${courseId}/profile`);

            if (response && response.success) {
                setCourseProfile(courseId, response.course);
                return response.course;
            } else {
                throw new Error(response?.message || 'Failed to fetch course profile');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to fetch course profile');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    const updateCourseCover = async (courseId, coverImage) => {
        const cacheKey = `update_cover_${courseId}`;
        const api = useApi();

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            const formData = new FormData();
            formData.append('cover_image', coverImage);

            const response = await api.post(`/courses/${courseId}/cover`, formData);

            if (response && response.success) {
                updateCourseProfile(courseId, { cover_image: response.cover_image });
                return response.cover_image;
            } else {
                throw new Error(response?.message || 'Failed to update course cover');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to update course cover');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    const updateCourseLogo = async (courseId, logoImage) => {
        const cacheKey = `update_logo_${courseId}`;
        const api = useApi();

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            const formData = new FormData();
            formData.append('logo_image', logoImage);

            const response = await api.post(`/courses/${courseId}/logo`, formData);

            if (response && response.success) {
                updateCourseProfile(courseId, { logo_image: response.logo_image });
                return response.logo_image;
            } else {
                throw new Error(response?.message || 'Failed to update course logo');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to update course logo');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    const updateCourseHeader = async (courseId, header) => {
        const cacheKey = `update_header_${courseId}`;
        const api = useApi();

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            const response = await api.patch(`/courses/${courseId}/header`, { header });

            if (response && response.success) {
                updateCourseProfile(courseId, { cover_header: response.header });
                return response.header;
            } else {
                throw new Error(response?.message || 'Failed to update course header');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to update course header');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    const updateCourseSubheader = async (courseId, subheader) => {
        const cacheKey = `update_subheader_${courseId}`;
        const api = useApi();

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            const response = await api.patch(`/courses/${courseId}/subheader`, { subheader });

            if (response && response.success) {
                updateCourseProfile(courseId, { cover_subheader: response.subheader });
                return response.subheader;
            } else {
                throw new Error(response?.message || 'Failed to update course subheader');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to update course subheader');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    const requestToBeMember = async (courseId, groupId = null) => {
        const cacheKey = `request_member_${courseId}`;
        const api = useApi();

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            // Check if user has enough points
            const page = usePage();
            const userPoints = page.props.auth.user.pp;
            const tuitionFees = page.props.course.data.tuition_fees;

            if (userPoints < tuitionFees) {
                if (Swal) {
                    Swal.fire(
                        'ขออภัย',
                        'คุณมีแต้มสะสมไม่เพียงพอที่จะสมัครเข้าร่วมกลุ่มรายวิชานี้ , กรุณาเติมแต้มสะสมก่อน',
                        'warning'
                    );
                }
                return;
            }

            const response = await api.post(`/courses/${courseId}/members`, { group_id: groupId });

            if (response && response.success) {
                // Update course profile with new member status
                updateCourseProfile(courseId, {
                    member_status: response.newCourseMember.course_member_status,
                    course_member_of_auth: response.newCourseMember
                });

                page.props.course.data.enrolled_students++;

                // Update page props to reflect changes
                if (page.props.courseMemberOfAuth !== undefined) {
                    page.props.courseMemberOfAuth = response.newCourseMember;
                }

                // Update course member status
                if (response.newCourseMember && response.newCourseMember.course_member_status == 1) {
                    page.props.course.data.isMember = true;
                } else {
                    page.props.course.data.isMember = false;
                }

                // Show success message
                if (Swal) {
                    Swal.fire(
                        'เสร็จสิ้น',
                        'ขอเป็นสมาชิกเรียบร้อยแล้ว',
                        'success'
                    );
                } else if (window.$toast) {
                    window.$toast.success(response.message || 'ส่งคำขอเข้าร่วมรายวิชาสำเร็จ');
                }

                return response;
            } else {
                if (Swal) {
                    Swal.fire(
                        'ขออภัย',
                        'เกิดข้อผิดพลาดในการขอเข้าร่วมกลุ่ม',
                        response?.msg || response?.message || 'กรุณาลองใหม่อีกครั้ง'
                    );
                } else {
                    throw new Error(response?.message || 'Failed to request membership');
                }
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || error.message || 'Failed to request membership';
            setError(cacheKey, errorMessage);

            // Show error message
            if (Swal) {
                Swal.fire(
                    'ขออภัย',
                    'เกิดข้อผิดพลาดในการขอเข้าร่วมกลุ่ม',
                    errorMessage
                );
            } else if (window.$toast) {
                window.$toast.error(errorMessage);
            }

            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    const requestToBeUnmember = async (courseId, memberId) => {
        const cacheKey = `request_unmember_${courseId}`;
        const api = useApi();

        setLoading(cacheKey, true);
        clearError(cacheKey);

        try {
            const response = await api.delete(`/courses/${courseId}/members/${memberId}`);

            if (response && response.success) {
                // Update course profile with new member status
                updateCourseProfile(courseId, {
                    member_status: response.member_status,
                    course_member_of_auth: null
                });

                // Update page props to reflect changes
                const page = usePage();
                if (page.props.courseMemberOfAuth !== undefined) {
                    page.props.courseMemberOfAuth = null;
                }
                page.props.course.data.isMember = false;

                // Show success message
                if (Swal) {
                    Swal.fire(
                        'เสร็จสิ้น',
                        'ออกจากรายวิชาเรียบร้อยแล้ว',
                        'success'
                    );
                } else if (window.$toast) {
                    window.$toast.success(response.message || 'ออกจากรายวิชาสำเร็จ');
                }

                return response;
            } else {
                if (Swal) {
                    Swal.fire(
                        'ขออภัย',
                        'เกิดข้อผิดพลาดในการออกจากรายวิชา',
                        response?.msg || response?.message || 'กรุณาลองใหม่อีกครั้ง'
                    );
                } else {
                    throw new Error(response?.message || 'Failed to cancel membership');
                }
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || error.message || 'Failed to cancel membership';
            setError(cacheKey, errorMessage);

            // Show error message
            if (Swal) {
                Swal.fire(
                    'ขออภัย',
                    'เกิดข้อผิดพลาดในการออกจากรายวิชา',
                    errorMessage
                );
            } else if (window.$toast) {
                window.$toast.error(errorMessage);
            }

            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    // Utility Actions
    const clearCourseCache = (courseId) => {
        delete courseProfiles.value[courseId];
        delete cacheTimestamps.value[`course_${courseId}`];
        delete showOptionGroups.value[courseId];
        delete showAcceptMemberOption.value[courseId];
        delete inputHeaderEditing.value[courseId];
        delete inputSubheaderEditing.value[courseId];
        clearAllTempImages(courseId);
    };
    
    const clearAllCache = () => {
        courseProfiles.value = {};
        cacheTimestamps.value = {};
        loadingStates.value = {};
        errorStates.value = {};
        showOptionGroups.value = {};
        showAcceptMemberOption.value = {};
        inputHeaderEditing.value = {};
        inputSubheaderEditing.value = {};
        tempImages.value = {};
    };
    
    return {
        // State
        courseProfiles,
        loadingStates,
        errorStates,
        
        // Getters
        getCourseProfile,
        isLoading,
        getError,
        isCacheValid,
        getShowOptionGroups,
        getShowAcceptMemberOption,
        getInputHeaderEditing,
        getInputSubheaderEditing,
        getTempImage,
        
        // Actions
        setLoading,
        setError,
        clearError,
        setCourseProfile,
        updateCourseProfile,
        setShowOptionGroups,
        setShowAcceptMemberOption,
        setInputHeaderEditing,
        setInputSubheaderEditing,
        setTempImage,
        clearTempImage,
        clearAllTempImages,
        
        // API Actions
        fetchCourseProfile,
        updateCourseCover,
        updateCourseLogo,
        updateCourseHeader,
        updateCourseSubheader,
        requestToBeMember,
        requestToBeUnmember,
        
        // Utility Actions
        clearCourseCache,
        clearAllCache,
    };
});

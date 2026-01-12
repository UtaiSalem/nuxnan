<script setup>
import { ref, shallowRef, computed } from 'vue';
import { useDebounceFn } from "@vueuse/core";
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const { $apiFetch } = useNuxtApp();

definePageMeta({
    layout: 'admin',
    middleware: ['auth', 'plearnd-admin']
});

// Constants
const REQUIRED_POINTS = 4800;
const POINTS_PER_BAHT = 1080;

const searchQuery = ref('');
const usersResult = shallowRef([]);
const isSearching = ref(false);
const searchType = ref('all');
const hasSearched = ref(false);

// Search type options
const searchTypes = [
    { value: 'all', label: 'ทั้งหมด', icon: 'mdi:magnify' },
    { value: 'email', label: 'อีเมล', icon: 'mdi:email' },
    { value: 'name', label: 'ชื่อ', icon: 'mdi:account' },
    { value: 'phone', label: 'เบอร์โทร', icon: 'mdi:phone' },
    { value: 'code', label: 'รหัสประจำตัว', icon: 'mdi:card-account-details' },
];

const currentSearchType = computed(() => 
    searchTypes.find(t => t.value === searchType.value) || searchTypes[0]
);

const placeholderText = computed(() => {
    const typeLabels = {
        all: 'ค้นหาด้วย อีเมล, ชื่อ, เบอร์โทร หรือรหัสประจำตัว',
        email: 'กรอกอีเมลที่ต้องการค้นหา',
        name: 'กรอกชื่อที่ต้องการค้นหา',
        phone: 'กรอกเบอร์โทรที่ต้องการค้นหา',
        code: 'กรอกรหัสประจำตัวที่ต้องการค้นหา'
    };
    return typeLabels[searchType.value] || typeLabels.all;
});

// Helper functions สำหรับตรวจสอบแต้ม/wallet ของสมาชิก
function getUserPoints(user) {
    return user.points ?? 0;
}

function getUserWallet(user) {
    return user.wallet ?? 0;
}

// คำนวณมูลค่ารวม (แต้ม + wallet แปลงเป็นแต้ม)
function getTotalAvailablePoints(user) {
    const points = getUserPoints(user);
    const walletAsPoints = getUserWallet(user) * POINTS_PER_BAHT;
    return points + walletAsPoints;
}

// ตรวจสอบว่ามีเพียงพอหรือไม่ (รวมแต้ม + wallet)
function canResetPassword(user) {
    return getTotalAvailablePoints(user) >= REQUIRED_POINTS;
}

// คำนวณว่าจะหักจากแต้มเท่าไหร่
function getPointsToDeduct(user) {
    const userPoints = getUserPoints(user);
    return Math.min(userPoints, REQUIRED_POINTS);
}

// คำนวณว่าจะหักจาก wallet เท่าไหร่ (บาท)
function getWalletToDeduct(user) {
    const userPoints = getUserPoints(user);
    const remainingPoints = Math.max(0, REQUIRED_POINTS - userPoints);
    return Math.ceil(remainingPoints / POINTS_PER_BAHT);
}

// คำนวณแต้มที่ขาด
function getNeededPoints(user) {
    const totalAvailable = getTotalAvailablePoints(user);
    return Math.max(0, REQUIRED_POINTS - totalAvailable);
}

// คำนวณเงินที่ต้องเติม
function getNeededMoney(user) {
    return Math.ceil(getNeededPoints(user) / POINTS_PER_BAHT);
}

// Search function
const handleSearchUsers = useDebounceFn(async () => {
    if (!searchQuery.value.trim()) {
        usersResult.value = [];
        hasSearched.value = false;
        return;
    }

    try {
        isSearching.value = true;
        hasSearched.value = true;
        
        const response = await $apiFetch('/api/forgot-password/getuser', {
            method: 'POST',
            body: { 
                email: searchQuery.value.trim(),
                search_type: searchType.value
            }
        });
        
        if (response?.users) {
            usersResult.value = structuredClone(response.users);
        } else {
            usersResult.value = [];
        }
    } catch (error) {
        console.error('Search error:', error);
        usersResult.value = [];
    } finally {
        isSearching.value = false;
    }
}, 400);

// Reset Password - ใช้แต้มของสมาชิก (รวมกับ Wallet อัตโนมัติ)
async function handleResetPassword(user) {
    const isDark = document.documentElement.classList.contains('dark');
    const userPoints = getUserPoints(user);
    const userWallet = getUserWallet(user);
    const pointsToDeduct = getPointsToDeduct(user);
    const walletToDeduct = getWalletToDeduct(user);
    
    // สร้าง HTML สำหรับแสดงค่าใช้จ่าย
    let costHtml = '';
    if (walletToDeduct > 0) {
        // ต้องใช้ทั้งแต้มและ Wallet
        costHtml = `
            <div class="p-3 ${isDark ? 'bg-blue-900/30' : 'bg-blue-50'} rounded-lg border ${isDark ? 'border-blue-800' : 'border-blue-200'}">
                <p class="text-xs ${isDark ? 'text-blue-300' : 'text-blue-600'} mb-2 font-medium">การหักค่าบริการ (รวมแต้ม + Wallet)</p>
                <div class="text-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="${isDark ? 'text-gray-300' : 'text-gray-600'}">หักจากแต้ม:</span>
                        <span class="font-bold text-amber-500">-${pointsToDeduct.toLocaleString()} แต้ม</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="${isDark ? 'text-gray-300' : 'text-gray-600'}">หักจาก Wallet:</span>
                        <span class="font-bold text-green-500">-฿${walletToDeduct.toLocaleString()}</span>
                    </div>
                    <div class="border-t ${isDark ? 'border-blue-700' : 'border-blue-200'} pt-1 mt-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="${isDark ? 'text-gray-300' : 'text-gray-600'}">แต้มคงเหลือ:</span>
                            <span class="font-bold text-amber-400">${(userPoints - pointsToDeduct).toLocaleString()} แต้ม</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="${isDark ? 'text-gray-300' : 'text-gray-600'}">Wallet คงเหลือ:</span>
                            <span class="font-bold text-green-400">฿${(userWallet - walletToDeduct).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else {
        // ใช้แต้มอย่างเดียวพอ
        costHtml = `
            <div class="p-3 ${isDark ? 'bg-green-900/30' : 'bg-green-50'} rounded-lg border ${isDark ? 'border-green-800' : 'border-green-200'}">
                <div class="flex items-center justify-between text-sm">
                    <span class="${isDark ? 'text-gray-300' : 'text-gray-600'}">หักจากแต้มสมาชิก:</span>
                    <span class="font-bold text-amber-500">-${REQUIRED_POINTS.toLocaleString()} แต้ม</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                    <span class="${isDark ? 'text-gray-300' : 'text-gray-600'}">แต้มคงเหลือ:</span>
                    <span class="font-bold text-green-500">${(userPoints - REQUIRED_POINTS).toLocaleString()} แต้ม</span>
                </div>
            </div>
        `;
    }

    const result = await Swal.fire({
        title: 'ยืนยันการรีเซ็ตรหัสผ่าน',
        html: `
            <div class="text-left space-y-3">
                <p class="text-center">รีเซ็ตรหัสผ่านให้ <strong>${user.name}</strong></p>
                
                ${costHtml}
                
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1">รหัสผ่านใหม่:</label>
                    <input 
                        type="text" 
                        id="swal-new-password" 
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 ${isDark ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300'}" 
                        value="00000000" 
                        placeholder="รหัสผ่านใหม่">
                    <p class="text-xs text-gray-500 mt-1">ค่าเริ่มต้น: 00000000</p>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน รีเซ็ตรหัสผ่าน',
        cancelButtonText: 'ยกเลิก',
        customClass: {
            popup: isDark ? 'rounded-xl !bg-gray-800 !text-white' : 'rounded-xl',
            confirmButton: '!bg-teal-500 hover:!bg-teal-600',
            cancelButton: '!bg-gray-300 hover:!bg-gray-400 !text-gray-800'
        },
        preConfirm: () => {
            const password = document.getElementById('swal-new-password').value;
            if (!password || password.length < 4) {
                Swal.showValidationMessage('รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร');
                return false;
            }
            return password;
        }
    });

    if (!result.isConfirmed) return;

    try {
        const passwordToSet = result.value || '00000000';
        const response = await $apiFetch(`/api/forgot-password/reset/${user.id}`, {
            method: 'POST',
            body: {
                new_password: passwordToSet
            }
        });
        
        if (response.success) {
            // Refresh search results
            await handleSearchUsers();
            
            let deductionInfo = '';
            if (response.money_deducted > 0) {
                deductionInfo = `<p class="text-xs text-blue-500">หักจาก Wallet: ฿${response.money_deducted.toLocaleString()}</p>`;
            }
            if (response.points_deducted > 0) {
                deductionInfo += `<p class="text-xs text-amber-500">หักแต้ม: ${response.points_deducted.toLocaleString()} แต้ม</p>`;
            }
            
            await Swal.fire({
                title: 'รีเซ็ตรหัสผ่านสำเร็จ!',
                html: `
                    <div class="space-y-3">
                        <p>รหัสผ่านใหม่ของ <strong>${user.name}</strong></p>
                        <div class="p-4 ${isDark ? 'bg-gray-700' : 'bg-gray-100'} rounded-lg">
                            <code class="text-2xl font-bold text-teal-500">${response.new_password}</code>
                        </div>
                        <p class="text-sm text-gray-500">กรุณาแจ้งผู้ใช้ให้เปลี่ยนรหัสผ่านใหม่หลังเข้าสู่ระบบ</p>
                        <div class="pt-2 border-t ${isDark ? 'border-gray-600' : 'border-gray-200'}">
                            ${deductionInfo}
                            <p class="text-xs text-gray-400">แต้มคงเหลือ: ${response.user_remaining_points?.toLocaleString() ?? 'N/A'} | Wallet: ฿${response.user_remaining_wallet?.toLocaleString() ?? 'N/A'}</p>
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'ตกลง',
                customClass: {
                    popup: isDark ? 'rounded-xl !bg-gray-800 !text-white' : 'rounded-xl',
                    confirmButton: '!bg-teal-500 hover:!bg-teal-600'
                }
            });
        } else {
            throw new Error(response.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        await Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: error.data?.message || error.message || 'ไม่สามารถรีเซ็ตรหัสผ่านได้',
            icon: 'error',
            confirmButtonText: 'ตกลง',
            customClass: {
                popup: isDark ? 'rounded-xl !bg-gray-800 !text-white' : 'rounded-xl',
                confirmButton: '!bg-teal-500 hover:!bg-teal-600'
            }
        });
    }
}

// เติมแต้มให้สมาชิก
async function handleTopUpPoints(user) {
    const isDark = document.documentElement.classList.contains('dark');
    const userPoints = getUserPoints(user);
    const userWallet = getUserWallet(user);
    const neededPts = getNeededPoints(user);
    const suggestedMoney = getNeededMoney(user);
    
    const result = await Swal.fire({
        title: `เติมแต้มให้ ${user.name}`,
        html: `
            <div class="text-left space-y-4">
                <div class="p-4 ${isDark ? 'bg-gray-700' : 'bg-amber-50'} rounded-lg border ${isDark ? 'border-gray-600' : 'border-amber-200'}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm ${isDark ? 'text-gray-300' : 'text-gray-600'}">แต้มปัจจุบัน:</span>
                        <span class="font-bold text-amber-500">${userPoints.toLocaleString()} แต้ม</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm ${isDark ? 'text-gray-300' : 'text-gray-600'}">Wallet:</span>
                        <span class="font-bold text-green-500">฿${userWallet.toLocaleString()}</span>
                    </div>
                    <div class="flex items-center justify-between border-t ${isDark ? 'border-gray-600' : 'border-amber-200'} mt-2 pt-2">
                        <span class="text-sm ${isDark ? 'text-gray-300' : 'text-gray-600'}">ขาดอีก:</span>
                        <span class="font-bold text-orange-500">${neededPts.toLocaleString()} แต้ม</span>
                    </div>
                </div>
                
                <div class="p-3 ${isDark ? 'bg-blue-900/30' : 'bg-blue-50'} rounded-lg border ${isDark ? 'border-blue-800' : 'border-blue-200'}">
                    <p class="text-sm ${isDark ? 'text-blue-300' : 'text-blue-700'}">
                        <strong>อัตราแลกเปลี่ยน:</strong> 1 บาท = ${POINTS_PER_BAHT.toLocaleString()} แต้ม
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">จำนวนเงินที่ต้องการเติม (บาท):</label>
                    <input 
                        type="number" 
                        id="swal-topup-amount" 
                        class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-lg font-bold text-center ${isDark ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300'}" 
                        value="${suggestedMoney}" 
                        min="1"
                        placeholder="จำนวนเงิน">
                    <p class="text-xs ${isDark ? 'text-gray-400' : 'text-gray-500'} mt-1 text-center">
                        แนะนำ: ${suggestedMoney} บาท (ได้ ${(suggestedMoney * POINTS_PER_BAHT).toLocaleString()} แต้ม)
                    </p>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'เติมแต้ม',
        cancelButtonText: 'ยกเลิก',
        customClass: {
            popup: isDark ? 'rounded-xl !bg-gray-800 !text-white' : 'rounded-xl',
            confirmButton: '!bg-green-500 hover:!bg-green-600',
            cancelButton: '!bg-gray-300 hover:!bg-gray-400 !text-gray-800'
        },
        preConfirm: () => {
            const amount = parseInt(document.getElementById('swal-topup-amount').value);
            if (!amount || amount < 1) {
                Swal.showValidationMessage('กรุณากรอกจำนวนเงินที่ถูกต้อง');
                return false;
            }
            return amount;
        }
    });

    if (!result.isConfirmed) return;

    try {
        const amount = result.value;
        const response = await $apiFetch(`/api/forgot-password/exchange/${user.id}`, {
            method: 'POST',
            body: { money: amount }
        });

        if (response.success) {
            // Refresh search results
            await handleSearchUsers();
            
            await Swal.fire({
                title: 'เติมแต้มสำเร็จ!',
                html: `
                    <div class="space-y-2">
                        <p>เติมแต้มให้ <strong>${user.name}</strong></p>
                        <p>จำนวน <strong class="text-amber-500">${(amount * POINTS_PER_BAHT).toLocaleString()}</strong> แต้ม</p>
                        <p class="text-lg font-bold text-green-500">แต้มปัจจุบัน: ${response.pp?.toLocaleString()} แต้ม</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'ตกลง',
                customClass: {
                    popup: isDark ? 'rounded-xl !bg-gray-800 !text-white' : 'rounded-xl',
                    confirmButton: '!bg-teal-500 hover:!bg-teal-600'
                }
            });
        } else {
            throw new Error(response.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        await Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: error.data?.message || error.message || 'ไม่สามารถเติมแต้มได้',
            icon: 'error',
            confirmButtonText: 'ตกลง',
            customClass: {
                popup: isDark ? 'rounded-xl !bg-gray-800 !text-white' : 'rounded-xl',
                confirmButton: '!bg-teal-500 hover:!bg-teal-600'
            }
        });
    }
}

function clearSearch() {
    searchQuery.value = '';
    usersResult.value = [];
    hasSearched.value = false;
}

function changeSearchType(type) {
    searchType.value = type;
    if (searchQuery.value.trim()) {
        handleSearchUsers();
    }
}

function highlightMatch(text, query) {
    if (!text || !query) return text;
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800 px-0.5 rounded">$1</mark>');
}
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รีเซ็ตรหัสผ่าน</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ค้นหาสมาชิกและรีเซ็ตรหัสผ่าน (ใช้แต้มของสมาชิก)</p>
            </div>
            <NuxtLink 
                to="/" 
                class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
            >
                <Icon icon="mdi:arrow-left" class="w-5 h-5" />
                <span>กลับหน้าหลัก</span>
            </NuxtLink>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <Icon icon="mdi:information" class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" />
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <p class="font-medium mb-1">การรีเซ็ตรหัสผ่านจะใช้แต้มของสมาชิก</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-600 dark:text-blue-400">
                        <li>ค่าบริการ: <strong>{{ REQUIRED_POINTS.toLocaleString() }} แต้ม</strong> ต่อครั้ง</li>
                        <li>หักจากแต้มก่อน ส่วนที่ขาดจะหักจาก Wallet อัตโนมัติ</li>
                        <li>อัตราแลกเปลี่ยน: 1 บาท = {{ POINTS_PER_BAHT.toLocaleString() }} แต้ม</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <!-- Search Type Tabs -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="type in searchTypes"
                    :key="type.value"
                    @click="changeSearchType(type.value)"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        searchType === type.value 
                            ? 'bg-teal-500 text-white shadow-md' 
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                >
                    <Icon :icon="type.icon" class="w-4 h-4" />
                    {{ type.label }}
                </button>
            </div>

            <!-- Search Input -->
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Icon 
                            :icon="isSearching ? 'svg-spinners:ring-resize' : currentSearchType.icon" 
                            class="w-5 h-5 text-gray-400" 
                        />
                    </div>
                    <input
                        v-model="searchQuery"
                        @input="handleSearchUsers"
                        type="search"
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all"
                        :placeholder="placeholderText"
                    />
                </div>
                <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors flex items-center gap-2"
                >
                    <Icon icon="mdi:close" class="w-5 h-5" />
                    <span class="hidden sm:inline">ล้าง</span>
                </button>
            </div>
        </div>

        <!-- Results Section -->
        <div v-if="hasSearched && usersResult.length > 0" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    ผลการค้นหา ({{ usersResult.length }} รายการ)
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div 
                    v-for="user in usersResult" 
                    :key="user.id"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <!-- User Card Header -->
                    <div class="h-16 bg-gradient-to-r from-teal-400 via-teal-500 to-blue-500 relative">
                        <div class="absolute -bottom-8 left-4">
                            <div class="w-16 h-16 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden bg-white shadow-lg">
                                <img 
                                    :src="user.avatar || 'https://cdn.pixabay.com/photo/2016/08/08/09/17/avatar-1577909_1280.png'" 
                                    :alt="user.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        </div>
                        <div class="absolute top-2 right-2 px-2 py-1 bg-white/20 backdrop-blur rounded text-xs text-white font-medium">
                            ID: {{ user.id }}
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="pt-10 px-4 pb-4 space-y-4">
                        <!-- Basic Info -->
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white" v-html="highlightMatch(user.name, searchQuery)"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" v-html="highlightMatch(user.email, searchQuery)"></p>
                        </div>

                        <!-- Tags -->
                        <div class="flex flex-wrap gap-2">
                            <span v-if="user.personal_code" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">
                                <Icon icon="mdi:card-account-details" class="w-3 h-3" />
                                <span v-html="highlightMatch(user.personal_code, searchQuery)"></span>
                            </span>
                            <span v-if="user.phone" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">
                                <Icon icon="mdi:phone" class="w-3 h-3" />
                                <span v-html="highlightMatch(user.phone, searchQuery)"></span>
                            </span>
                        </div>

                        <!-- Points & Wallet - ของสมาชิก -->
                        <div class="p-3 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Icon icon="mdi:star-circle" class="w-5 h-5 text-amber-500" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">แต้มสมาชิก</p>
                                        <p class="font-bold" :class="canResetPassword(user) ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                                            {{ getUserPoints(user).toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="w-px h-10 bg-amber-200 dark:bg-amber-700"></div>
                                <div class="flex items-center gap-2">
                                    <Icon icon="mdi:wallet" class="w-5 h-5 text-green-500" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Wallet</p>
                                        <p class="font-bold text-green-600 dark:text-green-400">฿{{ getUserWallet(user).toLocaleString() }}</p>
                                    </div>
                                </div>
                                <div class="w-px h-10 bg-amber-200 dark:bg-amber-700"></div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ต้องใช้</p>
                                    <p class="font-bold text-gray-600 dark:text-gray-400">{{ REQUIRED_POINTS.toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Actions -->
                        <div class="space-y-2">
                            <!-- สามารถรีเซ็ตได้ (แต้มพอ หรือ รวมกับ Wallet พอ) -->
                            <div v-if="canResetPassword(user)">
                                <!-- มีแต้มพอ (ไม่ต้องใช้ Wallet) -->
                                <template v-if="getWalletToDeduct(user) === 0">
                                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm mb-2">
                                        <Icon icon="mdi:check-circle" class="w-4 h-4" />
                                        <span>แต้มเพียงพอ พร้อมรีเซ็ตรหัสผ่าน</span>
                                    </div>
                                    <button
                                        @click="handleResetPassword(user)"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg transition-colors font-medium"
                                    >
                                        <Icon icon="mdi:lock-reset" class="w-5 h-5" />
                                        รีเซ็ตรหัสผ่าน (-{{ REQUIRED_POINTS.toLocaleString() }} แต้ม)
                                    </button>
                                </template>
                                <!-- ต้องใช้ Wallet ร่วมด้วย -->
                                <template v-else>
                                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400 text-sm mb-2">
                                        <Icon icon="mdi:information" class="w-4 h-4" />
                                        <span>ใช้แต้มร่วมกับ Wallet</span>
                                    </div>
                                    <button
                                        @click="handleResetPassword(user)"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium"
                                    >
                                        <Icon icon="mdi:wallet" class="w-5 h-5" />
                                        รีเซ็ตรหัสผ่าน (-{{ getPointsToDeduct(user).toLocaleString() }} แต้ม, -฿{{ getWalletToDeduct(user).toLocaleString() }})
                                    </button>
                                </template>
                            </div>

                            <!-- แต้มและ Wallet รวมกันไม่พอ -->
                            <div v-else>
                                <div class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm mb-2">
                                    <Icon icon="mdi:alert-circle" class="w-4 h-4" />
                                    <span>แต้มและเงินใน Wallet ไม่เพียงพอ (ขาดอีก {{ (REQUIRED_POINTS - getTotalAvailablePoints(user)).toLocaleString() }} แต้ม)</span>
                                </div>
                                <button
                                    @click="handleTopUpPoints(user)"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors font-medium"
                                >
                                    <Icon icon="mdi:plus-circle" class="w-5 h-5" />
                                    เติมแต้มให้สมาชิก
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div 
            v-else-if="isSearching" 
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center"
        >
            <Icon icon="svg-spinners:blocks-shuffle-3" class="w-16 h-16 mx-auto text-teal-500 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">กำลังค้นหา...</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">กรุณารอสักครู่</p>
        </div>

        <!-- Empty State -->
        <div 
            v-else-if="hasSearched && usersResult.length === 0" 
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center"
        >
            <Icon icon="mdi:account-search" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ไม่พบผู้ใช้</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">ไม่พบผู้ใช้ที่ตรงกับ "{{ searchQuery }}"</p>
        </div>

        <!-- Initial State -->
        <div 
            v-else 
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center"
        >
            <Icon icon="mdi:account-key" class="w-16 h-16 mx-auto text-teal-500 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">เริ่มต้นการค้นหา</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">กรอกข้อมูลในช่องค้นหาเพื่อค้นหาสมาชิกที่ต้องการรีเซ็ตรหัสผ่าน</p>
        </div>
    </div>
</template>

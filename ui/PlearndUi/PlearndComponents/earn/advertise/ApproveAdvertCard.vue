<script setup>
    import {
        ref
    } from "vue";
    import Swal from "sweetalert2";
    const props = defineProps({
        advert: {
            type: Object,
            required: true,
        },
    });
    const isLoading = ref(false);
    const edit = ref(props.advert.status !== 0);
    const handleRecieveDonate = () => {
        try {
            isLoading.value = true;
            Swal.fire({
                title: "ตอบรับการโฆษณา",
                text: "คุณต้องการตอบรับการโฆษณานี้ใช่หรือไม่?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: "ยกเลิก",
                confirmButtonColor: "#4caf50",
                cancelButtonColor: "#f44336",
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const response = await axios.patch(
                            `/plearnd-admin/supports/advertises/${props.advert.id}/approve`
                        );
                        if (response.status === 200) {
                            Swal.fire({
                                title: "สำเร็จ",
                                text: "ตอบรับการบริจาคเรียบร้อยแล้ว",
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1200,
                            });
                            edit.value = true;
                            props.advert.status = 1;
                        }
                        isLoading.value = false;
                    } catch (error) {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: "ไม่สามารถตอบรับการบริจาคได้",
                            icon: "error",
                            showConfirmButton: false,
                            timer: 1200,
                        });
                    }
                },
            });
            isLoading.value = false;
        } catch (error) {
            console.error(error);
            isLoading.value = false;
        }
    };
    const handleRejectDonate = () => {
        try {
            Swal.fire({
                title: "ปฏิเสธการโฆษณา",
                text: "คุณต้องการปฏิเสธการโฆษณานี้ใช่หรือไม่?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: "ยกเลิก",
                confirmButtonColor: "#4caf50",
                cancelButtonColor: "#f44336",
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const response = await axios.patch(
                            `/plearnd-admin/supports/advertises/${props.advert.id}/reject`
                        );
                        if (response.status === 200) {
                            Swal.fire({
                                title: "สำเร็จ",
                                text: "ปฏิเสธการโฆษณาเรียบร้อยแล้ว",
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1200,
                            });
                            edit.value = true;
                            props.advert.status = 2;
                        }
                    } catch (error) {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: "ไม่สามารถปฏิเสธการโฆษณาได้",
                            icon: "error",
                            showConfirmButton: false,
                            timer: 1200,
                        });
                    }
                },
            });
        } catch (error) {
            console.log(error);
        }
    };
</script>

<template>
    <div class="text-gray-900 bg-white border border-gray-400 rounded-lg shadow-xl pb-2">
        <div class="overflow-hidden rounded-t-lg">
            <img class="object-cover object-top" :src="advert.slip" alt="Slip" />
        </div>

        <figure class="flex items-center p-2 bg-gray-300">
            <div class="flex-shrink-0">
                <img class="w-12 h-12 rounded-full" :src="advert.advertiser.avatar"
                    :alt="advert.advertiser.name + 'photo'" />
            </div>
            <div class="w-full ps-3">
                <div class="flex flex-col mb-1 text-sm text-gray-700 dark:text-gray-400">
                    <span class="text-sm font-bold text-blue-500">{{
                        advert.advertiser.name
                    }}</span>
                    <span class="font-semibold">{{
                        advert.advertiser.personal_code
                    }}</span>
                </div>
            </div>
        </figure>

        <figure class="flex items-center p-0">
            <img class="w-full" :src="advert.media_image" :alt="advert.advertiser.name + 'photo'">
        </figure>

        <div class="m-4">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="font-bold text-left text-gray-700">
                            รายการ
                        </th>
                        <th class="font-bold text-right text-gray-700">
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-left text-gray-700">วันที่โอน</td>
                        <td class="text-right text-gray-700">
                            {{ advert.transfer_date }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left text-gray-700">เวลาโอน</td>
                        <td class="text-right text-gray-700">
                            {{ advert.transfer_time }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="font-bold text-left text-gray-700">
                            จำนวนเงิน
                        </td>
                        <td class="font-bold text-right text-gray-700">
                            {{ advert.amounts }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="flex justify-between m-4">
            <p class="py-1.5 text-sm text-gray-700">
                สถานะ: {{ advert.status === 0 ? "รอการตอบรับ" : "ตอบรับแล้ว" }}
            </p>
            <button v-if="edit" @click.prevent="edit = !edit" class="px-2 bg-orange-300 rounded-lg">
                {{ "แก้ไข" }}
            </button>
            <button v-if="!edit && advert.status !== 0" @click.prevent="edit = !edit"
                class="px-2 bg-orange-300 rounded-lg">
                ยกเลิก
            </button>
        </div>
        <div class="flex p-4 mx-4 space-x-2 border-t mt-2" v-if="!edit">
            <button @click.prevent="handleRejectDonate" v-if="advert.status !== 2"
                class="block w-1/2 px-2 py-1 mx-auto font-base text-sm text-white bg-pink-500 rounded-lg hover:shadow-lg">
                ปฏิเสธ
            </button>
            <button @click.prevent="handleRecieveDonate" v-if="advert.status !== 1"
                class="block w-1/2 px-2 py-1 mx-auto font-base text-sm text-white bg-green-500 rounded-lg hover:shadow-lg">
                ตอบรับ
            </button>
        </div>
    </div>
</template>

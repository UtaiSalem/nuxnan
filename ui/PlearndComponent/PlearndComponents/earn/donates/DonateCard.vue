<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const config = useRuntimeConfig();



const props = defineProps({
  donate: {
    type: Object,
    required: true,
  },
});

const isLoading = ref(false);
const edit = ref(props.donate.status !== 0);

const donorName = computed(() => {
    // API sends 'donor' object and 'donor_name' string
    // If donor object exists, use its name, otherwise use donor_name (which defaults to 'ไม่ประสงค์ออกนาม')
    return props.donate.donor?.name || props.donate.donor_name || 'ไม่ประสงค์ออกนาม';
});

const donorAvatar = computed(() => {
    return props.donate.donor?.avatar || `${config.public.apiBase}/storage/images/plearnd-logo.png`;
});

const handleRecieveDonate = () => {
  try {
    isLoading.value = true;
    Swal.fire({
      title: 'ตอบรับการบริจาค',
      text: 'คุณต้องการตอบรับการบริจาคนี้ใช่หรือไม่?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'ตกลง',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#4caf50',
      cancelButtonColor: '#f44336',
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        try {
          const response = await axios.patch(`/plearnd-admin/supports/donates/${props.donate.id}/recieve`);
          if (response.status === 200) {
            Swal.fire({
              title: 'สำเร็จ',
              text: 'ตอบรับการบริจาคเรียบร้อยแล้ว',
              icon: 'success',
              showConfirmButton: false,
              timer: 1200
            });
            edit.value = true;
            props.donate.status = 1;
          }
          isLoading.value = false;
        } catch (error) {
          Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถตอบรับการบริจาคได้',
            icon: 'error',
            showConfirmButton: false,
            timer: 1200
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
            title: 'ปฏิเสธการบริจาค',
            text: 'คุณต้องการปฏิเสธการบริจาคนี้ใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#4caf50',
            cancelButtonColor: '#f44336',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                try {
                    const response = await axios.patch(`/plearnd-admin/supports/donates/${props.donate.id}/reject`);
                    if (response.status === 200) {
                        Swal.fire({
                            title: 'สำเร็จ',
                            text: 'ปฏิเสธการบริจาคเรียบร้อยแล้ว',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1200
                        });
                        edit.value = true;
                        props.donate.status = 2;
                    }
                } catch (error) {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถปฏิเสธการบริจาคได้',
                        icon: 'error',
                        showConfirmButton: false,
                        timer: 1200
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
          <img class="object-cover object-top" :src="donate.slip" alt="Slip" />
      </div>


      <figure class="relative flex flex-col-reverse bg-slate-100 rounded-lg p-2 dark:bg-slate-800 dark:highlight-white/5">
          <figcaption class="flex items-center space-x-4">
              <img :src="donorAvatar" alt="" class="flex-none object-cover rounded-full w-14 h-14" loading="lazy" decoding="async">
              <div class="flex-auto">
                  <div class="text-base font-semibold text-slate-900 dark:text-slate-200">
                      {{ donorName }}
                  </div>
              </div>
          </figcaption>
      </figure>

      
      <div class="m-4">
          <table class="w-full">
              <thead>
                  <tr>
                      <th class="font-bold text-left text-gray-700">รายการ</th>
                      <th class="font-bold text-right text-gray-700">Amount</th>
                  </tr>
              </thead>
              <tbody>
                  <tr>
                      <td class="text-left text-gray-700">วันที่ทำรายการ</td>
                      <td class="text-right text-gray-700">{{ donate.transfer_date }}</td>
                  </tr>
                  <tr>
                      <td class="text-left text-gray-700">เวลาทำรายการ</td>
                      <td class="text-right text-gray-700">{{ donate.transfer_time }}</td>
                  </tr>
              </tbody>
              <tfoot>
                  <tr>
                      <td class="font-bold text-left text-gray-700">จำนวนเงิน</td>
                      <td class="font-bold text-right text-gray-700">{{ donate.amounts }}</td>
                  </tr>
              </tfoot>
          </table>
      </div>
      <div class="flex justify-between m-4">
          <p class="py-1.5 text-sm text-gray-700">สถานะ: {{ donate.status === 0 ? 'รอการตอบรับ' : 'ตอบรับแล้ว' }}</p>
          <button v-if="edit" @click.prevent="edit = !edit" class="px-2 bg-orange-300 rounded-lg">{{ 'แก้ไข' }}</button>
          <button v-if="!edit && donate.status !== 0" @click.prevent="edit = !edit" class="px-2 bg-orange-300 rounded-lg">ยกเลิก</button>
      </div>
      <div class="flex p-4 mx-4 space-x-2 border-t mt-2" v-if="!edit" >
          <button @click.prevent="handleRejectDonate" v-if="donate.status !==2" class="block w-1/2 px-2 py-1 mx-auto font-base text-sm text-white bg-pink-500 rounded-lg hover:shadow-lg">
              ปฏิเสธ
          </button>
          <button @click.prevent="handleRecieveDonate" v-if="donate.status !==1" class="block w-1/2 px-2 py-1 mx-auto font-base text-sm text-white bg-green-500 rounded-lg hover:shadow-lg">
              ตอบรับ
          </button>
      </div>
  </div>
</template>


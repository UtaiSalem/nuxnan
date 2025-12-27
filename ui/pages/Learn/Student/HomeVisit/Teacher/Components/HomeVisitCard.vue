
<template>
  <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-green-50 to-emerald-50">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            <i class="fas fa-home mr-2 text-green-600"></i>
            การเยี่ยมบ้าน
          </h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">
            จัดการข้อมูลการเยี่ยมบ้านและอัพโหลดรูปภาพหลักฐาน
          </p>
        </div>
        <button
          @click="toggleCreateForm"
          class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
        >
          <i class="fas fa-plus mr-2"></i>
          สร้างการเยี่ยมบ้านใหม่
        </button>
      </div>
    </div>

    <div class="px-4 py-5 sm:p-6">
      <!-- Create New Home Visit Form (Collapsible) -->
      <div v-if="showCreateForm" class="mb-6 p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg border border-green-200">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-md font-medium text-gray-900">สร้างการเยี่ยมบ้านใหม่</h4>
          <button @click="toggleCreateForm" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <form @submit.prevent="submitHomeVisit" class="space-y-6">
          <!-- Visit Date & Time -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700">วันที่เยี่ยม *</label>
              <input
                v-model="visitForm.visit_date"
                type="date"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">เวลาเยี่ยม</label>
              <input
                v-model="visitForm.visit_time"
                type="time"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
              >
            </div>
          </div>

          <!-- Zone Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
              โซน <span class="text-gray-500 text-xs">(ไม่บังคับ)</span>
            </label>
            <select
              v-model="visitForm.zone_id"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
            >
              <option value="">ไม่ระบุโซน</option>
              <option 
                v-for="zone in zones" 
                :key="zone.id" 
                :value="zone.id"
              >
                {{ zone.zone_name }}
              </option>
            </select>
          </div>

          <!-- Participants Section -->
          <div class="bg-white p-4 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-medium text-gray-700">
                <i class="fas fa-users mr-2 text-green-600"></i>
                ครูผู้เข้าร่วมการเยี่ยมบ้าน
              </label>
              <button
                @click="addParticipant"
                type="button"
                class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-md transition-colors"
              >
                <i class="fas fa-plus mr-1"></i>
                เพิ่มครู
              </button>
            </div>

            <div v-if="visitForm.participants.length === 0" class="text-sm text-gray-500 text-center py-4">
              ยังไม่มีครูผู้เข้าร่วม กรุณาเพิ่มอย่างน้อย 1 คน
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(participant, index) in visitForm.participants"
                :key="index"
                class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200"
              >
                <div class="flex-1">
                  <input
                    v-model="participant.participant_name"
                    type="text"
                    placeholder="ชื่อครู *"
                    required
                    class="w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"
                  >
                </div>
                <button
                  @click="removeParticipant(index)"
                  type="button"
                  class="flex-shrink-0 text-red-600 hover:text-red-800 p-2"
                  title="ลบ"
                >
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Observations & Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">การสังเกตการณ์</label>
            <textarea
              v-model="visitForm.observations"
              rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm placeholder:text-gray-400"
              placeholder="บันทึกสภาพแวดล้อมบ้าน สภาพความเป็นอยู่ และการสังเกตการณ์..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">บันทึกเพิ่มเติม</label>
            <textarea
              v-model="visitForm.notes"
              rows="2"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm placeholder:text-gray-400"
              placeholder="หมายเหตุ..."
            ></textarea>
          </div>

          <!-- เพิ่มฟิลด์ใหม่ที่นี่ -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ข้อเสนอแนะ</label>
            <textarea
              v-model="visitForm.recommendations"
              rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm placeholder:text-gray-400"
              placeholder="ข้อเสนอแนะสำหรับการพัฒนานักเรียน..."
            ></textarea>
          </div>

          <!-- <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">การติดตามผล</label>
            <textarea
              v-model="visitForm.follow_up"
              rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
              placeholder="แผนการติดตามผล..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">การเยี่ยมครั้งต่อไป</label>
            <input
              v-model="visitForm.next_visit"
              type="date"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
            >
          </div> -->
          <!-- จบฟิลด์ใหม่ -->

          <!-- Image Upload -->
          <div>
            <label class="text-sm font-medium text-gray-700 mb-2 flex items-center">
              <i class="fas fa-image mr-2 text-green-600"></i>
              อัพโหลดรูปภาพหลักฐาน
            </label>
            <div
              class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-lg p-6 cursor-pointer transition bg-white hover:bg-green-50"
              :class="{ 'border-green-500 bg-green-50': isDragging }"
              @dragover.prevent="onDragOver"
              @dragleave.prevent="onDragLeave"
              @drop.prevent="onDrop"
              @click="triggerFileInput"
            >
              <input ref="fileInput" @change="handleImageUpload" type="file" multiple accept="image/*" class="hidden">
              <div class="flex flex-col items-center">
                <i class="fas fa-cloud-upload-alt text-3xl text-green-500 mb-2"></i>
                <span class="text-sm text-gray-700 font-semibold">ลากไฟล์รูปภาพมาวาง หรือ <span class="underline text-green-700">คลิกเพื่อเลือกไฟล์</span></span>
                <span class="text-xs text-gray-500 mt-1">รองรับไฟล์ JPG, PNG, GIF (ขนาดจะถูกบีบอัดอัตโนมัติ)</span>
                <span v-if="visitForm.images && visitForm.images.length > 0" class="text-xs text-green-700 font-normal mt-1">
                  เลือกแล้ว {{ visitForm.images.length }} ไฟล์
                </span>
                <button v-if="visitForm.images && visitForm.images.length > 0" @click.stop="removeAllImages" type="button" class="mt-2 text-xs text-red-600 hover:underline">ลบทั้งหมด</button>
              </div>
              <div v-if="isDragging" class="absolute inset-0 bg-green-100 bg-opacity-50 flex items-center justify-center pointer-events-none rounded-lg">
                    <span class="text-green-700 font-normal text-lg">ปล่อยไฟล์ที่นี่เพื่ออัพโหลด</span>
              </div>
            </div>
          </div>

          <!-- Image Preview -->
          <div v-if="visitForm.images && visitForm.images.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div v-for="(image, index) in visitForm.images" :key="index" class="relative group">
              <img :src="image.preview" alt="Preview" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200">
              <button @click="removeImage(index)" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-80 group-hover:opacity-100 transition">×</button>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="toggleCreateForm"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors"
            >
              ยกเลิก
            </button>
            <button
              type="submit"
              :disabled="visitForm.participants.length === 0"
              class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-save mr-2"></i>
              บันทึกการเยี่ยมบ้าน
            </button>
          </div>
        </form>
      </div>

      <!-- Home Visits History - แสดงฟอร์มแก้ไขตลอดเวลา -->
      <div>
        <h4 class="text-md font-medium text-gray-900 mb-4 flex items-center">
          <i class="fas fa-history mr-2 text-green-600"></i>
          ประวัติการเยี่ยมบ้าน
        </h4>

        <div v-if="homeVisits && homeVisits.length > 0" class="space-y-6">
          <div
            v-for="visit in homeVisits"
            :key="visit.id"
            class="bg-white rounded-lg shadow-sm border-2 border-gray-200 overflow-hidden"
          >
            <!-- Visit Header -->
            <div class="p-4 bg-gray-50 border-b border-gray-200">
              <div class="flex items-start justify-between">
                <!-- <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                      <i class="fas fa-calendar-alt mr-2"></i>
                      {{ formatDate(visit.visit_date) }}
                    </span>
                    <span v-if="visit.visit_time" class="text-sm text-gray-600">
                      <i class="fas fa-clock mr-1"></i>
                      {{ visit.visit_time }}
                    </span>
                  </div>
                  

                  <div v-if="visit.participants && visit.participants.length > 0" class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="text-xs text-gray-500">ครูผู้เยี่ยม:</span>
                    <div
                      v-for="participant in visit.participants"
                      :key="participant.id"
                      class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800"
                    >
                      <i class="fas fa-user mr-1"></i>
                      {{ participant.participant_name }}
                    </div>
                  </div>
                  <div v-else class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-user mr-1"></i>
                    {{ visit.visitor_name || 'ไม่ระบุครูผู้เยี่ยม' }}
                  </div>
                </div> -->

                <!-- Actions -->
                <div class="flex items-center gap-2">
                  <button
                    v-if="visit.images && visit.images.length > 0"
                    @click="viewImages(visit)"
                    class="text-blue-600 hover:text-blue-800 text-sm px-3 py-1 rounded hover:bg-blue-50 flex items-center gap-1"
                  >
                    <Icon icon="heroicons:photo" class="w-4 h-4" />
                    รูปภาพ ({{ visit.images.length }})
                  </button>
                </div>
              </div>
            </div>

            <!-- Edit Form - แสดงฟอร์มแก้ไขตลอดเวลา -->
            <form @submit.prevent="saveVisit(visit)" class="p-4 space-y-4">
              <!-- Visit Date & Time -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เยี่ยม *</label>
                  <input
                    :value="formatDateForInput(visit.visit_date)"
                    @input="visit.visit_date = $event.target.value"
                    type="date"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">เวลาเยี่ยม</label>
                  <input
                    v-model="visit.visit_time"
                    type="time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                  >
                </div>
              </div>

              <!-- Zone Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                  โซน <span class="text-gray-500 text-xs">(ไม่บังคับ)</span>
                </label>
                <select
                  v-model="visit.zone_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                >
                  <option :value="null">ไม่ระบุโซน</option>
                  <option 
                    v-for="zone in zones" 
                    :key="zone.id" 
                    :value="zone.id"
                  >
                    {{ zone.zone_name }}
                  </option>
                </select>
                <p v-if="visit.zone" class="mt-1 text-xs text-gray-500">
                  โซนปัจจุบัน: {{ visit.zone.zone_name }}
                </p>
              </div>

              <!-- Participants Section -->
              <div class="bg-white p-4 rounded-lg border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                  <label class="block text-sm font-medium text-gray-700">
                    <Icon icon="heroicons:users-20-solid" class="w-5 h-5 inline mr-2 text-green-600" />
                    ครูผู้เข้าร่วมการเยี่ยมบ้าน
                  </label>
                  <button
                    @click="addParticipantToVisit(visit)"
                    type="button"
                    class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-md transition-colors"
                  >
                    <Icon icon="heroicons:plus-20-solid" class="w-4 h-4 mr-1" />
                    เพิ่มครู
                  </button>
                </div>

                <div v-if="!visit.participants || visit.participants.length === 0" class="text-sm text-gray-500 text-center py-4">
                  ยังไม่มีครูผู้เข้าร่วม กรุณาเพิ่มอย่างน้อย 1 คน
                </div>

                <div v-else class="space-y-3">
                  <div
                    v-for="(participant, index) in visit.participants"
                    :key="index"
                    class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200"
                  >
                    <div class="flex-1">
                      <input
                        v-model="participant.participant_name"
                        type="text"
                        placeholder="ชื่อครู *"
                        required
                        class="w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"
                      >
                    </div>
                    <button
                      @click="removeParticipantFromVisit(visit, index)"
                      type="button"
                      class="flex-shrink-0 text-red-600 hover:text-red-800 p-2"
                      title="ลบ"
                    >
                      <Icon icon="heroicons:trash-20-solid" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Observations & Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">การสังเกตการณ์</label>
                <textarea
                  v-model="visit.observations"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"
                  placeholder="บันทึกสภาพแวดล้อมบ้าน สภาพความเป็นอยู่ และการสังเกตการณ์..."
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">บันทึกเพิ่มเติม</label>
                <textarea
                  v-model="visit.notes"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"
                  placeholder="หมายเหตุ..."
                ></textarea>
              </div>

              <!-- Recommendations -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ข้อเสนอแนะ</label>
                <textarea
                  v-model="visit.recommendations"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"
                  placeholder="ข้อเสนอแนะสำหรับการพัฒนานักเรียน..."
                ></textarea>
              </div>

              <!-- Image Upload Section -->
              <div>
                <label class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                  <Icon icon="heroicons:photo-20-solid" class="w-5 h-5 mr-2 text-green-600" />
                  เพิ่มรูปภาพหลักฐาน
                </label>
                <div
                  class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-lg p-6 cursor-pointer transition bg-white hover:bg-green-50"
                  :class="{ 'border-green-500 bg-green-50': isDraggingInline[visit.id] }"
                  @dragover.prevent="onDragOverInline($event, visit.id)"
                  @dragleave.prevent="onDragLeaveInline($event, visit.id)"
                  @drop.prevent="onDropInline($event, visit.id)"
                  @click="triggerInlineFileInput(visit.id)"
                >
                  <input :ref="el => { if (el) fileInputRefs[visit.id] = el }" @change="handleInlineImageUpload($event, visit.id)" type="file" multiple accept="image/*" class="hidden">
                  <div class="flex flex-col items-center">
                    <Icon icon="heroicons:cloud-arrow-up-20-solid" class="w-8 h-8 text-green-500 mb-2" />
                    <span class="text-sm text-gray-700 font-normal">ลากไฟล์รูปภาพมาวาง หรือ <span class="underline text-green-700">คลิกเพื่อเลือกไฟล์</span></span>
                    <span class="text-xs text-gray-500 mt-1">รองรับไฟล์ JPG, PNG, GIF (ขนาดจะถูกบีบอัดอัตโนมัติ)</span>
                    <span v-if="visit.newImages && visit.newImages.length > 0" class="text-xs text-green-700 font-normal mt-1">
                      เลือกแล้ว {{ visit.newImages.length }} ไฟล์
                    </span>
                    <button v-if="visit.newImages && visit.newImages.length > 0" @click.stop="removeAllInlineImages(visit)" type="button" class="mt-2 text-xs text-red-600 hover:underline">ลบทั้งหมด</button>
                  </div>
                  <div v-if="isDraggingInline[visit.id]" class="absolute inset-0 bg-green-100 bg-opacity-50 flex items-center justify-center pointer-events-none rounded-lg">
                    <span class="text-green-700 font-normal text-lg">ปล่อยไฟล์ที่นี่เพื่ออัพโหลด</span>
                  </div>
                </div>
              </div>

              <!-- New Images Preview -->
              <div v-if="visit.newImages && visit.newImages.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div v-for="(image, index) in visit.newImages" :key="index" class="relative group">
                  <img :src="image.preview" alt="Preview" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200">
                  <button @click="removeInlineImage(visit, index)" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-80 group-hover:opacity-100 transition">×</button>
                </div>
              </div>

              <!-- Existing Images Management -->
              <div v-if="visit.images && visit.images.length > 0">
                <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพที่มีอยู่</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  <div 
                    v-for="(image, index) in visit.images.filter(img => !img._deleted)" 
                    :key="index" 
                    class="relative group"
                  >
                    <img :src="getImageThumbnail(image)" alt="Existing image" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200">
                    <button @click="removeExistingInlineImage(visit, index)" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-80 group-hover:opacity-100 transition">×</button>
                    <div class="absolute bottom-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded">
                      {{ index + 1 }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button
                  type="button"
                  @click="resetVisit(visit)"
                  class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium min-w-[100px]"
                >
                  คืนค่า
                </button>
                <button
                  type="submit"
                  :disabled="!visit.participants || visit.participants.length === 0 || savingVisits[visit.id]"
                  class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 min-w-[150px] justify-center"
                >
                  <Icon v-if="savingVisits[visit.id]" icon="heroicons:arrow-path-20-solid" class="w-4 h-4 animate-spin" />
                  <Icon v-else icon="heroicons:check-circle-20-solid" class="w-4 h-4" />
                  {{ savingVisits[visit.id] ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-else class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-300">
          <i class="fas fa-home text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500 mb-2">ยังไม่มีประวัติการเยี่ยมบ้าน</p>
          <button
            @click="toggleCreateForm"
            class="inline-flex items-center px-4 py-2 text-sm text-green-600 hover:text-green-700 font-medium"
          >
            <i class="fas fa-plus mr-2"></i>
            สร้างการเยี่ยมบ้านแรก
          </button>
        </div>
      </div>
    </div>

    <!-- Image Gallery Modal -->
    <ImageGalleryModal
      :show="showImageGallery"
      :visit="selectedVisit"
      :images="selectedVisit?.images || []"
      @close="closeImageGallery"
    />
  </div>
</template>
<script setup>
// Format date string for <input type="date"> (yyyy-MM-dd)
function formatDateForInput(dateString) {
  if (!dateString) return ''
  // If already yyyy-MM-dd, return as is
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString
  // If ISO format, extract date part
  if (dateString.includes('T')) return dateString.split('T')[0]
  // Otherwise, try to parse
  const d = new Date(dateString)
  if (isNaN(d)) return ''
  return d.toISOString().split('T')[0]
}
import { ref, onMounted, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import ImageGalleryModal from './ImageGalleryModal.vue'

const props = defineProps({
  homeVisits: Array,
  visitForm: Object,
  zones: {
    type: Array,
    default: () => []
  },
  createNewHomeVisit: Function,
  handleImageUpload: Function,
  removeImage: Function,
})

const emit = defineEmits(['view-visit-images', 'visit-updated'])

// State
const showCreateForm = ref(false)
const isDragging = ref(false)
const fileInput = ref(null)
const showImageGallery = ref(false)
const selectedVisit = ref(null)

// State สำหรับจัดการการแก้ไขแบบ inline
const isDraggingInline = ref({})
const fileInputRefs = ref({})
const savingVisits = ref({})
const originalVisits = ref({}) // เก็บข้อมูลเดิมเพื่อคืนค่า

// Initialize form
onMounted(() => {
  initializeForm()
  initializeVisitsForEditing()
})

function initializeForm() {
  const now = new Date()
  const yyyy = now.getFullYear()
  const mm = String(now.getMonth() + 1).padStart(2, '0')
  const dd = String(now.getDate()).padStart(2, '0')
  const hh = String(now.getHours()).padStart(2, '0')
  const min = String(now.getMinutes()).padStart(2, '0')
  
  props.visitForm.visit_date = `${yyyy}-${mm}-${dd}`
  props.visitForm.visit_time = `${hh}:${min}`
  
  if (!props.visitForm.participants) {
    props.visitForm.participants = []
  }
  
  if (props.visitForm.participants.length === 0) {
    addParticipant()
  }

  props.visitForm.recommendations = props.visitForm.recommendations || ''
  props.visitForm.follow_up = props.visitForm.follow_up || ''
  props.visitForm.next_visit = props.visitForm.next_visit || ''
}

// เตรียมข้อมูลการเยี่ยมบ้านสำหรับการแก้ไข
function initializeVisitsForEditing() {
  if (props.homeVisits) {
    props.homeVisits.forEach(visit => {
      // เก็บข้อมูลเดิมไว้
      originalVisits.value[visit.id] = JSON.parse(JSON.stringify(visit))
      
      // เตรียมพร็อพเพอร์ตี้ที่จำเป็นสำหรับการแก้ไข
      if (!visit.newImages) visit.newImages = []
      if (!visit.imagesToDelete) visit.imagesToDelete = []
    })
  }
}

function toggleCreateForm() {
  showCreateForm.value = !showCreateForm.value
  if (showCreateForm.value && props.visitForm.participants.length === 0) {
    addParticipant()
  }
}

function viewImages(visit) {
  selectedVisit.value = visit
  showImageGallery.value = true
}

function closeImageGallery() {
  showImageGallery.value = false
  selectedVisit.value = null
}

// บันทึกการแก้ไขสำหรับแต่ละรายการ
async function saveVisit(visit) {
  if (!visit.participants || visit.participants.length === 0) {
    await Swal.fire({
      title: 'ข้อมูลไม่ครบถ้วน',
      text: 'กรุณาเพิ่มครูผู้เข้าร่วมอย่างน้อย 1 คน',
      icon: 'warning',
    })
    return
  }
  
  savingVisits.value[visit.id] = true
  
  try {
    const formData = new FormData()
    
    // Add basic visit data
    formData.append('visit_date', visit.visit_date)
    formData.append('visit_time', visit.visit_time)
    formData.append('observations', visit.observations || '')
    formData.append('notes', visit.notes || '')
    formData.append('recommendations', visit.recommendations || '')
    
    // Add zone_id if selected
    if (visit.zone_id) {
      formData.append('zone_id', visit.zone_id)
    }
    
    // Add participants
    visit.participants.forEach((participant, index) => {
      formData.append(`participants[${index}][participant_name]`, participant.participant_name)
    })
    
    // Add new images
    if (visit.newImages) {
      visit.newImages.forEach((image, index) => {
        formData.append(`new_images[${index}]`, image.file)
      })
    }
    
    // Add images to delete
    if (visit.imagesToDelete) {
      visit.imagesToDelete.forEach((imageId) => {
        formData.append('images_to_delete[]', imageId)
      })
    }
    
    const response = await fetch(`/api/home-visit/${visit.id}/update`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: formData
    })
    
    const result = await response.json()
    
    if (result.success) {
      await Swal.fire({
        title: 'สำเร็จ',
        text: 'อัปเดตข้อมูลการเยี่ยมบ้านเรียบร้อย',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
      
      // อัปเดตข้อมูลใน array และข้อมูลเดิม
      Object.assign(visit, result.visit)
      originalVisits.value[visit.id] = JSON.parse(JSON.stringify(result.visit))
      
      // ล้างข้อมูลรูปภาพชั่วคราว
      visit.newImages = []
      visit.imagesToDelete = []
    } else {
      await Swal.fire({
        title: 'เกิดข้อผิดพลาด',
        text: result.message || 'ไม่สามารถอัปเดตข้อมูลได้',
        icon: 'error',
      })
    }
  } catch (err) {
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด',
      text: err.message || 'ไม่สามารถอัปเดตข้อมูลได้',
      icon: 'error',
    })
  } finally {
    savingVisits.value[visit.id] = false
  }
}

// คืนค่าข้อมูลเดิม
function resetVisit(visit) {
  const original = originalVisits.value[visit.id]
  if (original) {
    Object.assign(visit, JSON.parse(JSON.stringify(original)))
    // เตรียมพร็อพเพอร์ตี้ที่จำเป็นสำหรับการแก้ไขใหม่
    visit.newImages = []
    visit.imagesToDelete = []
  }
}

// Participants management
function addParticipant() {
  props.visitForm.participants.push({
    participant_name: ''
  })
}

function removeParticipant(index) {
  props.visitForm.participants.splice(index, 1)
}

function addParticipantToVisit(visit) {
  if (!visit.participants) {
    visit.participants = []
  }
  visit.participants.push({
    participant_name: ''
  })
}

function removeParticipantFromVisit(visit, index) {
  visit.participants.splice(index, 1)
}

// Image handling for inline edit
function triggerInlineFileInput(visitId) {
  nextTick(() => {
    const input = fileInputRefs.value[visitId]
    if (input) {
      input.click()
    }
  })
}

function onDragOverInline(e, visitId) {
  isDraggingInline.value[visitId] = true
}

function onDragLeaveInline(e, visitId) {
  isDraggingInline.value[visitId] = false
}

function onDropInline(e, visitId) {
  isDraggingInline.value[visitId] = false
  const files = Array.from(e.dataTransfer.files)
  if (files.length) {
    const event = { target: { files } }
    handleInlineImageUpload(event, visitId)
  }
}

async function handleInlineImageUpload(event, visitId) {
  const files = Array.from(event.target.files)
  const visit = props.homeVisits.find(v => v.id === visitId)
  
  if (!visit) return
  
  if (!visit.newImages) {
    visit.newImages = []
  }
  
  for (const file of files) {
    const compressedFile = await compressImage(file)
    
    const reader = new FileReader()
    reader.onload = (e) => {
      visit.newImages.push({
        file: compressedFile,
        preview: e.target.result
      })
    }
    reader.readAsDataURL(compressedFile)
  }
}

function removeInlineImage(visit, index) {
  visit.newImages.splice(index, 1)
}

function removeAllInlineImages(visit) {
  visit.newImages = []
}

function removeExistingInlineImage(visit, index) {
  const actualImage = visit.images[index]
  
  if (actualImage) {
    if (!visit.imagesToDelete) {
      visit.imagesToDelete = []
    }
    
    visit.imagesToDelete.push(actualImage.id)
    visit.images[index]._deleted = true
  }
}

// Compress image function
async function compressImage(file, maxWidth = 1200, quality = 0.8) {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const img = new Image()
      img.onload = () => {
        const canvas = document.createElement('canvas')
        let width = img.width
        let height = img.height
        
        if (width > maxWidth) {
          height = (height * maxWidth) / width
          width = maxWidth
        }
        
        canvas.width = width
        canvas.height = height
        
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)
        
        canvas.toBlob(
          (blob) => {
            const compressedFile = new File([blob], file.name, {
              type: 'image/jpeg',
              lastModified: Date.now()
            })
            resolve(compressedFile)
          },
          'image/jpeg',
          quality
        )
      }
      img.src = e.target.result
    }
    reader.readAsDataURL(file)
  })
}

// Image handling for create form
function triggerFileInput() {
  if (fileInput.value) fileInput.value.click()
}

function onDragOver(e) {
  isDragging.value = true
}

function onDragLeave(e) {
  isDragging.value = false
}

function onDrop(e) {
  isDragging.value = false
  const files = Array.from(e.dataTransfer.files)
  if (files.length) {
    const event = { target: { files } }
    props.handleImageUpload(event)
  }
}

function removeAllImages() {
  if (props.visitForm.images) {
    props.visitForm.images.splice(0, props.visitForm.images.length)
  }
}

// Submit
function submitHomeVisit() {
  if (props.visitForm.participants.length === 0) {
    alert('กรุณาเพิ่มครูผู้เข้าร่วมอย่างน้อย 1 คน')
    return
  }
  
  props.createNewHomeVisit()
  showCreateForm.value = false
}

// Date formatting
function formatDate(dateString) {
  if (!dateString) return 'ไม่ระบุวันที่'
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getImageThumbnail(image) {
  if (!image) return '/images/placeholder.png'
  
  if (image.image_url) return image.image_url
  if (image.image_path) {
    const path = image.image_path.replace(/^public\//, '')
    return `/storage/${path}`
  }
  return '/images/placeholder.png'
}
</script>


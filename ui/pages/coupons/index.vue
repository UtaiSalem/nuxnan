<template>
  <div class="coupons-page">
    <div class="page-header">
      <h1 class="page-title">คูปองของฉัน</h1>
      <button 
        @click="showCreateModal = true" 
        class="create-btn"
      >
        <span class="btn-icon">➕</span>
        <span>สร้างคูปอง</span>
      </button>
    </div>

    <CouponList v-if="!showCreateModal" />

    <div class="modal-overlay" v-if="showCreateModal" @click="showCreateModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>สร้างคูปองใหม่</h2>
          <button @click="showCreateModal = false" class="close-btn">
            ✕
          </button>
        </div>
        
        <CouponCreationForm 
          @created="handleCouponCreated"
          @cancel="showCreateModal = false"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import CouponList from '~/components/coupons/CouponList.vue'
import CouponCreationForm from '~/components/coupons/CouponCreationForm.vue'

const showCreateModal = ref(false)

function handleCouponCreated(coupon: any) {
  showCreateModal.value = false
  // Refresh the coupon list
  // In a real app, you might emit an event to refresh the list
  location.reload()
}
</script>

<style scoped>
.coupons-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  background: white;
  padding: 20px 24px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.create-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  background: #6366f1;
  color: white;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.create-btn:hover {
  background: #4f46e5;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.create-btn .btn-icon {
  font-size: 20px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0;
}

.close-btn {
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: #fee2e2;
  color: white;
}
</style>

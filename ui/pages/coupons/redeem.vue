<template>
  <div class="redeem-page">
    <div class="page-header">
      <h1 class="page-title">รับคูปอง</h1>
      <p class="page-subtitle">
        สแกน QR Code หรือป้อนรหัสคูปองเพื่อรับแต้มหรือเงิน
      </p>
    </div>

    <CouponRedemption />
  </div>
</template>

<script setup lang="ts">
import CouponRedemption from '~/components/coupons/CouponRedemption.vue'
</script>

<style scoped>
.redeem-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 24px;
}

.page-header {
  text-align: center;
  margin-bottom: 32px;
  background: white;
  padding: 32px 24px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 12px;
}

.page-subtitle {
  font-size: 16px;
  color: #6b7280;
  line-height: 1.6;
  margin: 0;
}
</style>

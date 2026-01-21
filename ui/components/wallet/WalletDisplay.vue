<template>
  <div class="wallet-display">
    <!-- Wallet Card -->
    <div class="wallet-card">
      <div class="wallet-header">
        <div class="wallet-icon">
          <i class="lni lni-wallet"></i>
        </div>
        <div class="wallet-info">
          <h3 class="wallet-title">กระเป๋าเงิน</h3>
          <p class="wallet-subtitle">Wallet</p>
        </div>
      </div>
      
      <div class="wallet-amount">
        <span class="wallet-value">{{ formatMoney(wallet) }}</span>
      </div>
      
      <!-- Action Buttons -->
      <div class="wallet-actions">
        <button 
          class="btn btn-primary btn-sm"
          @click="showDepositModal = true"
        >
          <i class="lni lni-plus"></i>
          เติมเงิน
        </button>
        <button 
          class="btn btn-outline-primary btn-sm"
          @click="showWithdrawModal = true"
          :disabled="wallet < 10"
        >
          <i class="lni lni-minus"></i>
          ถอนเงิน
        </button>
        <button 
          class="btn btn-outline-secondary btn-sm"
          @click="showTransferModal = true"
          :disabled="wallet < 10"
        >
          <i class="lni lni-exchange"></i>
          โอน
        </button>
        <button 
          class="btn btn-outline-secondary btn-sm"
          @click="showHistoryModal = true"
        >
          <i class="lni lni-list"></i>
          ประวัติ
        </button>
      </div>
    </div>
    
    <!-- Deposit Modal -->
    <div v-if="showDepositModal" class="modal-overlay" @click="showDepositModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>เติมเงินเข้ากระเป๋า</h3>
          <button class="close-btn" @click="showDepositModal = false">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="current-balance">
            <p>ยอดเงินปัจจุบัน: <strong>{{ formatMoney(wallet) }}</strong></p>
          </div>
          
          <div class="deposit-form">
            <label for="deposit-amount">จำนวนเงิน:</label>
            <div class="amount-input-group">
              <input 
                type="number" 
                id="deposit-amount"
                v-model.number="depositAmount"
                :min="10"
                step="10"
                class="form-control"
                placeholder="ระบุจำนวนเงิน"
              >
              <span class="currency-label">บาท</span>
            </div>
            
            <!-- Quick Amount Buttons -->
            <div class="quick-amounts">
              <button 
                v-for="amount in quickDepositAmounts"
                :key="amount"
                class="btn btn-outline-secondary btn-sm"
                @click="depositAmount = amount"
              >
                {{ amount }} บาท
              </button>
            </div>
            
            <div class="form-group">
              <label for="deposit-method">วิธีการชำระเงิน:</label>
              <select 
                id="deposit-method"
                v-model="depositMethod"
                class="form-control"
              >
                <option value="promptpay">PromptPay</option>
                <option value="bank_transfer">โอนเงินผ่านธนาคาร</option>
                <option value="credit_card">บัตรเครดิต/เดบิต</option>
              </select>
            </div>
          </div>
          
          <div class="modal-footer">
            <button 
              class="btn btn-secondary"
              @click="showDepositModal = false"
            >
              ยกเลิก
            </button>
            <button 
              class="btn btn-primary"
              @click="depositMoney"
              :disabled="!canDeposit || isDepositing"
            >
              <span v-if="isDepositing">
                <i class="lni lni-spinner lni-spin"></i>
                กำลังดำเนินการ...
              </span>
              <span v-else>เติมเงิน</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Withdraw Modal -->
    <div v-if="showWithdrawModal" class="modal-overlay" @click="showWithdrawModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>ถอนเงิน</h3>
          <button class="close-btn" @click="showWithdrawModal = false">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="current-balance">
            <p>ยอดเงินที่ถอนได้: <strong>{{ formatMoney(wallet) }}</strong></p>
          </div>
          
          <div class="withdraw-form">
            <label for="withdraw-amount">จำนวนเงิน:</label>
            <div class="amount-input-group">
              <input 
                type="number" 
                id="withdraw-amount"
                v-model.number="withdrawAmount"
                :max="wallet"
                :min="25"
                step="1"
                class="form-control"
                placeholder="ระบุจำนวนเงิน (ขั้นต่ำ 25 บาท)"
              >
              <span class="currency-label">บาท</span>
            </div>
            
            <!-- Fee Calculation -->
            <div class="fee-info" v-if="withdrawAmount > 0">
              <p>ค่าธรรมเนียม: <strong>{{ formatMoney(calculateFee(withdrawAmount)) }}</strong></p>
              <p class="net-amount">ยอดที่ได้รับ: <strong class="text-success">{{ formatMoney(getNetAmount(withdrawAmount)) }}</strong></p>
            </div>
            
            <div class="form-group">
              <label for="withdraw-method">วิธีการรับเงิน:</label>
              <select 
                id="withdraw-method"
                v-model="withdrawMethod"
                class="form-control"
              >
                <option value="bank_transfer">โอนเงินเข้าบัญชีธนาคาร</option>
              </select>
            </div>
            
            <div class="bank-account-form" v-if="withdrawMethod === 'bank_transfer'">
              <div class="form-group">
                <label for="bank-name">ธนาคาร:</label>
                <select 
                  id="bank-name"
                  v-model="bankAccount.bank_name"
                  class="form-control"
                >
                  <option value="">เลือกธนาคาร</option>
                  <option value="kbank">ธนาคารกสิกรไทย (KBANK)</option>
                  <option value="krungthai">ธนาคารกรุงไทย (KTB)</option>
                  <option value="scb">ธนาคารไทยพาณิชย์ (SCB)</option>
                  <option value="bbl">ธนาคารกรุงเทพ (BBL)</option>
                  <option value="bay">ธนาคารกรุงศรีอยุธยา (BAY)</option>
                  <option value="gsb">ธนาคารออมสิน (GSB)</option>
                  <option value="tmb">ธนาคารทหารไทยธนชาต (TTB)</option>
                  <option value="cimb">ธนาคารซีไอเอ็มบีไทย (CIMB)</option>
                  <option value="uob">ธนาคารยูโอบี (UOB)</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="account-number">เลขบัญชี:</label>
                <input 
                  type="text" 
                  id="account-number"
                  v-model="bankAccount.account_number"
                  class="form-control"
                  placeholder="เลขบัญชี 10 หลัก"
                  maxlength="10"
                >
              </div>
              
              <div class="form-group">
                <label for="account-name">ชื่อบัญชี:</label>
                <input 
                  type="text" 
                  id="account-name"
                  v-model="bankAccount.account_name"
                  class="form-control"
                  placeholder="ชื่อ-นามสกุล"
                >
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button 
              class="btn btn-secondary"
              @click="showWithdrawModal = false"
            >
              ยกเลิก
            </button>
            <button 
              class="btn btn-primary"
              @click="withdrawMoney"
              :disabled="!canWithdraw || isWithdrawing"
            >
              <span v-if="isWithdrawing">
                <i class="lni lni-spinner lni-spin"></i>
                กำลังดำเนินการ...
              </span>
              <span v-else>ถอนเงิน</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Transfer Modal -->
    <div v-if="showTransferModal" class="modal-overlay" @click="showTransferModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>โอนเงิน</h3>
          <button class="close-btn" @click="showTransferModal = false">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="current-balance">
            <p>ยอดเงินปัจจุบัน: <strong>{{ formatMoney(wallet) }}</strong></p>
          </div>
          
          <div class="transfer-form">
            <div class="form-group">
              <label for="recipient-id">รหัสผู้รับ:</label>
              <input 
                type="number" 
                id="recipient-id"
                v-model.number="transferData.recipient_id"
                class="form-control"
                placeholder="ระบุรหัสผู้รับ"
              >
            </div>
            
            <div class="form-group">
              <label for="transfer-amount">จำนวนเงิน:</label>
              <div class="amount-input-group">
                <input 
                  type="number" 
                  id="transfer-amount"
                  v-model.number="transferData.amount"
                  :max="wallet"
                  :min="10"
                  step="10"
                  class="form-control"
                  placeholder="ระบุจำนวนเงิน"
                >
                <span class="currency-label">บาท</span>
              </div>
            </div>
            
            <div class="form-group">
              <label for="transfer-message">ข้อความ (ไม่บังคับ):</label>
              <textarea 
                id="transfer-message"
                v-model="transferData.message"
                class="form-control"
                rows="3"
                placeholder="ข้อความประกอบการโอน"
              ></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button 
              class="btn btn-secondary"
              @click="showTransferModal = false"
            >
              ยกเลิก
            </button>
            <button 
              class="btn btn-primary"
              @click="transferMoney"
              :disabled="!canTransfer || isTransferring"
            >
              <span v-if="isTransferring">
                <i class="lni lni-spinner lni-spin"></i>
                กำลังโอน...
              </span>
              <span v-else>โอนเงิน</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
      <div class="modal-content modal-lg" @click.stop>
        <div class="modal-header">
          <h3>ประวัติธุรกรรม</h3>
          <button class="close-btn" @click="showHistoryModal = false">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <!-- Filters -->
          <div class="transaction-filters">
            <select 
              v-model="walletFilters.type" 
              class="form-control form-control-sm"
              @change="loadWalletTransactions"
            >
              <option value="">ทั้งหมด</option>
              <option value="deposit">เติมเงิน</option>
              <option value="withdraw">ถอนเงิน</option>
              <option value="transfer">โอนเงิน</option>
              <option value="conversion">แลกแต้ม</option>
              <option value="reward">รับรางวัล</option>
              <option value="admin_adjust">ปรับจากระบบ</option>
            </select>
          </div>
          
          <!-- Transactions List -->
          <div class="transactions-list" v-if="!isLoadingTransactions">
            <div 
              v-for="transaction in walletTransactions" 
              :key="transaction.id"
              class="transaction-item"
            >
              <div class="transaction-icon" :class="transaction.type">
                <i :class="getWalletTransactionIcon(transaction.type)"></i>
              </div>
              
              <div class="transaction-details">
                <h4 class="transaction-title">{{ transaction.description || getWalletTypeLabel(transaction.type) }}</h4>
                <p class="transaction-meta">
                  {{ formatDate(transaction.created_at) }}
                  <span v-if="transaction.reference_number"> • Ref: {{ transaction.reference_number }}</span>
                </p>
              </div>
              
              <div class="transaction-amount" :class="transaction.type">
                <span v-if="['deposit', 'reward'].includes(transaction.type)">+</span>
                <span v-else>-</span>
                {{ formatMoney(transaction.amount) }}
              </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="walletTransactions.length === 0" class="empty-state">
              <i class="lni lni-empty-file"></i>
              <p>ไม่มีประวัติธุรกรรม</p>
            </div>
          </div>
          
          <!-- Loading State -->
          <div v-else class="loading-state">
            <i class="lni lni-spinner lni-spin"></i>
            <p>กำลังโหลด...</p>
          </div>
          
          <!-- Pagination -->
          <div class="pagination" v-if="walletPagination.total_pages > 1">
            <button 
              class="btn btn-outline-primary btn-sm"
              @click="loadWalletPage(walletPagination.current_page - 1)"
              :disabled="walletPagination.current_page <= 1"
            >
              <i class="lni lni-chevron-left"></i>
            </button>
            
            <span class="page-info">
              หน้า {{ walletPagination.current_page }} / {{ walletPagination.total_pages }}
            </span>
            
            <button 
              class="btn btn-outline-primary btn-sm"
              @click="loadWalletPage(walletPagination.current_page + 1)"
              :disabled="walletPagination.current_page >= walletPagination.total_pages"
            >
              <i class="lni lni-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useWallet } from '~/composables/useWallet'

const { 
  wallet, 
  isLoading, 
  deposit, 
  withdraw, 
  transfer, 
  getTransactions,
  formatMoney,
  canWithdraw,
  calculateFee,
  getNetAmount,
} = useWallet()

// Modal states
const showDepositModal = ref(false)
const showWithdrawModal = ref(false)
const showTransferModal = ref(false)
const showHistoryModal = ref(false)

// Deposit
const depositAmount = ref(0)
const depositMethod = ref('promptpay')
const isDepositing = ref(false)
const quickDepositAmounts = [100, 500, 1000, 5000]

// Withdraw
const withdrawAmount = ref(0)
const withdrawMethod = ref('bank_transfer')
const isWithdrawing = ref(false)
const bankAccount = ref({
  bank_name: '',
  account_number: '',
  account_name: '',
})

// Transfer
const transferData = ref({
  recipient_id: 0,
  amount: 0,
  message: '',
})
const isTransferring = ref(false)

// Transactions
const walletTransactions = ref<any[]>([])
const isLoadingTransactions = ref(false)
const walletFilters = ref({
  type: '',
  date_from: '',
  date_to: '',
})
const walletPagination = ref({
  current_page: 1,
  total_pages: 1,
  per_page: 20,
  total: 0,
})

// Computed
const canDeposit = computed(() => {
  return depositAmount.value >= 10
})

const canWithdraw = computed(() => {
  return withdrawAmount.value >= 10 && withdrawAmount.value <= wallet.value
})

const canTransfer = computed(() => {
  return transferData.value.recipient_id > 0 && 
         transferData.value.amount >= 10 && 
         transferData.value.amount <= wallet.value
})

// Methods
const depositMoney = async () => {
  if (!canDeposit.value) return
  
  try {
    isDepositing.value = true
    await deposit({
      amount: depositAmount.value,
      method: depositMethod.value,
      description: `เติมเงิน ${depositAmount.value} บาท`,
    })
    
    // Show success notification
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'เติมเงินสำเร็จ!',
        text: `เติมเงิน ${formatMoney(depositAmount.value)} เรียบร้อยแล้ว`,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
    
    // Reset and close modal
    depositAmount.value = 0
    showDepositModal.value = false
  } catch (error: any) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'เติมเงินไม่สำเร็จ',
        text: error.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
        confirmButtonText: 'ตกลง',
      })
    }
  } finally {
    isDepositing.value = false
  }
}

const withdrawMoney = async () => {
  if (!canWithdraw.value) return
  
  try {
    isWithdrawing.value = true
    await withdraw({
      amount: withdrawAmount.value,
      method: withdrawMethod.value,
      bank_account: bankAccount.value,
      description: `ถอนเงิน ${withdrawAmount.value} บาท`,
    })
    
    // Show success notification
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'ยื่นคำขอถอนเงินสำเร็จ!',
        html: `
          <p>ถอนเงิน ${formatMoney(withdrawAmount.value)}</p>
          <p>ค่าธรรมเนียม ${formatMoney(calculateFee(withdrawAmount.value))}</p>
          <p>ยอดที่จะได้รับ <strong>${formatMoney(getNetAmount(withdrawAmount.value))}</strong></p>
          <p class="text-muted">รอการอนุมัติ 1-3 วันทำการ</p>
        `,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
    
    // Reset and close modal
    withdrawAmount.value = 0
    bankAccount.value = {
      bank_name: '',
      account_number: '',
      account_name: '',
    }
    showWithdrawModal.value = false
  } catch (error: any) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'ถอนเงินไม่สำเร็จ',
        text: error.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
        confirmButtonText: 'ตกลง',
      })
    }
  } finally {
    isWithdrawing.value = false
  }
}

const transferMoney = async () => {
  if (!canTransfer.value) return
  
  try {
    isTransferring.value = true
    await transfer(transferData.value)
    
    // Show success notification
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'โอนเงินสำเร็จ!',
        text: `โอนเงิน ${formatMoney(transferData.value.amount)} ไปยังผู้รับเรียบร้อยแล้ว`,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
    
    // Reset and close modal
    transferData.value = {
      recipient_id: 0,
      amount: 0,
      message: '',
    }
    showTransferModal.value = false
  } catch (error: any) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'โอนเงินไม่สำเร็จ',
        text: error.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
        confirmButtonText: 'ตกลง',
      })
    }
  } finally {
    isTransferring.value = false
  }
}

const loadWalletTransactions = async () => {
  try {
    isLoadingTransactions.value = true
    const response = await getTransactions({
      ...walletFilters.value,
      page: walletPagination.value.current_page,
      per_page: walletPagination.value.per_page,
    })
    
    walletTransactions.value = response.transactions || []
    walletPagination.value = {
      current_page: response.current_page || 1,
      total_pages: response.total_pages || 1,
      per_page: response.per_page || 20,
      total: response.total || 0,
    }
  } catch (error) {
    console.error('Load wallet transactions error:', error)
  } finally {
    isLoadingTransactions.value = false
  }
}

const loadWalletPage = (page: number) => {
  walletPagination.value.current_page = page
  loadWalletTransactions()
}

const getWalletTransactionIcon = (type: string): string => {
  const icons: Record<string, string> = {
    deposit: 'lni-plus',
    withdraw: 'lni-minus',
    transfer: 'lni-exchange',
    conversion: 'lni-money',
    reward: 'lni-gift',
    admin_adjust: 'lni-cog',
  }
  return icons[type] || 'lni-wallet'
}

const getWalletTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    deposit: 'เติมเงิน',
    withdraw: 'ถอนเงิน',
    transfer: 'โอนเงิน',
    conversion: 'แลกแต้ม',
    reward: 'รับรางวัล',
    admin_adjust: 'ปรับจากระบบ',
  }
  return labels[type] || type
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.wallet-display {
  width: 100%;
}

.wallet-card {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  border-radius: 16px;
  padding: 24px;
  color: white;
  box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
}

.wallet-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.wallet-icon {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.wallet-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.wallet-subtitle {
  font-size: 12px;
  opacity: 0.8;
  margin: 0;
}

.wallet-amount {
  text-align: center;
  margin-bottom: 24px;
}

.wallet-value {
  font-size: 48px;
  font-weight: 700;
  display: block;
  line-height: 1;
}

.wallet-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.wallet-actions .btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-lg {
  max-width: 700px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
}

.modal-body {
  padding: 20px;
}

.current-balance {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
}

.current-balance p {
  margin: 0;
  font-size: 14px;
}

/* Form Styles */
.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  font-size: 14px;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.amount-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.amount-input-group input {
  flex: 1;
}

.currency-label {
  font-size: 14px;
  color: #6b7280;
  white-space: nowrap;
}

.quick-amounts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.quick-amounts .btn {
  padding: 6px 12px;
  font-size: 12px;
}

.fee-info {
  background: #fff7ed;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
}

.fee-info p {
  margin: 0 0 8px 0;
  font-size: 14px;
}

.fee-info p:last-child {
  margin-bottom: 0;
}

.net-amount {
  font-size: 16px;
  margin-top: 8px;
}

.text-success {
  color: #10b981;
}

.text-muted {
  color: #6b7280;
  font-size: 12px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.modal-footer .btn {
  padding: 8px 20px;
}

/* Transaction Filters */
.transaction-filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.transaction-filters select {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

/* Transactions List */
.transactions-list {
  max-height: 400px;
  overflow-y: auto;
}

.transaction-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.transaction-item:hover {
  background: #f9fafb;
}

.transaction-item:last-child {
  border-bottom: none;
}

.transaction-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.transaction-icon.deposit,
.transaction-icon.reward {
  background: #ecfdf5;
  color: #10b981;
}

.transaction-icon.withdraw {
  background: #fef2f2;
  color: #ef4444;
}

.transaction-icon.transfer {
  background: #fff7ed;
  color: #f97316;
}

.transaction-icon.conversion {
  background: #faf5ff;
  color: #a855f7;
}

.transaction-icon.admin_adjust {
  background: #eff6ff;
  color: #3b82f6;
}

.transaction-details {
  flex: 1;
  min-width: 0;
}

.transaction-title {
  font-size: 14px;
  font-weight: 500;
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.transaction-meta {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

.transaction-amount {
  font-size: 16px;
  font-weight: 600;
  flex-shrink: 0;
}

.transaction-amount.deposit,
.transaction-amount.reward {
  color: #10b981;
}

.transaction-amount.withdraw,
.transaction-amount.transfer,
.transaction-amount.conversion {
  color: #ef4444;
}

.transaction-amount.admin_adjust {
  color: #3b82f6;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #9ca3af;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 12px;
  display: block;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.loading-state i {
  font-size: 32px;
  margin-bottom: 12px;
  display: block;
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
}

.lni-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>

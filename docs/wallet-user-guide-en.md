# Wallet and Points System User Guide

## System Overview

The Nuxnan wallet and points system is designed to allow users to:
- Earn points from system activities
- Convert points to money
- Deposit and withdraw money
- Transfer money to other users
- Redeem various rewards

---

## 1. Points System

### Point Types
- **Primary Points (PP)**: Points earned from participating in the system
- **Bonus Points**: Special points from special activities

### How to Earn Points

#### 1.1 Posting & Social Activities
| Activity | Points Earned | Details |
|---------|--------------|-----------|
| Create regular post | +180 points | Create a new text post |
| Create post with images | +240 points | Create a post with images |
| Receive like | +12 points/like | When others like your post |
| Receive share | +18 points/share | When others share your post |

#### 1.2 Learning Activities
| Activity | Points Earned | Details |
|---------|--------------|-----------|
| Correct quiz answers | +score earned | Based on quiz score |
| Submit assignment | +5-20 points | Based on assignment type |
| Access lesson | +10-50 points | Based on lesson level |

#### 1.3 Special Activities
| Activity | Points Earned | Details |
|---------|--------------|-----------|
| Vote | +points_per_vote | From poll pool |
| Watch advertisement | +4% × duration | Converted to wallet money |
| Receive donation | +240 points | Per donation |
| Daily Login | +10-50 points | Based on streak |

#### 1.4 Streak Bonus (Consecutive Login Bonus)
```
Day 1-3:   10 points/day
Day 4-7:   20 points/day
Day 8-14:  30 points/day
Day 15-30: 50 points/day
Day 31+:   100 points/day
```

#### 1.5 Level Up Bonus
- Earn **100 × level** points when leveling up

---

## 2. Spending Points

### Activities That Use Points
| Activity | Points Used | Details |
|---------|------------|-----------|
| Like | 24 points | Like a post |
| Unlike | 12 points | Unlike a post |
| Dislike | 12 points | Dislike a post |
| Share post | 36 points | Share a post |
| Comment | 12 points | Add a comment |
| Reply comment | 12 points | Reply to a comment |
| Access lesson | point_tuition_fee | As specified |
| Create course | 100 points | Create a new course |
| Create poll | 180 + pool | Create a poll with rewards |
| Donate | 270 points | Donate to others |
| Convert to money | 1,080 points = 1 THB | Exchange for money |

---

## 3. Wallet System

### Wallet Money Types
- **Cash Balance**: Money that can be withdrawn
- **Reward Balance**: Money earned from achieving goals
- **Locked Balance**: Money awaiting unlock

### 3.1 Converting Points to Money

**Exchange Rate:**
- **1,080 points = 1 THB**

**How to do it:**
```javascript
// API Call Example
POST /api/points/convert
{
  "points": 1080  // Number of points to convert
}

// Response
{
  "success": true,
  "points_converted": 1080,
  "wallet_amount": 1.00,
  "new_points_balance": 0,
  "new_wallet_balance": 1.00
}
```

### 3.2 Depositing Money

**Deposit Methods:**
- Bank Transfer
- Credit Card

**API Call:**
```javascript
POST /api/wallet/deposit
{
  "amount": 1000,
  "method": "bank_transfer",
  "reference": "REF123456",
  "description": "Deposit money"
}
```

### 3.3 Withdrawing Money

**Withdrawal Conditions:**
- Minimum withdrawal: **100 THB**
- Maximum withdrawal: **50,000 THB/transaction**
- Maximum withdrawal/day: **200,000 THB**
- Maximum withdrawal/month: **1,000,000 THB**
- Fee: **0.5%** (minimum 10 THB)

**API Call:**
```javascript
POST /api/wallet/withdraw
{
  "amount": 500,
  "method": "bank_transfer",
  "bank_account": {
    "bank_name": "Krungthai Bank",
    "account_number": "123-4-56789-0",
    "account_name": "Somchai Jaidee"
  }
}
```

**Withdrawal Status:**
- `pending`: Waiting for admin approval
- `completed`: Withdrawal successful
- `cancelled`: Withdrawal cancelled

### 3.4 Transferring Money

**API Call:**
```javascript
POST /api/wallet/transfer
{
  "to_user_id": 123,
  "amount": 100,
  "message": "Thank you"
}
```

---

## 4. Gamification System

### 4.1 User Levels

**Calculation Formula:**
```
Level = floor((Total Points / 100) ^ (2/3))
XP for Next Level = 100 × (Level + 1)^1.5
```

**Examples:**
- 0-99 points: Level 0
- 100-299 points: Level 1
- 300-599 points: Level 2
- 600-999 points: Level 3
- 1,000-1,499 points: Level 4
- And so on...

### 4.2 Achievements

#### Achievement Types

**Points Achievements:**
- First points earned
- Accumulate 1,000 points
- Accumulate 10,000 points
- Accumulate 100,000 points

**Action Achievements:**
- Post 10 times
- Receive 100 likes
- Comment 50 times

**Streak Achievements:**
- Login 3 consecutive days
- Login 7 consecutive days
- Login 30 consecutive days
- Login 100 consecutive days

**Social Achievements:**
- Add 10 friends
- Add 100 friends
- Add 1,000 friends

**Learning Achievements:**
- First lesson access
- First quiz attempt
- First assignment submission
- First course completion

### 4.3 Badge System

**Rarity Levels:**
- **Common**: 10-50 points
- **Uncommon**: 51-100 points
- **Rare**: 101-200 points
- **Epic**: 201-500 points
- **Legendary**: 501+ points

### 4.4 Leaderboard System

**Leaderboard Types:**
- **Points Leaderboard**: Ranked by total accumulated points
- **Weekly Leaderboard**: Ranked by points in the week
- **Monthly Leaderboard**: Ranked by points in the month
- **Streak Leaderboard**: Ranked by longest streak
- **Achievement Leaderboard**: Ranked by number of achievements

---

## 5. Rewards System

### Reward Types
- **Wallet Rewards**: Gift cards, cash
- **Badge Rewards**: Special badges
- **Feature Rewards**: Special features, themes
- **Discount Rewards**: Discounts, promotions

### How to Redeem Rewards

**1. View available rewards**
```javascript
GET /api/rewards
```

**2. Redeem a reward**
```javascript
POST /api/rewards/redeem
{
  "reward_id": 1,
  "quantity": 1
}
```

**3. View redeemed rewards**
```javascript
GET /api/rewards/my
```

---

## 6. Main API Endpoints

### Points API
```javascript
// View current point balance
GET /api/points/balance

// Earn points from activity
POST /api/points/earn
{
  "activity_type": "post",
  "activity_id": 123
}

// Spend points
POST /api/points/spend
{
  "activity_type": "like",
  "activity_id": 456
}

// Convert points to money
POST /api/points/convert
{
  "points": 1080
}

// View point transaction history
GET /api/points/transactions?page=1&per_page=20
```

### Wallet API
```javascript
// View current money balance
GET /api/wallet/balance

// Deposit money
POST /api/wallet/deposit
{
  "amount": 1000,
  "method": "bank_transfer",
  "reference": "REF123456"
}

// Withdraw money
POST /api/wallet/withdraw
{
  "amount": 500,
  "method": "bank_transfer",
  "bank_account": {
    "bank_name": "Krungthai Bank",
    "account_number": "123-4-56789-0",
    "account_name": "Somchai Jaidee"
  }
}

// Transfer money
POST /api/wallet/transfer
{
  "to_user_id": 123,
  "amount": 100,
  "message": "Thank you"
}

// View wallet transaction history
GET /api/wallet/transactions?page=1&per_page=20
```

### Gamification API
```javascript
// View current streak info
GET /api/gamification/streak

// View all achievements
GET /api/gamification/achievements

// View available achievements
GET /api/gamification/achievements/available

// View leaderboard
GET /api/gamification/leaderboard/points
GET /api/gamification/leaderboard/streak
GET /api/gamification/leaderboard/achievements
```

---

## 7. Usage Examples

### Example 1: Earning Points and Converting to Money

```javascript
// 1. Create a new post (earn 180 points)
POST /api/points/earn
{
  "activity_type": "post",
  "activity_id": 123
}

// 2. Receive 10 likes (earn 120 points)
// System automatically adds points

// 3. Check point balance
GET /api/points/balance
// Response: { "pp": 300, "total_points_earned": 300 }

// 4. Convert 1,080 points to 1 THB
POST /api/points/convert
{
  "points": 1080
}

// 5. Check wallet balance
GET /api/wallet/balance
// Response: { "cash_balance": 1.00, "total_balance": 1.00 }
```

### Example 2: Withdrawing Money

```javascript
// 1. Check money balance
GET /api/wallet/balance
// Response: { "cash_balance": 500.00 }

// 2. Request withdrawal of 500 THB
POST /api/wallet/withdraw
{
  "amount": 500,
  "method": "bank_transfer",
  "bank_account": {
    "bank_name": "Krungthai Bank",
    "account_number": "123-4-56789-0",
    "account_name": "Somchai Jaidee"
  }
}

// Response
{
  "success": true,
  "transaction_id": 789,
  "amount": 500,
  "fee": 10,
  "net_amount": 490,
  "status": "pending"
}

// 3. Wait for admin approval (1-3 business days)
```

### Example 3: Transferring Money to a Friend

```javascript
// 1. Transfer 100 THB to a friend
POST /api/wallet/transfer
{
  "to_user_id": 123,
  "amount": 100,
  "message": "Thanks for helping"
}

// Response
{
  "success": true,
  "message": "Transfer successful",
  "amount": 100,
  "new_balance": 400
}
```

### Example 4: Tracking Streak

```javascript
// 1. Login every day
POST /api/gamification/login

// 2. Check streak info
GET /api/gamification/streak

// Response
{
  "current_streak": 7,
  "longest_streak": 15,
  "total_logins": 30,
  "today_points": 20,
  "streak_level": "week"  // day, week, two_weeks, month, long_term
}
```

---

## 8. Tips for Earning Points

### 8.1 Basic Tips
1. **Login daily** - Earn daily login points and build streak
2. **Post frequently** - Posts with images earn more points
3. **Interact with others** - Like, comment, share
4. **Learn continuously** - Access lessons, take quizzes, submit assignments
5. **Watch advertisements** - Convert to wallet money

### 8.2 Advanced Tips
1. **Create engaging content** - To receive likes and shares
2. **Join special activities** - Earn bonus points
3. **Create courses** - Earn points and generate income
4. **Create polls** - Earn points and increase engagement
5. **Donate** - Earn points and help others

### 8.3 Point Conversion Strategy
1. **Convert to money when you have many points** - Convert 1,080 points or more at a time
2. **Save points for special activities** - Some activities require points
3. **Convert to money for withdrawal** - Convert to money when you need to withdraw

---

## 9. Frequently Asked Questions (FAQ)

### Q1: Do points expire?
**A:** Points do not expire, but some bonuses may have specific conditions.

### Q2: How long does withdrawal take?
**A:** 1-3 business days, depending on admin approval.

### Q3: Can I transfer money to anyone?
**A:** You can only transfer to friends or users in the system.

### Q4: What is the minimum withdrawal amount?
**A:** 100 THB.

### Q5: What is the withdrawal fee?
**A:** 0.5% (minimum 10 THB).

### Q6: Can I convert money back to points?
**A:** No, once converted, you cannot convert back.

### Q7: What is the maximum withdrawal per day?
**A:** 200,000 THB/day.

### Q8: What is the maximum withdrawal per month?
**A:** 1,000,000 THB/month.

### Q9: What happens if withdrawal is rejected?
**A:** Money will be automatically refunded to your wallet.

### Q10: Can I track transaction history?
**A:** Yes, you can view it at `/api/points/transactions` and `/api/wallet/transactions`.

---

## 10. Support

If you have problems or questions about the system:
- Contact through the Support system
- Email: support@nuxnan.com
- Other channels: As specified by the system

---

## Summary

The Nuxnan wallet and points system is designed to:
- ✅ Create incentives for users to use the system
- ✅ Generate income from usage
- ✅ Promote learning
- ✅ Build a strong community
- ✅ Reward active users

Start using the system today to earn points, convert to money, and receive various rewards!

---

**This document was last updated on: January 14, 2026**

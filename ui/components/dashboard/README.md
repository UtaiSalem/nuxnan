# Dashboard Components

This directory contains reusable dashboard widgets and components for the Nuxnan application.

## Components

### DashboardStatsCard.vue
A reusable stats card component for displaying key metrics on the dashboard.

**Props:**
- `type` (required): Type of stats card - 'points' | 'wallet' | 'achievements' | 'leaderboard'
- `value` (required): The numeric value to display
- `subtitle` (required): Subtitle text
- `link` (optional): Navigation link for "View All" button
- `progress` (optional): Progress percentage (0-100)
- `progressLabel` (optional): Label for progress bar
- `progressText` (optional): Text showing current/max values
- `additionalInfo` (optional): Additional info text

**Features:**
- Gradient backgrounds based on card type
- Progress bar for points card
- Responsive design
- Hover animations

### DashboardQuickActions.vue
Quick action buttons for common dashboard tasks.

**Features:**
- 4 main quick actions: Earn Points, Convert Points, Rewards, Achievements
- Gradient backgrounds with hover effects
- Scale animations on hover
- Responsive grid layout

### DashboardActivityFeed.vue
Displays recent transactions/activities in a feed format.

**Props:**
- `transactions` (required): Array of transaction objects
- `showViewAll` (optional): Show "View All" link (default: true)

**Transaction Object Structure:**
```typescript
{
  id: number
  type: 'earn' | 'spend' | 'refund' | 'transfer' | 'conversion'
  amount: number
  description?: string
  source_type?: string
  created_at: string
}
```

**Features:**
- Color-coded transaction types
- Icon-based transaction indicators
- Formatted dates in Thai
- Empty state handling

### DashboardLeaderboard.vue
Displays a preview of the points leaderboard.

**Props:**
- `users` (required): Array of leaderboard users

**LeaderboardUser Object Structure:**
```typescript
{
  id: number
  name: string
  total_points: number
}
```

**Features:**
- Special styling for top 3 ranks
- Medal colors (gold, silver, bronze)
- Animated entry
- Responsive design

## Usage Example

```vue
<template>
  <div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <DashboardStatsCard
        type="points"
        :value="points"
        subtitle="แต้มสะสม"
        link="/Earn/Points"
        :progress="levelProgress"
        progressLabel="XP Progress"
        progressText="`${currentXP} / ${xpForNextLevel}`"
      />
      
      <DashboardStatsCard
        type="wallet"
        :value="wallet"
        subtitle="ยอดเงินในกระเป๋า"
        link="/Earn/Wallet"
        additionalInfo="1,080 แต้ม = 1 บาท"
      />
      
      <!-- More cards... -->
    </div>

    <!-- Quick Actions -->
    <DashboardQuickActions />

    <!-- Activity Feed -->
    <DashboardActivityFeed
      :transactions="recentTransactions"
      :showViewAll="true"
    />

    <!-- Leaderboard -->
    <DashboardLeaderboard
      :users="topUsers"
    />
  </div>
</template>
```

## Styling

All components use:
- Tailwind CSS for styling
- Gradient backgrounds
- Smooth transitions and animations
- Dark mode support
- Responsive design patterns

## Color Scheme

- **Points**: Purple to Indigo gradient
- **Wallet**: Emerald to Teal gradient
- **Achievements**: Amber to Orange gradient
- **Leaderboard**: Rose to Pink gradient

## Animations

Components include subtle animations:
- Fade-in on load
- Hover scale effects
- Smooth transitions
- Progress bar animations

## Integration

These components integrate with existing composables:
- `usePoints()` - For points data
- `useWallet()` - For wallet data
- `useRewards()` - For rewards data
- `useGamification()` - For achievements and leaderboard data

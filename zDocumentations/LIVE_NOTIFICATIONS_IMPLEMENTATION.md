# Live Notification System Implementation

## Overview

Implemented a comprehensive live notification system that automatically detects and alerts users about new updates, edits, and pending items in the HRIS system.

## Features

### 1. **Pop-up Toast Notifications**
- Automatic toast notifications appear in the top-right corner
- Different types: Success (green), Error (red), Warning (orange), Info (blue)
- Auto-dismiss after configurable duration (4-8 seconds)
- Smooth slide-in/slide-out animations
- Can be manually dismissed by clicking the X button

### 2. **Notification Bell Icon**
- Located in the header next to the time display
- Shows a red badge with the count of pending items
- Badge displays "99+" for counts over 99
- Click to open a dropdown panel with pending item categories
- Real-time count updates every 30 seconds

### 3. **Notification Panel**
- Quick access to pending items by category:
  - **Password Reset Requests** (DIOS only) - Review pending password reset requests
  - **Leave Requests** - Review pending leave applications
  - **Travel Orders** - Review pending travel order requests
- Click any item to navigate directly to that module
- Shows "All caught up!" message when no pending items exist

### 4. **Live Polling System**
- Automatically polls for updates every 30 seconds
- Initial check after 5 seconds of login
- Checks multiple modules based on user role:
  - Password reset requests (DIOS only)
  - Audit logs (DIOS only)
  - Employee additions (Admin and above)
  - Leave requests (All users)
  - Travel orders (All users)
  - Training additions (Admin and above)

## Notification Types

### For DIOS Users:
- **Password Reset Requests**: "New Password Reset Request" - Shows when a user submits a password reset request
- **Audit Logs**: "System Activity" - Shows important system actions (CREATE, UPDATE, DELETE, LOGIN, LOGOUT)
- **Employee Additions**: "New Employee Added" - Shows when a new employee is added
- **Training Additions**: "Training Added" - Shows when a new training is created

### For All Users:
- **Leave Requests**: "New Leave Request" - Shows when an employee files a leave request
- **Travel Orders**: "New Travel Order" - Shows when an employee files a travel order

## Technical Implementation

### Files Created:
1. **`client/src/composables/useLiveNotifications.js`**
   - Composable for live notification polling
   - Checks multiple API endpoints for new data
   - Compares timestamps to detect new items
   - Triggers toast notifications for new items
   - Role-based filtering of notifications

### Files Modified:
1. **`client/src/App.vue`**
   - Imported and initialized `useLiveNotifications` composable
   - Watches for auth changes to start/stop polling
   - Polls every 30 seconds when user is logged in

2. **`client/src/components/AppHeader.vue`**
   - Added notification bell icon with badge
   - Added notification panel dropdown
   - Added `fetchPendingCount()` function to count pending items
   - Refreshes count every 30 seconds
   - Added CSS styles for bell, badge, and panel

### Existing Components Used:
1. **`client/src/components/Notifications.vue`** - Toast notification display component
2. **`client/src/stores/notifications.js`** - Notification state management

## Polling Strategy

### Timing:
- **Initial check**: 5 seconds after login
- **Regular polling**: Every 30 seconds
- **Pending count refresh**: Every 30 seconds

### Data Comparison:
- Stores last checked timestamp for each module
- Compares new data timestamps with last checked time
- Only shows notifications for items created after last check
- Prevents duplicate notifications on page refresh

### Performance Optimization:
- Lightweight API calls (only fetches necessary data)
- Efficient timestamp comparison
- Automatic cleanup on component unmount
- Stops polling when user logs out

## User Experience

### Visual Feedback:
- **Bell icon**: Pulses subtly when there are pending items
- **Badge**: Red circular badge with white text
- **Toast notifications**: Slide in from the right with smooth animation
- **Panel**: Dropdown with organized categories and icons

### Interaction:
- Click bell to view pending items summary
- Click any category to navigate to that module
- Click X on toast to dismiss manually
- Toasts auto-dismiss after duration
- Panel closes when clicking outside

### Accessibility:
- Clear visual indicators
- Color-coded by importance (red for urgent, orange for pending, blue for info)
- Descriptive titles and messages
- Keyboard accessible (can be enhanced further)

## Configuration

### Polling Interval:
```javascript
startPolling(30000) // 30 seconds (default)
```

### Toast Duration:
- Success: 4 seconds
- Error: 6 seconds
- Warning: 5-7 seconds
- Info: 4-6 seconds

### Notification Priorities:
1. **High**: Password resets, errors
2. **Medium**: Leave requests, travel orders
3. **Low**: Employee additions, trainings, audit logs

## Future Enhancements

### Potential Improvements:
1. **WebSocket Integration**: Replace polling with real-time WebSocket connections
2. **Sound Notifications**: Optional sound alerts for important notifications
3. **Browser Notifications**: Desktop notifications when tab is not active
4. **Notification History**: View all past notifications in a dedicated page
5. **Notification Preferences**: User settings to customize which notifications to receive
6. **Mark as Read**: Track which notifications have been viewed
7. **Notification Grouping**: Group similar notifications together
8. **Priority Levels**: Different visual styles for urgent vs normal notifications

### Advanced Features:
- Push notifications via service workers
- Email notifications for critical items
- SMS notifications for urgent matters
- Notification scheduling (quiet hours)
- Custom notification rules per user role

## Testing Recommendations

1. **Login as different roles** to verify role-based notifications
2. **Create test data** (leave requests, travel orders, etc.) to trigger notifications
3. **Wait 30 seconds** to see if polling detects new items
4. **Check notification bell** badge count updates
5. **Click bell icon** to verify panel displays correctly
6. **Navigate to modules** from notification panel
7. **Test on multiple browsers** for compatibility
8. **Test responsiveness** on mobile devices

## Security Considerations

- All API calls use authenticated requests with user ID headers
- Role-based access control for sensitive notifications
- No sensitive data displayed in toast notifications
- Timestamps prevent replay attacks
- Polling stops when user logs out

## Date
May 16, 2026

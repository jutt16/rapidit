# Quick Reference Guide

## 🚀 Quick Start

### 1. Run Migration
```bash
php artisan migrate --force
```

### 2. Test Endpoints
All endpoints require authentication. Include this header:
```
Authorization: Bearer {your_sanctum_token}
```

---

## 📱 Most Common Use Cases

### Get User Notifications
```bash
GET /api/notifications
```

### Get Nearby Experts (within 10km)
```bash
POST /api/experts/near-you/count
{
  "latitude": 28.6139,
  "longitude": 77.2090,
  "radius": 10
}
```

### Get Nearest Maid Price
```bash
POST /api/maid/nearest-price
{
  "latitude": 28.6139,
  "longitude": 77.2090
}
```

### Get Random Reviews for Homepage
```bash
GET /api/reviews/random?count=5&rating_min=4
```

### Get Last 24 Hours Bookings
```bash
# For Users
GET /api/bookings/stats/last-24-hours

# For Partners
GET /api/partner/bookings/stats/last-24-hours

# For Admins
GET /api/admin/bookings/stats/last-24-hours
```

---

## 🔧 How to Send FCM Notification (In Code)

```php
use App\Services\FcmService;
use App\Models\User;

$user = User::find(1);
$fcmService = app(FcmService::class);

// This will store in DB and send via FCM
$fcmService->sendToUser(
    $user,
    'Booking Confirmed',
    'Your booking has been confirmed',
    ['booking_id' => 123]
);
```

---

## 📊 API Response Format

All APIs follow this format:

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description"
}
```

---

## 🗺️ Distance Calculation

The location APIs use the **Haversine formula** to calculate distances in kilometers.

**Default Radii:**
- Experts near you: 10 km
- Nearest maid price: 20 km

Both can be customized via the `radius` parameter.

---

## 🔐 Role-Based Access

| Endpoint | User | Partner | Admin |
|----------|------|---------|-------|
| Notifications | ✅ | ✅ | ✅ |
| User booking stats | ✅ | ❌ | ❌ |
| Partner booking stats | ❌ | ✅ | ❌ |
| Admin booking stats | ❌ | ❌ | ✅ |
| Experts near you | ✅ | ✅ | ✅ |
| Random reviews | ✅ | ✅ | ✅ |

---

## 🎯 Key Features

### ✅ Notifications
- Stores FCM notifications in database before sending
- Supports both topic-based and user-specific notifications
- Track read/unread status
- Get unread count

### ✅ Booking Statistics
- Last 24 hours counts
- Breakdown by status
- Role-based filtering

### ✅ Location Services
- Find experts near any location
- Get minimum maid service price
- Filter by service type
- Adjustable search radius

### ✅ Reviews
- Random sampling for testimonials
- Filter by rating and reviewer type
- Human-readable timestamps

### ✅ Admin
- Logout redirects to home page

---

## 📁 Important Files

### Models
- `app/Models/UserNotification.php`

### Controllers
- `app/Http/Controllers/Api/NotificationController.php`
- `app/Http/Controllers/Api/BookingStatsController.php`
- `app/Http/Controllers/Api/PartnerLocationController.php`

### Services
- `app/Services/FcmService.php`

### Routes
- `routes/api.php` (13 new routes added)

### Migrations
- `database/migrations/2025_11_06_012008_create_user_notifications_table.php`

---

## 🐛 Troubleshooting

### Issue: "No maids found within the specified radius"
**Solution:** 
1. Check if partner_profiles have latitude and longitude values
2. Increase the radius parameter
3. Verify partners have 'approved' status

### Issue: "Access denied for user"
**Solution:** Check database credentials in `.env` file

### Issue: Notifications not sending
**Solution:**
1. Verify FCM token exists for user
2. Check Firebase configuration
3. Review `storage/logs/laravel.log` for errors

---

## 💡 Tips

1. **Testing Location APIs:** Use real coordinates or tools like https://www.latlong.net/
2. **FCM Setup:** Ensure `storage/app/firebase/firebase-admin.json` exists
3. **Performance:** Add indexes on frequently queried columns (latitude, longitude, created_at)
4. **Caching:** Consider caching reviews and location data for better performance

---

## 📞 Need Help?

Refer to:
- `API_DOCUMENTATION.md` - Complete API documentation
- `IMPLEMENTATION_SUMMARY.md` - Technical implementation details
- Laravel docs: https://laravel.com/docs
- FCM docs: https://firebase.google.com/docs/cloud-messaging

---

**Last Updated:** November 6, 2025


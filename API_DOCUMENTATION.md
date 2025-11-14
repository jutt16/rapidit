# RapidIt API Documentation

This document contains information about the newly implemented APIs.

## Table of Contents
1. [Notifications API](#notifications-api)
2. [Booking Stats API](#booking-stats-api)
3. [Partner Location API](#partner-location-api)
4. [Random Reviews API](#random-reviews-api)
5. [Admin Changes](#admin-changes)

---

## Notifications API

### Get All Notifications
Retrieves all notifications for the authenticated user (both topic-based and FCM notifications).

**Endpoint:** `GET /api/notifications`

**Authentication:** Required (Sanctum)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Booking Confirmed",
      "body": "Your booking has been confirmed",
      "type": "fcm",
      "data": {},
      "is_read": false,
      "sent": true,
      "created_at": "2025-11-06 12:00:00",
      "notification_type": "user"
    },
    {
      "id": 2,
      "title": "New Feature",
      "body": "Check out our new feature",
      "topic": "user",
      "data": {},
      "sent": true,
      "created_at": "2025-11-06 11:00:00",
      "notification_type": "topic"
    }
  ],
  "count": 2
}
```

### Mark Notification as Read
Marks a specific notification as read.

**Endpoint:** `POST /api/notifications/{id}/mark-read`

**Authentication:** Required (Sanctum)

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### Mark All Notifications as Read
Marks all notifications as read for the authenticated user.

**Endpoint:** `POST /api/notifications/mark-all-read`

**Authentication:** Required (Sanctum)

**Response:**
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

### Get Unread Count
Gets the count of unread notifications.

**Endpoint:** `GET /api/notifications/unread-count`

**Authentication:** Required (Sanctum)

**Response:**
```json
{
  "success": true,
  "unread_count": 5
}
```

---

## Booking Stats API

### User Last 24 Hours Booking Count
Gets booking counts for the authenticated user in the last 24 hours.

**Endpoint:** `GET /api/bookings/stats/last-24-hours`

**Authentication:** Required (Sanctum)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_count": 5,
    "by_status": {
      "pending": 2,
      "completed": 2,
      "cancelled": 1
    },
    "period": "last_24_hours",
    "from": "2025-11-05 12:00:00",
    "to": "2025-11-06 12:00:00"
  }
}
```

### Partner Last 24 Hours Booking Count
Gets booking counts for the authenticated partner in the last 24 hours.

**Endpoint:** `GET /api/partner/bookings/stats/last-24-hours`

**Authentication:** Required (Sanctum, Role: partner)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_count": 10,
    "by_status": {
      "pending": 3,
      "completed": 5,
      "in_progress": 2
    },
    "period": "last_24_hours",
    "from": "2025-11-05 12:00:00",
    "to": "2025-11-06 12:00:00"
  }
}
```

### System-wide Last 24 Hours Booking Count (Admin Only)
Gets system-wide booking counts in the last 24 hours.

**Endpoint:** `GET /api/admin/bookings/stats/last-24-hours`

**Authentication:** Required (Sanctum, Role: admin)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_count": 100,
    "by_status": {
      "pending": 25,
      "completed": 50,
      "cancelled": 15,
      "in_progress": 10
    },
    "by_service": {
      "Maid": 60,
      "Cook": 40
    },
    "period": "last_24_hours",
    "from": "2025-11-05 12:00:00",
    "to": "2025-11-06 12:00:00"
  }
}
```

---

## Partner Location API

### Get Experts Near You Count
Gets the count of experts (partners) near a specific location using latitude and longitude.

**Endpoint:** `POST /api/experts/near-you/count`

**Authentication:** Required (Sanctum)

**Request Body:**
```json
{
  "latitude": 28.6139,
  "longitude": 77.2090,
  "radius": 10,
  "service_id": 1
}
```

**Parameters:**
- `latitude` (required): Latitude of the location (-90 to 90)
- `longitude` (required): Longitude of the location (-180 to 180)
- `radius` (optional): Radius in kilometers (default: 10)
- `service_id` (optional): Filter by specific service

**Response:**
```json
{
  "success": true,
  "data": {
    "experts_count": 25,
    "latitude": 28.6139,
    "longitude": 77.2090,
    "radius_km": 10,
    "service_id": 1
  }
}
```

### Get Experts Near You List
Gets a list of experts near a specific location with details.

**Endpoint:** `POST /api/experts/near-you/list`

**Authentication:** Required (Sanctum)

**Request Body:**
```json
{
  "latitude": 28.6139,
  "longitude": 77.2090,
  "radius": 10,
  "service_id": 1,
  "limit": 20
}
```

**Parameters:**
- `latitude` (required): Latitude of the location
- `longitude` (required): Longitude of the location
- `radius` (optional): Radius in kilometers (default: 10)
- `service_id` (optional): Filter by specific service
- `limit` (optional): Maximum number of results (1-50, default: 20)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 10,
      "full_name": "John Doe",
      "average_rating": 4.5,
      "years_of_experience": 5,
      "distance": 2.5,
      "user": {
        "id": 10,
        "name": "John Doe",
        "phone": "1234567890",
        "status": "active"
      },
      "services": [...]
    }
  ],
  "count": 20,
  "search_params": {
    "latitude": 28.6139,
    "longitude": 77.2090,
    "radius_km": 10,
    "service_id": 1
  }
}
```

### Get Nearest Maid Starting Price
Gets the minimum (starting) price for maid service from nearest available maids.

**Endpoint:** `POST /api/maid/nearest-price`

**Authentication:** Required (Sanctum)

**Request Body:**
```json
{
  "latitude": 28.6139,
  "longitude": 77.2090,
  "radius": 20
}
```

**Parameters:**
- `latitude` (required): Latitude of the location
- `longitude` (required): Longitude of the location
- `radius` (optional): Radius in kilometers (default: 20)

**Response:**
```json
{
  "success": true,
  "data": {
    "starting_price": 450.0,
    "original_price": 500.0,
    "discount_percentage": 10,
    "discount_amount": 50.0,
    "service_time_minutes": 120,
    "latitude": 28.6139,
    "longitude": 77.2090,
    "radius_km": 20,
    "maids_available": true
  }
}
```

**Error Response (No Maids Found):**
```json
{
  "success": false,
  "message": "No maids found within the specified radius",
  "data": {
    "starting_price": null,
    "radius_km": 20
  }
}
```

---

## Random Reviews API

### Get Random Reviews
Gets random reviews from users with optional filtering.

**Endpoint:** `GET /api/reviews/random?count={number}&rating_min={rating}&reviewer_type={type}`

**Authentication:** Required (Sanctum)

**Query Parameters:**
- `count` (optional): Number of reviews to fetch (1-50, default: 10)
- `rating_min` (optional): Minimum rating filter (1-5)
- `reviewer_type` (optional): Filter by reviewer type ('user' or 'partner')

**Example Request:**
```
GET /api/reviews/random?count=5&rating_min=4&reviewer_type=user
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "rating": 5,
      "comment": "Excellent service! Very professional.",
      "reviewer_type": "user",
      "reviewer_name": "Jane Smith",
      "booking_id": 123,
      "created_at": "2025-11-05 10:30:00",
      "created_ago": "1 day ago"
    },
    {
      "id": 2,
      "rating": 4,
      "comment": "Good work, would recommend.",
      "reviewer_type": "user",
      "reviewer_name": "John Doe",
      "booking_id": 124,
      "created_at": "2025-11-04 14:20:00",
      "created_ago": "2 days ago"
    }
  ],
  "count": 2
}
```

---

## Admin Changes

### Admin Logout Redirect
After admin logout, the user will now be redirected to the home route instead of the admin login page.

**Change Made:** 
- Updated `app/Http/Controllers/Admin/AuthController.php`
- Logout now redirects to `route('home')` instead of `route('admin.login')`

---

## Database Changes

### New Table: user_notifications
A new table has been created to store user-specific notifications.

**Fields:**
- `id` (bigint, primary key)
- `user_id` (bigint, foreign key to users table)
- `title` (string)
- `body` (text)
- `type` (string, default: 'fcm')
- `data` (json, nullable)
- `is_read` (boolean, default: false)
- `sent` (boolean, default: false)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Migration File:** `database/migrations/2025_11_06_012008_create_user_notifications_table.php`

---

## FCM Service Updates

The `FcmService` has been updated to store FCM notifications in the database before sending them.

**New Method:**
```php
public function sendToUser(User $user, string $title, string $body, array $data = []): bool
```

This method:
1. Stores the notification in the `user_notifications` table
2. Attempts to send the notification via FCM if the user has a token
3. Updates the `sent` status in the database
4. Returns `true` if sent successfully, `false` otherwise

**Usage Example:**
```php
$fcmService = app(FcmService::class);
$fcmService->sendToUser($user, 'Booking Confirmed', 'Your booking has been confirmed', ['booking_id' => 123]);
```

---

## Notes

1. All new APIs require authentication using Laravel Sanctum.
2. The notifications system now supports both topic-based (broadcast) and user-specific (FCM) notifications.
3. The partner location APIs use the Haversine formula to calculate distances in kilometers.
4. The database migration needs to be run: `php artisan migrate`
5. Make sure the partner profiles have latitude and longitude values for the location-based APIs to work properly.

---

## Testing

To test these APIs, you can use tools like Postman or cURL. Make sure to include the authentication token in the header:

```
Authorization: Bearer {your_sanctum_token}
```

Example cURL request:
```bash
curl -X GET \
  http://your-domain.com/api/notifications \
  -H 'Authorization: Bearer your_token_here' \
  -H 'Content-Type: application/json'
```


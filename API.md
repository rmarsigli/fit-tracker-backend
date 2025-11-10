# FitTrack BR - API Documentation

## Base URL
```
http://localhost/api/v1
```

## Authentication
All endpoints (except registration and login) require authentication using Laravel Sanctum.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 1. Authentication

### Register
**POST** `/auth/register`

**Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "username": "johndoe",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:** `201 Created`
```json
{
  "user": { ... },
  "token": "1|abc123..."
}
```

### Login
**POST** `/auth/login`

**Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "user": { ... },
  "token": "2|xyz456..."
}
```

### Get Current User
**GET** `/auth/me`

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe"
  }
}
```

### Logout
**POST** `/auth/logout`

**Response:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

---

## 2. Activities

### List Activities
**GET** `/activities`

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 15)

**Response:** `200 OK`
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67
  }
}
```

### Create Activity
**POST** `/activities`

**Body:**
```json
{
  "type": "run",
  "title": "Morning Run",
  "description": "Easy pace run",
  "distance_meters": 5000,
  "duration_seconds": 1800,
  "moving_time_seconds": 1750,
  "elevation_gain": 50,
  "elevation_loss": 45,
  "avg_speed_kmh": 10.5,
  "max_speed_kmh": 15.2,
  "visibility": "public",
  "started_at": "2025-11-10T06:00:00Z",
  "completed_at": "2025-11-10T06:30:00Z"
}
```

**Response:** `201 Created`

### Get Activity
**GET** `/activities/{id}`

**Response:** `200 OK`

### Update Activity
**PUT** `/activities/{id}`

**Response:** `200 OK`

### Delete Activity
**DELETE** `/activities/{id}`

**Response:** `204 No Content`

---

## 3. Activity Tracking (Real-time)

### Start Tracking
**POST** `/tracking/start`

**Body:**
```json
{
  "type": "run",
  "title": "Morning Run",
  "latitude": -23.5505,
  "longitude": -46.6333
}
```

**Response:** `201 Created`
```json
{
  "activity_id": "temp_123",
  "message": "Activity tracking started"
}
```

### Track Point
**POST** `/tracking/{activityId}/track`

**Body:**
```json
{
  "latitude": -23.5510,
  "longitude": -46.6340,
  "altitude": 750,
  "speed_kmh": 12.5,
  "heart_rate": 145
}
```

**Response:** `200 OK`

### Pause Tracking
**POST** `/tracking/{activityId}/pause`

**Response:** `200 OK`

### Resume Tracking
**POST** `/tracking/{activityId}/resume`

**Response:** `200 OK`

### Finish Tracking
**POST** `/tracking/{activityId}/finish`

**Response:** `200 OK`
```json
{
  "activity": { ... },
  "message": "Activity completed successfully"
}
```

### Get Tracking Status
**GET** `/tracking/{activityId}/status`

**Response:** `200 OK`
```json
{
  "activity_id": "temp_123",
  "status": "active",
  "distance": 2500,
  "duration": 900,
  "points_count": 150
}
```

---

## 4. Statistics

### Get User Statistics
**GET** `/statistics/me`

**Response:** `200 OK`
```json
{
  "data": {
    "total": {
      "activities": 45,
      "distance_km": 250.5,
      "duration_hours": 25.5,
      "elevation_gain": 1500
    },
    "by_type": { ... },
    "last_7_days": { ... },
    "last_30_days": { ... }
  }
}
```

### Get Activity Feed
**GET** `/statistics/feed`

**Query Parameters:**
- `limit` (optional): Number of activities (default: 20, max: 100)

**Response:** `200 OK`

### Get Activity Splits
**GET** `/statistics/activities/{id}/splits`

**Response:** `200 OK`
```json
{
  "data": [
    {
      "km": 1,
      "time_seconds": 300,
      "pace_min_per_km": "5:00",
      "speed_kmh": 12.0
    }
  ]
}
```

### Get Activity Pace Zones
**GET** `/statistics/activities/{id}/pace-zones`

**Response:** `200 OK`
```json
{
  "data": [
    {
      "zone": "Zone 1 (Easy)",
      "min_pace": "6:30",
      "max_pace": "7:00",
      "percentage": 45.5
    }
  ]
}
```

---

## 5. Segments

### List Segments
**GET** `/segments`

**Query Parameters:**
- `creator_id` (optional): Filter by creator
- `type` (optional): Filter by type (climb, sprint, downhill)

**Response:** `200 OK`

### Create Segment
**POST** `/segments`

**Body:**
```json
{
  "name": "Paulista Avenue Sprint",
  "type": "sprint",
  "distance_meters": 2500,
  "elevation_gain": 15,
  "city": "São Paulo",
  "state": "SP",
  "country": "BR"
}
```

**Response:** `201 Created`

### Get Segment
**GET** `/segments/{id}`

**Response:** `200 OK`

### Update Segment
**PUT** `/segments/{id}`

**Response:** `200 OK`

### Delete Segment
**DELETE** `/segments/{id}`

**Response:** `204 No Content`

### Find Nearby Segments
**GET** `/segments/nearby`

**Query Parameters:**
- `lat` (required): Latitude
- `lng` (required): Longitude
- `radius` (optional): Radius in meters (default: 5000)

**Response:** `200 OK`

---

## 6. Social - Follow System

### Follow User
**POST** `/users/{userId}/follow`

**Response:** `200 OK`
```json
{
  "message": "Successfully followed user"
}
```

### Unfollow User
**DELETE** `/users/{userId}/unfollow`

**Response:** `200 OK`
```json
{
  "message": "Successfully unfollowed user"
}
```

### Get Followers
**GET** `/users/{userId}/followers`

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe",
      "followed_at": "2025-11-10T10:00:00Z"
    }
  ]
}
```

### Get Following
**GET** `/users/{userId}/following`

**Response:** `200 OK`

---

## 7. Social - Likes

### Toggle Like
**POST** `/activities/{activityId}/likes`

**Response:** `200 OK`
```json
{
  "liked": true,
  "likes_count": 15
}
```

### Get Activity Likes
**GET** `/activities/{activityId}/likes`

**Response:** `200 OK`
```json
{
  "data": [
    {
      "user": {
        "id": 1,
        "name": "John Doe"
      },
      "created_at": "2025-11-10T10:00:00Z"
    }
  ]
}
```

---

## 8. Social - Comments

### List Comments
**GET** `/activities/{activityId}/comments`

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "content": "Great run!",
      "user": {
        "id": 2,
        "name": "Jane Smith"
      },
      "created_at": "2025-11-10T10:30:00Z"
    }
  ]
}
```

### Create Comment
**POST** `/activities/{activityId}/comments`

**Body:**
```json
{
  "content": "Amazing effort!"
}
```

**Response:** `201 Created`

### Delete Comment
**DELETE** `/comments/{commentId}`

**Response:** `204 No Content`

---

## 9. Social - Feed

### Get Following Feed
**GET** `/feed/following`

**Query Parameters:**
- `limit` (optional): Number of activities (default: 20, max: 50)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "activity": { ... },
      "user": { ... },
      "likes_count": 5,
      "comments_count": 2
    }
  ]
}
```

### Get Nearby Feed
**GET** `/feed/nearby`

**Query Parameters:**
- `lat` (required): Latitude
- `lng` (required): Longitude
- `radius` (optional): Radius in meters (default: 10000)
- `limit` (optional): Number of activities (default: 20)

**Response:** `200 OK`

### Get Trending Feed
**GET** `/feed/trending`

**Query Parameters:**
- `days` (optional): Number of days to look back (default: 7, max: 30)
- `limit` (optional): Number of activities (default: 20, max: 50)

**Response:** `200 OK`

---

## 10. Challenges

### List Challenges
**GET** `/challenges`

**Query Parameters:**
- `page` (optional): Page number

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "name": "January Distance Challenge",
      "type": "distance",
      "goal_value": 100.00,
      "goal_unit": "km",
      "starts_at": "2025-01-01T00:00:00Z",
      "ends_at": "2025-01-31T23:59:59Z",
      "participants_count": 45
    }
  ],
  "meta": { ... }
}
```

### Create Challenge
**POST** `/challenges`

**Body:**
```json
{
  "name": "February Running Challenge",
  "description": "Run 100km in February",
  "type": "distance",
  "goal_value": 100,
  "goal_unit": "km",
  "starts_at": "2025-02-01T00:00:00Z",
  "ends_at": "2025-02-28T23:59:59Z",
  "is_public": true,
  "max_participants": 50
}
```

**Response:** `201 Created`

### Get Challenge
**GET** `/challenges/{id}`

**Response:** `200 OK`

### Update Challenge
**PUT** `/challenges/{id}`

**Response:** `200 OK`

### Delete Challenge
**DELETE** `/challenges/{id}`

**Response:** `204 No Content`

### Get My Challenges
**GET** `/challenges/my`

**Query Parameters:**
- `status` (optional): Filter by status (active, upcoming, ended)

**Response:** `200 OK`

### Get Available Challenges
**GET** `/challenges/available`

**Response:** `200 OK`

### Join Challenge
**POST** `/challenges/{id}/join`

**Response:** `200 OK`
```json
{
  "data": { ... },
  "message": "Successfully joined the challenge"
}
```

### Leave Challenge
**DELETE** `/challenges/{id}/leave`

**Response:** `200 OK`
```json
{
  "message": "Successfully left the challenge"
}
```

### Get Challenge Leaderboard
**GET** `/challenges/{id}/leaderboard`

**Response:** `200 OK`
```json
{
  "data": [
    {
      "user": {
        "id": 1,
        "name": "John Doe"
      },
      "current_progress": 75.50,
      "progress_percentage": 75.50,
      "joined_at": "2025-01-01T10:00:00Z"
    }
  ]
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "You are not authorized to perform this action"
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 422 Unprocessable Entity
```json
{
  "message": "The given data was invalid",
  "errors": { ... }
}
```

### 500 Internal Server Error
```json
{
  "message": "Server error"
}
```

---

## Rate Limiting

- **Authentication endpoints** (`/auth/login`, `/auth/register`): 5 requests per minute
- **Tracking endpoints** (`/tracking/*`): 20 requests per minute
- **All other authenticated endpoints**: 60 requests per minute

When rate limit is exceeded:
```json
{
  "message": "Too Many Requests",
  "retry_after": 45
}
```

---

## Activity Types

- `run` - Running
- `ride` - Cycling
- `walk` - Walking
- `hike` - Hiking
- `swim` - Swimming
- `other` - Other activities

## Activity Visibility

- `public` - Visible to everyone
- `followers` - Visible only to followers
- `private` - Visible only to the owner

## Challenge Types

- `distance` - Total distance goal (km)
- `duration` - Total time goal (hours)
- `elevation` - Total elevation gain goal (meters)

## Segment Types

- `climb` - Climbing segment
- `sprint` - Sprint segment
- `downhill` - Downhill segment

---

## Notes

- All timestamps are in ISO 8601 format (UTC)
- Distance is in meters in requests, but can be returned in km
- Speeds are in km/h
- Durations are in seconds
- Coordinates use WGS84 (EPSG:4326)
- PostGIS is used for spatial queries

# FitTrack BR API Documentation

> RESTful API for FitTrack fitness tracking platform.
> Base URL (local): `http://localhost:8000/api/v1`

**Last Updated**: 2025-11-10

---

## Quick Reference

**Authentication**: Laravel Sanctum (Bearer token)

**Common Headers**:
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your-token}
```

**Response Format**:
```json
{
  "data": {},          // Success response
  "message": "...",    // Success/error message
  "errors": {}         // Validation errors (422 only)
}
```

**Need something specific?** Jump to:
- [Common Workflows](#common-workflows) - Real-world usage examples
- [Authentication](#authentication) - Register, login, logout
- [Activities](#activities) - CRUD operations
- [GPS Tracking](#activity-tracking) - Real-time tracking
- [Segments & Leaderboards](#segments) - Compete on route segments
- [Social Features](#social-features) - Follow, like, comment
- [Error Handling](#error-handling) - What can go wrong

---

## Common Workflows

This section shows how to actually use the API for real-world tasks, not just individual endpoints.

### Creating Your First Activity (Quick Method)

**Use case**: Manually log a completed activity (no GPS tracking).

```bash
# Step 1: Register (skip if you have an account)
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "username": "joaosilva",
    "email": "joao@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }' | jq -r '.token')

# Step 2: Create activity
curl -X POST http://localhost:8000/api/v1/activities \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "run",
    "title": "Morning Run",
    "description": "Easy recovery run",
    "distance_meters": 5000,
    "duration_seconds": 1800,
    "moving_time_seconds": 1750,
    "started_at": "2025-11-10T06:00:00Z",
    "completed_at": "2025-11-10T06:30:00Z"
  }'
```

**What happens next?**
- Activity saved to database
- Appears in your profile
- Shows up in social feeds (if public)

---

### Real-Time GPS Tracking (Full Workflow)

**Use case**: Track an activity in real-time with GPS points.

```bash
# Step 1: Start tracking
TRACKING_ID=$(curl -s -X POST http://localhost:8000/api/v1/tracking/start \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "run",
    "title": "Morning Run"
  }' | jq -r '.activity_id')

echo "Tracking ID: $TRACKING_ID"

# Step 2: Send GPS points (repeat every 5-10 seconds while running)
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/track \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -23.5505,
    "longitude": -46.6333,
    "altitude": 760,
    "heart_rate": 145
  }'

# Continue sending GPS points...
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/track \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -23.5510,
    "longitude": -46.6340,
    "altitude": 765,
    "heart_rate": 150
  }'

# Step 3: Pause (optional - if you need a break)
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/pause \
  -H "Authorization: Bearer $TOKEN"

# Step 4: Resume (if paused)
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/resume \
  -H "Authorization: Bearer $TOKEN"

# Step 5: Finish and save
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/finish \
  -H "Authorization: Bearer $TOKEN"
```

**What happens next?**
- GPS points saved from Redis → PostgreSQL
- Distance/elevation calculated automatically
- Background job detects matching segments
- Leaderboards updated if you beat any records
- Activity appears in social feeds

**Pro tip**: Check status anytime with:
```bash
curl http://localhost:8000/api/v1/tracking/$TRACKING_ID/status \
  -H "Authorization: Bearer $TOKEN"
```

---

### Competing on Segments

**Use case**: Check leaderboards and your personal records.

```bash
# Find segments near you
curl -s "http://localhost:8000/api/v1/segments/nearby?latitude=-23.5505&longitude=-46.6333&radius=5" \
  -H "Authorization: Bearer $TOKEN" | jq '.data[] | {id, name, distance_meters}'

# Get leaderboard for a segment
SEGMENT_ID=1
curl "http://localhost:8000/api/v1/segments/$SEGMENT_ID/leaderboard" \
  -H "Authorization: Bearer $TOKEN"

# Check who holds the KOM (King of Mountain)
curl "http://localhost:8000/api/v1/segments/$SEGMENT_ID/kom" \
  -H "Authorization: Bearer $TOKEN"

# Check who holds the QOM (Queen of Mountain)
curl "http://localhost:8000/api/v1/segments/$SEGMENT_ID/qom" \
  -H "Authorization: Bearer $TOKEN"

# See all your personal records
curl "http://localhost:8000/api/v1/me/records" \
  -H "Authorization: Bearer $TOKEN"
```

**What you'll see**:
- Top 20 fastest athletes on each segment
- Your rank (if you've completed the segment)
- KOM/QOM holders (male/female record holders)
- Your personal records across all segments

**How segment detection works**:
- After finishing an activity, background job runs
- System checks if your route overlaps with any segments (≥90% overlap)
- Creates `SegmentEffort` records for matching segments
- Updates leaderboards automatically

---

### Social Interactions

**Use case**: Follow friends, like activities, leave comments.

```bash
# Follow a user
USER_ID=5
curl -X POST "http://localhost:8000/api/v1/users/$USER_ID/follow" \
  -H "Authorization: Bearer $TOKEN"

# Get activities from people you follow
curl "http://localhost:8000/api/v1/feed/following?limit=10" \
  -H "Authorization: Bearer $TOKEN"

# Like an activity
ACTIVITY_ID=42
curl -X POST "http://localhost:8000/api/v1/activities/$ACTIVITY_ID/likes" \
  -H "Authorization: Bearer $TOKEN"

# Comment on an activity
curl -X POST "http://localhost:8000/api/v1/activities/$ACTIVITY_ID/comments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Great run! Keep it up!"
  }'

# Discover activities near you
curl "http://localhost:8000/api/v1/feed/nearby?lat=-23.5505&lng=-46.6333&radius=10" \
  -H "Authorization: Bearer $TOKEN"

# Check trending activities
curl "http://localhost:8000/api/v1/feed/trending?days=7&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Authentication

FitTrack BR uses **Laravel Sanctum** for token-based authentication.

### How it works

1. **Register** or **Login** to get an access token
2. Include the token in all requests: `Authorization: Bearer {token}`
3. **Logout** to revoke the token

**Token lifetime**: Tokens never expire unless revoked

---

### Register

Create a new user account.

**POST** `/auth/register`

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "username": "joaosilva",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response** (201 Created):
```json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "username": "joaosilva",
    "avatar": null,
    "bio": null,
    "created_at": "2025-11-10T12:00:00.000000Z"
  },
  "token": "1|abcdef123456..."
}
```

**Save the token!** You'll need it for authenticated requests.

**Validation**:
- `name`: required, min:3
- `email`: required, email, unique
- `username`: required, min:3, unique
- `password`: required, min:8, confirmed

**Errors**:
- `422 Unprocessable Entity` - Email/username already taken, or weak password

---

### Login

Authenticate existing user.

**POST** `/auth/login`

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "password123"
  }'
```

**Response** (200 OK):
```json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "username": "joaosilva"
  },
  "token": "2|xyz789..."
}
```

**Errors**:
- `401 Unauthorized` - Invalid email or password

---

### Get Current User

Get authenticated user info.

**GET** `/auth/me`

**Request**:
```bash
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@example.com",
  "username": "joaosilva",
  "avatar": null,
  "bio": "Runner from São Paulo",
  "city": "São Paulo",
  "state": "SP",
  "created_at": "2025-11-10T12:00:00.000000Z"
}
```

---

### Logout

Revoke current access token.

**POST** `/auth/logout`

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "message": "Logged out successfully"
}
```

**Note**: Your token is now invalid. You'll need to login again.

---

## Activities

CRUD operations for activities.

### List User Activities

Get all your activities.

**GET** `/activities`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/activities \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "type": "run",
      "title": "Morning Run",
      "description": "Easy recovery run",
      "distance_meters": 5000,
      "duration_seconds": 1800,
      "moving_time_seconds": 1750,
      "elevation_gain": 50,
      "elevation_loss": 45,
      "avg_speed_kmh": 10.0,
      "max_speed_kmh": 14.5,
      "avg_heart_rate": 145,
      "max_heart_rate": 165,
      "calories": 350,
      "visibility": "public",
      "started_at": "2025-11-10T06:00:00.000000Z",
      "completed_at": "2025-11-10T06:30:00.000000Z"
    }
  ]
}
```

---

### Create Activity

Manually create an activity (no GPS tracking).

**POST** `/activities`
**Auth**: Required

**Use case**: Log past activities, import from other platforms.

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/activities \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "run",
    "title": "Morning Run",
    "description": "Easy recovery run",
    "distance_meters": 5000,
    "duration_seconds": 1800,
    "moving_time_seconds": 1750,
    "elevation_gain": 50,
    "elevation_loss": 45,
    "avg_speed_kmh": 10.0,
    "max_speed_kmh": 14.5,
    "avg_heart_rate": 145,
    "max_heart_rate": 165,
    "calories": 350,
    "visibility": "public",
    "started_at": "2025-11-10T06:00:00Z",
    "completed_at": "2025-11-10T06:30:00Z"
  }'
```

**Response** (201 Created):
```json
{
  "id": 2,
  "type": "run",
  "title": "Morning Run",
  "distance_meters": 5000,
  ...
}
```

**Required fields**:
- `type`: run, ride, walk, swim, gym, other
- `title`: min:3 characters
- `started_at`: ISO 8601 datetime

**Optional fields**: All metrics (distance, duration, elevation, heart rate, etc)

**Validation errors** (422):
- Invalid activity type
- Title too short
- Negative distance/duration
- `completed_at` before `started_at`

---

### Show Activity

Get a single activity by ID.

**GET** `/activities/{id}`
**Auth**: Required
**Permission**: Can only view your own activities

**Request**:
```bash
curl http://localhost:8000/api/v1/activities/1 \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK): Same structure as Create Activity

**Errors**:
- `403 Forbidden` - Trying to view someone else's activity
- `404 Not Found` - Activity doesn't exist

---

### Update Activity

Update an existing activity.

**PUT/PATCH** `/activities/{id}`
**Auth**: Required
**Permission**: Can only update your own activities

**Request**:
```bash
curl -X PATCH http://localhost:8000/api/v1/activities/1 \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "description": "New description"
  }'
```

**Response** (200 OK): Updated activity object

**Note**: All fields are optional. Send only what you want to change.

---

### Delete Activity

Delete an activity.

**DELETE** `/activities/{id}`
**Auth**: Required
**Permission**: Can only delete your own activities

**Request**:
```bash
curl -X DELETE http://localhost:8000/api/v1/activities/1 \
  -H "Authorization: Bearer {your-token}"
```

**Response** (204 No Content)

**Warning**: This action cannot be undone!

---

## Activity Tracking

Real-time GPS tracking for activities.

### How Tracking Works

1. **Start** tracking with activity type and title
2. **Track** GPS points continuously (every 5-10 seconds)
3. **Pause/Resume** as needed
4. **Finish** to save the activity
5. **Status** to check current state

**Data Storage**:
- GPS points stored in **Redis** during tracking (2-hour TTL)
- Persisted to **PostgreSQL** on finish
- Distance/elevation calculated automatically using Haversine formula

---

### Start Tracking

Initialize a new tracking session.

**POST** `/tracking/start`
**Auth**: Required

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/tracking/start \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "run",
    "title": "Morning Run"
  }'
```

**Response** (201 Created):
```json
{
  "activity_id": "tracking_1_1699440000",
  "status": "active",
  "message": "Tracking started successfully"
}
```

**Save the `activity_id`!** You'll need it for tracking GPS points.

**Validation**:
- `type`: required, enum (run, ride, walk, swim, gym, other)
- `title`: required, min:3

---

### Track Location

Add a GPS point to the current tracking session.

**POST** `/tracking/{activityId}/track`
**Auth**: Required

**Use case**: Send this request every 5-10 seconds while activity is active.

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/track \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -23.5505,
    "longitude": -46.6333,
    "altitude": 760,
    "heart_rate": 145
  }'
```

**Response** (200 OK):
```json
{
  "message": "Location tracked successfully",
  "total_points": 45,
  "current_distance_meters": 2345.67,
  "current_duration_seconds": 720
}
```

**Validation**:
- `latitude`: required, between:-90,90
- `longitude`: required, between:-180,180
- `altitude`: optional, numeric (meters)
- `heart_rate`: optional, between:30,220 (bpm)

**Pro tip**: Mobile apps should send points even when paused, just mark them differently.

---

### Pause Tracking

Pause the current tracking session.

**POST** `/tracking/{activityId}/pause`
**Auth**: Required

**Use case**: User takes a break, traffic light, etc.

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/pause \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "status": "paused",
  "message": "Tracking paused"
}
```

**Note**: GPS points sent while paused won't count toward moving time.

---

### Resume Tracking

Resume a paused tracking session.

**POST** `/tracking/{activityId}/resume`
**Auth**: Required

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/resume \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "status": "active",
  "message": "Tracking resumed"
}
```

---

### Finish Tracking

Finalize tracking and save activity to database.

**POST** `/tracking/{activityId}/finish`
**Auth**: Required

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/tracking/$TRACKING_ID/finish \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "activity": {
    "id": 5,
    "type": "run",
    "title": "Morning Run",
    "distance_meters": 5234.56,
    "duration_seconds": 1845,
    "elevation_gain": 52.3,
    "elevation_loss": 48.7,
    "avg_speed_kmh": 10.2,
    ...
  },
  "message": "Activity saved successfully"
}
```

**What happens next?**
1. GPS points moved from Redis → PostgreSQL
2. Activity metrics calculated
3. Background job `ProcessSegmentEfforts` dispatched
4. Matching segments detected (≥90% route overlap)
5. Leaderboards updated if you beat records
6. Activity appears in social feeds

**Pro tip**: Wait a few seconds after finishing, then check segment records with `GET /me/records`.

---

### Get Tracking Status

Get current status of a tracking session.

**GET** `/tracking/{activityId}/status`
**Auth**: Required

**Use case**: Check progress, resume after app crash, debug.

**Request**:
```bash
curl http://localhost:8000/api/v1/tracking/$TRACKING_ID/status \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "activity_id": "tracking_1_1699440000",
  "status": "active",
  "started_at": "2025-11-10T06:00:00Z",
  "total_points": 45,
  "distance_meters": 2345.67,
  "duration_seconds": 720,
  "elevation_gain": 25.5,
  "elevation_loss": 18.2
}
```

**Status values**:
- `active` - Tracking in progress
- `paused` - Tracking paused
- `finished` - Activity saved (no longer in Redis)

**Errors**:
- `404 Not Found` - Tracking session doesn't exist or expired (2-hour TTL)

---

## Segments

Route segments and leaderboards.

### What are Segments?

Segments are specific portions of routes where athletes compete for the fastest time (like Strava KOMs).

**How they work**:
1. Someone creates a segment (e.g., "Ibirapuera Loop")
2. When you finish an activity, the system checks if your route overlaps with any segments (≥90%)
3. If it matches, a `SegmentEffort` is created
4. Leaderboards update automatically
5. Fastest male = KOM (King of Mountain), fastest female = QOM (Queen of Mountain)

---

### List Segments

Get all segments with optional filters.

**GET** `/segments`
**Auth**: Required

**Query Parameters**:
- `creator_id` - Filter by creator (integer)
- `type` - Filter by type (run, ride)

**Request**:
```bash
# All segments
curl http://localhost:8000/api/v1/segments \
  -H "Authorization: Bearer {your-token}"

# Only running segments
curl "http://localhost:8000/api/v1/segments?type=run" \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Ibirapuera Loop",
      "description": "Full loop around Ibirapuera Park",
      "type": "run",
      "distance_meters": 3850,
      "avg_grade_percent": 0.5,
      "max_grade_percent": 2.1,
      "elevation_gain": 15,
      "total_attempts": 342,
      "unique_athletes": 87,
      "city": "São Paulo",
      "state": "SP",
      "creator": {
        "id": 5,
        "name": "Maria Santos",
        "username": "mariasantos"
      },
      "created_at": "2025-10-01T12:00:00.000000Z"
    }
  ]
}
```

---

### Create Segment

Create a new segment.

**POST** `/segments`
**Auth**: Required

**Use case**: Mark a favorite route for competition.

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/segments \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paulista Sprint",
    "description": "Fast section of Avenida Paulista",
    "type": "run",
    "distance_meters": 1200,
    "avg_grade_percent": -0.5,
    "max_grade_percent": 1.2,
    "elevation_gain": 8,
    "city": "São Paulo",
    "state": "SP",
    "is_hazardous": false
  }'
```

**Response** (201 Created):
```json
{
  "id": 10,
  "name": "Paulista Sprint",
  "type": "run",
  "distance_meters": 1200,
  ...
}
```

**Required fields**:
- `name`: min:3
- `type`: run or ride
- `distance_meters`: between:100,100000

**Optional fields**: Description, grades, elevation, location

**Validation errors** (422):
- Segment too short (< 100m) or too long (> 100km)
- Invalid segment type

---

### Show Segment

Get a single segment by ID.

**GET** `/segments/{id}`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/segments/1 \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK): Same structure as Create Segment

---

### Update Segment

Update an existing segment.

**PUT/PATCH** `/segments/{id}`
**Auth**: Required
**Permission**: Only creator can update

**Request**:
```bash
curl -X PATCH http://localhost:8000/api/v1/segments/1 \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "description": "New description"
  }'
```

**Response** (200 OK): Updated segment object

**Errors**:
- `403 Forbidden` - Not the segment creator

---

### Delete Segment

Delete a segment.

**DELETE** `/segments/{id}`
**Auth**: Required
**Permission**: Only creator can delete

**Request**:
```bash
curl -X DELETE http://localhost:8000/api/v1/segments/1 \
  -H "Authorization: Bearer {your-token}"
```

**Response** (204 No Content)

**Warning**: This deletes all segment efforts and leaderboard data!

---

### Find Nearby Segments

Find segments near a location using PostGIS.

**GET** `/segments/nearby`
**Auth**: Required

**Query Parameters**:
- `latitude` - required, between:-90,90
- `longitude` - required, between:-180,180
- `radius` - optional, kilometers (default: 10, max: 100)

**Request**:
```bash
curl "http://localhost:8000/api/v1/segments/nearby?latitude=-23.5505&longitude=-46.6333&radius=5" \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Ibirapuera Loop",
      "type": "run",
      "distance_meters": 3850,
      "distance_to_point_km": 1.2,
      "city": "São Paulo",
      "state": "SP"
    },
    {
      "id": 5,
      "name": "Pinheiros River Trail",
      "type": "run",
      "distance_meters": 5200,
      "distance_to_point_km": 3.8
    }
  ]
}
```

**Note**: Uses PostGIS `ST_DWithin` for efficient geospatial queries.

---

### Get Segment Leaderboard

Get top 20 fastest efforts for a segment.

**GET** `/segments/{segmentId}/leaderboard`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/segments/1/leaderboard \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "rank": 1,
      "user": {
        "id": 5,
        "name": "Maria Santos",
        "username": "mariasantos",
        "avatar": "https://..."
      },
      "elapsed_time_seconds": 542.5,
      "elapsed_time_formatted": "00:09:02",
      "average_speed_kmh": 18.5,
      "average_pace_min_km": "03:14",
      "achieved_at": "2025-11-10T10:30:00Z",
      "is_kom": true,
      "is_pr": true
    },
    {
      "rank": 2,
      "user": {
        "id": 12,
        "name": "João Silva",
        "username": "joaosilva"
      },
      "elapsed_time_seconds": 558.2,
      "elapsed_time_formatted": "00:09:18",
      "average_speed_kmh": 18.1,
      "achieved_at": "2025-11-09T15:20:00Z",
      "is_kom": false,
      "is_pr": true
    }
  ],
  "segment": {
    "id": 1,
    "name": "Ibirapuera Loop",
    "distance_km": 3.85,
    "total_efforts": 342
  }
}
```

**Notes**:
- Shows best effort for each unique athlete (not all efforts)
- Limited to top 20
- `is_kom` = current KOM/QOM holder
- `is_pr` = user's personal record

---

### Get User Personal Records

Get all your personal records on segments.

**GET** `/me/records` (your records)
**GET** `/users/{userId}/records` (another user's records)
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/me/records \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "segment": {
        "id": 1,
        "name": "Ibirapuera Loop",
        "distance_km": 3.85,
        "type": "run"
      },
      "personal_record": {
        "elapsed_time_seconds": 542.5,
        "elapsed_time_formatted": "00:09:02",
        "average_speed_kmh": 18.5,
        "achieved_at": "2025-11-10T10:30:00Z",
        "is_kom": true
      },
      "rank": 1,
      "total_attempts": 5
    }
  ],
  "user": {
    "id": 5,
    "name": "Maria Santos",
    "username": "mariasantos"
  }
}
```

**Note**: Only segments where you have efforts marked as PR (Personal Record).

---

### Get Current KOM

Get the current KOM (King of Mountain) holder for a segment.

**GET** `/segments/{segmentId}/kom`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/segments/1/kom \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": {
    "user": {
      "id": 12,
      "name": "João Silva",
      "username": "joaosilva",
      "avatar": "https://..."
    },
    "elapsed_time_seconds": 542.5,
    "elapsed_time_formatted": "00:09:02",
    "average_speed_kmh": 18.5,
    "achieved_at": "2025-11-10T10:30:00Z"
  },
  "segment": {
    "id": 1,
    "name": "Ibirapuera Loop"
  }
}
```

**No KOM Response** (200 OK):
```json
{
  "data": null,
  "message": "No KOM recorded for this segment yet"
}
```

**Note**: KOM = fastest **male** athlete. Returns `null` if no male athletes have completed the segment.

---

### Get Current QOM

Get the current QOM (Queen of Mountain) holder for a segment.

**GET** `/segments/{segmentId}/qom`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/segments/1/qom \
  -H "Authorization: Bearer {your-token}"
```

**Response**: Same structure as Get Current KOM

**Note**: QOM = fastest **female** athlete.

---

## Statistics

Activity statistics and analytics.

### User Statistics

Get aggregated stats for the authenticated user.

**GET** `/statistics/me`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/statistics/me \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "total": {
    "activities": 42,
    "distance_meters": 215000,
    "duration_seconds": 86400,
    "elevation_gain": 1250
  },
  "by_type": {
    "run": {
      "activities": 30,
      "distance_meters": 180000,
      "duration_seconds": 72000,
      "elevation_gain": 1000
    },
    "ride": {
      "activities": 10,
      "distance_meters": 30000,
      "duration_seconds": 12000,
      "elevation_gain": 200
    }
  },
  "last_7_days": {
    "activities": 5,
    "distance_meters": 25000,
    "duration_seconds": 10000,
    "elevation_gain": 150
  },
  "last_30_days": {
    "activities": 18,
    "distance_meters": 90000,
    "duration_seconds": 36000,
    "elevation_gain": 500
  }
}
```

**Use case**: Display user profile stats, progress tracking.

---

### Activity Feed

Get public activities feed (global feed).

**GET** `/statistics/feed`
**Auth**: Required

**Query Parameters**:
- `limit` - max:100 (default: 20)
- `offset` - (default: 0)

**Request**:
```bash
curl "http://localhost:8000/api/v1/statistics/feed?limit=10" \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 42,
      "user": {
        "id": 5,
        "name": "Maria Santos",
        "username": "mariasantos",
        "avatar": "https://..."
      },
      "type": "run",
      "title": "Evening Run in Ibirapuera",
      "distance_meters": 8000,
      "duration_seconds": 2400,
      "started_at": "2025-11-10T18:00:00.000000Z"
    }
  ],
  "meta": {
    "limit": 10,
    "offset": 0,
    "total": 150
  }
}
```

**Note**: Only shows public completed activities.

---

### Activity Splits

Get per-kilometer splits for an activity.

**GET** `/statistics/activities/{id}/splits`
**Auth**: Required

**Use case**: Pace analysis, identify slow/fast sections.

**Request**:
```bash
curl http://localhost:8000/api/v1/statistics/activities/1/splits \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "activity_id": 1,
  "splits": [
    {
      "split_number": 1,
      "distance_meters": 1000,
      "pace_per_km": "5:30",
      "speed_kmh": 10.9
    },
    {
      "split_number": 2,
      "distance_meters": 1000,
      "pace_per_km": "5:25",
      "speed_kmh": 11.1
    }
  ]
}
```

**Note**: Returns empty array if activity has no GPS data.

---

### Activity Pace Zones

Get pace zone distribution for an activity.

**GET** `/statistics/activities/{id}/pace-zones`
**Auth**: Required

**Use case**: Training analysis, understand effort distribution.

**Request**:
```bash
curl http://localhost:8000/api/v1/statistics/activities/1/pace-zones \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "activity_id": 1,
  "avg_pace_per_km": "5:30",
  "pace_zones": [
    {
      "zone": "Recovery",
      "min_pace": "6:19",
      "max_pace": "∞",
      "percentage": 15
    },
    {
      "zone": "Easy",
      "min_pace": "5:46",
      "max_pace": "6:19",
      "percentage": 25
    },
    {
      "zone": "Moderate",
      "min_pace": "5:13",
      "max_pace": "5:46",
      "percentage": 35
    },
    {
      "zone": "Tempo",
      "min_pace": "4:40",
      "max_pace": "5:13",
      "percentage": 18
    },
    {
      "zone": "Threshold",
      "min_pace": "4:07",
      "max_pace": "4:40",
      "percentage": 5
    },
    {
      "zone": "Interval",
      "min_pace": "0:00",
      "max_pace": "4:07",
      "percentage": 2
    }
  ]
}
```

**Note**: Zones calculated based on activity's average pace.

---

## Social Features

Follow users, like activities, leave comments, and discover new content.

### Follow System

#### Follow a User

**POST** `/users/{userId}/follow`
**Auth**: Required

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/users/5/follow \
  -H "Authorization: Bearer {your-token}"
```

**Response** (201 Created):
```json
{
  "message": "Successfully followed user",
  "user": {
    "id": 5,
    "name": "Maria Santos",
    "username": "mariasantos",
    "followers_count": 15,
    "following_count": 23,
    "is_following": true
  }
}
```

**Errors**:
- `400 Bad Request` - Cannot follow yourself
- `409 Conflict` - Already following this user

---

#### Unfollow a User

**DELETE** `/users/{userId}/unfollow`
**Auth**: Required

**Request**:
```bash
curl -X DELETE http://localhost:8000/api/v1/users/5/unfollow \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "message": "Successfully unfollowed user"
}
```

**Errors**:
- `404 Not Found` - Not following this user

---

#### Get User Followers

Get paginated list of users who follow someone.

**GET** `/users/{userId}/followers`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/users/5/followers \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 3,
      "name": "Pedro Costa",
      "username": "pedrocosta",
      "followers_count": 10,
      "following_count": 15,
      "is_following": false
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

#### Get User Following

Get paginated list of users someone follows.

**GET** `/users/{userId}/following`
**Auth**: Required

**Response**: Same structure as Get User Followers

---

### Likes

#### Toggle Like on Activity

Like or unlike an activity (toggle action).

**POST** `/activities/{activityId}/likes`
**Auth**: Required

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/activities/42/likes \
  -H "Authorization: Bearer {your-token}"
```

**Response** (201 Created or 200 OK):
```json
{
  "message": "Activity liked successfully",
  "liked": true,
  "likes_count": 15
}
```

Or when unliking:
```json
{
  "message": "Like removed successfully",
  "liked": false,
  "likes_count": 14
}
```

**Note**: Same endpoint for both like and unlike. It toggles.

---

#### Get Activity Likes

Get paginated list of users who liked an activity.

**GET** `/activities/{activityId}/likes`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/activities/42/likes \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 5,
        "name": "Ana Silva",
        "username": "anasilva"
      },
      "created_at": "2025-11-10T12:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 15
  }
}
```

---

### Comments

#### Get Activity Comments

Get paginated list of comments on an activity.

**GET** `/activities/{activityId}/comments`
**Auth**: Required

**Request**:
```bash
curl http://localhost:8000/api/v1/activities/42/comments \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "activity_id": 42,
      "content": "Great run! Keep it up!",
      "user": {
        "id": 5,
        "name": "Carlos Oliveira",
        "username": "carlosoliveira",
        "is_following": true
      },
      "created_at": "2025-11-10T14:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

---

#### Create Comment

Post a comment on an activity.

**POST** `/activities/{activityId}/comments`
**Auth**: Required

**Request**:
```bash
curl -X POST http://localhost:8000/api/v1/activities/42/comments \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Awesome pace! How do you maintain it?"
  }'
```

**Response** (201 Created):
```json
{
  "message": "Comment created successfully",
  "data": {
    "id": 2,
    "activity_id": 42,
    "content": "Awesome pace! How do you maintain it?",
    "user": {
      "id": 1,
      "name": "João Silva",
      "username": "joaosilva"
    },
    "created_at": "2025-11-10T15:00:00.000000Z"
  }
}
```

**Validation**:
- `content`: required, min:1, max:1000

---

#### Delete Comment

Delete your own comment.

**DELETE** `/comments/{commentId}`
**Auth**: Required
**Permission**: Can only delete your own comments

**Request**:
```bash
curl -X DELETE http://localhost:8000/api/v1/comments/2 \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "message": "Comment deleted successfully"
}
```

**Errors**:
- `403 Forbidden` - Trying to delete someone else's comment

---

### Activity Feeds

#### Following Feed

Get activities from users you follow.

**GET** `/feed/following`
**Auth**: Required

**Query Parameters**:
- `limit` - optional, max:50 (default: 20)

**Request**:
```bash
curl "http://localhost:8000/api/v1/feed/following?limit=10" \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 100,
      "user_id": 5,
      "type": "run",
      "title": "Morning Run",
      "distance_meters": 5000,
      "duration_seconds": 1800,
      "visibility": "public",
      "started_at": "2025-11-10T06:00:00.000000Z"
    }
  ],
  "meta": {
    "count": 10,
    "limit": 10
  }
}
```

**Notes**:
- Only public activities
- Only completed activities
- Cached for 5 minutes
- Ordered by most recent first

---

#### Nearby Feed

Discover activities near a location using PostGIS.

**GET** `/feed/nearby`
**Auth**: Required

**Query Parameters**:
- `lat` - required, between:-90,90
- `lng` - required, between:-180,180
- `radius` - optional, kilometers (default: 10, max: 100)
- `limit` - optional, max:50 (default: 20)

**Request**:
```bash
curl "http://localhost:8000/api/v1/feed/nearby?lat=-23.5505&lng=-46.6333&radius=5&limit=20" \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 101,
      "user_id": 8,
      "type": "ride",
      "title": "Cycling in Ibirapuera",
      "distance_meters": 15000,
      "duration_seconds": 2700,
      "completed_at": "2025-11-10T08:00:00.000000Z"
    }
  ],
  "meta": {
    "count": 15,
    "radius_km": 5,
    "limit": 20
  }
}
```

**Notes**:
- Only activities with GPS routes
- Uses PostGIS `ST_DWithin` for efficient queries
- Cached for 5 minutes

---

#### Trending Feed

Get trending activities based on likes.

**GET** `/feed/trending`
**Auth**: Required

**Query Parameters**:
- `days` - optional, max:30 (default: 7)
- `limit` - optional, max:50 (default: 20)

**Request**:
```bash
curl "http://localhost:8000/api/v1/feed/trending?days=7&limit=10" \
  -H "Authorization: Bearer {your-token}"
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 150,
      "user_id": 12,
      "type": "run",
      "title": "Marathon PR!",
      "distance_meters": 42195,
      "duration_seconds": 10800,
      "completed_at": "2025-11-09T10:00:00.000000Z",
      "likes_count": 45
    }
  ],
  "meta": {
    "count": 10,
    "days": 7,
    "limit": 10
  }
}
```

**Notes**:
- Only activities with at least 1 like
- Ordered by likes (descending), then date
- Cached for 5 minutes

---

## Error Handling

### Standard Error Response

All errors follow this structure:

```json
{
  "message": "Human-readable error description",
  "errors": {
    "field_name": [
      "Specific validation error message"
    ]
  }
}
```

---

### HTTP Status Codes

| Code | Meaning | When It Happens |
|------|---------|-----------------|
| 200 | OK | Request successful (GET, PUT, PATCH) |
| 201 | Created | Resource created successfully (POST) |
| 204 | No Content | Resource deleted successfully (DELETE) |
| 400 | Bad Request | Invalid request format or business rule violation |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Valid token but no permission for this action |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Something broke on our end (check Sentry) |

---

### Common Error Examples

#### Validation Error (422)

**When**: Invalid request data

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required.",
      "The email must be a valid email address."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

**How to fix**: Check the `errors` object for field-specific messages. Update your request and try again.

---

#### Unauthorized (401)

**When**: No token, invalid token, or expired token

```json
{
  "message": "Unauthenticated."
}
```

**How to fix**: Login again to get a new token. Include it in the `Authorization: Bearer {token}` header.

---

#### Forbidden (403)

**When**: Valid token but no permission (e.g., editing someone else's activity)

```json
{
  "message": "This action is unauthorized."
}
```

**How to fix**: You can only edit/delete your own resources. Check that you're using the correct user account.

---

#### Not Found (404)

**When**: Resource doesn't exist or was deleted

```json
{
  "message": "Resource not found."
}
```

**How to fix**: Double-check the resource ID. It may have been deleted or never existed.

---

#### Rate Limit Exceeded (429)

**When**: Too many requests in a short time

```json
{
  "message": "Too Many Attempts."
}
```

**Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 45
```

**How to fix**: Wait for the time specified in `Retry-After` header (seconds). Implement exponential backoff in your client.

---

#### Server Error (500)

**When**: Something broke on the server

```json
{
  "message": "Server Error"
}
```

**How to fix**: This is on us, not you. The error has been logged to Sentry. Try again in a few moments. If it persists, contact support.

---

## Rate Limiting

**Default limits**:
- General endpoints: **60 requests/minute** per IP
- Auth endpoints (register, login): **5 requests/minute**
- Tracking endpoints: **20 requests/minute**

**Authenticated users**: Higher limits (**1000 requests/minute**)

**Response Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Retry-After: 45  (only when rate limited)
```

**What to do when rate limited**:
1. Check `Retry-After` header (seconds until reset)
2. Implement exponential backoff
3. Cache responses when possible
4. Use webhooks instead of polling (when available)

---

## Data Types & Units

### Timestamps

All timestamps use **ISO 8601 format** with UTC timezone:

```
2025-11-10T12:00:00.000000Z
```

**Converting to local time**: Parse the timestamp and convert in your client application.

---

### Distance Units

All distances are in **meters**:

```
5000 meters = 5 km
```

**Conversions**:
- To kilometers: `meters / 1000`
- To miles: `meters / 1609.34`

---

### Speed Units

All speeds are in **km/h** (kilometers per hour):

```
10.0 km/h
```

**Conversions**:
- To m/s: `kmh / 3.6`
- To mph: `kmh / 1.60934`

---

### Duration

All durations are in **seconds**:

```
1800 seconds = 30 minutes
```

**Converting to formatted time**: Use the `*_formatted` fields in responses (e.g., `elapsed_time_formatted: "00:09:02"`).

---

### Coordinates

**Latitude**: -90 to 90 (negative = South)
**Longitude**: -180 to 180 (negative = West)
**SRID**: 4326 (WGS84)

**Example** (São Paulo, Brazil):
```json
{
  "latitude": -23.5505,
  "longitude": -46.6333
}
```

---

## Activity Types

Enum values for `activity.type`:

| Value | Description |
|-------|-------------|
| `run` | Running |
| `ride` | Cycling |
| `walk` | Walking |
| `swim` | Swimming |
| `gym` | Gym workout |
| `other` | Other activity type |

---

## Activity Visibility

Enum values for `activity.visibility`:

| Value | Description |
|-------|-------------|
| `public` | Visible to everyone |
| `followers` | Visible to followers only |
| `private` | Visible only to you |

**Default**: `public`

---

## Segment Types

Enum values for `segment.type`:

| Value | Description |
|-------|-------------|
| `run` | Running segment |
| `ride` | Cycling segment |

**Note**: Segment type determines which activities can match. A running segment only matches running activities.

---

## Background Jobs

Some operations trigger background jobs that run asynchronously:

### ProcessSegmentEfforts

**Triggered by**: Finishing an activity (`POST /tracking/{id}/finish`)

**What it does**:
1. Checks if activity route overlaps with any segments (≥90% overlap)
2. Creates `SegmentEffort` records for matches
3. Calculates time, speed, and rank
4. Updates leaderboards (KOM/QOM)
5. Marks personal records (PR)

**How long it takes**: Usually < 2 seconds

**How to check if it's done**: Call `GET /me/records` to see your new segment efforts.

**Troubleshooting**: If segments aren't detected:
- Ensure activity has GPS route data
- Check that route overlaps ≥90% with segment
- Verify queue worker is running: `php artisan queue:work`

---

## Testing

### Postman Collection

**Import**: `docs/postman-collection.json`

**Features**:
- All endpoints pre-configured
- Auto-saves authentication token
- Example request bodies
- Environment variables for base URL

**Setup**:
1. Open Postman
2. Import → File → Select `docs/postman-collection.json`
3. Create environment: `base_url` = `http://localhost:8000`
4. Register a user → Token auto-saved for all requests

---

### Quick cURL Test

**Test the entire workflow** in one go:

```bash
# 1. Health check (no auth)
curl http://localhost:8000/api/health

# 2. Register and save token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "username": "testuser",
    "email": "test@test.com",
    "password": "password123",
    "password_confirmation": "password123"
  }' | jq -r '.token')

echo "Token: $TOKEN"

# 3. Create activity
curl -X POST http://localhost:8000/api/v1/activities \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "run",
    "title": "Test Run",
    "distance_meters": 5000,
    "duration_seconds": 1800,
    "started_at": "2025-11-10T10:00:00Z"
  }'

# 4. Get my activities
curl http://localhost:8000/api/v1/activities \
  -H "Authorization: Bearer $TOKEN"

# 5. Get my stats
curl http://localhost:8000/api/v1/statistics/me \
  -H "Authorization: Bearer $TOKEN"
```

If all commands succeed, the API is working! 🎉

---

## Questions?

**Need help getting started?**
- Check [docs/onboarding.md](onboarding.md) for setup guide

**Want to understand the architecture?**
- Read [docs/architecture.md](architecture.md) for system design

**Contributing to the project?**
- See [docs/contributing.md](contributing.md) for coding standards

**Found a bug?**
- Report it in Slack: #backend-fittrack

---

**End of API Documentation**

*Last updated: 2025-11-10 - FitTrack BR v1.0.0*

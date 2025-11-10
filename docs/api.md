# FitTrack BR - API Documentation

**Version**: 1.0.0
**Base URL**: `http://localhost:8000/api/v1`
**Last Updated**: 2025-11-10

---

## Table of Contents

1. [Authentication](#authentication)
2. [Users](#users)
3. [Activities](#activities)
4. [Activity Tracking](#activity-tracking)
5. [Statistics](#statistics)
6. [Segments](#segments)
7. [Social Features](#social-features)
   - [Follow System](#follow-system)
   - [Likes](#likes)
   - [Comments](#comments)
   - [Activity Feeds](#activity-feeds)
8. [Data Models](#data-models)
9. [Error Responses](#error-responses)

---

## Authentication

FitTrack BR uses **Laravel Sanctum** for API authentication with token-based authentication.

### How Authentication Works

1. **Register** or **Login** to receive an access token
2. Include the token in all authenticated requests:
   ```
   Authorization: Bearer {your-token}
   ```
3. **Logout** to revoke the current token

### Endpoints

#### Register

Create a new user account.

**Endpoint**: `POST /auth/register`
**Authentication**: Not required

**Request Body**:
```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "username": "joaosilva",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Validation Rules**:
- `name`: required, min:3
- `email`: required, email, unique
- `username`: required, min:3, unique
- `password`: required, min:8, confirmed

**Success Response** (201 Created):
```json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "username": "joaosilva",
    "avatar": null,
    "bio": null,
    "city": null,
    "state": null,
    "created_at": "2025-11-08T12:00:00.000000Z"
  },
  "token": "1|abcdef123456..."
}
```

---

#### Login

Authenticate and receive an access token.

**Endpoint**: `POST /auth/login`
**Authentication**: Not required

**Request Body**:
```json
{
  "email": "joao@example.com",
  "password": "password123"
}
```

**Validation Rules**:
- `email`: required, email
- `password`: required

**Success Response** (200 OK):
```json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "username": "joaosilva",
    "avatar": null,
    "created_at": "2025-11-08T12:00:00.000000Z"
  },
  "token": "2|xyz789..."
}
```

**Error Response** (401 Unauthorized):
```json
{
  "message": "Invalid credentials"
}
```

---

#### Get Current User

Get authenticated user information.

**Endpoint**: `GET /auth/me`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@example.com",
  "username": "joaosilva",
  "avatar": null,
  "cover_photo": null,
  "bio": "Runner from São Paulo",
  "city": "São Paulo",
  "state": "SP",
  "created_at": "2025-11-08T12:00:00.000000Z",
  "updated_at": "2025-11-08T12:00:00.000000Z"
}
```

---

#### Logout

Revoke the current access token.

**Endpoint**: `POST /auth/logout`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "message": "Logged out successfully"
}
```

---

## Users

User profile and related endpoints.

**Authentication**: All endpoints require authentication

_(Note: User management endpoints will be expanded in SCRUM 4)_

---

## Activities

CRUD operations for activities.

### Endpoints

#### List User Activities

Get all activities for the authenticated user.

**Endpoint**: `GET /activities`
**Authentication**: Required

**Query Parameters**:
- None (returns all user's activities)

**Success Response** (200 OK):
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
      "avg_cadence": 170,
      "visibility": "public",
      "started_at": "2025-11-08T06:00:00.000000Z",
      "completed_at": "2025-11-08T06:30:00.000000Z",
      "created_at": "2025-11-08T06:30:15.000000Z",
      "updated_at": "2025-11-08T06:30:15.000000Z"
    }
  ]
}
```

---

#### Create Activity

Create a new activity.

**Endpoint**: `POST /activities`
**Authentication**: Required

**Request Body**:
```json
{
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
  "avg_cadence": 170,
  "visibility": "public",
  "started_at": "2025-11-08T06:00:00Z",
  "completed_at": "2025-11-08T06:30:00Z"
}
```

**Validation Rules**:
- `type`: required, enum (run, ride, walk, swim, gym, other)
- `title`: required, min:3
- `description`: nullable, string
- `distance_meters`: nullable, numeric, min:0
- `duration_seconds`: nullable, integer, min:0
- `moving_time_seconds`: nullable, integer, min:0
- `elevation_gain`: nullable, numeric
- `elevation_loss`: nullable, numeric
- `avg_speed_kmh`: nullable, numeric, min:0
- `max_speed_kmh`: nullable, numeric, min:0
- `avg_heart_rate`: nullable, integer, min:30
- `max_heart_rate`: nullable, integer, min:30
- `calories`: nullable, integer, min:0
- `avg_cadence`: nullable, integer, min:0
- `visibility`: nullable, enum (public, followers, private)
- `started_at`: required, datetime
- `completed_at`: nullable, datetime, after_or_equal:started_at

**Success Response** (201 Created):
```json
{
  "id": 2,
  "type": "run",
  "title": "Morning Run",
  ...
}
```

---

#### Show Activity

Get a single activity by ID.

**Endpoint**: `GET /activities/{id}`
**Authentication**: Required

**Authorization**: User can only view their own activities

**Success Response** (200 OK):
```json
{
  "id": 1,
  "type": "run",
  "title": "Morning Run",
  ...
}
```

**Error Response** (403 Forbidden):
```json
{
  "message": "Unauthorized"
}
```

---

#### Update Activity

Update an existing activity.

**Endpoint**: `PUT/PATCH /activities/{id}`
**Authentication**: Required
**Authorization**: User can only update their own activities

**Request Body**: Same as Create Activity (all fields optional)

**Success Response** (200 OK):
```json
{
  "id": 1,
  "type": "run",
  "title": "Updated Title",
  ...
}
```

---

#### Delete Activity

Delete an activity.

**Endpoint**: `DELETE /activities/{id}`
**Authentication**: Required
**Authorization**: User can only delete their own activities

**Success Response** (204 No Content)

---

## Activity Tracking

Real-time GPS tracking for activities.

### How Tracking Works

1. **Start** tracking with activity type and title
2. **Track** GPS points continuously (latitude, longitude, altitude, heart rate)
3. **Pause/Resume** as needed during the activity
4. **Finish** to save the activity to the database
5. **Status** to check current tracking state

**Data Storage**: GPS points are stored in **Redis** with 2-hour TTL during tracking, then persisted to PostgreSQL on finish.

### Endpoints

#### Start Tracking

Initialize a new tracking session.

**Endpoint**: `POST /tracking/start`
**Authentication**: Required

**Request Body**:
```json
{
  "type": "run",
  "title": "Morning Run"
}
```

**Validation Rules**:
- `type`: required, enum (run, ride, walk, swim, gym, other)
- `title`: required, min:3

**Success Response** (201 Created):
```json
{
  "activity_id": "tracking_1_1699440000",
  "status": "active",
  "message": "Tracking started successfully"
}
```

---

#### Track Location

Add a GPS point to the current tracking session.

**Endpoint**: `POST /tracking/{activityId}/track`
**Authentication**: Required

**Request Body**:
```json
{
  "latitude": -23.5505,
  "longitude": -46.6333,
  "altitude": 760,
  "heart_rate": 145
}
```

**Validation Rules**:
- `latitude`: required, numeric, between:-90,90
- `longitude`: required, numeric, between:-180,180
- `altitude`: nullable, numeric
- `heart_rate`: nullable, integer, between:30,220

**Success Response** (200 OK):
```json
{
  "message": "Location tracked successfully",
  "total_points": 45,
  "current_distance_meters": 2345.67,
  "current_duration_seconds": 720
}
```

---

#### Pause Tracking

Pause the current tracking session.

**Endpoint**: `POST /tracking/{activityId}/pause`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "status": "paused",
  "message": "Tracking paused"
}
```

---

#### Resume Tracking

Resume a paused tracking session.

**Endpoint**: `POST /tracking/{activityId}/resume`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "status": "active",
  "message": "Tracking resumed"
}
```

---

#### Finish Tracking

Finalize tracking and save activity to database.

**Endpoint**: `POST /tracking/{activityId}/finish`
**Authentication**: Required

**Success Response** (200 OK):
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

**Note**: This also triggers background job `ProcessSegmentEfforts` to detect matching segments.

---

#### Get Tracking Status

Get current status of a tracking session.

**Endpoint**: `GET /tracking/{activityId}/status`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "activity_id": "tracking_1_1699440000",
  "status": "active",
  "started_at": "2025-11-08T06:00:00Z",
  "total_points": 45,
  "distance_meters": 2345.67,
  "duration_seconds": 720,
  "elevation_gain": 25.5,
  "elevation_loss": 18.2
}
```

---

## Statistics

Activity statistics and analytics.

### Endpoints

#### User Statistics

Get aggregated statistics for the authenticated user.

**Endpoint**: `GET /statistics/me`
**Authentication**: Required

**Success Response** (200 OK):
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
    },
    "walk": {
      "activities": 2,
      "distance_meters": 5000,
      "duration_seconds": 2400,
      "elevation_gain": 50
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

---

#### Activity Feed

Get public activities feed.

**Endpoint**: `GET /statistics/feed`
**Authentication**: Required

**Query Parameters**:
- `limit`: integer, max:100 (default: 20)
- `offset`: integer (default: 0)

**Example**: `GET /statistics/feed?limit=10&offset=0`

**Success Response** (200 OK):
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
      "started_at": "2025-11-08T18:00:00.000000Z",
      "completed_at": "2025-11-08T18:40:00.000000Z"
    }
  ],
  "meta": {
    "limit": 10,
    "offset": 0,
    "total": 150
  }
}
```

---

#### Activity Splits

Get per-kilometer splits for an activity.

**Endpoint**: `GET /statistics/activities/{id}/splits`
**Authentication**: Required

**Success Response** (200 OK):
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
    },
    {
      "split_number": 3,
      "distance_meters": 1000,
      "pace_per_km": "5:35",
      "speed_kmh": 10.7
    }
  ]
}
```

**Note**: Returns empty array if activity has no GPS data.

---

#### Activity Pace Zones

Get pace zone distribution for an activity.

**Endpoint**: `GET /statistics/activities/{id}/pace-zones`
**Authentication**: Required

**Success Response** (200 OK):
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

**Note**: Zones are calculated based on activity's average pace.

---

## Segments

Route segments and competitions.

### Endpoints

#### List Segments

Get all segments with optional filters.

**Endpoint**: `GET /segments`
**Authentication**: Required

**Query Parameters**:
- `creator_id`: integer (filter by creator)
- `type`: enum (run, ride)

**Example**: `GET /segments?type=run&creator_id=5`

**Success Response** (200 OK):
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
      "is_hazardous": false,
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

#### Create Segment

Create a new segment.

**Endpoint**: `POST /segments`
**Authentication**: Required

**Request Body**:
```json
{
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
}
```

**Validation Rules**:
- `name`: required, min:3
- `description`: nullable, string
- `type`: required, enum (run, ride)
- `distance_meters`: required, numeric, between:100,100000
- `avg_grade_percent`: nullable, numeric
- `max_grade_percent`: nullable, numeric
- `elevation_gain`: nullable, numeric
- `city`: nullable, string
- `state`: nullable, string
- `is_hazardous`: nullable, boolean

**Success Response** (201 Created):
```json
{
  "id": 10,
  "name": "Paulista Sprint",
  ...
}
```

---

#### Show Segment

Get a single segment by ID.

**Endpoint**: `GET /segments/{id}`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "id": 1,
  "name": "Ibirapuera Loop",
  ...
}
```

---

#### Update Segment

Update an existing segment.

**Endpoint**: `PUT/PATCH /segments/{id}`
**Authentication**: Required
**Authorization**: Only creator can update

**Request Body**: Same as Create Segment (all fields optional)

**Success Response** (200 OK)

---

#### Delete Segment

Delete a segment.

**Endpoint**: `DELETE /segments/{id}`
**Authentication**: Required
**Authorization**: Only creator can delete

**Success Response** (204 No Content)

---

#### Find Nearby Segments

Find segments near a location.

**Endpoint**: `GET /segments/nearby`
**Authentication**: Required

**Query Parameters**:
- `latitude`: required, numeric, between:-90,90
- `longitude`: required, numeric, between:-180,180
- `radius`: optional, integer, min:1 (default: 10 km)

**Example**: `GET /segments/nearby?latitude=-23.5505&longitude=-46.6333&radius=5`

**Success Response** (200 OK):
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
      "state": "SP",
      ...
    },
    {
      "id": 5,
      "name": "Pinheiros River Trail",
      "type": "run",
      "distance_meters": 5200,
      "distance_to_point_km": 3.8,
      ...
    }
  ]
}
```

---

### Get Segment Leaderboard

Get top 20 efforts for a segment, ordered by fastest time.

**Endpoint**: `GET /segments/{segment_id}/leaderboard`
**Authentication**: Required

**Example**: `GET /segments/1/leaderboard`

**Success Response** (200 OK):
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
        "username": "joaosilva",
        "avatar": null
      },
      "elapsed_time_seconds": 558.2,
      "elapsed_time_formatted": "00:09:18",
      "average_speed_kmh": 18.1,
      "average_pace_min_km": "03:18",
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
- Shows the best effort for each unique user
- Limited to top 20 efforts
- `is_kom` indicates if this is the segment's KOM (King/Queen of Mountain)
- `is_pr` indicates if this is the user's personal record

---

### Get User Personal Records

Get all segments where user has efforts, showing their best time.

**Endpoint**: `GET /me/records` (authenticated user)
**Endpoint**: `GET /users/{user_id}/records` (specific user)
**Authentication**: Required

**Example**: `GET /me/records`

**Success Response** (200 OK):
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
    },
    {
      "segment": {
        "id": 3,
        "name": "Paulista Climb",
        "distance_km": 1.2,
        "type": "run"
      },
      "personal_record": {
        "elapsed_time_seconds": 312.0,
        "elapsed_time_formatted": "00:05:12",
        "average_speed_kmh": 13.8,
        "achieved_at": "2025-11-08T08:15:00Z",
        "is_kom": false
      },
      "rank": 3,
      "total_attempts": 8
    }
  ],
  "user": {
    "id": 5,
    "name": "Maria Santos",
    "username": "mariasantos"
  }
}
```

**Notes**:
- Only returns segments where user has efforts marked as PR (Personal Record)
- `rank` shows user's position in segment leaderboard
- `total_attempts` shows how many times user has completed this segment

---

### Get Current KOM

Get the current KOM (King of Mountain) holder for a segment.

**Endpoint**: `GET /segments/{segment_id}/kom`
**Authentication**: Required

**Example**: `GET /segments/1/kom`

**Success Response** (200 OK):
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

**Notes**:
- KOM is the fastest **male** athlete on the segment
- Returns `null` if no male athletes have completed the segment

---

### Get Current QOM

Get the current QOM (Queen of Mountain) holder for a segment.

**Endpoint**: `GET /segments/{segment_id}/qom`
**Authentication**: Required

**Example**: `GET /segments/1/qom`

**Success Response** (200 OK):
```json
{
  "data": {
    "user": {
      "id": 5,
      "name": "Maria Santos",
      "username": "mariasantos",
      "avatar": "https://..."
    },
    "elapsed_time_seconds": 558.2,
    "elapsed_time_formatted": "00:09:18",
    "average_speed_kmh": 18.1,
    "achieved_at": "2025-11-09T15:20:00Z"
  },
  "segment": {
    "id": 1,
    "name": "Ibirapuera Loop"
  }
}
```

**No QOM Response** (200 OK):
```json
{
  "data": null,
  "message": "No QOM recorded for this segment yet"
}
```

**Notes**:
- QOM is the fastest **female** athlete on the segment
- Returns `null` if no female athletes have completed the segment

---

## Social Features

FitTrack BR includes comprehensive social features to connect users and create an engaging fitness community.

### Follow System

Users can follow each other to see their activities in personalized feeds.

#### Follow a User

**Endpoint**: `POST /users/{userId}/follow`
**Authentication**: Required

**Success Response** (201 Created):
```json
{
  "message": "Successfully followed user",
  "user": {
    "id": 2,
    "name": "Maria Santos",
    "username": "mariasantos",
    "email": "maria@example.com",
    "followers_count": 15,
    "following_count": 23,
    "is_following": true,
    "created_at": "2025-11-10T12:00:00.000000Z"
  }
}
```

**Error Responses**:
- `400 Bad Request` - Cannot follow yourself
- `409 Conflict` - Already following this user

---

#### Unfollow a User

**Endpoint**: `DELETE /users/{userId}/unfollow`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "message": "Successfully unfollowed user"
}
```

**Error Responses**:
- `404 Not Found` - Not following this user

---

#### Get User Followers

Get a paginated list of users who follow a specific user.

**Endpoint**: `GET /users/{userId}/followers`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "data": [
    {
      "id": 3,
      "name": "Pedro Costa",
      "username": "pedrocosta",
      "email": "pedro@example.com",
      "followers_count": 10,
      "following_count": 15,
      "is_following": false,
      "created_at": "2025-11-10T12:00:00.000000Z"
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

Get a paginated list of users that a specific user follows.

**Endpoint**: `GET /users/{userId}/following`
**Authentication**: Required

**Response**: Same structure as Get User Followers

---

### Likes

Users can like activities to show appreciation and support.

#### Toggle Like on Activity

Like or unlike an activity (toggle action).

**Endpoint**: `POST /activities/{activityId}/likes`
**Authentication**: Required

**Success Response** (201 Created or 200 OK):
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

---

#### Get Activity Likes

Get a paginated list of users who liked an activity.

**Endpoint**: `GET /activities/{activityId}/likes`
**Authentication**: Required

**Success Response** (200 OK):
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
    "last_page": 1,
    "per_page": 50,
    "total": 15
  }
}
```

---

### Comments

Users can comment on activities to provide feedback and encouragement.

#### Get Activity Comments

Get a paginated list of comments on an activity.

**Endpoint**: `GET /activities/{activityId}/comments`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "activity_id": 10,
      "user_id": 5,
      "content": "Great run! Keep it up!",
      "user": {
        "id": 5,
        "name": "Carlos Oliveira",
        "username": "carlosoliveira",
        "email": "carlos@example.com",
        "followers_count": 20,
        "following_count": 30,
        "is_following": true,
        "created_at": "2025-11-10T10:00:00.000000Z"
      },
      "created_at": "2025-11-10T14:30:00.000000Z",
      "updated_at": "2025-11-10T14:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

---

#### Create Comment

Post a comment on an activity.

**Endpoint**: `POST /activities/{activityId}/comments`
**Authentication**: Required

**Request Body**:
```json
{
  "content": "Awesome pace! How do you maintain it?"
}
```

**Validation Rules**:
- `content`: required, string, min:1, max:1000

**Success Response** (201 Created):
```json
{
  "message": "Comment created successfully",
  "data": {
    "id": 2,
    "activity_id": 10,
    "user_id": 1,
    "content": "Awesome pace! How do you maintain it?",
    "user": {
      "id": 1,
      "name": "João Silva",
      "username": "joaosilva",
      "email": "joao@example.com",
      "followers_count": 10,
      "following_count": 15,
      "created_at": "2025-11-10T10:00:00.000000Z"
    },
    "created_at": "2025-11-10T15:00:00.000000Z",
    "updated_at": "2025-11-10T15:00:00.000000Z"
  }
}
```

---

#### Delete Comment

Delete your own comment.

**Endpoint**: `DELETE /comments/{commentId}`
**Authentication**: Required

**Success Response** (200 OK):
```json
{
  "message": "Comment deleted successfully"
}
```

**Error Responses**:
- `403 Forbidden` - You can only delete your own comments

---

### Activity Feeds

Personalized activity feeds to discover and stay connected.

#### Following Feed

Get activities from users you follow.

**Endpoint**: `GET /feed/following`
**Authentication**: Required

**Query Parameters**:
- `limit` (optional, default: 20, max: 50) - Number of activities to return

**Example**: `GET /feed/following?limit=10`

**Success Response** (200 OK):
```json
{
  "data": [
    {
      "id": 100,
      "user_id": 5,
      "type": "run",
      "title": "Morning Run",
      "description": "Easy recovery run",
      "distance_meters": 5000,
      "duration_seconds": 1800,
      "elevation_gain": 50,
      "avg_speed_kmh": 10.0,
      "visibility": "public",
      "started_at": "2025-11-10T06:00:00.000000Z",
      "completed_at": "2025-11-10T06:30:00.000000Z"
    }
  ],
  "meta": {
    "count": 10,
    "limit": 10
  }
}
```

**Notes**:
- Only shows public activities
- Only shows completed activities
- Results are cached for 5 minutes
- Ordered by most recent first

---

#### Nearby Feed

Discover activities near a specific location using PostGIS.

**Endpoint**: `GET /feed/nearby`
**Authentication**: Required

**Query Parameters**:
- `lat` (required, float, between: -90,90) - Latitude
- `lng` (required, float, between: -180,180) - Longitude
- `radius` (optional, integer, min: 1, max: 100, default: 10) - Search radius in kilometers
- `limit` (optional, integer, min: 1, max: 50, default: 20) - Number of activities

**Example**: `GET /feed/nearby?lat=-23.5505&lng=-46.6333&radius=5&limit=20`

**Success Response** (200 OK):
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
      "visibility": "public",
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
- Uses PostGIS ST_DWithin for efficient geospatial queries
- Results are cached for 5 minutes

---

#### Trending Feed

Get trending activities based on likes.

**Endpoint**: `GET /feed/trending`
**Authentication**: Required

**Query Parameters**:
- `days` (optional, integer, max: 30, default: 7) - Time window in days
- `limit` (optional, integer, min: 1, max: 50, default: 20) - Number of activities

**Example**: `GET /feed/trending?days=7&limit=10`

**Success Response** (200 OK):
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
      "visibility": "public",
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
- Only shows activities with at least 1 like
- Ordered by likes count (descending), then by date
- Results are cached for 5 minutes

---

## Data Models

### Activity Types

Enum values for `activity.type`:
- `run` - Running
- `ride` - Cycling
- `walk` - Walking
- `swim` - Swimming
- `gym` - Gym workout
- `other` - Other activity

### Activity Visibility

Enum values for `activity.visibility`:
- `public` - Visible to everyone
- `followers` - Visible to followers only
- `private` - Visible only to user

### Segment Types

Enum values for `segment.type`:
- `run` - Running segment
- `ride` - Cycling segment

---

## Error Responses

### Standard Error Format

All errors follow this structure:

```json
{
  "message": "Error description",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST (resource created) |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | Valid token but no permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Common Error Examples

#### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

#### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

#### Forbidden (403)
```json
{
  "message": "This action is unauthorized."
}
```

#### Not Found (404)
```json
{
  "message": "Resource not found."
}
```

---

## Rate Limiting

**Default**: 60 requests per minute per IP
**Tracking endpoints**: 20 requests per minute
**Auth endpoints**: 5 requests per minute

**Headers**:
- `X-RateLimit-Limit`: Total requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `Retry-After`: Seconds until rate limit resets (when exceeded)

---

## Notes

### Timestamps

All timestamps are in **ISO 8601 format** with UTC timezone:
```
2025-11-08T12:00:00.000000Z
```

### Distance Units

- All distances are in **meters**
- Convert to km: `meters / 1000`
- Convert to miles: `meters / 1609.34`

### Speed Units

- All speeds are in **km/h**
- Convert to m/s: `kmh / 3.6`
- Convert to mph: `kmh / 1.60934`

### Coordinates

- **Latitude**: -90 to 90 (negative = South)
- **Longitude**: -180 to 180 (negative = West)
- **SRID**: 4326 (WGS84)

### Background Jobs

Some operations trigger background jobs:
- **Finishing an activity** → Triggers `ProcessSegmentEfforts` job
- Segment detection happens automatically (90% route overlap threshold)

---

## Future Endpoints

The following endpoints are planned for upcoming SCRUMs:

### SCRUM 4 - Social Features (Coming Soon)
- `POST /users/{user}/follow` - Follow a user
- `DELETE /users/{user}/unfollow` - Unfollow a user
- `GET /users/{user}/followers` - Get followers list
- `GET /users/{user}/following` - Get following list
- `POST /activities/{activity}/kudos` - Toggle kudos
- `GET /activities/{activity}/comments` - Get comments
- `POST /activities/{activity}/comments` - Add comment
- `DELETE /comments/{comment}` - Delete comment
- `GET /feed/following` - Following feed
- `GET /feed/nearby` - Nearby activities feed
- `GET /feed/trending` - Trending activities

### SCRUM 5 - Challenges
- `GET /challenges` - List challenges
- `POST /challenges` - Create challenge
- `POST /challenges/{challenge}/join` - Join challenge
- `GET /challenges/{challenge}/leaderboard` - Challenge leaderboard

### Segment Leaderboards (Pending)
- `GET /segments/{segment}/leaderboard` - Segment leaderboard
- `GET /users/me/personal-records` - User's PRs
- `GET /users/me/kom-achievements` - User's KOMs

---

**End of Documentation**

For implementation details, see:
- ADR documents in `.claude/decisions/`
- Implementation guide in `.claude/IMPLEMENTATION-GUIDE-DATA-VALUEOBJECTS.md`
- Sprint documentation in `.claude/current-sprint.md`

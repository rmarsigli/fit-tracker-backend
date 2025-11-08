# FitTrack BR - API Documentation

**Version**: 1.0.0
**Base URL**: `http://localhost:8000/api/v1`
**Last Updated**: 2025-11-08

---

## Table of Contents

1. [Authentication](#authentication)
2. [Users](#users)
3. [Activities](#activities)
4. [Activity Tracking](#activity-tracking)
5. [Statistics](#statistics)
6. [Segments](#segments)
7. [Data Models](#data-models)
8. [Error Responses](#error-responses)

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

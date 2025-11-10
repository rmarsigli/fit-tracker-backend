# 📚 FitTrack BR - Technical Documentation

Welcome to the technical documentation of **FitTrack BR**, an advanced fitness activity tracking platform with geolocation capabilities.

---

## 📑 Documentation Index

### 🏗️ Architecture

- **[Why We Use Spatie Data + ValueObjects](./architecture/data-classes-decision.md)** ⭐
  - Complete architectural decision
  - Detailed comparison: Form Requests vs Data Classes
  - Real project examples
  - FAQ and implementation strategy

### 📋 Architectural Decision Records (ADRs)

All important decisions are documented in `.claude/decisions.md`:

- **ADR-001**: PostGIS Native vs Packages
- **ADR-002**: Real-time Tracking with Redis
- **ADR-003**: API Versioning Strategy
- **ADR-004**: Validation via Form Requests
- **ADR-005**: Smart Files Organization
- **ADR-006**: Testing with Pest 4
- **ADR-007**: Segment Detection Strategy
- **ADR-009**: Data Classes & ValueObjects Architecture

### 🚀 Development Guides

*Coming soon*:
- Local Environment Setup
- Contribution Guide
- Code Standards
- Testing Guidelines

### 📊 Project Progress

Track development progress in `.claude/current-sprint.md`:

- ✅ **SCRUM 1**: Foundation & Database (23 tests)
- ✅ **SCRUM 2**: Activities Core Features (37 tests)
- ✅ **SCRUM 3**: Geolocation & Segments (41 tests)
- ⏳ **SCRUM 4**: Social Features (pending)
- ⏳ **SCRUM 5**: Challenges & MVP Polish (pending)

**Current Status**: 60% complete | 142 tests passing

---

## 🎯 Quick Links

### Development

- [Complete ADRs](../.claude/decisions.md)
- [Current Sprint](../.claude/current-sprint.md)
- [Backlog](../.claude/backlog.md)
- [Completed Sprints](../.claude/completed/)

### Code

- [Models](../app/Models/)
- [Services](../app/Services/)
- [Data Classes](../app/Data/)
- [ValueObjects](../app/ValueObjects/)
- [Controllers](../app/Http/Controllers/Api/v1/)

### Tests

- [Feature Tests](../tests/Feature/)
- [Unit Tests](../tests/Unit/)

---

## 📖 About The Project

**FitTrack BR** is a fitness activity tracking platform (inspired by Strava) focused on the Brazilian market. The project uses modern technologies and advanced development patterns.

### Core Stack

- **Backend**: Laravel 12.37.0 (PHP 8.4.14)
- **Database**: PostgreSQL 16 + PostGIS 3.4
- **Cache**: Redis 7
- **Testing**: Pest 4
- **Architecture**: Data Classes (Spatie) + ValueObjects

### Main Features

- ✅ Real-time GPS tracking via Redis
- ✅ PostGIS for geospatial queries
- ✅ Automatic segment detection
- ✅ Leaderboards and Personal Records (PR)
- ✅ King/Queen of Mountain (KOM/QOM)
- ✅ Advanced statistics (splits, pace zones)
- ⏳ Social system (follow, kudos, comments)
- ⏳ Challenge system

---

## 🤝 Contributing

Before contributing, read:

1. **[Data Classes Decision](./architecture/data-classes-decision.md)** - Understand our architecture
2. **[ADRs](../.claude/decisions.md)** - Know our technical decisions
3. **[CLAUDE.md](../CLAUDE.md)** - Development guide (Laravel Boost)

---

## 📞 Support

- **Issues**: GitHub Issues
- **Laravel Documentation**: https://laravel.com/docs/12.x
- **Spatie Data Docs**: https://spatie.be/docs/laravel-data
- **PostGIS Docs**: https://postgis.net/documentation

---

**Last Updated**: 2025-11-10
**Version**: 0.6.0 (MVP 60%)

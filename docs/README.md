# 📚 FitTrack BR - Technical Documentation

Welcome to the technical documentation of **FitTrack BR**, an advanced fitness activity tracking platform with geolocation capabilities.

---

## 🗂️ Documentation Structure

```
docs/
├── README.md                          # This file (main index)
├── architecture/                      # Architecture and technical decisions
│   ├── decisions/                     # Architecture Decision Records (ADRs) - New decisions
│   ├── data-classes-decision.md       # Why we use Spatie Data + ValueObjects
│   └── overview.md                    # Architecture overview
├── implementation/                    # Sprints and implementations
│   ├── 2025-11-10-sprint-summary.md  # Optimization sprint (NEW)
│   └── status.md                      # Current task status (NEW)
├── guides/                            # Practical guides
│   ├── onboarding.md                  # How to get started
│   ├── deployment.md                  # How to deploy
│   ├── testing.md                     # How to run and create tests
│   └── performance.md                 # How to optimize queries
└── changelog/                         # Change history
    └── 2025-11.md                     # Changes from November/2025
```

---

## 🚀 Quick Start

### For New Developers
1. 📖 Read [Onboarding Guide](guides/onboarding.md)
2. 🏛️ Understand the [Architecture](architecture/data-classes-decision.md)
3. 📋 Review [Architectural Decisions](architecture/decisions/README.md)
4. 🔧 Configure following [CLAUDE.md](../CLAUDE.md)

### For Code Review
1. 🔍 Consult [relevant ADRs](architecture/decisions/README.md)
2. 📊 Check [Implementation Status](implementation/status.md)
3. 📝 Review old decisions in [.claude/decisions.md](../.claude/decisions.md)

### For Deployment
1. 🚢 Follow [Deploy Guide](guides/deployment.md)
2. ✅ Validate [Deploy Checklist](guides/deployment.md#checklist)

---

## 📑 Documentation Index

### 🏗️ Architecture

#### Core Architecture Decisions
- **[Why We Use Spatie Data + ValueObjects](./architecture/data-classes-decision.md)** ⭐
  - Complete architectural decision
  - Detailed comparison: Form Requests vs Data Classes
  - Real project examples
  - FAQ and implementation strategy

- **[Architecture Overview](./architecture/overview.md)**
  - Complete technology stack
  - Directory structure
  - Design patterns used

#### Architecture Decision Records (ADRs)

**Historical Decisions** (in `.claude/decisions.md`):
- **ADR-001**: PostGIS Native vs Packages
- **ADR-002**: Real-time Tracking with Redis
- **ADR-003**: API Versioning Strategy
- **ADR-004**: Validation via Form Requests
- **ADR-005**: Smart Files Organization
- **ADR-006**: Testing with Pest 4
- **ADR-007**: Segment Detection Strategy
- **ADR-009**: Data Classes & ValueObjects Architecture

**Recent Decisions** (Sprint 2025-11-10) in `architecture/decisions/`:
- **[ADR-001](architecture/decisions/ADR-001-skip-scribe.md)**: Why NOT implement Scribe API Docs
- **[ADR-002](architecture/decisions/ADR-002-skip-integration-tests.md)**: Why NOT implement Integration Tests
- **[ADR-003](architecture/decisions/ADR-003-skip-performance-tests.md)**: Use Telescope instead of Performance Tests
- **[ADR-004](architecture/decisions/ADR-004-skip-repository-pattern.md)**: Why NOT use Repository Pattern ⭐
- **[ADR-005](architecture/decisions/ADR-005-keep-raw-sql.md)**: Why KEEP Raw SQL ⭐
- **[ADR-006](architecture/decisions/ADR-006-implement-cache-leaderboards.md)**: Cache in Leaderboards (40x faster) ⭐
- **[ADR-007](architecture/decisions/ADR-007-implement-cursor-pagination.md)**: Cursor Pagination
- **[ADR-008](architecture/decisions/ADR-008-implement-covering-indexes.md)**: Covering Indexes

See [complete ADR index](architecture/decisions/README.md)

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

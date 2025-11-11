# Architecture Decision Records (ADRs)

This directory contains all Architecture Decision Records (ADRs) for the project.

## What is an ADR?

An ADR documents a significant architectural decision along with its context and consequences.

## Format

Each ADR follows this template:

```markdown
# ADR-XXX: Decision Title

**Status**: Accepted | Rejected | Superseded | Deprecated
**Date**: YYYY-MM-DD
**Deciders**: Name(s)
**Context**: [Link to issue/discussion]

## Context and Problem

Describe the problem or situation that motivated the decision.

## Decision

What decision was made and why.

## Consequences

### Positive
- Benefit 1
- Benefit 2

### Negative
- Trade-off 1
- Trade-off 2

## Alternatives Considered

- Alternative 1: why it was rejected
- Alternative 2: why it was rejected

## Links
- [Original discussion](#)
- [Implementation](#)
```

## ADR Index

| # | Title | Status | Date |
|---|-------|--------|------|
| 001 | Do not implement Scribe API Docs | Accepted | 2025-11-10 |
| 002 | Do not implement Integration Tests | Accepted | 2025-11-10 |
| 003 | Use Telescope instead of Performance Tests | Accepted | 2025-11-10 |
| 004 | Do not implement Repository Pattern | Accepted | 2025-11-10 |
| 005 | Keep Raw SQL instead of Query Builders | Accepted | 2025-11-10 |
| 006 | Implement Cache in Leaderboards | Accepted | 2025-11-10 |
| 007 | Implement Cursor Pagination | Accepted | 2025-11-10 |
| 008 | Implement Covering Indexes | Accepted | 2025-11-10 |
| 009 | Use ValueObject Accessors | Accepted | 2025-11-10 |

## When to create an ADR?

Create an ADR when:
- ✅ Decision affects system architecture
- ✅ Decision is difficult to reverse
- ✅ Important trade-offs were considered
- ✅ Future team needs to understand the "why"

Don't create ADR for:
- ❌ Trivial decisions (naming, formatting)
- ❌ Obvious decisions (using Git)
- ❌ Decisions that change frequently

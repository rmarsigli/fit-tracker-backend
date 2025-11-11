# Project Management

## Organization Structure

Use `.claude/` directory for project management, sprint tracking, and architectural decisions.

@verbatim
<code-snippet name=".claude/ Folder Structure">
.claude/
├── context.md              # Current session context
├── current-sprint.md       # Active sprint tracking
├── backlog.md             # Future tasks and ideas
├── settings.local.json    # Local configuration
│
├── agents/                # AI agent templates
├── bugs/                  # Bug tracking
├── completed/             # ✅ Archived sprints
├── decisions/             # 🏛️ Architecture Decision Records (ADRs)
├── guidelines/            # 📚 Implementation guides
├── notes/                 # 📝 Quick notes, bugs, ideas
├── planning/              # 📋 Future SCRUMs (not started)
└── summaries/             # 📊 Session summaries
</code-snippet>
@endverbatim

---

## Folder Purposes

### Root Files (Always Present)

**context.md** - Current session state
- What you're working on now
- Last completed task
- Next task to continue
- Important context to remember
- Updated before clearing context

**current-sprint.md** - Active sprint tracking
- Current sprint details
- Task checklist
- Progress tracking
- Metrics (estimated vs actual time)

**backlog.md** - Future work
- Post-MVP features
- Technical debt
- Nice-to-have improvements
- Ideas and proposals

**settings.local.json** - Local configuration
- Project-specific settings
- Should be gitignored

---

### Folder Descriptions

**agents/** - AI agent templates
- Code reviewer agents
- Test generator agents
- Bug tracker agents
- Custom automation agents

**bugs/** - Bug tracking
- Bug templates
- Known issues
- Bug examples
- Separate from `notes/bugs.md` (bugs/ is more structured)

**completed/** - Sprint archive
- Naming: `YYYY-MM-DD-scrum-X-sprint-Y-description.md`
- Move here when sprint is done
- Reference for future agents
- Historical record
- Guides that are no longer needed

**decisions/** - Architecture Decision Records (ADRs)
- Format: `ADR-XXX-decision-title.md`
- Why decisions were made
- Trade-offs considered
- Alternatives rejected
- Has README.md with ADR table

**guidelines/** - Implementation guides
- Implementation patterns
- Best practices
- Complex feature guides
- Team knowledge
- Format: `IMPLEMENTATION-GUIDE-topic.md`

**notes/** - Quick notes
- `notes.md` - General quick thoughts
- `bugs.md` - Known bugs to fix
- Ad-hoc notes during development
- Temporary information

**planning/** - Future SCRUMs
- Detailed sprint plans not yet started
- Implementation guides for future work
- Keep here until sprint starts
- Move to `completed/` when done
- Format: `scrum-X-description.md`

**summaries/** - Session records
- Naming: `session-summary-YYYY-MM-DD-description.md`
- Auto-generated session summaries
- Useful for context continuation
- Can be cleaned up after sprint completion

---

## Sprint Management

### Rules
- Work in `current-sprint.md` for active tasks
- Move completed sprints to `completed/YYYY-MM-DD-scrum-X-*.md`
- Use checkboxes `[ ]` for task tracking
- Record estimated vs actual time
- Future ideas go in `backlog.md`
- Active planning goes in `planning/scrum-X-*.md`

### Creating New Sprint

1. **Plan in `planning/`**
@verbatim
   <code-snippet name="Create sprint plan" lang="bash">
   # Create new SCRUM plan
   .claude/planning/scrum-X-description.md
   </code-snippet>
@endverbatim

2. **Add to `current-sprint.md`**
   - Copy sprint overview
   - Add task checklist
   - Set status to IN PROGRESS

3. **Create todos**
   - Use TodoWrite tool
   - Track progress

### Completing Sprint

1. **Mark tasks complete**
   - Update task checklist
   - Add actual time spent
   - Document results

2. **Move to `completed/`**
@verbatim
   <code-snippet name="Archive completed sprint" lang="bash">
   mv .claude/planning/scrum-X-*.md \
      .claude/completed/YYYY-MM-DD-scrum-X-*.md
   </code-snippet>
@endverbatim

3. **Update `current-sprint.md`**
   - Mark sprint as COMPLETED
   - Add summary
   - Update metrics

4. **Update `backlog.md`**
   - Mark completed items
   - Add new tasks discovered

---

## Sprint Format

### SCRUM File (in planning/)

@verbatim
<code-snippet name="SCRUM Plan Template" lang="markdown">
# SCRUM X - Description

**Created**: YYYY-MM-DD
**Status**: PENDING / IN PROGRESS / COMPLETED
**Priority**: P1 - CRITICAL / P2 - HIGH / P3 - MEDIUM
**Goal**: What this SCRUM achieves
**Estimated Time**: X hours

## Sprints

### Sprint X.1 - Description
**Estimated**: X hours
**Priority**: PX
**Status**: PENDING

**Tasks**:
- [ ] Task 1
- [ ] Task 2

**Deliverables**:
- Deliverable 1
- Deliverable 2

**Success Criteria**:
- Criterion 1
- Criterion 2
</code-snippet>
@endverbatim

### current-sprint.md Format

@verbatim
<code-snippet name="Current Sprint Tracking" lang="markdown">
# Current Sprint - Project Name

**Last Updated**: YYYY-MM-DD HH:MM
**Current Status**: SCRUM X - Sprint X.Y
**Overall Progress**: X/Y SCRUMs completed (XX%)
**Current Score**: XX/100

## Active Sprint

### Sprint X.Y - Description

**Estimated**: X hours
**Actual**: Y hours (if completed)
**Priority**: PX
**Status**: IN PROGRESS / COMPLETED

**Tasks**:
- [x] Completed task
- [ ] In progress task
- [ ] Pending task

**Deliverables**: [list what was delivered]

**Success Criteria**: [list what was achieved]
</code-snippet>
@endverbatim

### Priority Levels
- **[P1]** Critical - Must complete this sprint
- **[P2]** High - Should complete this sprint
- **[P3]** Medium - Nice to have
- **[P4]** Low - Can move to backlog

---

## Architecture Decision Records (ADRs)

### When to Create an ADR

Create a new ADR when:
- Making architectural decisions
- Choosing between multiple valid approaches
- Establishing patterns or conventions
- Decision has long-term impact
- Team needs to understand "why"

### ADR Format

@verbatim
<code-snippet name="ADR Template" lang="markdown">
# ADR-XXX: Decision Title

**Date**: YYYY-MM-DD
**Status**: Accepted / Superseded / Deprecated
**Context**: Sprint/SCRUM where decision was made

## Context

Why did we need to make this decision?

## Decision

What did we decide?

## Consequences

### Positive
- Benefit 1
- Benefit 2

### Negative
- Trade-off 1
- Trade-off 2

## Alternatives Considered

What other options did we consider?
- Option A: Why rejected
- Option B: Why rejected

## Implementation

How is this implemented in the codebase?
- File references
- Code examples

## References

- Related ADRs
- External docs
- Sprint references
</code-snippet>
@endverbatim

### ADR Naming

Format: `ADR-XXX-decision-title.md`

**Examples**:
- `ADR-001-postgis-native.md`
- `ADR-009-data-valueobjects-architecture.md`
- `ADR-011-cicd-github-actions.md`

ADRs are numbered sequentially starting from 001.

---

## Context Management

### Before Clearing Context **ALWAYS update these files**:

1. **`.claude/current-sprint.md`**
   - Mark completed tasks `[x]`
   - Add new tasks discovered
   - Update time spent

2. **`.claude/context.md`**

@verbatim
<code-snippet name="context.md Template" lang="markdown">
## Last Updated: [timestamp]

## Current State
- Working on: [specific feature/file]
- Last completed: [what finished]
- Next task: [where to continue]
- Current file: [path/to/file.tsx]

## Important Context
- [Decisions made]
- [Blockers encountered]
- [Dependencies to remember]

## Code in Progress
- [Exact function/component being edited]
- [Uncommitted logic or approach]
</code-snippet>
@endverbatim

3. **Save work state**
   - Ensure all files saved
   - Note uncommitted changes in context.md

### After Clearing Context

Start new session with: "Follow session start protocol and continue development"

New agent should:
- Read `context.md` first
- Continue from "Next task"
- Have full context to proceed

---

## File Naming Conventions

### SCRUMs
`scrum-X-description.md`
- Example: `scrum-12-production-deployment.md`

### Completed Sprints
`YYYY-MM-DD-scrum-X-sprint-Y-description.md`
- Example: `2025-11-10-scrum-7-phpstan-level-5.md`

### Session Summaries
`session-summary-YYYY-MM-DD-description.md`
- Example: `session-summary-2025-11-10-sprint-6.2.md`

### ADRs
`ADR-XXX-decision-title.md`
- Example: `ADR-009-data-valueobjects-architecture.md`

### Guidelines
`IMPLEMENTATION-GUIDE-topic.md`
- Example: `IMPLEMENTATION-GUIDE-DATA-VALUEOBJECTS.md`

### Guides (when completed)
`guide-*.md`
- Example: `guide-sprint-1.1.md`

---

## Best Practices

### For AI Agents

1. **Always read first**:
   - `.claude/context.md` - Current state
   - `.claude/current-sprint.md` - Active sprint
   - `.claude/planning/scrum-X-*.md` - Sprint details

2. **Update frequently**:
   - Update todos after each task (TodoWrite tool)
   - Update `current-sprint.md` with progress
   - Update `context.md` before finishing

3. **Document thoroughly**:
   - Detailed sprint plans
   - Clear success criteria
   - Code examples when helpful

4. **Organize properly**:
   - New SCRUMs → `planning/`
   - Active work → `current-sprint.md`
   - Completed → `completed/`
   - Decisions → `decisions/ADR-XXX-*.md`

### For Developers

1. **Check current sprint**:
@verbatim
   <code-snippet name="View current sprint" lang="bash">
   cat .claude/current-sprint.md
   </code-snippet>
@endverbatim

2. **See what's planned**:
@verbatim
   <code-snippet name="List planned work" lang="bash">
   ls .claude/planning/
   </code-snippet>
@endverbatim

3. **Reference decisions**:
@verbatim
   <code-snippet name="View ADRs" lang="bash">
   cat .claude/decisions/README.md
   cat .claude/decisions/ADR-009-*.md
   </code-snippet>
@endverbatim

4. **Update as you work**:
   - Note blockers in `context.md`
   - Update task progress
   - Add discoveries to `notes/notes.md`

---

## Pre-Deployment Checklist

Before deploying to production, ensure all items are checked:

- [ ] All critical security issues fixed
- [ ] All high-priority performance issues fixed
- [ ] Code formatted: `vendor/bin/pint`
- [ ] All tests passing: `php artisan test`
- [ ] PHPStan Level 5 - 0 errors: `vendor/bin/phpstan analyse`
- [ ] Security audit: `composer audit` - 0 vulnerabilities
- [ ] No N+1 queries (check Telescope)
- [ ] Response time < 200ms (check Telescope)
- [ ] Frontend builds: `npm run build` or `pnpm run build`
- [ ] No console errors in browser
- [ ] Sprint documented in `current-sprint.md`
- [ ] All completed sprints moved to `completed/`
- [ ] ADRs created for major decisions
- [ ] Git commits pushed
- [ ] CI/CD pipeline passing

---

## Useful Commands

### View Structure
@verbatim
<code-snippet name="View .claude/ structure" lang="bash">
# List all folders
ls -1 .claude/

# Count files per folder
find .claude -type f | wc -l

# View specific folder
ls -la .claude/decisions/
</code-snippet>
@endverbatim

### Clean Up
@verbatim
<code-snippet name="Clean up old files" lang="bash">
# Move completed sprints
mv .claude/planning/scrum-X-*.md .claude/completed/YYYY-MM-DD-scrum-X-*.md

# Clean up old summaries (after archiving info)
rm .claude/summaries/session-summary-2024-*.md
</code-snippet>
@endverbatim

---

## Questions?

**Structure unclear?** Check folder READMEs:
- `.claude/planning/README.md`
- `.claude/notes/README.md`
- `.claude/decisions/README.md`
- `.claude/guidelines/README.md`
- `.claude/summaries/README.md`

**Need examples?** See completed sprints:
- `.claude/completed/`

**Update needed?** This file lives at:
- `.ai/guidelines/sprint-guidelines.blade.php`

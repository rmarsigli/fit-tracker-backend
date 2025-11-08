# Project Management

## Organization Structure
Use `.claude/` directory (gitignored):

@verbatim
    <code-snippet name="Folder structure explanation">
        .claude/
        ├── current-sprint.md    # Active sprint only
        ├── backlog.md           # Future tasks and ideas
        ├── completed/           # Archived sprints by date and sprint name (YYYY-MM-DD-sprint-name.md)
        ├── notes.md             # Quick thoughts, blockers, ideas
        ├── context.md           # State between sessions
        └── decisions.md         # Architectural decisions
    </code-snippet>
@endverbatim

## Sprint Management

### Rules
- Work in `current-sprint.md` for active tasks
- Move completed sprints to `completed/YYYY-MM-DD-sprint-name.md`
- Use checkboxes `[ ]` for task tracking
- Record estimated vs actual time
- Future ideas go in `backlog.md`

### Sprint Format
@verbatim
    <code-snippet name="How to edit sprint file" lang="markdown">
        ## Sprint #3 - Feature Name

        **Started**: 2025-01-15 09:00
        **Estimated**: 4 hours
        **Status**: IN_PROGRESS / COMPLETED
        **Priority**: High / Medium / Low

        ### Tasks
        - [x] Task description [P1]
        - [ ] Another task [P2]

        ### Dependencies
        - Task B depends on: Task A

        **Ended**: 2025-01-15 14:30
        **Actual**: 5.5 hours

        ### Sprint Metrics
        - **Velocity**: 2/4 tasks (50%)
        - **Time Accuracy**: 5.5h / 4h = 137%
        - **Blockers**: 0

        **Notes**: Any important learnings
    </code-snippet>
@endverbatim

### Priority Levels
- **[P1]** Critical - Must complete this sprint
- **[P2]** High - Should complete this sprint
- **[P3]** Medium - Nice to have
- **[P4]** Low - Can move to backlog

---

## Definition of Done

Task complete when ALL checked

---

## Context Management

### Before clearing context.md **ALWAYS update these files**:

1. **`.claude/current-sprint.md`**
- Mark completed tasks `[x]`
- Add new tasks discovered
- Update time spent

2. **`.claude/context.md`**
@verbatim
    <code-snippet name="context.md example file" lang="markdown">
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
        ```

        3. **Save work state**
        - Ensure all files saved
        - Note uncommitted changes in context.md

        ### After Clearing Context
        Start new session with: "Follow session start protocol and continue development"
    </code-snippet>
@endverbatim

## Pre-Deployment Checklist

- [ ] All critical security issues fixed
- [ ] All high-priority performance issues fixed
- [ ] Code formatted: `vendor/bin/pint`
- [ ] All tests passing: `php artisan test`
- [ ] No N+1 queries (Telescope)
- [ ] Response time < 200ms (Telescope)
- [ ] Frontend builds: `npm run build`
- [ ] No console errors in browser
- [ ] Sprint documented
- [ ] Git commits pushed

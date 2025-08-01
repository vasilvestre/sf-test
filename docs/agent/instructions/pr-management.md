# Pull Request Management Instructions

## Overview

This document defines standards and workflows for creating and managing pull requests using GitHub CLI (`gh`), ensuring consistency with our semantic versioning and commit standards.

## Prerequisites

### GitHub CLI Authentication
Ensure `gh` is authenticated:
```bash
gh auth status
# If not authenticated:
gh auth login
```

## PR Creation Standards

### PR Title Format

PR titles MUST follow the same format as semantic commits:

```
<type>[optional scope]: <description>
```

Examples:
- `feat(auth): add OAuth2 integration`
- `fix: resolve memory leak in cache service`
- `docs: update API documentation`
- `chore(deps): update Symfony to 7.3.1`

### PR Body Template

All PRs MUST include a structured body:

```markdown
## Summary
Brief description of what this PR accomplishes

## Motivation
Why this change is needed (link to issue if applicable)

## Changes
- Bullet points of key changes
- Keep it concise but comprehensive

## Type of Change
- [ ] 🐛 Bug fix (non-breaking change)
- [ ] ✨ New feature (non-breaking change)
- [ ] 💥 Breaking change
- [ ] 📝 Documentation update
- [ ] ♻️ Code refactoring
- [ ] 🎨 Style/formatting
- [ ] ⚡ Performance improvement
- [ ] ✅ Test improvement

## Testing
- [ ] All tests pass locally
- [ ] New tests added for new functionality
- [ ] Existing tests updated as needed

## Checklist
- [ ] Code follows project standards
- [ ] Self-review completed
- [ ] Comments added for complex logic
- [ ] Documentation updated
- [ ] No new warnings/errors
- [ ] Semantic commit format used

## Related Issues
Closes #XXX

## Additional Notes
Any additional context or screenshots

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

## Pre-PR Quality Checks

### MANDATORY: Run QA Tools Before PR Creation

```bash
# Run all quality checks
docker compose exec app composer qa

# If issues found, run fixes
docker compose exec app composer qa:fix

# Verify all checks pass
docker compose exec app composer qa
```

**PR Creation Blocked If:**
- PHPUnit tests fail
- ECS reports style violations
- PHPStan finds type or logic errors
- Rector suggests improvements
- Twig CS Fixer finds template issues

## PR Creation Workflow

### 1. Standard Feature PR

```bash
# Ensure branch is up to date
git checkout feature/user-authentication
git pull origin main --rebase

# MANDATORY: Run QA checks
docker compose exec app composer qa

# Fix any issues
docker compose exec app composer qa:fix

# Verify all pass
docker compose exec app composer qa

# Create PR with semantic title and body
gh pr create \
  --title "feat(auth): implement user authentication" \
  --body "$(cat <<'EOF'
## Summary
Implements complete user authentication system with JWT tokens

## Motivation
Users need secure authentication to access protected resources (#45)

## Changes
- Add JWT authentication middleware
- Implement login/logout endpoints
- Add user session management
- Create authentication tests

## Type of Change
- [x] ✨ New feature (non-breaking change)

## Testing
- [x] All tests pass locally
- [x] New tests added for new functionality
- [x] Integration tests for auth flow

## Checklist
- [x] Code follows project standards
- [x] Self-review completed
- [x] Comments added for complex logic
- [x] Documentation updated
- [x] No new warnings/errors
- [x] Semantic commit format used

## Related Issues
Closes #45

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)" \
  --base main \
  --head feature/user-authentication
```

### 2. Bug Fix PR

```bash
gh pr create \
  --title "fix: prevent memory leak in cache service" \
  --body "$(cat <<'EOF'
## Summary
Fixes memory leak occurring in cache service during long-running processes

## Motivation
Memory usage was growing unbounded in production (#78)

## Changes
- Add proper cleanup in cache destructor
- Implement memory limit checks
- Add monitoring for memory usage

## Type of Change
- [x] 🐛 Bug fix (non-breaking change)

## Testing
- [x] All tests pass locally
- [x] Memory leak test added
- [x] Tested with production-like load

## Checklist
- [x] Code follows project standards
- [x] Self-review completed
- [x] No new warnings/errors
- [x] Semantic commit format used

## Related Issues
Fixes #78

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"
```

### 3. Breaking Change PR

```bash
gh pr create \
  --title "feat!: restructure API response format" \
  --body "$(cat <<'EOF'
## Summary
Restructures all API responses to use consistent envelope format

## Motivation
Current API responses are inconsistent, making client integration difficult

## Changes
- All responses now wrapped in `{data: ..., meta: ...}` format
- Error responses standardized
- Migration guide added

## Type of Change
- [x] 💥 Breaking change

## BREAKING CHANGES
All API responses now use envelope format. Clients must update to handle new structure.
See migration guide: docs/migration/v2-api-changes.md

## Testing
- [x] All tests pass locally
- [x] API tests updated for new format
- [x] Backward compatibility tests

## Checklist
- [x] Code follows project standards
- [x] Self-review completed
- [x] Documentation updated
- [x] Migration guide created
- [x] Semantic commit format used

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"
```

## PR Management Commands

### Viewing PRs

```bash
# List all open PRs
gh pr list

# List PRs by state
gh pr list --state open
gh pr list --state closed
gh pr list --state merged

# View specific PR
gh pr view 123

# View PR in browser
gh pr view 123 --web
```

### Updating PRs

```bash
# Update PR title
gh pr edit 123 --title "fix: updated title"

# Update PR body
gh pr edit 123 --body "Updated description"

# Add labels
gh pr edit 123 --add-label "bug,priority-high"

# Add reviewers
gh pr edit 123 --add-reviewer @username,@team-name

# Add assignees
gh pr edit 123 --add-assignee @username
```

### PR Review Process

```bash
# Check out PR locally
gh pr checkout 123

# Review PR
gh pr review 123 --approve --body "LGTM! Great implementation"
gh pr review 123 --request-changes --body "Please address the comments"
gh pr review 123 --comment --body "Some suggestions for improvement"

# View PR diff
gh pr diff 123

# View PR checks status
gh pr checks 123
```

### Merging PRs

```bash
# Merge with squash (recommended for feature branches)
gh pr merge 123 --squash --delete-branch

# Merge with rebase (for clean history)
gh pr merge 123 --rebase --delete-branch

# Merge with merge commit (for preserving history)
gh pr merge 123 --merge --delete-branch

# Auto-merge when checks pass
gh pr merge 123 --auto --squash --delete-branch
```

## Integration with Semantic Versioning

### Release PRs

When creating a release PR:

```bash
# Create release branch
git checkout -b release/1.2.0

# Update version files and CHANGELOG.md
# ... make changes ...

# Create release PR
gh pr create \
  --title "chore: prepare release v1.2.0" \
  --body "$(cat <<'EOF'
## Release v1.2.0

### Features
- feat(auth): add OAuth2 support (#45)
- feat(api): implement rate limiting (#52)

### Bug Fixes
- fix: memory leak in cache service (#78)
- fix: validation error messages (#81)

### Breaking Changes
None

### Full Changelog
See [CHANGELOG.md](./CHANGELOG.md)

## Checklist
- [x] Version bumped in composer.json
- [x] CHANGELOG.md updated
- [x] All tests passing
- [x] Documentation updated

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)" \
  --base main
```

## PR Automation

### PR Templates

Store in `.github/pull_request_template.md`:

```markdown
## Summary
<!-- Brief description of changes -->

## Type of Change
- [ ] 🐛 Bug fix
- [ ] ✨ New feature
- [ ] 💥 Breaking change
- [ ] 📝 Documentation
- [ ] ♻️ Refactoring
- [ ] 🎨 Style
- [ ] ⚡ Performance
- [ ] ✅ Test

## Testing
- [ ] Tests pass
- [ ] New tests added
- [ ] Manual testing completed

## Checklist
- [ ] Follows code standards
- [ ] Self-review done
- [ ] Documentation updated
- [ ] Semantic commits used

## Related Issues
<!-- Link issues with Closes #XXX -->
```

### Useful Aliases

Add to shell configuration:

```bash
# Create PR with semantic type
alias pr-feat='gh pr create --title "feat: "'
alias pr-fix='gh pr create --title "fix: "'
alias pr-docs='gh pr create --title "docs: "'
alias pr-chore='gh pr create --title "chore: "'

# Quick PR operations
alias pr-list='gh pr list --limit 10'
alias pr-mine='gh pr list --author @me'
alias pr-review='gh pr list --search "review-requested:@me"'
```

## Best Practices

1. **Always run checks locally** before creating PR
2. **Keep PRs focused** - one feature/fix per PR
3. **Update PR promptly** based on review feedback
4. **Use draft PRs** for work in progress: `gh pr create --draft`
5. **Link related issues** using keywords: Closes, Fixes, Resolves
6. **Request reviews** from appropriate team members
7. **Squash commits** when merging feature branches
8. **Delete branches** after merging

## Common Issues

### PR Checks Failing
```bash
# View check details
gh pr checks 123 --watch

# Re-run failed checks
gh pr checks 123 --rerun-failed
```

### Conflicts with Base Branch
```bash
# Update local branch
git checkout feature-branch
git pull origin main --rebase

# Push and update PR
git push --force-with-lease
```

Remember: Consistent PR management ensures smooth collaboration and maintains project quality.
# Git Workflow Instructions

## Overview

This document defines Git workflow standards including semantic commits, semantic versioning, and branch management for consistent version control practices.

## Semantic Commits

### Commit Message Format

All commits MUST follow the Conventional Commits specification:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Commit Types

- **feat**: New feature (MINOR version bump)
- **fix**: Bug fix (PATCH version bump)
- **docs**: Documentation only changes
- **style**: Code style changes (formatting, missing semicolons, etc.)
- **refactor**: Code change that neither fixes a bug nor adds a feature
- **perf**: Performance improvements
- **test**: Adding or updating tests
- **build**: Changes to build system or dependencies
- **ci**: CI configuration changes
- **chore**: Other changes that don't modify src or test files
- **revert**: Reverts a previous commit

### Commit Examples

```bash
# Feature with scope
feat(auth): add OAuth2 authentication support

# Bug fix
fix: resolve memory leak in user service

# Breaking change
feat!: change API response format

BREAKING CHANGE: API responses now use camelCase instead of snake_case

# Documentation
docs: update installation instructions

# Refactoring with description
refactor(user): extract validation logic to separate class

This improves testability and follows single responsibility principle.
```

### Commit Message Rules

1. **Subject line**:
   - Use imperative mood ("add" not "added" or "adds")
   - Don't capitalize first letter after type
   - No period at the end
   - Maximum 72 characters

2. **Body** (optional):
   - Wrap at 72 characters
   - Explain what and why, not how
   - Separate from subject with blank line

3. **Footer** (optional):
   - Reference issues: `Fixes #123`
   - Note breaking changes: `BREAKING CHANGE: description`

## Semantic Versioning

### Version Format

Follow Semantic Versioning 2.0.0: `MAJOR.MINOR.PATCH`

- **MAJOR**: Incompatible API changes
- **MINOR**: Backwards-compatible functionality
- **PATCH**: Backwards-compatible bug fixes

### Version Bumping Rules

Based on commits since last release:

1. If any commit has `BREAKING CHANGE` or `!` → MAJOR
2. If any commit type is `feat` → MINOR
3. If only `fix` and other commits → PATCH

### Pre-release Versions

```
1.0.0-alpha.1  # Alpha release
1.0.0-beta.1   # Beta release
1.0.0-rc.1     # Release candidate
```

## Branch Management

### Branch Naming

```
main                    # Production-ready code
develop                 # Integration branch
feature/description     # New features
fix/description        # Bug fixes
hotfix/description     # Emergency fixes
release/1.2.0          # Release preparation
chore/description      # Maintenance tasks
```

### Git Flow

1. **Feature Development**:
   ```bash
   git checkout -b feature/user-authentication
   # Work on feature with semantic commits
   git commit -m "feat(auth): add login endpoint"
   git commit -m "test(auth): add login endpoint tests"
   ```

2. **Bug Fixes**:
   ```bash
   git checkout -b fix/memory-leak
   git commit -m "fix: prevent memory leak in cache service"
   ```

3. **Releases**:
   ```bash
   git checkout -b release/1.2.0
   # Update version numbers, changelogs
   git commit -m "chore: prepare release 1.2.0"
   ```

## Automated Changelog

### Commit Groups in Changelog

Commits are grouped by type:

```markdown
## [1.2.0] - 2024-01-11

### Features
- **auth**: add OAuth2 authentication support
- **api**: add rate limiting

### Bug Fixes
- resolve memory leak in user service
- **ui**: fix button alignment issue

### Performance Improvements
- optimize database queries for user list
```

## Integration with Commands

### With Act (TDD)

When implementing features:
```bash
# Red phase
git commit -m "test: add failing test for user validation"

# Green phase
git commit -m "feat: implement user validation"

# Refactor phase
git commit -m "refactor: extract validation rules to config"
```

### With Report

When creating ADRs:
```bash
git commit -m "docs: add ADR-001 for authentication strategy"
```

### Commit After Task Completion

Only commit when:
1. All tests pass
2. Code follows standards
3. No linting errors
4. Feature is complete

## Pull Request Guidelines

### PR Title Format

Same as commit format:
```
feat(scope): description
fix: description
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change)
- [ ] New feature (non-breaking change)
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added
- [ ] Existing tests updated

## Checklist
- [ ] Follows semantic commit format
- [ ] Documentation updated
- [ ] No linting errors
```

## Git Aliases for Semantic Commits

Suggest these aliases in development docs:

```bash
# ~/.gitconfig
[alias]
    feat = "!f() { git commit -m \"feat: $1\"; }; f"
    fix = "!f() { git commit -m \"fix: $1\"; }; f"
    docs = "!f() { git commit -m \"docs: $1\"; }; f"
    style = "!f() { git commit -m \"style: $1\"; }; f"
    refactor = "!f() { git commit -m \"refactor: $1\"; }; f"
    test = "!f() { git commit -m \"test: $1\"; }; f"
    chore = "!f() { git commit -m \"chore: $1\"; }; f"
```

## Common Scenarios

### Adding a New Feature
```bash
git checkout -b feature/payment-integration
git commit -m "feat(payment): add Stripe integration"
git commit -m "test(payment): add payment processing tests"
git commit -m "docs: add payment integration guide"
```

### Fixing a Bug
```bash
git checkout -b fix/user-login
git commit -m "fix(auth): resolve login timeout issue"
git commit -m "test(auth): add test for login timeout"
```

### Breaking Change
```bash
git commit -m "feat!: restructure API endpoints

BREAKING CHANGE: All API endpoints now use /api/v2 prefix.
Migration guide available in docs/migration-v2.md"
```

## Version Tag Creation

When creating release tags:

```bash
# After all release commits
git tag -a v1.2.0 -m "Release version 1.2.0

### Features
- Add user authentication
- Add payment processing

### Bug Fixes
- Fix memory leak in cache service

Full changelog: CHANGELOG.md"

git push origin v1.2.0
```

Remember: Consistent commit messages enable automated tooling and clear project history.
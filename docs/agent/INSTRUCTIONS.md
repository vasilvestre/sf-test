# Agent Instructions

## Overview

This document references specific instructions for agents to ensure consistency and adherence to project standards.

## Workflow Instructions

### 📋 [Git Workflow](./instructions/git-workflow.md)
- Semantic Commits (Conventional Commits)
- Semantic Versioning
- Branch Management (Git Flow)
- Automatic Changelog Generation
- TDD Integration (Red-Green-Refactor)

### 📋 [Pull Request Management](./instructions/pr-management.md)
- Creating PRs with GitHub CLI (`gh`)
- Standardized Templates
- Mandatory Quality Checks (`composer qa`)
- Review and Merge Process

## Mandatory Quality Standards

**Before each commit**:
```bash
docker compose exec app composer qa     # Check
docker compose exec app composer qa:fix # Auto-fix
```

**Included Tools**: PHPUnit, ECS, PHPStan, Rector, PHPArkitect, Twig CS Fixer

## Application

These instructions are **MANDATORY** to maintain:
- ✅ Code Quality
- ✅ Semantic Git History
- ✅ Standardized Processes
- ✅ Reliable Continuous Integration

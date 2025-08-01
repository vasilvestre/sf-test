# AI Tools Usage Guide for the Team

## 📑 Table of Contents

- [🎯 Purpose of this document](#-purpose-of-this-document)
- [⚠️ Important warning](#️-important-warning)
- [🛠️ Available tools](#️-available-tools)
  - [1. Opencode - Terminal AI Agent](#1-opencode---terminal-ai-agent)
    - [Installation](#installation)
    - [Configuration](#configuration)
    - [Project-specific Configuration](#project-specific-configuration)
    - [Using Context7 MCP](#using-context7-mcp)
    - [Recommended usage](#recommended-usage)
    - [Usage examples](#usage-examples)
  - [2. Pre-configured Modes](#2-pre-configured-modes)
    - [Available Modes](#available-modes)
    - [Mode-specific Features](#mode-specific-features)
  - [3. Context7 MCP Server](#3-context7-mcp-server)
    - [What Context7 Solves](#what-context7-solves)
    - [How to Use Context7](#how-to-use-context7)
    - [Context7 Integration Status](#context7-integration-status)
  - [4. Local agent prompts](#4-local-agent-prompts)
    - [Prompt structure](#prompt-structure)
    - [How to use these prompts](#how-to-use-these-prompts)
- [📋 Recommended workflow](#-recommended-workflow)
  - [For a new feature](#for-a-new-feature)
  - [For maintenance/debugging](#for-maintenancedebugging)
- [🔌 MCP Integration Benefits](#-mcp-integration-benefits)
  - [Context7 Advantages for the Team](#context7-advantages-for-the-team)
  - [When to Use Context7](#when-to-use-context7)
- [🚀 Integration with existing tools](#-integration-with-existing-tools)
  - [With Docker Compose](#with-docker-compose)
  - [With project QA tools and current standards](#with-project-qa-tools-and-current-standards)
  - [With Git and PRs](#with-git-and-prs)
- [📝 Team best practices](#-team-best-practices)
  - [Do's ✅](#dos-)
  - [Don'ts ❌](#donts-)
- [🔒 Security and privacy](#-security-and-privacy)
  - [Data to NEVER share with AI](#data-to-never-share-with-ai)
  - [Data OK to share](#data-ok-to-share)
- [📊 Measuring effectiveness](#-measuring-effectiveness)
  - [Positive metrics](#positive-metrics)
  - [Warning signs](#warning-signs)
- [🎓 Continuous learning](#-continuous-learning)
  - [Resources](#resources)
  - [Skill evolution](#skill-evolution)
- [🤝 Support and mutual aid](#-support-and-mutual-aid)
  - [In case of problems](#in-case-of-problems)
  - [Continuous improvement](#continuous-improvement)
- [💡 Conclusion](#-conclusion)

---

## 🎯 Purpose of this document

This guide explains how to use the artificial intelligence tools available to the team to improve your development productivity. These tools are designed to **assist** you in your daily tasks, **not to replace you**.

## ⚠️ Important warning

> **AI tools are assistants, not replacements**
> 
> - They can make mistakes
> - They don't always understand the complete business context
> - Their code must **always** be reviewed and tested
> - Your human expertise remains **irreplaceable**
> - Never blindly trust AI suggestions

## 🛠️ Available tools

### 1. Opencode - Terminal AI Agent

[Opencode](https://opencode.ai) is an AI development agent that works directly in your terminal and understands your codebase.

#### Installation

```bash
# Installation via script (recommended)
curl -fsSL https://opencode.ai/install | bash

# Or via npm
npm install -g opencode-ai
```

#### Configuration

**Authentication options:**

1. **GitHub Copilot** (recommended for team consistency)
   ```bash
   opencode auth login
   # Select GitHub
   # Use your existing GitHub Copilot subscription
   ```

2. **Local LLM** (for privacy-sensitive work)
   ```bash
   opencode auth login
   # Select Local/Ollama
   # Configure your local model endpoint
   ```

**Note:** The project is already initialized with `AGENTS.md` file and pre-configured modes.

#### Project-specific Configuration

The project includes a pre-configured `opencode.json` with:

**🔧 Specialized modes:**
- `build`: TDD development with Claude Sonnet 4
- `plan`: Planning mode with Gemini 2.5 Pro (read-only)
- `spec:*`: Complete specification-driven development workflow
- `tools:*`: Analysis and quality assurance tools

**🔌 MCP Integration:**
- **Context7**: Provides up-to-date documentation for any library/framework
- Automatically resolves library names to current documentation
- Fetches version-specific code examples and API references

#### Using Context7 MCP

Context7 MCP is already configured and provides access to up-to-date documentation for thousands of libraries. You can:

1. **Ask for specific library docs:**
   ```
   How do I implement JWT authentication in Symfony? use context7
   ```

2. **Get version-specific examples:**
   ```
   Show me Doctrine ORM query builder examples for the latest version. use context7
   ```

3. **Reference specific libraries:**
   ```
   Configure Symfony Messenger with RabbitMQ. Use library /symfony/messenger for reference
   ```

**Context7 Benefits:**
- ✅ Always up-to-date documentation (no outdated training data)
- ✅ Version-specific API examples
- ✅ Real code samples from official docs
- ✅ No hallucinated APIs that don't exist

#### Recommended usage

**✅ Best practices:**
- Ask for a plan before implementation
- Use Plan mode (`<TAB>`) for new features
- Provide business context and examples
- Specify existing files as references with `@`

**❌ Avoid:**
- Implementing critical features without supervision
- Accepting code without review
- Using for major architectural decisions alone
- Sharing sensitive information (credentials, client data)

#### Usage examples

```bash
# Understanding existing code with Context7 documentation
How is authentication handled in @src/Security/Common/User/ and how does it compare 
to Symfony's latest security best practices? use context7

# Planning a new feature (Plan mode)
<TAB>
I want to add an email notification system when a declaration status changes. 
Look at how it's done in @src/ThirdParty/ERP/ for inspiration and use Context7 
for Symfony Messenger documentation. use context7

# Direct implementation with up-to-date documentation
Add email validation in @src/Account/Common/Organization/Domain/Organization.php 
following Symfony Validator's latest patterns. use context7 /symfony/validator
```

### 2. Pre-configured Modes

The project includes specialized opencode modes tailored for different development phases:

#### Available Modes

```bash
# TDD Development
opencode --mode build              # Claude Sonnet 4 with TDD prompt

# Planning (read-only)
opencode --mode plan               # Gemini 2.5 Pro, no file modifications

# Specification-driven development
opencode --mode spec:plan          # Interactive project planning
opencode --mode spec:requirements  # EARS requirements definition
opencode --mode spec:design        # Technical design with various tech stacks
opencode --mode spec:tasks         # TDD task breakdown
opencode --mode spec:execute       # Implementation execution

# Analysis tools
opencode --mode tools:qa           # Quality assurance analysis
opencode --mode tools:understand   # Legacy code analysis
```

#### Mode-specific Features

**Build Mode (Default TDD):**
- Uses `@docs/agent/prompt/build.md`
- Follows Red-Green-Refactor cycle strictly
- Claude Sonnet 4 for implementation quality

**Plan Mode:**
- Read-only mode for safe planning
- No write/edit/bash tool access
- Ideal for architectural discussions

**Spec Modes:**
- Complete specification-driven development workflow
- EARS (Easy Approach to Requirements Syntax) format
- Different models optimized for each phase

### 3. Context7 MCP Server

[Context7](https://context7.com) is a Model Context Protocol (MCP) server that provides up-to-date documentation for thousands of libraries and frameworks, already integrated into the project.

#### What Context7 Solves

**❌ Without Context7:**
- Code examples are outdated (based on year-old training data)
- Hallucinated APIs that don't exist
- Generic answers for old package versions

**✅ With Context7:**
- Always up-to-date, version-specific documentation
- Real code samples from official sources
- No hallucinated APIs

#### How to Use Context7

1. **Add `use context7` to your prompts:**
   ```
   Create a Symfony form with file upload validation. use context7
   ```

2. **Reference specific libraries:**
   ```
   Configure Doctrine migrations. Use library /doctrine/migrations for reference
   ```

3. **Get framework-specific best practices:**
   ```
   Implement API Platform custom filters following current best practices. use context7
   ```

#### Context7 Integration Status

The project's `opencode.json` includes Context7 MCP configuration:
```json
"mcp": {
  "context7": {
    "type": "local",
    "command": ["npx", "-y", "@upstash/context7-mcp"],
    "enabled": true
  }
}
```

This means Context7 is automatically available in all opencode sessions.

### 4. Local agent prompts

The project contains structured prompts in `docs/agent/prompt/` that work seamlessly with the configured modes.

#### Prompt structure

```
docs/agent/prompt/
├── spec/              # Specification-driven development
│   ├── plan.md       # Project planning
│   ├── requirements.md # Requirements definition (EARS)
│   ├── design.md     # Technical design
│   ├── tasks.md      # TDD task breakdown
│   └── execute.md    # Plan execution
├── tools/            # Analysis tools
│   ├── qa.md        # Quality assurance
│   └── understand.md # Legacy code analysis
├── build.md          # Default TDD mode
└── plan.md          # Simple planning
```

#### How to use these prompts

1. **For legacy code analysis:**
   ```
   Use @docs/agent/prompt/tools/understand.md with src/Declaration/
   ```

2. **For developing a new feature:**
   ```
   Use @docs/agent/prompt/spec/plan.md with "Advanced notification system"
   ```

3. **For TDD development with current documentation:**
   ```
   Use @docs/agent/prompt/build.md to implement email validation tests. use context7
   ```

4. **For quality assurance with latest standards:**
   ```
   Use @docs/agent/prompt/tools/qa.md to analyze code quality against current Symfony best practices. use context7
   ```

## 📋 Recommended workflow

### For a new feature

1. **Planning** (with human input)
   - Define business needs
   - Assess impact on existing architecture
   - Decide on technical constraints

2. **AI-assisted specification with up-to-date documentation**
   ```
   Use @docs/agent/prompt/spec/requirements.md with "Automatic email notifications"
   Add: use context7 for Symfony Messenger and Mailer documentation
   ```

3. **Technical design** (mandatory human validation)
   ```
   Use @docs/agent/prompt/spec/design.md
   Add: use context7 /symfony/messenger /symfony/mailer for current best practices
   ```

4. **Guided implementation with current standards**
   ```
   Use @docs/agent/prompt/spec/tasks.md then @docs/agent/prompt/spec/execute.md
   Always include: use context7 for framework-specific documentation
   ```

5. **Human review** (mandatory)
   - Comprehensive testing
   - Code review
   - Business validation

### For maintenance/debugging

1. **Problem analysis with documentation context**
   ```
   Use @docs/agent/prompt/tools/understand.md with the problematic file
   Add: use context7 to compare with current framework patterns
   ```

2. **Solution research**
   - AI can suggest approaches with Context7's up-to-date docs
   - **You** validate business relevance

3. **Controlled implementation**
   - Minimal changes following current best practices
   - Before/after testing
   - Post-deployment monitoring

## 🔌 MCP Integration Benefits

### Context7 Advantages for the Team

1. **No Outdated Information:**
   - Framework documentation is always current
   - API examples match the installed versions
   - Security recommendations are up-to-date

2. **Symfony-Specific Benefits:**
   - Current Doctrine ORM patterns
   - Latest Symfony Component usage
   - Modern API Platform configurations
   - Updated Messenger patterns

3. **Quality Assurance:**
   - Current coding standards (PSR-12, Symfony conventions)
   - Modern testing patterns (PHPUnit 11+)
   - Latest security best practices

### When to Use Context7

**✅ Always use Context7 for:**
- Framework configuration questions
- API documentation requests
- Best practices validation
- Library version compatibility

**❌ Don't use Context7 for:**
- Business logic questions
- Project-specific architecture decisions
- Internal application patterns

## 🚀 Integration with existing tools

### With Docker Compose

```bash
# AI can help with Docker commands but doesn't execute them for you
docker compose exec app composer qa
docker compose exec app php bin/console doctrine:migrations:status
```

### With project QA tools and current standards

```bash
# AI can explain QA errors and suggest fixes with current documentation
docker compose exec app composer qa:fix
# Then ask: "Explain these ECS violations and how to fix them using current Symfony standards. use context7"
```

### With Git and PRs

```bash
# AI can help write semantic commit messages and validate against current practices
# But follow the guidelines in @docs/agent/instructions/
# Example: "Generate a semantic commit message for adding JWT refresh tokens following current Symfony Security best practices. use context7"
```

## 📝 Team best practices

### Do's ✅

- **Collaborate** with AI, don't delegate entirely
- **Explain business context** in your prompts
- **Always validate** suggestions before applying
- **Thoroughly test** generated code
- **Document** decisions made with AI assistance
- **Share** effective prompts with the team

### Don'ts ❌

- **Never** push unreviewed AI-generated code
- **Never** use AI with sensitive data
- **Never** trust architectural suggestions without human validation
- **Never** bypass QA processes to "save time"
- **Never** use AI as an excuse not to understand the code

## 🔒 Security and privacy

### Data to NEVER share with AI

- Passwords or API keys
- Real client data
- Sensitive financial information
- Security vulnerability details
- Complete production configuration

### Data OK to share

- Code structure (without secrets)
- Architecture patterns
- Error messages (without sensitive data)
- Technical documentation
- Unit tests

## 📊 Measuring effectiveness

### Positive metrics

- Reduced legacy code understanding time
- Improved test quality
- More complete documentation
- More consistent code patterns

### Warning signs

- Increased production bugs
- Decreased team code understanding
- Excessive dependence on AI suggestions
- Reduced human reviews

## 🎓 Continuous learning

### Resources

- [Opencode Documentation](https://opencode.ai/docs/)
- [Context7 Documentation](https://context7.com) and [GitHub Repository](https://github.com/upstash/context7)
- Agent prompts in `docs/agent/prompt/`
- Specific instructions in `docs/agent/instructions/`
- Project-specific modes in `opencode.json`
- Team experience sharing (to be organized)

### Skill evolution

1. **Novice**: Use AI to understand existing code
2. **Intermediate**: Use AI to generate tests and documentation
3. **Advanced**: Use AI for technical design with appropriate validation
4. **Expert**: Create custom prompts and train the team

## 🤝 Support and mutual aid

### In case of problems

1. **Check** this guide and tool documentation
2. **Ask** the team for help
3. **Document** solutions found
4. **Share** learnings

### Continuous improvement

- Suggest improvements to this guide
- Share your effective prompts
- Document project-specific use cases
- Organize experience sharing sessions

---

## 💡 Conclusion

AI tools are **force multipliers** for our development team. Used correctly, they can:

- Accelerate code understanding
- Improve test quality
- Standardize code patterns
- Reduce repetitive tasks

But they never replace:

- Your business expertise
- Your technical judgment
- Your responsibility for the code produced
- Your human interactions within the team

**Use them as intelligent assistants, not as autonomous developers.**

---

*This guide will evolve with our collective experience. Feel free to suggest improvements!*
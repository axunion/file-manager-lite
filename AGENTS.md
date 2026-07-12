# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

> **Sync note:** This file is kept in sync with `CLAUDE.md`. When you update one, update the other to match.

Behavioral defaults plus house conventions. Bias toward caution over speed; on trivial tasks, use judgment.

## Approach

- **Think before coding.** State assumptions; if uncertain, ask. When multiple
  interpretations exist, surface them rather than silently picking one. If a simpler path
  exists, say so and push back when warranted.
- **Simplest thing that works.** Write the minimum code that solves the stated problem —
  nothing speculative. No unasked-for abstractions, flexibility, or error handling for
  impossible cases. If 200 lines could be 50, rewrite it.
- **Surgical changes.** Every changed line should trace to the request. Don't refactor,
  reformat, or "improve" adjacent code that isn't broken; match the surrounding style.
  Remove only the imports and symbols your change orphaned; leave unrelated dead code alone
  and mention it.
- **Goal-driven.** Turn each task into a verifiable outcome ("fix the bug" → "write a
  failing test that reproduces it, then make it pass"). For multi-step work, state a brief
  plan with a verification check per step, then loop until it passes.

## Language

Write in **English only**: in-code comments, console output, error and log messages, and
AI-readable config files (CLAUDE.md, AGENT.md, etc.).

## Project Overview

A lightweight, security-first PHP API for file management operations. No external framework dependencies, strict typing throughout, class-based architecture with comprehensive test coverage.

## Security Guidelines

- All user-provided paths must go through `resolvePath()` or `resolvePathWithTrash()` (calls `PathSecurity::resolveSafePath()`)
- Validate filenames with `PathSecurity::validateFileName()` before any create/rename operation
- Verify MIME types via `finfo` (content analysis) — never trust file extensions
- Verify uploads with `is_uploaded_file()` before moving
- Never access `$_GET`, `$_POST`, `$_FILES` directly in endpoints — use `getInput()` and the bootstrap helpers
- Never expose internal filesystem paths in responses or error messages
- Authentication is optional: bootstrap's `requireApiKey()` enforces the `X-Api-Key` header only when a key is configured (env `API_KEY` or server-local `src/config.local.php`)

## Commands

### Testing
```bash
# Run all unit tests
php test/run-all.php

# Run all API tests (auto-starts PHP server on port 8000)
php test-api/run-all.php

# Run individual API test (auto-starts server, no manual setup needed)
php test-api/list.test.php
```

### Static analysis
```bash
# PHPStan level 8 (dev/CI only — nothing is deployed to the server)
composer install
vendor/bin/phpstan analyse --memory-limit=512M
```

No build process - pure PHP at runtime.

## Architecture

### Request Flow
```
HTTP Request → Endpoint (public/api/{endpoint}/index.php)
    ↓
Bootstrap (loads dependencies, discovers data/trash dirs, handles CORS)
    ↓
Validation Layer (PathSecurity, UploadValidator)
    ↓
Business Logic (DirectoryScanner, FileOperations)
    ↓
JSON Response (sendSuccess/sendError helpers)
```

### Key Components

**PathSecurity** (`src/PathSecurity.php`) - Security-critical path validation:
- `resolveSafePath()` - Prevents directory traversal attacks
- `validateFileName()` - Enforces platform-specific filename rules
- `constructSequentialFilePath()` - Prevents overwrites with file locking

**Bootstrap** (`src/bootstrap.php`) - Central orchestrator:
- Auto-discovers data/trash directories by walking up from executing script
- Provides helpers: `handleCors()`, `validateMethod()`, `getInput()`, `resolvePath()`, `resolvePathWithTrash()`, `sendSuccess()`, `sendError()`, `handleError()`
- Maps exceptions to HTTP status: PathException/ValidationException/DirectoryException/RuntimeException → 400, others → 500

**Config** (`src/Config.php`) - Upload limits, allowed MIME types, CORS settings

### Endpoint Pattern
All endpoints follow this structure:
```php
require_once __DIR__ . '/../../../src/bootstrap.php';
validateMethod(['POST']);
try {
    $input = getInput(INPUT_POST, 'key', 'default');
    $path = resolvePath($input);  // or resolvePathWithTrash() for trash support
    // Business logic
    sendSuccess(['result' => 'data']);
} catch (Throwable $e) {
    handleError($e);
}
```

Current endpoints (list, upload, upload-images, rename, delete, move) are specified in `docs/openapi.yaml`.

## Adding New Code

**New endpoint**: Create `public/api/{name}/index.php` following the Endpoint Pattern above.

**New utility class**: Add directly to `src/` (alongside existing classes), use `declare(strict_types=1)`, explicit types throughout.

**New tests**: Unit tests in `test/{ClassName}.test.php`, API tests in `test-api/{endpoint-name}.test.php`. Always test success, error, and path-traversal cases. Use `test/TestHelpers.php` for unit test setup and `test-api/ApiTestHelpers.php` + `test-api/TestSetup.php` for API tests.

**API spec**: Update `docs/openapi.yaml` in the same commit whenever you add or modify an endpoint (parameters, response shape, error conditions, or constraints). Also update `docs/api-usage.md` if the change affects the fetch() patterns, upload limits, or collision-handling behaviour documented there.

## Code Structure

- Name variables, functions, and files to communicate intent.
- One concern per file; split when a file exceeds ~300 lines.
- Extract a helper only when used in 3+ places; otherwise inline it.
- Delete dead code you create; never comment it out.

PHP specifics:
- `declare(strict_types=1)` in every PHP file
- All parameters and return types explicitly typed
- PSR-12 style with 4-space indentation
- Validate inputs early, throw exceptions for invalid states

## Testing

- Write tests before or alongside implementation — they are your success criteria.
- Test observable outcomes and edge cases, not implementation details.
- Each test is fully self-contained; no shared mutable state between tests.

Project layout:
- **Unit tests** (`test/`): Direct class testing with simple assertions
- **API tests** (`test-api/`): HTTP requests via curl, auto-manages server startup/shutdown. Require `TestSetup.php` before `ApiTestHelpers.php`; register uploaded files with `registerUploadedFile()` for cleanup
- `ApiTestHelpers.php` provides HTTP helpers, assertions, temp-file factories, and upload cleanup tracking

## Commits

Format:

```
<one-line summary>

<Why: one sentence — motivation or problem>

- <change 1>
- <change 2>
```

- Summary: English, imperative mood ("Add move endpoint", not "Added"), ≤70 chars, no trailing period, no prefix tags (`feat:`, `fix:`, etc.).
- Why line: include only when motivation is not evident from the diff alone.
- Bullets: include only for 2+ distinct changes.
- Atomic commits: one logical change per commit.
- Run tests before committing.
- Never commit secrets (`*.key`, `*.pem`, `credentials*`).
- Never use `--no-verify` or `--amend`; always create a new commit.

## Deployment

FTP deploy via GitHub Actions on push to `main`. Target paths come from secrets, keeping the repo decoupled from any server layout:

- `src/` → `SRC_DIR` — PHP classes; its `.htaccess` denies direct HTTP access
- `public/` → `PUBLIC_DIR` — endpoints + `.htaccess`; the URL prefix is set by the server admin via the secret, not by the repository
- `data/` and `trash/` are excluded from sync — bootstrap creates them at runtime beside `public/api/`; structure tracked via `.gitkeep`, contents gitignored

## Environment Variables

- `TESTING=true` - Disables HTTPS redirects (set automatically by test runner)
- `TEST_SERVER_MANAGED=1` - Disables auto-server startup (used by run-all.php)
- `API_KEY` - Enables API key authentication; takes precedence over `src/config.local.php` (used by auth tests)

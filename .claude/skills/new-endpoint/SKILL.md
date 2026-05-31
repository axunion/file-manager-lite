---
name: new-endpoint
description: Scaffold a new API endpoint with tests
argument-hint: "<endpoint-name>"
---

Create a new API endpoint. Ask for: endpoint name, HTTP method, description.

Create these files following existing patterns:

1. `public/api/$ARGUMENTS/index.php`
   - Follow endpoint pattern in CLAUDE.md
2. `test-api/$ARGUMENTS.test.php`
   - Include TestSetup.php, ApiTestHelpers.php
   - Test success, error, and security cases
3. Update `openapi.yaml` — add the new path, parameters, and response shapes
4. Update `docs/api-usage.md` if the change affects fetch() patterns, upload limits, or collision-handling

After creating, run the test to verify.

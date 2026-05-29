## Tests API

- **Purpose**: Document the API endpoints that manage tests (trial blocks) within a survey.
- **Scope**:
  - Listing tests for a survey (`GET /tests/{survey}`).
  - Creating, updating, copying, and deleting tests.
  - Detailed schemas for `config`, `primes`, and `targets`.
- **Conventions**:
  - Match payload structures to what the editor uses (see `TestController` and `TestControllerInterface`).
  - Provide one or more complete example test definitions per endpoint.



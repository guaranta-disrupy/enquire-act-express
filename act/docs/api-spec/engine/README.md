## Engine API

- **Purpose**: Describe the public survey engine endpoints that power live surveys and response saving.
- **Scope**:
  - Fetching live project/survey data (`/api/v1/fetch`, `/api/v2/fetch`).
  - Saving responses from the engine (`/api/v1/save`) and any related hooks.
  - Redirect/forwarding behaviour and status logic.
- **Conventions**:
  - Clearly separate **frontend usage examples** (EngineTest) from **raw HTTP examples**.
  - Document required query parameters (e.g. `survey_id`, respondent `id`) and validation rules.



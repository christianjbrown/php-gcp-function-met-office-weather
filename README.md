# Met Office Weather Google Cloud Run Function

[![CI](https://github.com/christianjbrown/php-gcp-function-met-office-weather/actions/workflows/ci.yml/badge.svg)](https://github.com/christianjbrown/php-gcp-function-met-office-weather/actions/workflows/ci.yml)

A small [Google Cloud Run function](https://cloud.google.com/run) (PHP) that reads the current outdoor weather for a fixed latitude/longitude from the [Met Office Weather DataHub](https://datahub.metoffice.gov.uk/) Site-Specific **hourly** forecast API and returns it as a single JSON payload.

It fetches the hourly forecast for the configured location, selects the step for the *current* hour (the latest step whose time is at or before now, falling back to the earliest step when the whole series is in the future), and returns that step's temperature, feels-like temperature, humidity, precipitation probability, UV index, visibility, pressure, dew point, wind speed/gust/direction and weather type — plus the validity window of the reading.



## :heavy_check_mark: Prerequisites

- [Git](https://git-scm.com/)
- [PHP](https://www.php.net/) 8.5 or higher (8.x)
- [Composer](https://getcomposer.org/)
- A [Met Office Weather DataHub](https://datahub.metoffice.gov.uk/) Site-Specific API key
- Read access to the private `christianjbrown/*` package repositories this function depends on (Composer needs a GitHub token — see [CI & deployment](#rocket-ci--deployment))

:bulb: If you're on macOS and have [Homebrew](https://brew.sh/), PHP and Composer will install with `brew install composer`.



## :building_construction: Installation

```bash
git clone git@github.com:christianjbrown/php-gcp-function-met-office-weather.git
cd php-gcp-function-met-office-weather
composer install
```



## :gear: Configuration

Configuration is read entirely from environment variables.

| Variable | Required | Description |
| --- | --- | --- |
| `MET_OFFICE_WEATHER_API_KEY` | ✅ | Your Met Office Weather DataHub Site-Specific API key. |
| `MET_OFFICE_WEATHER_LATITUDE` | ✅ | Latitude of the location to forecast (decimal degrees). |
| `MET_OFFICE_WEATHER_LONGITUDE` | ✅ | Longitude of the location to forecast (decimal degrees). |
| `K_REVISION` | ✅ | Set automatically by the Cloud Run runtime; only needs setting yourself when running locally. |
| `REQUIRED_HEADER_KEY` | — | If set (with `REQUIRED_HEADER_VALUE`), requests must send this header to be served. |
| `REQUIRED_HEADER_VALUE` | — | Expected value for `REQUIRED_HEADER_KEY`. |
| `REQUIRED_ORIGIN` | — | Restricts responses to this CORS origin. |
| `USE_CACHE_TTL` | — | Seconds a fresh response may be cached (`Cache-Control`). |
| `USE_CACHE_BUT_REQUEST_TTL` | — | Seconds a cached response may be served while revalidating. |
| `USE_CACHE_IF_ERROR_TTL` | — | Seconds a cached response may be served if the origin errors. |
| `DEBUG` | — | Set to `true` for verbose error output. |

For local development, put these in a `.local.env` file in the project root (git-ignored). `composer start` exports it automatically:

```env
MET_OFFICE_WEATHER_API_KEY=your-met-office-datahub-api-key
MET_OFFICE_WEATHER_LATITUDE=51.546111
MET_OFFICE_WEATHER_LONGITUDE=-0.183111
K_REVISION=local
```



## :computer: Usage

### Run locally

```bash
composer start
```

This serves the function at `http://localhost:8080` (override with `PORT`). Send it a request:

```bash
curl http://localhost:8080
```

### Response

```json
{
    "valid_from": 1752580800,
    "valid_from_iso8601": "2025-07-15T12:00:00+00:00",
    "valid_to": 1752584400,
    "valid_to_iso8601": "2025-07-15T13:00:00+00:00",
    "temp": 18.7,
    "temp_feels_like": 17.2,
    "humidity": 65.5,
    "precipitation": 20,
    "uv_index": 3,
    "visibility": 30000,
    "pressure": 1013.2,
    "dew_point": 12.3,
    "wind_speed": 22.369362920544,
    "wind_gust": 26.8432355046528,
    "wind_direction": "E",
    "wind_direction_degrees": 90,
    "type": 1,
    "type_name": "SUNNY_DAY"
}
```

- `valid_from` / `valid_from_iso8601` — the Unix time (and ISO-8601 form) the reading is valid from. Always present.
- `valid_to` / `valid_to_iso8601` — one hour after `valid_from`; the end of this step's validity window. Always present.
- `temp` — screen (air) temperature in °C. Present only when reported.
- `temp_feels_like` — "feels like" temperature in °C. Present only when reported.
- `humidity` — screen relative humidity as a percentage. Present only when reported.
- `precipitation` — probability of precipitation as a percentage. Present only when reported.
- `uv_index` — UV index. Present only when reported.
- `visibility` — visibility in metres. Present only when reported.
- `pressure` — mean sea level pressure in **hectopascals (hPa)** (converted from the API's pascals at full precision — round for display). Present only when reported.
- `dew_point` — dew point temperature in °C. Present only when reported.
- `wind_speed` — 10 m wind speed in **mph** (converted from the API's m/s at full precision — round for display). Present only when reported.
- `wind_gust` — maximum 10 m wind gust in **mph** (converted from m/s at full precision). Present only when reported.
- `wind_direction` — the 10 m wind direction as a 16-point compass code (e.g. `ENE`). Present only when reported.
- `wind_direction_degrees` — the same 10 m wind direction as raw degrees (e.g. `90`). Present only when reported.
- `type` — the Met Office significant weather code (int, e.g. `1`). Present only when reported.
- `type_name` — the weather code as a stable `WeatherType` enum-name token (e.g. `SUNNY_DAY`). Present only when reported. Display wording (a human-readable name and emoji) is intentionally the consumer's concern — the website maps this token to a localised name and emoji.



## :test_tube: Tests & code style

```bash
composer test              # PHPUnit with coverage, then opens the HTML report
composer check-style       # PHPCS across src/ and tests/
composer check-style-diff  # PHPCS on changed files only
composer fix-style         # auto-fix style in src/ and tests/
composer fix-style-diff    # auto-fix changed files only
```



## :books: API documentation

The committed `openapi.yaml` is generated from the `#[OA\...]` attributes in `src/`
(`composer openapi:generate`). Dev-only [Redoc](https://redocly.com/redoc) tooling
(`@redocly/cli`) renders and lints it — it is separate from the PHP runtime and excluded
from both git and the GCP deploy, so it never affects the deployed function.

```bash
npm install            # one-time: installs the docs tooling (Node/npm)
npm run docs:preview   # live browser preview of openapi.yaml (local server)
npm run docs:build     # write a shareable static openapi.html (git-ignored build artifact)
npm run docs:lint      # lint openapi.yaml
```



## :rocket: CI & deployment

- **`.github/workflows/ci.yml`** runs on pushes and pull requests to `main`: `composer install`, PHPCS, PHPStan, PHPUnit, and an OpenAPI spec-drift check.
- **`.github/workflows/deploy.yml`** runs on push to `main`: deploys the Cloud Run function (`php85` runtime, `europe-west2`, function name `get-met-office-weather`) via Workload Identity Federation, grants public (`allUsers`) invoker access on the underlying Cloud Run service, smoke-tests the deployed URL, then purges the Fastly edge cache.

The Met Office API key, latitude, longitude, and request-gating values are supplied at deploy time from Google Secret Manager (see `deploy.yml`).



## :package: Architecture

The entry point is `run()` in [`index.php`](index.php), which wires the pieces together:

- **`ConfigTransformer`** reads the environment into a `Config` (API key, latitude, longitude + request/caching config).
- **`MetOffice`** (from [`christianjbrown/php-met-office-weather-datahub-api-lib`](https://github.com/christianjbrown/php-met-office-weather-datahub-api-lib)) provides the hourly forecast API client.
- **`DataProvider`** fetches the hourly forecast for the configured location and selects the current hour's step.
- **`OutputTransformer`** shapes that step into the JSON response, converting wind speeds to mph and emitting the weather code as both its raw number (`type`) and its `WeatherType` enum-name token (`type_name`); display wording is left to the consumer.
- **`CloudFunction`** (from [`christianjbrown/php-gcp-function-lib`](https://github.com/christianjbrown/php-gcp-function-lib)) handles the HTTP request/response, header/origin gating, and caching headers.



## :page_facing_up: License

Released under the [MIT License](LICENSE).

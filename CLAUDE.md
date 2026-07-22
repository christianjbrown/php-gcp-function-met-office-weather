# CLAUDE.md

Guidance for working in this repository. Match the existing conventions exactly — this codebase is
small, uniform, and highly opinionated, so new code should be indistinguishable from what's here.

## What this is

A deployable **Google Cloud Run function** (PHP 8.5+, `php85` runtime) that reads the current outdoor
weather for a fixed latitude/longitude from the Met Office Weather DataHub **Site-Specific hourly**
forecast API and returns it as a single JSON envelope. It is an **application, not a library** — it
wires the sibling `christianjbrown/*` libraries together behind an HTTP entry point: the `run()`
function in `index.php` builds the config, constructs a `MetOffice` client, and returns the PSR-7
response.

The app consumes several private `dev-main` sibling packages: `php-gcp-function-lib` (the HTTP
envelope/gating/caching framework), `php-met-office-api-lib` (the read-only Met Office Weather
DataHub client), `php-user-friendly-exception-lib`, `php-christianbrown-database-orm` (the shared
Doctrine ORM for the `christianbrown` schema), plus `php-code-quality-scripts` (dev). It runs
on Google's [Functions Framework](https://github.com/GoogleCloudPlatform/functions-framework-php)
locally.

**Climate history.** On each request the function also records the observed outdoor temperature and
humidity to the shared `met_office_weather` table (append-only, one row per origin request), via the
shared `EntityManagerFactory` + `ClimateMeasurementRecorder` from `php-christianbrown-database-orm`.
The write is best-effort: `DataProvider` wraps it in a `try/catch` and `error_log()`s failures so a
database problem never disturbs the response. This is the app's only stateful behaviour; it needs the
`CHRISTIANBROWN_DATABASE_DSN` env var and (in production) the shared Cloud SQL instance attached over
the `/cloudsql` socket.

## Commands

Binaries install into `bin/` (Composer `bin-dir`), not `vendor/bin/`. Both `bin/` and `vendor/` are
gitignored and Composer-installed, so run `composer install` first. Unlike the libraries, this app
**commits `composer.lock`** for reproducible deploys.

| Task | Command |
| --- | --- |
| Run the function locally (Functions Framework) | `composer start` |
| Run tests + coverage (opens HTML report) | `composer test` |
| Run tests, no coverage | `php -d memory_limit=-1 ./bin/phpunit --no-coverage` |
| Run one test | `php -d memory_limit=-1 ./bin/phpunit --filter DataProviderTest` |
| Static analysis | `composer stan` |
| Check code style | `composer check-style` |
| Auto-fix code style | `composer fix-style` |
| Check / fix style on git diff only | `composer check-style-diff` / `composer fix-style-diff` |
| Regenerate `openapi.yaml` from `#[OA\...]` attributes | `composer openapi:generate` |
| Preview the API docs live in a browser | `npm install` then `npm run docs:preview` |
| Build a shareable static `openapi.html` | `npm run docs:build` |
| Lint `openapi.yaml` | `npm run docs:lint` |

`composer start` exports `.local.env` (git-ignored) and serves the function at `http://localhost:8080`
(override with `PORT`) via `FUNCTION_TARGET=run` on the Functions Framework router. A local run needs
at least `MET_OFFICE_WEATHER_API_KEY`, `MET_OFFICE_WEATHER_LATITUDE`, `MET_OFFICE_WEATHER_LONGITUDE`,
`CHRISTIANBROWN_DATABASE_DSN` (a reachable MySQL DSN — e.g. the shared instance via the Cloud SQL
proxy) and `K_REVISION` set — see `README.md` for the full env-var list.

Style tooling comes from the `christianjbrown/php-code-quality-scripts` dev dependency (`check-style`
runs **PHP_CodeSniffer 4** with the `ChristianBrown` standard — slevomat sniffs plus PSR/PEAR/Squiz/Generic
— for linting, while **php-cs-fixer** with `@PhpCsFixer`/`@Symfony` handles formatting); the `bin/php-cs*`
scripts are thin wrappers over it.
Static analysis is **PHPStan at `level: max`** (`phpstan.neon.dist`). Always run `composer fix-style`
first, then `composer check-style` to surface anything left to fix by hand, then `composer stan` and
`composer test` before finishing.

The `npm run docs:*` scripts are **dev-only** [Redoc](https://redocly.com/redoc) tooling
(`@redocly/cli`, in `package.json`) for rendering/linting the committed `openapi.yaml`. They are
completely separate from the PHP runtime: `node_modules/`, `package.json`, `package-lock.json`,
`redocly.yaml`, and the generated `openapi.html` are all in `.gcloudignore` (kept out of the deploy so
the `php85` buildpack still sees a pure-PHP app) and the build artifacts (`node_modules/`,
`openapi.html`) are git-ignored. `redocly.yaml` relaxes three opinionated Redocly governance rules
(`no-empty-servers`, `security-defined`, `info-license`) that don't fit this single header-gated
endpoint — the config is the place to do that because `openapi.yaml` is generated and must not be
hand-edited. `@redocly/cli` is pinned to `^1.x` because the `preview-docs` live-server command was
removed in v2.

## Architecture

Everything lives directly under `src/` (no sub-layers). PSR-4: `ChristianBrown\MetOfficeWeather\` →
`src/` (`autoload`), `ChristianBrown\MetOfficeWeather\Tests\` → `tests/` (`autoload-dev`). The
top-level `index.php` holds the framework entry point and is intentionally outside the namespace.

- **`index.php`** — defines `run(ServerRequestInterface): ResponseInterface`, the Functions Framework
  target, and sets `date_default_timezone_set('UTC')` at the top. It is a thin **composition root
  only**: it reads `getenv()`, builds a `Config` via `ConfigTransformer`, then constructs an anonymous
  `CloudFunctionFactoryInterface` whose `create()` holds the wiring (the `MetOffice` facade + its hourly
  forecast client, the `DataProvider` and `OutputTransformer`, handed to a `CloudFunction`). It passes that factory + the `FunctionConfig`
  to a `RequestHandler` and returns `handle($request)`. All the `new` wiring lives here (outside the
  namespace, so it is excluded from coverage/PHPStan/phpcs, which only scan `src`/`tests`); the testable
  orchestration lives in `src`.
- **`RequestHandler`** / **`RequestHandlerInterface`** — the testable entry-point orchestration.
  `handle()` calls the injected `CloudFunctionFactoryInterface::create()` and returns
  `CloudFunction::run($request)`, wrapping **both** in one `try/catch (Throwable)`. Because the MetOffice
  client is built in the factory *before* the `CloudFunction` exists, a failure there would otherwise
  escape as a bare 500; the catch instead `error_log()`s the cause and returns the framework's
  `JsonErrorResponse` envelope (matching the sibling `php-gcp-function-smartthings-climate` app).
- **`CloudFunctionFactoryInterface`** — the seam that defers the wiring so `RequestHandler` can wrap it;
  implemented as an anonymous class in `index.php` (the composition root) and mocked in tests.
- **`Config`** / **`ConfigInterface`** — a small holder for the API key, latitude, longitude, plus the
  `FunctionConfigInterface` (from `php-gcp-function-lib`) that drives gating/caching.
- **`ConfigTransformer`** / **`ConfigTransformerInterface`** — builds a `Config` from the environment
  array. It guards `MET_OFFICE_WEATHER_API_KEY` (`ENV_API_KEY`, presence + `is_string`),
  `MET_OFFICE_WEATHER_LATITUDE` / `MET_OFFICE_WEATHER_LONGITUDE` (`ENV_LATITUDE` / `ENV_LONGITUDE`,
  `isset` + `is_numeric`, then `(float)` cast — `isset` not `empty` so a legitimate `0` survives) and
  `CHRISTIANBROWN_DATABASE_DSN` (`ENV_DATABASE_DSN`, presence + `is_string`) with sequential checks, and
  delegates the rest of the env to the injected `FunctionConfigTransformer`.
- **`DataProvider`** — implements the lib's `DataProviderInterface`. `getData()` fetches the hourly
  forecast for the configured lat/lon, then selects the **current step**: the step with the greatest
  `getTime()` that is at or before `time()` (captured once in the constructor as `$this->now`),
  falling back to the earliest step when every step is in the future. It throws a
  `UserFriendlyException` (`ERROR_NO_FORECAST`) when there are no steps, or when the selected step is
  not a `HourlyForecastTimeStepInterface`. Selection is done with `usort` + `array_filter` +
  `array_key_last` (no `foreach` with an inner `if`) so each branch is an independent path. Once a step
  is selected it records that step's `getScreenTemperature()` / `getScreenRelativeHumidity()` to the
  `met_office_weather` table (best-effort, isolated by `try/catch`) before handing the step to the
  `OutputTransformer`.
- **`OutputTransformer`** / **`OutputTransformerInterface`** — shapes one `HourlyForecastTimeStep`
  into the response array. The `valid_from` / `valid_from_iso8601` / `valid_to` / `valid_to_iso8601`
  window fields are always emitted; every other field (temp, feels-like, humidity, precipitation,
  UV, visibility, wind speed/gust/direction, weather `type`/`type_name`) is unioned via its own
  self-contained private helper so its presence is an independent path. Wind speeds are converted
  m/s → mph via `METRES_PER_SECOND_TO_MPH` at full precision (the website rounds for display); the
  window length is `WINDOW_SECONDS` (3600). Weather type is emitted as the raw code (`type`, from
  `WeatherType->value`) and a stable enum-name token (`type_name`, from `WeatherType->name`) — display
  wording (name/emoji) is **not** produced here; the consuming website owns that (locale-sensitive)
  mapping.

## Output contract

The JSON keys are `KEY_*` constants on `OutputTransformerInterface`. `valid_from`,
`valid_from_iso8601`, `valid_to`, `valid_to_iso8601` are always present. `temp`, `temp_feels_like`,
`humidity`, `precipitation`, `uv_index`, `visibility`, `pressure` (hPa, from pascals ÷ 100),
`dew_point`, `wind_speed`, `wind_gust`, `wind_direction`,
`type`, and `type_name` appear only when their source value is non-null. `type` is the raw Met Office
code (int) and `type_name` is the `WeatherType` enum-name token (e.g. `"HEAVY_RAIN"`); the website
maps `type_name` to a display name + emoji. This
contract is load-bearing — a downstream website validates it — so do not rename keys or round the
numeric values.

## Conventions (follow all of these)

- `declare(strict_types=1);` on every file, immediately after `<?php`.
- **Every concrete class is `final` and implements a matching `...Interface`** in the same namespace
  (`DataProvider`/`DataProviderInterface`, `OutputTransformer`/`OutputTransformerInterface`). No
  abstract base classes — composition over inheritance.
- **Constants live on the interface, not the class**: env keys (`ENV_*`), response body keys
  (`KEY_*`), the mph factor / window constants, and error messages (`ERROR_*`) — all typed constants.
- **No constructor property promotion** — declare typed `private` properties and assign them in the
  constructor body. Class members (properties then methods) are ordered **alphabetically**.
- Import functions explicitly with `use function array_filter;` etc. and constants with
  `use const DATE_ATOM;` (after class imports, blank line between groups) and call them unqualified.
- **Value objects** (`Config`): required fields are constructor args; getters `getX()`
  (boolean getters `isX()`). No enums, no `readonly`, no immutability.
- **Transformers**: one `transform(...)` method returning the shaped result. Arrays crossing a public
  boundary carry a `@param mixed[]` / `@return mixed[]` docblock so PHPStan `level: max` is satisfied
  (the payload can be a list or a map, so `mixed[]`, not `array<string, mixed>`).
- **Coverage-driven control flow**: guards are deliberately split into sequential `if`s (rather than a
  single `||`) and optional blocks are unioned as self-contained helpers so each branch is an
  independently reachable path — keep this pattern, it exists to hit 100% path coverage. Avoid
  compound `&&`/`||` conditions and `foreach`-with-inner-`if` in `src/`.

## Testing

The `phpunit.xml` config is strict (`requireCoverageMetadata`, `beStrictAboutCoverageMetadata`,
`failOnRisky`, `failOnWarning`, `beStrictAboutOutputDuringTests`, path coverage). With that in mind:

- **Coverage must stay at 100%** — line, path, method/function, and branch. Every code path, including
  each defensive guard, the current-step vs earliest-fallback branches in `DataProvider`, and every
  optional-field block in `OutputTransformer` (present and absent, plus the weather-type name/emoji
  present/absent sub-branches), must be exercised. **Always run `composer test` and check the coverage
  report** before finishing — it prints a text summary to stdout and writes HTML to
  `.phpunit.cache/code-coverage-html/index.html`. New code without full coverage is not done.
- **Every test class needs a `#[CoversClass(...)]` attribute** (may list more than one — e.g.
  `ConfigTransformerTest` also covers `Config` because it constructs one) or the run fails. Use
  PHPUnit 12 **attributes, not annotations**: `#[CoversClass]`, `#[DataProvider]`, `#[TestWith]`.
- Tests mirror `src/` under `tests/`, one `final class XTest extends TestCase` per class. Double every
  collaborator via its interface — `self::createStub(...)` for a return-only double (never `->with()`
  on a stub), `self::createMock(...)` + `->expects(...)` for a verified call. Assert statically
  (`self::assertSame`). Reference the **same interface constants** production code uses so no strings
  are hardcoded.

## Adding a feature

1. Add the class + its matching interface (constants, if any, on the interface).
2. Follow the conventions above (final, no promotion, alphabetical members, function/const imports,
   `mixed[]` docblocks on array boundaries, sequential guards / self-contained helpers for path
   coverage).
3. If it needs new wiring, extend `index.php`'s `run()` to construct and inject it.
4. Add a matching `#[CoversClass]` test under `tests/`.
5. Run `composer fix-style`, then `composer check-style`, then `composer stan`, then `composer test`
   and **confirm the coverage report is 100%** on lines, paths, methods, and branches.

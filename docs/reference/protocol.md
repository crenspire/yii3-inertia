# Protocol support

The adapter implements the [Inertia.js protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol) used by
Inertia.js 3.

## Request headers

| Header | Handling |
|---|---|
| `X-Inertia` | Returns the page as JSON instead of HTML |
| `X-Inertia-Version` | Compared with the current version on `GET`; a mismatch returns `409` |
| `X-Inertia-Partial-Component` | Enables partial reload filtering when it matches the rendered component |
| `X-Inertia-Partial-Data` | Props to include, with dot notation |
| `X-Inertia-Partial-Except` | Props to exclude, with dot notation |
| `X-Inertia-Reset` | Merge props to replace instead of merge |
| `X-Inertia-Error-Bag` | Nests the default validation errors under the bag name |
| `X-Inertia-Except-Once-Props` | Once props the client already has |
| `X-Inertia-Infinite-Scroll-Merge-Intent` | `prepend` makes scroll props prepend |
| `Purpose: prefetch` | Keeps fragment redirects as regular redirects |

## Response headers and status codes

| Situation | Response |
|---|---|
| Inertia visit | `200`, `Content-Type: application/json`, `X-Inertia: true` |
| First visit | `200`, `Content-Type: text/html; charset=UTF-8` |
| Every response through the middleware | `Vary: X-Inertia` |
| Outdated asset version on `GET` | `409`, `X-Inertia-Location`, `X-Inertia-Version` |
| `location()` for an Inertia visit | `409`, `X-Inertia-Location` |
| `302` after `PUT`, `PATCH` or `DELETE` | Changed to `303` |
| Redirect to a URL with a fragment | `409`, `X-Inertia-Redirect` |
| Empty `200` response to an Inertia visit | Redirect back |

## Page object

| Key | When present |
|---|---|
| `component`, `props`, `url`, `version` | Always |
| `sharedProps` | When there are shared props (always, because of `errors`) |
| `deferredProps` | Deferred props were left out |
| `mergeProps`, `prependProps`, `deepMergeProps`, `matchPropsOn` | Merge props were included or announced |
| `scrollProps` | Scroll props were included |
| `onceProps` | Once props were included or skipped |
| `rescuedProps` | Deferred props with `rescue` failed |
| `flash` | Flash data was stored |
| `encryptHistory` | History encryption is enabled |
| `clearHistory` | History clearing was requested |
| `preserveFragment` | Fragment preservation was requested |

## Not supported

- Precognition (live validation requests)
- Automatic existence checks for page component files

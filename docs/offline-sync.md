# Offline Synchronization: First Release Contract

## Scope and decision

The first offline release synchronizes only **recorded operational data**: attendance creation, exam-result creation, payment creation, and worksheet-submission updates. It does not mirror the whole LMS, publish worksheets offline, cache notification contents, modify academic groups, or queue administrative configuration changes. This keeps the initial operational surface small, reviewable, and compatible with the existing authorization model.

> IndexedDB is transactional, asynchronous browser storage for structured data and is more appropriate than Web Storage for significant structured application data. [1]

| Actor                   | Offline snapshot                                                                        | Accepted offline operation types                                                           | Excluded capabilities                                                                            |
| ----------------------- | --------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------ |
| Administrator / teacher | Minimal student roster plus recorded operations and assigned worksheet records in scope | `attendance.create`, `exam_result.create`, `payment.create`, `worksheet_submission.submit` | Staff settings, roles, payment-provider setup, group changes, notifications, destructive updates |
| Student                 | Own recorded history and own worksheet assignments only                                 | `worksheet_submission.submit` for own assignment                                           | Attendance, exam results, payments, other learners, staff actions                                |
| Parent                  | Linked learner’s read-only recorded history and assignments only                        | None in the first release                                                                  | All writes and all other learners                                                                |

The snapshot sends only fields that the offline workflow needs. In particular, it uses the roster fields `id`, `name`, `grade`, and `group`; it omits phone numbers, parent phone numbers, authentication data, access tokens, permissions, notification content, and provider configuration. Cached data is namespaced by authenticated user ID and role, cleared at logout, and expires after seven days.

## API contract

`GET /api/sync/snapshot` returns a server-authoritative, role-scoped snapshot. It always fetches the current allowed state in this first release rather than attempting a fragile partial-delta protocol. The response contains an ISO-8601 `generated_at` value and the arrays `students`, `worksheets`, `attendance`, `exams`, and `payments`.

`POST /api/sync/operations` receives no more than 50 ordered operations in one request. Each operation has a client-generated UUID `id`, an allowed `type`, an ISO-8601 `occurred_at`, an optional `base_updated_at`, and a type-specific `data` object. The server stores the user ID and UUID under a unique database constraint before returning its result. Retrying the same UUID returns the prior result without creating a duplicate record.

```json
{
  "operations": [
    {
      "id": "1a15cc59-4ac8-41ea-bb91-1c1ef402e460",
      "type": "attendance.create",
      "occurred_at": "2026-08-20T08:30:00Z",
      "data": {
        "student_id": 14,
        "date_at": "2026-08-20T08:30:00Z",
        "status": "present",
        "note": "تسجيل دون اتصال"
      }
    }
  ]
}
```

| Outcome     | Meaning                                                          | Client behavior                                                                                       |
| ----------- | ---------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `applied`   | The server authorized and wrote the operation.                   | Remove the outbox item and refresh the scoped snapshot.                                               |
| `duplicate` | The same user and client UUID were already applied.              | Remove the outbox item; use the stored result.                                                        |
| `rejected`  | Validation, scope, or authorization failed.                      | Retain a visible rejected record with a safe Arabic message; do not retry automatically.              |
| `conflict`  | The target worksheet assignment changed after `base_updated_at`. | Retain the item for review; refresh server data; do not overwrite automatically.                      |
| `retry`     | Network or transient server failure.                             | Retain the item in original order and retry on the next explicit or online-triggered synchronization. |

## Server rules

The sync controller delegates to a dedicated service inside a database transaction. Every operation is re-authorized server-side; browser role claims and local timestamps are never trusted as permission evidence. Existing attendance, exam, payment, worksheet, cache-invalidation, and strict lazy-loading contracts are reused rather than recreated in client code.

The server applies a conservative conflict policy. Creates are idempotent through the operation UUID, not by guessing duplicate business records. Worksheet submission includes `base_updated_at`; if the server assignment has changed since the client read it, the server returns `conflict` and preserves the newer server record. The first release does not replay destructive operations or staff edits, eliminating last-writer-wins data loss.

## Browser persistence and reconnect behavior

The browser uses IndexedDB stores for `snapshots`, `outbox`, and `sync_results`, and keeps only the existing authorization token in its current storage mechanism. Each outbox item holds a typed operation, user/role scope, creation time, retry count, and status. Outbox rows are written before the UI treats an offline action as saved.

The dashboard requests a current server snapshot when online and falls back to the authenticated user’s local snapshot only when a request fails. It replays the typed outbox in order after the browser `online` event and exposes a manual sync action for deterministic user feedback. A future service-worker background-sync enhancement may be added as a progressive enhancement, but the core correctness path must not depend on it because the browser controls when background work runs and can stop a worker. [2]

| Retention boundary     | Rule                                                                                                       |
| ---------------------- | ---------------------------------------------------------------------------------------------------------- |
| Snapshot TTL           | Delete snapshots older than seven days when the app opens or signs out.                                    |
| Account switch         | Never read a snapshot or outbox row whose user ID and role do not match the current authenticated account. |
| Logout                 | Delete all snapshots, outbox items, and sync outcomes for the logged-out account.                          |
| Rejected/conflict rows | Keep for at most 30 days or until the user discards them; show non-sensitive feedback only.                |
| Queue size             | Reject additional local operations after 500 rows and tell the user to reconnect and synchronize.          |

## Non-goals and rollout safeguards

This release does not register a service worker, use background sync as a reliability requirement, store files/blobs, expose payment-provider credentials, or make payment approval available offline. It also does not turn the free Autoscale deployment into an always-on process. The existing signed scheduled notification queue drain remains separate from browser record reconciliation.

Validation covers role-scoped snapshots, ownership violations, idempotent duplicate replay, validation rejection, worksheet conflict detection, compact snapshot serialization, IndexedDB namespace/expiry logic, ordered outbox replay, rejected-operation retention, and dashboard status feedback.

## References

[1]: https://developer.mozilla.org/en-US/docs/Web/API/IndexedDB_API "MDN — IndexedDB API"
[2]: https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Offline_and_background_operation "MDN — Offline and background operation"

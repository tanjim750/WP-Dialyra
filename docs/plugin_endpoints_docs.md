### `POST /api/v2/auth/login`

Request:

```json
{
  "email": "string",
  "password": "string"
}
```

Response `200`:

```json
{
  "access_token": "jwt",
  "refresh_token": "string",
  "user": {
    "id": 1,
    "full_name": "string",
    "email": "string",
    "role": "superuser|stuff|general",
    "status": "active|inactive|suspended",
    "last_login_at": "ISO_DATETIME|null",
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  },
  "business": {
    "id": 1,
    "name": "string",
    "slug": "string",
    "owner_user_id": 1,
    "phone": "string",
    "email": "string",
    "status": "active",
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  }
}
```

### `POST /api/v2/auth/refresh`

Request:

```json
{
  "refresh_token": "string"
}
```

Response `200`:

```json
{
  "access_token": "jwt",
  "refresh_token": "string",
  "user": {
    "id": 1,
    "full_name": "string",
    "email": "string",
    "role": "superuser|stuff|general",
    "status": "active|inactive|suspended",
    "last_login_at": "ISO_DATETIME|null",
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  }
}
```

### `POST /api/v2/auth/logout`

Request:

```json
{
  "refresh_token": "string"
}
```

Response `200`:

```json
{
  "message": "Logout successful"
}
```

### `GET /api/v2/auth/me`

Response `200`:

```json
{
  "user": {
    "id": 1,
    "full_name": "string",
    "email": "string",
    "role": "superuser|stuff|general",
    "status": "active|inactive|suspended",
    "last_login_at": "ISO_DATETIME|null",
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  },
  "business": {
    "id": 1,
    "name": "string",
    "slug": "string",
    "owner_user_id": 1,
    "phone": "string",
    "email": "string",
    "status": "active",
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  }
}
```

### `POST /api/v2/auth/users`

Request:

```json
{
  "full_name": "string",
  "email": "string",
  "password": "string",
  "role": "superuser|stuff|general",
  "business_id": 1,
  "membership_role": "admin|manager|agent|viewer"
}
```

### `GET /api/v2/auth/users`

Access:
- `superuser|stuff`

Response `200` for superuser:

```json
{
  "items": [
    {
      "id": 1,
      "full_name": "string",
      "email": "string",
      "role": "superuser|stuff|general",
      "status": "active|inactive|suspended",
      "last_login_at": "ISO_DATETIME|null",
      "created_at": "ISO_DATETIME",
      "updated_at": "ISO_DATETIME"
    }
  ]
}
```

Response `200` for stuff (members of owned businesses):

```json
{
  "items": [
    {
      "user": {
        "id": 2,
        "full_name": "string",
        "email": "string",
        "role": "general",
        "status": "active",
        "last_login_at": null,
        "created_at": "ISO_DATETIME",
        "updated_at": "ISO_DATETIME"
      },
      "memberships": [
        {
          "membership_id": 5,
          "business_id": 1,
          "membership_role": "viewer",
          "membership_status": "active",
          "joined_at": "ISO_DATETIME"
        }
      ]
    }
  ]
}
```

Note:
- `stuff` can create only `general`.
- `superuser` can create `superuser|stuff|general`.
- For `general`, `business_id` and `membership_role` are required.
- Stuff user can only pass a `business_id` they own.
- Superuser can pass any valid business id when creating `general`.
- Membership is auto-created only when `role=general`.

Response `201`:

```json
{
  "message": "User created successfully",
  "user": {
    "id": 1,
    "full_name": "string",
    "email": "string",
    "role": "superuser|stuff|general",
    "status": "active|inactive|suspended",
    "last_login_at": null,
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  }
}
```

### `POST /api/v2/auth/users/:user_id/membership`
### `PUT /api/v2/auth/users/:user_id/membership`

Request:

```json
{
  "business_id": 1,
  "membership_role": "admin|manager|agent|viewer",
  "status": "active|inactive|suspended"
}
```

Note:
- `role` can be used as fallback alias for `membership_role`.
- Only `general` users can be assigned/updated by this endpoint.

Response `200`:

```json
{
  "message": "Membership created successfully",
  "membership": {
    "id": 1,
    "business_id": 1,
    "user_id": 2,
    "membership_role": "viewer",
    "status": "active",
    "joined_at": "ISO_DATETIME"
  }
}
```

### `DELETE /api/v2/auth/users/:user_id/membership`

Request:

```json
{
  "business_id": 1
}
```

Response `200`:

```json
{
  "message": "Membership removed successfully"
}
```

## Businesses

### `POST /api/v2/businesses`

Request:

```json
{
  "name": "string",
  "slug": "string",
  "email": "string",
  "phone": "string",
  "timezone": "Asia/Dhaka",
  "country": "Bangladesh"
}
```

Response `201`:

```json
{
  "id": 1,
  "uuid": "string",
  "name": "string",
  "slug": "string",
  "owner_user_id": 1,
  "email": "string",
  "phone": "string",
  "timezone": "string",
  "country": "string|null",
  "logo_path": "string|null",
  "status": "active|inactive|suspended|deleted",
  "settings": {},
  "deleted_at": "ISO_DATETIME|null",
  "created_at": "ISO_DATETIME",
  "updated_at": "ISO_DATETIME"
}
```

### `GET /api/v2/businesses`

Response `200`:

```json
{
  "items": [
    {
      "id": 1,
      "uuid": "string",
      "name": "string",
      "slug": "string",
      "owner_user_id": 1,
      "email": "string",
      "phone": "string",
      "timezone": "string",
      "country": "string|null",
      "logo_path": "string|null",
      "status": "active|inactive|suspended|deleted",
      "settings": {},
      "deleted_at": "ISO_DATETIME|null",
      "created_at": "ISO_DATETIME",
      "updated_at": "ISO_DATETIME"
    }
  ]
}
```

### `GET /api/v2/businesses/:business_id`

Response `200`: same business object as above.

### `PUT /api/v2/businesses/:business_id`

Request (all fields optional):

```json
{
  "name": "string",
  "email": "string",
  "phone": "string",
  "timezone": "string",
  "country": "string",
  "logo_path": "string",
  "status": "active|inactive|suspended|deleted"
}
```

Response `200`: same business object as above.

### `DELETE /api/v2/businesses/:business_id`

Behavior:
- Soft delete only.
- Sets business `status` to `deleted`.
- Sets `deleted_at` timestamp.

Response `200`:

```json
{
  "message": "Business deleted"
}
```

### `GET /api/v2/businesses/:business_id/settings`

Response `200`:

```json
{
  "business_id": 1,
  "settings": {}
}
```

### `PUT /api/v2/businesses/:business_id/settings`

Request:

```json
{
  "settings": {
    "max_users": 50,
    "max_concurrent_calls": 10,
    "max_campaigns": 5,
    "max_sip_trunks": 3,
    "max_storage": 1024
  }
}
```

Response `200`:

```json
{
  "business_id": 1,
  "settings": {}
}
```

### `GET /api/v2/businesses/:business_id/inbound-call-config`

Returns the saved inbound call config plus the effective flow that would be used for inbound calls.

If no config row exists, response returns an implicit default:

- `enabled=true`
- `flow_id=null`
- `fallback_mode=latest_published_flow`
- effective flow resolves to the latest published flow for the business

Response `200`:

```json
{
  "id": 1,
  "business_id": 1,
  "enabled": true,
  "flow_id": 12,
  "fallback_mode": "latest_published_flow",
  "metadata": {},
  "source": "saved_config",
  "effective_flow_id": 12,
  "effective_flow_version_id": 18,
  "effective_source": "configured_flow",
  "ready_for_inbound": true,
  "readiness_reason": null,
  "created_at": "ISO_DATETIME",
  "updated_at": "ISO_DATETIME"
}
```

Implicit default response example:

```json
{
  "id": null,
  "business_id": 1,
  "enabled": true,
  "flow_id": null,
  "fallback_mode": "latest_published_flow",
  "metadata": null,
  "source": "implicit_default",
  "effective_flow_id": 15,
  "effective_flow_version_id": 21,
  "effective_source": "latest_published_flow",
  "ready_for_inbound": true,
  "readiness_reason": null,
  "created_at": null,
  "updated_at": null
}
```

Possible `effective_source` values:

- `configured_flow`
- `latest_published_flow`
- `none`

Possible `readiness_reason` values:

- `inbound_disabled`
- `configured_flow_unpublished`
- `no_published_flow`
- `business_not_found`

### `POST /api/v2/businesses/:business_id/inbound-call-config`

Creates inbound call config for the business.

Request:

```json
{
  "enabled": true,
  "flow_id": 12,
  "fallback_mode": "latest_published_flow",
  "metadata": {
    "note": "Main inbound IVR"
  }
}
```

Fields:

- `enabled`: optional boolean, default `true`
- `flow_id`: optional published flow id from the same business
- `fallback_mode`: optional, `latest_published_flow|strict_configured_flow`
- `metadata`: optional object

Response `201`: same shape as `GET /api/v2/businesses/:business_id/inbound-call-config`.

Errors:

```json
{"error": "Inbound call config already exists for this business"}
```

```json
{"error": "flow_id must reference a published flow for this business"}
```

```json
{"error": "fallback_mode must be one of: latest_published_flow, strict_configured_flow"}
```

### `PUT /api/v2/businesses/:business_id/inbound-call-config`

Updates inbound call config for the business. If the config row does not exist, backend creates it with defaults and applies the provided fields.

Request (all fields optional):

```json
{
  "enabled": true,
  "flow_id": 15,
  "fallback_mode": "strict_configured_flow",
  "metadata": {
    "note": "Use support IVR only"
  }
}
```

Response `200`: same shape as `GET /api/v2/businesses/:business_id/inbound-call-config`.

Validation:

- `flow_id` must belong to the same business.
- `flow_id` must have an active published flow version.
- `fallback_mode=latest_published_flow` falls back to latest published business flow when configured flow is missing/unpublished.
- `fallback_mode=strict_configured_flow` requires the configured flow to be usable.
- `enabled=false` disables inbound flow execution for the business.

### `POST /api/v2/businesses/:business_id/members`

Request:

```json
{
  "user_id": 2,
  "role": "admin|manager|agent|viewer",
  "status": "active|inactive|suspended"
}
```

Response `201`:

```json
{
  "id": 1,
  "business_id": 1,
  "user_id": 2,
  "role": "viewer",
  "status": "active",
  "joined_at": "ISO_DATETIME"
}
```

### `GET /api/v2/businesses/:business_id/members`

Response `200`:

```json
{
  "items": [
    {
      "id": 1,
      "business_id": 1,
      "user_id": 2,
      "role": "viewer",
      "status": "active",
      "joined_at": "ISO_DATETIME"
    }
  ]
}
```

### `PUT /api/v2/businesses/:business_id/members/:member_id`

Request (any of):

```json
{
  "role": "admin|manager|agent|viewer",
  "status": "active|inactive|suspended"
}
```

Response `200`:

```json
{
  "id": 1,
  "business_id": 1,
  "user_id": 2,
  "role": "manager",
  "status": "active",
  "joined_at": "ISO_DATETIME"
}
```

### `DELETE /api/v2/businesses/:business_id/members/:member_id`

Response `200`:

```json
{
  "message": "Member removed"
}
```

## Access Tokens

### `POST /api/v2/access-tokens`

Request:

```json
{
  "name": "Production Runtime",
  "business_id": 1,
  "expires_days": 365,
  "scopes": ["calls:originate", "calls:read"]
}
```

Response `201`:

```json
{
  "token": "dialyra_live_xxx",
  "message": "Save this token now. It will not be shown again.",
  "access_token": {
    "id": 1,
    "business_id": 1,
    "name": "Production Runtime",
    "token_prefix": "dialyra_live_xxx",
    "scopes": ["calls:originate"],
    "is_active": true,
    "last_used_at": null,
    "expires_at": "ISO_DATETIME",
    "created_by": 1,
    "created_at": "ISO_DATETIME",
    "revoked_at": null
  }
}
```

### `GET /api/v2/access-tokens`

Query params:
- `business_id` (optional for privileged roles, required for some contexts)

Response `200`:

```json
{
  "items": [
    {
      "id": 1,
      "business_id": 1,
      "name": "string",
      "token_prefix": "string",
      "scopes": [],
      "is_active": true,
      "last_used_at": "ISO_DATETIME|null",
      "expires_at": "ISO_DATETIME|null",
      "created_by": 1,
      "created_at": "ISO_DATETIME",
      "revoked_at": "ISO_DATETIME|null"
    }
  ]
}
```

### `GET /api/v2/access-tokens/:token_id`

Response `200`: `access_token` object shape above (without wrapper).

### `POST /api/v2/access-tokens/:token_id/revoke`

Response `200`:

```json
{
  "message": "Access token revoked",
  "access_token": {
    "id": 1,
    "business_id": 1,
    "name": "string",
    "token_prefix": "string",
    "scopes": [],
    "is_active": false,
    "last_used_at": "ISO_DATETIME|null",
    "expires_at": "ISO_DATETIME|null",
    "created_by": 1,
    "created_at": "ISO_DATETIME",
    "revoked_at": "ISO_DATETIME"
  }
}
```

### `DELETE /api/v2/access-tokens/:token_id`

Response `200`:

```json
{
  "message": "Access token deleted"
}
```

### Access Token Scopes (Current)

- `calls:originate`
  - `POST /api/v3/runtime/calls/originate`
  - `POST /api/v2/runtime/calls/:call_id/retry`
- `calls:hangup`
  - `POST /api/v2/runtime/calls/:call_id/hangup`
- `calls:read`
  - `GET /api/v2/runtime/calls` (alias: `/api/v2/calls`)
  - `GET /api/v2/runtime/calls/:call_id` (alias: `/api/v2/calls/:call_id`)
  - `GET /api/v2/runtime/calls/events`
  - `GET /api/v2/runtime/calls/webhook-jobs`
  - `GET /api/v2/runtime/calls/:call_id/webhook-jobs`
  - `GET /api/v2/runtime/calls/webhook-worker/health`
  - `GET /api/v2/runtime/calls/webhook-jobs/:job_id`
  - `POST /api/v2/runtime/calls/webhook-jobs/:job_id/retry`
  - `POST /api/v2/runtime/calls/webhook-jobs/retry`
  - `GET /api/v2/runtime/calls/webhook-jobs/summary`



## Business Event Webhooks

Business event webhooks are configured at the business level and receive selected runtime/call/billing events by outbound `POST` request. They are separate from flow-node `webhook` nodes, which execute inside a specific flow.

Auth:
- JWT only
- setup/update/delete/test/retry/delivery inspection require `businesses.manage`
- list/get subscription and worker health require `businesses.read`

Supported event types:

```json
[
  "call.completed",
  "call.failed",
  "node.executed",
  "dtmf.received",
  "transfer.connected",
  "transfer.failed",
  "transfer.timeout",
  "transfer.completed",
  "wait.completed",
  "wait.failed",
  "recording.started",
  "recording.stopped",
  "recording.failed",
  "runtime.error",
  "billing.charged",
  "billing.blocked",
  "billing.released"
]
```

Use `"*"` to subscribe to all supported events.

### `POST /api/v2/business-webhooks`

Creates a business webhook subscription.

Request:

```json
{
  "business_id": 1,
  "name": "CRM events webhook",
  "url": "https://crm.example.com/dialyra/events",
  "event_types": ["call.completed", "dtmf.received", "transfer.connected"],
  "timeout_seconds": 5
}
```

Optional:

```json
{
  "secret": "business-provided-hmac-secret"
}
```

If `secret` is omitted, backend generates one and returns it only once in the create response.

Response `201`:

```json
{
  "id": 1,
  "business_id": 1,
  "name": "CRM events webhook",
  "url": "https://crm.example.com/dialyra/events",
  "event_types": ["call.completed", "dtmf.received", "transfer.connected"],
  "status": "active",
  "timeout_seconds": 5,
  "secret_configured": true,
  "secret": "generated-secret-returned-once",
  "secret_returned_once": true,
  "created_at": "ISO_DATETIME",
  "updated_at": "ISO_DATETIME"
}
```

### `GET /api/v2/business-webhooks?business_id=1`

Lists webhook subscriptions and `supported_event_types`.

### `GET /api/v2/business-webhooks/:id`

Returns one subscription. The HMAC secret is not returned.

### `PUT /api/v2/business-webhooks/:id`

Updates subscription details.

Request:

```json
{
  "name": "CRM events webhook v2",
  "url": "https://crm.example.com/dialyra/events",
  "event_types": ["*"],
  "timeout_seconds": 10,
  "status": "active"
}
```

Rotate secret:

```json
{
  "rotate_secret": true
}
```

When a secret is created/rotated/set, it is returned once in the response.

### `DELETE /api/v2/business-webhooks/:id`

Disables the subscription by setting `status=disabled`.

### `POST /api/v2/business-webhooks/:id/test`

Queues one test delivery for this subscription.

Response `202`:

```json
{
  "status": "queued",
  "subscription_id": 1,
  "enqueued": 1,
  "event_type": "call.completed"
}
```

### `POST /api/v2/business-webhooks/:id/pause`
### `POST /api/v2/business-webhooks/:id/resume`

Pauses or resumes delivery creation for the subscription.

### `GET /api/v2/business-webhooks/:id/deliveries`

Lists delivery attempts. Requires `businesses.manage` because payloads may include call and billing data.

### `GET /api/v2/business-webhooks/deliveries/:delivery_id`

Returns one delivery. Stored HMAC signatures are not returned; receivers verify signatures from delivery headers.

### `POST /api/v2/business-webhooks/deliveries/:delivery_id/retry`

Resets a delivery to `pending` and wakes the worker.

### `GET /api/v2/business-webhooks/health`

Returns business webhook worker health.

Delivery headers:

```http
X-Dialyra-Event-Id: <event id>
X-Dialyra-Event-Type: <event type>
X-Dialyra-Timestamp: <UTC ISO timestamp>
X-Dialyra-Signature: sha256=<hex hmac>
```

Signature base string:

```text
<X-Dialyra-Timestamp>.<raw JSON request body>
```

Delivery body shape:

```json
{
  "event_id": "uuid",
  "event_type": "call.completed",
  "business_id": 1,
  "occurred_at": "ISO_DATETIME",
  "data": {
    "call_session_id": 170,
    "direction": "outbound",
    "status": "completed"
  }
}
```

The webhook URL may be `http://` or `https://`; private/internal destination addresses are blocked by default unless `BUSINESS_WEBHOOK_ALLOW_PRIVATE_URLS=true` is enabled for local/dev use.


## Flows (Draft CRUD)

These endpoints are JWT-protected and currently support draft lifecycle operations plus one-step create/publish.

### `POST /api/v2/flows`

Request:

```json
{
  "business_id": 11,
  "name": "Order Confirmation Flow",
  "description": "Draft flow for outbound confirmation calls"
}
```

Response `201`:

```json
{
  "id": 1,
  "business_id": 11,
  "name": "Order Confirmation Flow",
  "description": "Draft flow for outbound confirmation calls",
  "status": "draft",
  "version": 1,
  "start_node_id": null,
  "published_at": null,
  "created_by": 7,
  "created_at": "ISO_DATETIME",
  "updated_at": "ISO_DATETIME"
}
```

### `POST /api/v2/flows/create-and-publish`

Creates flow + nodes + edges and publishes in one request.

Validation highlights:
- `flow.business_id`, `flow.name`, non-empty `nodes`, and non-empty `edges` are required.
- Each node requires `node_key`, `node_type`, and `name`; `node_key` values must be unique.
- `start_node_key` is optional; when omitted, backend marks the first node as start.
- Edges can reference nodes by `source_node_key` / `target_node_key` (recommended) or by IDs.
- Duplicate edge conditions for the same source node are rejected.
- `play_audio` requires `config.audio_asset_id` and it must be a positive integer.
- `say_text` / `tts` require `config.text`; optional `config.audio_asset_id` must be a positive integer.
- When `audio_asset_id` is provided, it must exist under the same `business_id` and must not be deleted.
- `say_text` / `tts` text is materialized into an audio asset during publish when needed.

Request:

```json
{
  "flow": {
    "business_id": 11,
    "name": "Welcome SayText Flow",
    "description": "Create and publish in one request"
  },
  "start_node_key": "start",
  "nodes": [
    {
      "node_key": "start",
      "node_type": "say_text",
      "name": "Welcome",
      "config": {
        "text": "Hello {{customer_name}}, press 1 or 2",
        "provider": "google",
        "provider_variant": "gtts",
        "language": "en",
        "voice": "gtts:free",
        "variables": {
          "customer_name": "Tanjim"
        }
      }
    },
    {
      "node_key": "menu",
      "node_type": "gather_input",
      "name": "Menu",
      "config": {
        "max_digits": 1,
        "timeout_seconds": 5,
        "allowed_inputs": ["1", "2"]
      }
    },
    {
      "node_key": "end",
      "node_type": "hangup",
      "name": "End",
      "config": {
        "reason": "done"
      }
    }
  ],
  "edges": [
    {
      "source_node_key": "start",
      "target_node_key": "menu",
      "condition_type": "always",
      "priority": 1
    },
    {
      "source_node_key": "menu",
      "target_node_key": "end",
      "condition_type": "dtmf",
      "condition_value": "1",
      "priority": 1
    },
    {
      "source_node_key": "menu",
      "target_node_key": "end",
      "condition_type": "dtmf",
      "condition_value": "2",
      "priority": 2
    },
    {
      "source_node_key": "menu",
      "target_node_key": "end",
      "condition_type": "timeout",
      "priority": 3
    }
  ]
}
```

Response `201`:

```json
{
  "message": "Flow created and published successfully",
  "flow": {
    "id": 27,
    "business_id": 11,
    "name": "Welcome SayText Flow",
    "description": "Create and publish in one request",
    "status": "published",
    "version": 1,
    "start_node_id": 301,
    "published_at": "ISO_DATETIME",
    "created_by": 7,
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  },
  "flow_version": {
    "id": 99,
    "flow_id": 27,
    "business_id": 11,
    "version_number": 1,
    "published_by": 7,
    "published_at": "ISO_DATETIME",
    "is_active": true
  },
  "validation": {
    "valid": true
  },
  "counts": {
    "nodes": 3,
    "edges": 4
  }
}
```

Notes:
- `nodes` and `edges` arrays are dynamic size.
- Valid `node_type` values: `play_audio`, `say_text`, `tts`, `gather_input`, `condition`, `goto`, `webhook`, `transfer_call`, `hangup`, `wait`, `set_variable`, `record_control`.
- Valid edge `condition_type` values: `always`, `dtmf`, `timeout`, `invalid_input`, `condition_matched`, `condition_not_matched`, `variable_match`, `webhook_success`, `webhook_failed`, `transfer_connected`, `transfer_timeout`, `transfer_failed`, `retry_exceeded`, `error`.
- `transfer_call.target_type=queue` supports either existing `queue_id` or `auto_create_queue=true` with `queue_template.name`.
- Queue auto-create can use `queue_template.timeout_target_node_key`; backend resolves it to `timeout_target_node_id` after nodes are created.
- Queue hold audio supports either `queue_template.hold_music_audio_id` or `queue_template.hold_tts.text`, not both.
- `transfer_call.target_type=department` requires `department_id`; optional routing fields include `strategy`, `skill_requirements`, `skill_match_mode`, `department_*_key`, and `department_failure_route`.
- Webhook nodes in deferred mode capture post-call intents; Redis settings:
  - `REDIS_URL`
  - `POSTCALL_INTENT_TTL_SEC` (default `86400`)
  - `POSTCALL_FLUSH_LOCK_TTL_SEC` (default `60`)

### `GET /api/v2/flows`

Query params:
- `business_id` (optional)
- `status` (optional: `draft|published|archived|disabled`)

Response `200`:

```json
{
  "items": [
    {
      "id": 1,
      "business_id": 11,
      "name": "Order Confirmation Flow",
      "description": "Draft flow for outbound confirmation calls",
      "status": "draft",
      "version": 1,
      "start_node_id": null,
      "published_at": null,
      "created_by": 7,
      "created_at": "ISO_DATETIME",
      "updated_at": "ISO_DATETIME"
    }
  ]
}
```

### `GET /api/v2/flows/:id`

Response `200`: same flow object as above.

### `PUT /api/v2/flows/:id`

Request (all fields optional):

```json
{
  "name": "Order Confirmation Flow v2",
  "description": "Updated draft description",
  "status": "draft"
}
```

Response `200`: same flow object as above.

### `DELETE /api/v2/flows/:id`

Behavior:
- Soft lifecycle delete by setting status to `archived`.

Response `200`:

```json
{
  "message": "Flow archived",
  "id": 1
}
```

### `POST /api/v2/flows/:id/validate`

Response `200`:

```json
{
  "flow_id": 1,
  "status": "draft",
  "valid": true,
  "errors": [],
  "warnings": [],
  "stats": {
    "node_count": 4,
    "edge_count": 5
  }
}
```

Validation checks include:
- start node exists and is valid
- node config sanity for runtime-critical node types
- edge source/target integrity
- orphan node detection (warning)

### `POST /api/v2/flows/:id/publish`

Response `200`:

```json
{
  "message": "Flow published successfully",
  "flow": {
    "id": 1,
    "business_id": 11,
    "name": "Order Confirmation Flow",
    "description": "Draft flow for outbound confirmation calls",
    "status": "published",
    "version": 1,
    "start_node_id": 10,
    "published_at": "ISO_DATETIME",
    "created_by": 7,
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  },
  "flow_version": {
    "id": 3,
    "flow_id": 1,
    "business_id": 11,
    "version_number": 1,
    "published_by": 7,
    "published_at": "ISO_DATETIME",
    "is_active": true
  },
  "validation": {
    "flow_id": 1,
    "valid": true,
    "errors": [],
    "warnings": []
  }
}
```

Error `422` (example):

```json
{
  "error": "Flow validation failed",
  "validation": {
    "valid": false,
    "errors": [
      {
        "code": "START_NODE_MISSING",
        "message": "Start node is not configured"
      }
    ]
  }
}
```

### `POST /api/v2/flows/:id/duplicate`

Request (optional fields):

```json
{
  "name": "Order Confirmation Flow (Copy)",
  "description": "Draft copy for experiments"
}
```

Response `201`:

```json
{
  "message": "Flow duplicated successfully",
  "source_flow_id": 1,
  "flow": {
    "id": 2,
    "business_id": 11,
    "name": "Order Confirmation Flow (Copy)",
    "description": "Draft copy for experiments",
    "status": "draft",
    "version": 1,
    "start_node_id": 31,
    "published_at": null,
    "created_by": 7,
    "created_at": "ISO_DATETIME",
    "updated_at": "ISO_DATETIME"
  },
  "stats": {
    "copied_nodes": 6,
    "copied_edges": 7
  }
}
```

### `POST /api/v2/flows/:flow_id/nodes`

Request:

```json
{
  "node_key": "welcome_node",
  "node_type": "say_text",
  "name": "Welcome Prompt",
  "config": {
    "text": "Hello {{customer_name}}, welcome to Dialyra",
    "provider": "google",
    "provider_variant": "gemini-tts",
    "language": "en",
    "voice": "gemini-tts:Kore"
  },
  "position_x": 100,
  "position_y": 200,
  "is_start": true
}
```

Response `201`:

```json
{
  "id": 10,
  "flow_id": 1,
  "business_id": 11,
  "node_key": "welcome_node",
  "node_type": "say_text",
  "name": "Welcome Prompt",
  "config": {},
  "position_x": 100.0,
  "position_y": 200.0,
  "is_start": true,
  "created_at": "ISO_DATETIME",
  "updated_at": "ISO_DATETIME"
}
```

### `GET /api/v2/flows/:flow_id/nodes`

Response `200`:

```json
{
  "items": []
}
```

### `GET /api/v2/flow-nodes/:id`

Response `200`: same flow node object as above.

### `PUT /api/v2/flow-nodes/:id`

Request (all fields optional):

```json
{
  "name": "Welcome Prompt Updated",
  "config": {
    "text": "Hello {{customer_name}}, this is an updated message"
  },
  "is_start": true
}
```

Response `200`: same flow node object as above.

### `DELETE /api/v2/flow-nodes/:id`

Response `200`:

```json
{
  "message": "Flow node deleted",
  "id": 10
}
```

### `POST /api/v2/flows/:flow_id/edges`

Request:

```json
{
  "source_node_id": 10,
  "target_node_id": 11,
  "condition_type": "always",
  "condition_value": "",
  "priority": 100,
  "label": "default path"
}
```

Response `201`:

```json
{
  "id": 21,
  "flow_id": 1,
  "business_id": 11,
  "source_node_id": 10,
  "target_node_id": 11,
  "condition_type": "always",
  "condition_value": null,
  "priority": 100,
  "label": "default path",
  "created_at": "ISO_DATETIME",
  "updated_at": "ISO_DATETIME"
}
```

### `GET /api/v2/flows/:flow_id/edges`

Response `200`:

```json
{
  "items": []
}
```

### `PUT /api/v2/flow-edges/:id`

Request (all fields optional):

```json
{
  "condition_type": "dtmf",
  "condition_value": "1",
  "priority": 10,
  "label": "press 1 path"
}
```

Response `200`: same flow edge object as above.

### `DELETE /api/v2/flow-edges/:id`

Response `200`:

```json
{
  "message": "Flow edge deleted",
  "id": 21
}
```

Notes:
- Only `draft` flows are editable for node/edge operations.
- Valid `node_type` values:
  - `play_audio`, `say_text`, `tts`, `gather_input`, `condition`, `goto`, `webhook`, `transfer_call`, `hangup`, `wait`, `set_variable`, `record_control`
- Node config constraints:
  - `play_audio.config.audio_asset_id` is required and must be a positive integer.
  - `say_text.config.audio_asset_id` / `tts.config.audio_asset_id` are optional but must be positive integers when present.
  - `webhook.config` accepted keys:
    - `enabled`, `description`, `method`, `url`, `timeout_seconds`, `auth`, `headers`, `payload`, `input_map`, `success_criteria`
  - `webhook.config.auth.type` must be one of: `none`, `header`, `basic`.
  - `webhook.config.auth.type=header` requires non-empty `webhook.config.headers` object.
  - `webhook.config.auth.type=basic` requires non-empty `webhook.config.auth.username` and `webhook.config.auth.password`.
  - `webhook.config.url` must be absolute `http://` or `https://`.
  - `webhook.config.timeout_seconds` must be integer in range `1..30`.
  - For `POST|PUT|PATCH`, `webhook.config.payload` is required.
  - `webhook.config.input_map` and `webhook.config.success_criteria` are optional objects.
  - If `success_criteria.status_codes` is provided, it must be a non-empty array of valid HTTP status codes (`100..599`).
- Valid `condition_type` values:
  - `always`, `dtmf`, `timeout`, `invalid_input`, `variable_match`, `condition_matched`, `condition_not_matched`, `webhook_success`, `webhook_failed`, `transfer_connected`, `transfer_timeout`, `retry_exceeded`, `transfer_failed`, `error`

---

## Agent & Departments Admin APIs

Auth:
- JWT only
- permission middleware: `businesses.read`
- business-level role enforcement in service: `owner|admin|manager`
- exception: `POST /api/v2/agents/:id/availability` also allows self-update for `agent` role when mapped by `agent.user_id`

### Endpoint Index

- `POST /api/v2/agents`
- `GET /api/v2/agents`
- `GET /api/v2/agents/:agent_id`
- `PUT /api/v2/agents/:agent_id`
- `DELETE /api/v2/agents/:agent_id`
- `POST /api/v2/agents/:agent_id/availability`
- `POST /api/v2/agents/extensions`
- `GET /api/v2/agents/extensions`
- `GET /api/v2/agents/extensions/:id`
- `PUT /api/v2/agents/extensions/:id`
- `DELETE /api/v2/agents/extensions/:id`
- `POST /api/v2/agents/extensions/:id/bind`
- `PUT /api/v2/agents/:agent_id/extensions`
- `POST /api/v2/agents/calls/originate`
- `POST /api/v2/departments`
- `GET /api/v2/departments`
- `GET /api/v2/departments/:department_id`
- `PUT /api/v2/departments/:department_id`
- `DELETE /api/v2/departments/:department_id`
- `GET /api/v2/departments/:department_id/schedule`
- `POST /api/v2/departments/:department_id/schedule`
- `PUT /api/v2/departments/:department_id/schedule`
- `POST /api/v2/departments/:department_id/schedule/:mode`
- `GET /api/v2/departments/:department_id/agents`
- `POST /api/v2/departments/:department_id/agents`
- `PUT /api/v2/departments/:department_id/agents`
- `DELETE /api/v2/departments/:department_id/agents/:agent_id`
- `POST /api/v2/departments/:department_id/dry-run`
- `GET /api/v2/transfer-sessions`
- `GET /api/v2/transfer-sessions/:transfer_session_id`

### Agent Status Values

- `status`: `active|inactive|suspended|deleted`
- `availability_status`: `available|busy|offline|paused|after_call_work`

### Department Values

- `strategy`: `round_robin|least_busy|priority|random|skill_based` (default: `least_busy`)
- `status`: `active|inactive|archived`

### Transfer Node Key Contract (Phase 4)

- Preferred key: `config.target_type`
- Legacy key: `config.transfer_type` (deprecated)
- `FLOW_TRANSFER_CALL_ENABLED=true` enables the `transfer_call` node contract for all supported target types.
- Supported `target_type` values: `department`.
- Current compatibility:
  - if only `transfer_type` is provided, backend still accepts it temporarily
  - flow validation emits warning code: `LEGACY_TRANSFER_TYPE_DEPRECATED`
- Migration guidance:
  - replace `transfer_type` with `target_type`
  - keep same value semantics


### `POST /api/v2/agents`

Request:

```json
{
  "business_id": 2,
  "user_id": 21,
  "name": "Support Agent 1",
  "email": "agent1@example.com",
  "phone": "+8801XXXXXXXXX",
  "sip_extension": "1001",
  "status": "active",
  "availability_status": "offline",
  "max_concurrent_calls": 1,
  "current_active_calls": 0,
  "skills": {"language": ["bn", "en"]},
  "metadata": {"team": "support"}
}
```

Notes:
- `user_id` is required and must reference an active `users.id` with active `agent` membership in the same business.
- `sip_extension` is optional.
- If provided, it must be numeric (`2-16` digits).
- API derives SIP login username as `<business.sip_login_prefix><sip_extension>`.
- `sip_endpoint` is system-managed and uses tenant-safe SIP identity (typically prefixed username).

Response `201`: agent object (includes derived `sip_username` when `sip_extension` is set).

### `POST /api/v2/agents/extensions`

Creates/updates a local realtime SIP extension (`ps_auths`, `ps_aors`, `ps_endpoints`) for agent phones.

Request:

```json
{
  "business_id": 2,
  "user_id": 21,
  "extension": "1001",
  "password": "1001pass",
  "display_name": "Agent 1001",
  "transport": "transport-udp",
  "context": "dialyra-ivr",
  "allow": "ulaw,alaw",
  "dtmf_mode": "rfc4733",
  "max_contacts": 1,
  "qualify_frequency": 30,
  "remove_existing": true
}
```

Notes:
- Identity model:
  - `extension` = display/business extension (human-facing, e.g. `1005`)
  - `sip_username`/`sip_endpoint` = real Asterisk SIP identity (tenant-safe, e.g. `00011005`)
- `user_id` is optional; when provided, extension is bound to that user's agent profile in the same business scope.
- `extension` must be numeric (`2-16` digits).
- `username` is system-derived and should not be sent in payload.
- Derived `username` = `<business.sip_login_prefix><extension>`.
- `sip_endpoint` in realtime provisioning uses this same derived username identity.
- Returns `agent_id` as `agents.id` and `user_id` as `users.id` when bound.

Response `201`:

```json
{
  "business_id": 2,
  "extension": "1001",
  "username": "00011001",
  "sip_endpoint": "00011001",
  "transport": "transport-udp",
  "context": "dialyra-ivr",
  "allow": "ulaw,alaw",
  "dtmf_mode": "rfc4733",
  "ids": {
    "endpoint_id": "00011001",
    "aor_id": "00011001",
    "auth_id": "00011001-auth"
  }
}
```

### `GET /api/v2/agents/extensions?business_id=2`

Response `200`:

```json
{
  "items": [
    {
      "id": 31,
      "business_id": 2,
      "agent_id": 10,
      "user_id": 21,
      "agent_name": "Support Agent 1",
      "extension": "1001",
      "sip_username": "00011001",
      "sip_endpoint": "00011001",
      "is_primary": true,
      "is_active": true,
      "created_at": "2026-05-30T12:00:00",
      "updated_at": "2026-05-30T12:05:00"
    }
  ]
}
```

### `GET /api/v2/agents/extensions/:id`

Response `200`: single extension-binding object (same schema as list item).

### `PUT /api/v2/agents/:agent_id/extensions`

Request:

```json
{
  "extension": "1005",
  "transfer": true
}
```

Notes:
- `extension` must already exist in Asterisk SIP.
- `transfer` default is `false`.
- If the extension is owned by another agent and `transfer=false`, request fails with guidance.
- If `transfer=true`, extension is safely transferred and response includes warning + revert hint.

### `POST /api/v2/agents/extensions/:id/bind`

Bind an existing extension-assignment row to another agent user.

Request:

```json
{
  "user_id": 21,
  "is_primary": true
}
```

Notes:
- `user_id` must be active, must have active `agent` membership in same business.
- If `is_primary=true`, other extensions for the target agent are set non-primary via set-based update.

### `PUT /api/v2/agents/extensions/:id`

Request:

```json
{
  "is_active": false
}
```

Notes:
- Only `is_active` is supported in this endpoint.
- Setting `is_active=false` clears primary on this row and promotes another active extension for the same agent (if any).

### `DELETE /api/v2/agents/extensions/:id`

Notes:
- Soft-deletes the mapping row (`is_active=false`, `is_deleted=true`).
- Also deprovisions endpoint/auth/aor from Asterisk realtime tables.

### `POST /api/v2/agents/calls/originate`

Originate an agent-side call where source is a local SIP extension.

Supports:
- local extension to extension (`to_type=extension`)
- local extension to PSTN via trunk (`to_type=external_number`)

Validation:
- For `to_type=extension`, `from_extension` and `to` must be different.
- Dial behavior:
  - request uses display `from_extension`/`to`
  - runtime resolves mapped `sip_endpoint` from `agent_extensions`
  - originate executes against resolved `sip_endpoint` identities (not raw display extension)

Request (extension target):

```json
{
  "business_id": 2,
  "from_extension": "1003",
  "to": "1004",
  "to_type": "extension",
  "timeout_seconds": 30
}
```

Request (external number target):

```json
{
  "business_id": 2,
  "from_extension": "1004",
  "to": "09617179124",
  "to_type": "external_number",
  "sip_trunk_id": 1,
  "timeout_seconds": 30
}
```

Response `200`:

```json
{
  "status": "initiated",
  "business_id": 2,
  "from_extension": "1003",
  "to": "1004",
  "to_type": "extension",
  "call_log_uuid": "uuid",
  "call_session_id": 201,
  "action_id": "uuid",
  "sip_trunk_id": null,
  "sip_endpoint": null,
  "timeout_seconds": 30,
  "auth_type": "jwt",
  "response": "Response: Success"
}
```

### `GET /api/v2/agents?business_id=2`

Response `200`:

```json
{
  "items": []
}
```

### `GET /api/v2/agents/:agent_id`

Response `200`: agent object.

### `PUT /api/v2/agents/:agent_id`

Request: profile-only partial fields.  
Allowed fields: `name`, `phone`, `status`, `availability_status`, `max_concurrent_calls`, `skills`.  
Not allowed here: `business_id`, `agent_id`, `user_id`, `sip_extension`, `sip_endpoint`, `extensions`, `metadata`, `current_active_calls`.

Response `200`: updated agent object.

### `DELETE /api/v2/agents/:agent_id`

Response `200`:

```json
{
  "message": "Agent deleted",
  "id": 10
}
```

### `POST /api/v2/agents/:agent_id/availability`

Request:

```json
{
  "availability_status": "available"
}
```

Response `200`: updated agent object.

### `POST /api/v2/departments`

Creates a business-scoped department. Departments are independent from queues and flow nodes.

Request:

```json
{
  "business_id": 2,
  "name": "Billing Department",
  "description": "Billing and payment support",
  "status": "active",
  "strategy": "least_busy",
  "metadata": {
    "default_language": "bn"
  }
}
```

Response `201`:

```json
{
  "id": 3,
  "business_id": 2,
  "name": "Billing Department",
  "description": "Billing and payment support",
  "status": "active",
  "strategy": "least_busy",
  "metadata": {
    "default_language": "bn"
  },
  "created_at": "2026-06-04T10:00:00",
  "updated_at": "2026-06-04T10:00:00"
}
```

Validation notes:
- `name` required, max `128` chars, unique per business (`409` on duplicate).
- `strategy` defaults to `least_busy`.
- department stores no flow node ids.

### `GET /api/v2/departments?business_id=2`

Response `200`:

```json
{
  "items": [
    {
      "id": 3,
      "business_id": 2,
      "name": "Billing Department",
      "description": "Billing and payment support",
      "status": "active",
      "strategy": "least_busy",
      "metadata": {
        "default_language": "bn"
      },
      "created_at": "2026-06-04T10:00:00",
      "updated_at": "2026-06-04T10:00:00"
    }
  ]
}
```

### `GET /api/v2/departments/:department_id`

Response `200`:

```json
{
  "id": 3,
  "business_id": 2,
  "name": "Billing Department",
  "description": "Billing and payment support",
  "status": "active",
  "strategy": "least_busy",
  "availability_status": "open",
  "availability": {
    "availability_status": "open",
    "availability_mode": "always_open",
    "is_open_now": true,
    "schedule_reason": "schedule_not_configured",
    "timezone": "Asia/Dhaka",
    "is_configured": false
  },
  "metadata": {
    "default_language": "bn"
  },
  "created_at": "2026-06-04T10:00:00",
  "updated_at": "2026-06-04T10:00:00"
}
```

Availability notes:
- `availability_status` is resolved by the reusable department availability engine.
- `availability_status` allowed values: `open|closed`.
- `availability_mode` allowed values: `always_open|scheduled|closed`.
- The engine checks `availability_mode` first.
- If `availability_mode=always_open`, status is `open`.
- If `availability_mode=closed`, status is `closed`.
- If `availability_mode=scheduled`, status is determined using current date/time, configured timezone, weekly hours, and holiday overrides.
- If no `department_schedule_windows` row exists, the department resolves as `availability_mode=always_open` and `availability_status=open`.

### `PUT /api/v2/departments/:department_id`

Request: partial department fields.  
Allowed fields: `name`, `description`, `status`, `strategy`, `metadata`.

Response `200`: updated department object.

Validation notes:
- Duplicate name in same business returns `409`.
- `status`: `active|inactive|archived`.
- `strategy`: `round_robin|least_busy|priority|random|skill_based`.

### `DELETE /api/v2/departments/:department_id`

Response `200`:

```json
{
  "message": "Department deleted",
  "id": 3
}
```

Validation notes:
- Department with assigned agents cannot be deleted (`409`).
- Department schedule row is removed with the department.

### `GET /api/v2/departments/:department_id/agents`

Lists agent mappings for a department.

Response `200`:

```json
{
  "items": [
    {
      "id": 42,
      "business_id": 2,
      "department_id": 3,
      "agent_id": 10,
      "priority": 1,
      "is_active": true,
      "last_assigned_at": null,
      "created_at": "2026-06-04T10:05:00",
      "updated_at": "2026-06-04T10:05:00"
    }
  ]
}
```

Notes:
- Results are ordered by `priority ASC`, then mapping `id ASC`.
- This endpoint returns department membership/mapping data only; it does not modify agent profile fields.

### `POST /api/v2/departments/:department_id/agents`

Adds or updates an agent mapping for a department.

Request:

```json
{
  "agent_id": 10,
  "priority": 1,
  "is_active": true
}
```

Response `200`:

```json
{
  "id": 42,
  "business_id": 2,
  "department_id": 3,
  "agent_id": 10,
  "priority": 1,
  "is_active": true,
  "last_assigned_at": null,
  "created_at": "2026-06-04T10:05:00",
  "updated_at": "2026-06-04T10:05:00"
}
```

Validation notes:
- `priority` must be integer `>= 1`.
- `is_active` must be boolean.
- archived department cannot be modified.
- agent must belong to same business and be eligible (`status` not `deleted|suspended`).

### `PUT /api/v2/departments/:department_id/agents`

Updates mapping fields only. This does not modify the agent profile.

Request:

```json
{
  "agent_id": 10,
  "priority": 2,
  "is_active": false
}
```

Response `200`: department-agent mapping object.

### `DELETE /api/v2/departments/:department_id/agents/:agent_id`

Response `200`:

```json
{
  "message": "Agent removed from department",
  "department_id": 3,
  "department_name": "Billing Department",
  "agent_id": 10
}
```

### `GET /api/v2/departments/:department_id/schedule`

Returns the single schedule row for a department. If no row exists, the API returns the default open policy.

Response `200` when no configured row exists:

```json
{
  "id": null,
  "business_id": 2,
  "department_id": 3,
  "availability_mode": "always_open",
  "availability_status": "open",
  "timezone": "Asia/Dhaka",
  "weekly_hours": {},
  "holiday_overrides": [],
  "is_active": true,
  "metadata": null,
  "is_configured": false,
  "is_open_now": true,
  "schedule_reason": "schedule_not_configured",
  "created_at": null,
  "updated_at": null
}
```

### `POST /api/v2/departments/:department_id/schedule`

Creates the single schedule row for a department.

Request:

```json
{
  "availability_mode": "scheduled",
  "timezone": "Asia/Dhaka",
  "weekly_hours": {
    "mon": [{"open": "09:00", "close": "18:00"}],
    "tue": [{"open": "09:00", "close": "18:00"}],
    "wed": [{"open": "09:00", "close": "18:00"}],
    "thu": [{"open": "09:00", "close": "18:00"}],
    "fri": [{"open": "09:00", "close": "17:00"}],
    "sat": [],
    "sun": []
  },
  "holiday_overrides": [
    {"date": "2026-06-16", "mode": "closed"},
    {
      "date": "2026-06-17",
      "mode": "custom",
      "windows": [{"open": "10:00", "close": "14:00"}]
    }
  ],
  "is_active": true,
  "metadata": {
    "note": "Business hours schedule"
  }
}
```

Response `201`:

```json
{
  "id": 9,
  "business_id": 2,
  "department_id": 3,
  "availability_mode": "scheduled",
  "availability_status": "open",
  "timezone": "Asia/Dhaka",
  "weekly_hours": {
    "mon": [{"open": "09:00", "close": "18:00"}],
    "tue": [{"open": "09:00", "close": "18:00"}],
    "wed": [{"open": "09:00", "close": "18:00"}],
    "thu": [{"open": "09:00", "close": "18:00"}],
    "fri": [{"open": "09:00", "close": "17:00"}],
    "sat": [],
    "sun": []
  },
  "holiday_overrides": [
    {"date": "2026-06-16", "mode": "closed"},
    {
      "date": "2026-06-17",
      "mode": "custom",
      "windows": [{"open": "10:00", "close": "14:00"}]
    }
  ],
  "is_active": true,
  "metadata": {
    "note": "Business hours schedule"
  },
  "is_configured": true,
  "is_open_now": true,
  "schedule_reason": "weekly_open",
  "created_at": "2026-06-04T10:10:00",
  "updated_at": "2026-06-04T10:10:00"
}
```

Validation notes:
- Only one schedule row is allowed per department (`409` if already exists).
- `availability_mode`: `always_open|scheduled|closed`.
- `timezone` must be a valid IANA timezone when `availability_mode=scheduled`.
- `weekly_hours` uses day keys `mon|tue|wed|thu|fri|sat|sun`.
- time windows use `HH:MM` 24-hour format and `open` must be earlier than `close`.
- `holiday_overrides.mode`: `closed|custom`.
- Schedule date fields are normalized to canonical `YYYY-MM-DD`; for example `2026-06-4` is stored and evaluated as `2026-06-04`.

### `PUT /api/v2/departments/:department_id/schedule`

Updates the existing schedule row.

Request: partial or full schedule fields.

Response `200`: updated schedule object.

Validation notes:
- Returns `404` if the schedule row does not exist.
- Use `POST` first to create the department schedule.

### `POST /api/v2/departments/:department_id/schedule/:mode`

### `GET /api/v2/departments/:department_id/live`

Returns a department operational readiness snapshot.

Response `200`:

```json
{
  "department_id": 3,
  "department_name": "Service Department",
  "business_id": 1,
  "description": "Service routing group",
  "strategy": "least_busy",
  "status": "active",
  "availability": {
    "availability_mode": "scheduled",
    "availability_status": "open",
    "is_configured": true,
    "is_open_now": true,
    "schedule_reason": "weekly_window",
    "timezone": "Asia/Dhaka"
  },
  "total_agents": 2,
  "available_agents": 1,
  "at_capacity_agents": 0,
  "busy_agents": 1,
  "paused_agents": 0,
  "offline_agents": 0,
  "ineligible_agents": 0,
  "agents_without_active_extension": 0,
  "active_transfer_sessions": 1,
  "routing_readiness": "ready",
  "ready_for_transfer": true,
  "reason_code": null,
  "primary_readiness_reason": null,
  "last_assigned_at": "2026-06-04T15:30:00",
  "recent_selected_agents_5m": 1,
  "stale_sessions_detected": false,
  "stale_sessions_count": 0,
  "event_lag_ms": 1200,
  "last_consistency_warning_at": null
}
```

Readiness values include:

- `ready`
- `inactive_department`
- `outside_schedule`
- `no_mapped_agents`
- `no_capacity`
- `all_paused`
- `no_active_extension`
- `no_available_agent`

### `GET /api/v2/transfer-sessions?business_id=2&status=connected&page=1&page_size=20`

Optional filters:

- `queue_id`: return queue transfer sessions for one queue.
- `department_id`: return department transfer sessions for one department.
- `agent_id`: return transfer sessions selected for one agent.
- `call_session_id`: return transfer sessions for one call session.

Query params:
- `business_id` optional (defaults to first manageable business)
- `status` optional (`initiated|queued|ringing|connected|failed|timeout|abandoned|completed`)
- `queue_id` optional
- `agent_id` optional
- `call_session_id` optional
- `date_from` optional ISO datetime (filters `created_at >= date_from`)
- `date_to` optional ISO datetime (filters `created_at <= date_to`)
- `sort_by` optional (`id|created_at|started_at|ringing_at|connected_at|ended_at|status`), default `created_at`
- `sort_order` optional (`asc|desc`), default `desc`
- `page` optional, default `1`
- `page_size` optional, default `20`, max `200`

Response `200`:

```json
{
  "items": [
    {
      "id": 12,
      "business_id": 2,
      "call_session_id": 101,
      "flow_runtime_session_id": 44,
      "source_node_id": 88,
      "transfer_type": "queue",
      "queue_id": 5,
      "agent_id": 10,
      "external_number": null,
      "status": "connected",
      "started_at": "2026-05-31T12:39:58",
      "ringing_at": "2026-05-31T12:39:59",
      "connected_at": "2026-05-31T12:40:02",
      "ended_at": null,
      "failure_reason": null,
      "metadata": {
        "trace_id": "runtime-trace-123"
      },
      "created_at": "2026-05-31T12:39:58",
      "updated_at": "2026-05-31T12:40:02"
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 20,
    "total": 1,
    "pages": 1,
    "has_next": false,
    "has_prev": false
  }
}
```

### `GET /api/v2/transfer-sessions/:transfer_session_id`

Response `200`:

```json
{
  "id": 12,
  "business_id": 2,
  "call_session_id": 101,
  "flow_runtime_session_id": 44,
  "source_node_id": 88,
  "transfer_type": "queue",
  "queue_id": 5,
  "agent_id": 10,
  "external_number": null,
  "status": "connected",
  "started_at": "2026-05-31T12:39:58",
  "ringing_at": "2026-05-31T12:39:59",
  "connected_at": "2026-05-31T12:40:02",
  "ended_at": null,
  "failure_reason": null,
  "metadata": {
    "trace_id": "runtime-trace-123"
  },
  "detail": {
    "selection_context": {
      "queue_id": 5,
      "strategy_used": "least_busy",
      "selected_agent_id": 10,
      "selected_extension": "1005",
      "selected_sip_endpoint": "00011005",
      "selected_sip_username": "00011005",
      "reason_code": null,
      "decision_trace": {}
    },
    "bridge": {
      "action": "dial",
      "technology": "pjsip",
      "dial_string": "PJSIP/00011005",
      "endpoint": "00011005",
      "timeout_seconds": 20,
      "hold_music_audio_id": 1,
      "channel_vars": {
        "DIALYRA_TRANSFER_SESSION_ID": "12"
      },
      "cdr_userfield_append": "transfer_session_id=12"
    },
    "outcome_diagnostics": {
      "event_type": "transfer_connected",
      "dial_status": "ANSWER",
      "normalized_reason": "answered",
      "hangup_cause": "16",
      "dialed_peer_name": "PJSIP/00011005-0000001a",
      "dialed_peer_number": "00011005",
      "answered_time_ms": "10500",
      "dialed_time_ms": "10900",
      "applied_status": "connected",
      "failure_reason": null,
      "applied_at": "2026-05-31T12:40:02"
    },
    "outcome_guard": {
      "last_outcome_apply_state": "applied",
      "last_outcome_event_type": "transfer_connected",
      "last_outcome_applied_at": "2026-05-31T12:40:02",
      "duplicate_event_count": 0,
      "last_duplicate_event_key": null,
      "last_rejected_outcome": null
    },
    "timeout_resolution": {
      "dial_timeout_seconds": 20,
      "dial_timeout_source": "transfer_call.timeout_seconds",
      "dial_timeout_context": {
        "node_timeout": 20,
        "queue_timeout": 60,
        "precedence": "node_then_queue"
      }
    }
  },
  "created_at": "2026-05-31T12:39:58",
  "updated_at": "2026-05-31T12:40:02"
}
```

## Calls (Public + Runtime)

### `POST /api/v2/call`

Request:

```json
{
  "phone": "string",
  "sip_trunk_id": 2,
  "flow_id": 10,
  "campaign_flow_id": 12,
  "campaign_id": 501
}
```

Flow selection priority:
1. `flow_id` (explicit)
2. `campaign_flow_id` (campaign-linked flow provided by caller integration)
3. `business.settings_json.default_flow_id`
4. latest active published flow for business
5. if none: warning `NO_FLOW_AVAILABLE`

Response `200`:

```json
{
  "status": "initiated",
  "phone": "string",
  "response": "string"
}
```

Error `502` example:

```json
{
  "error": "AMI host resolution failed",
  "ami_host": "string",
  "details": "string"
}
```

### `POST /api/v3/runtime/calls/originate`

Access token scope required: `calls:originate`

Request:

```json
{
  "phone": "string",
  "sip_trunk_id": 1,
  "flow_id": 42,
  "webhook_variables": {
    "customer_name": "Tanjim",
    "order_id": "ORD-1001",
    "price": "1399"
  }
}
```

`webhook_variables` rules:
- optional object
- max keys: `100`
- max serialized size: `16384` bytes
- key format: `a-zA-Z0-9_` only, max key length `64`
- values can be scalar, object, or list (JSON-serializable)
- reserved system keys are blocked (for example `call_action_id`, `call_session_id`, `business_id`, `flow_id`, `flow_version_id`, `sip_trunk_id`, `dialed_number`, `dtmf_value`, `retry_count`)
- `template_variables` is not accepted

Response `200`:

```json
{
  "status": "initiated",
  "phone": "string",
  "business_id": 1,
  "call_log_uuid": "string",
  "call_session_id": 123,
  "action_id": "string",
  "sip_trunk_id": 2,
  "sip_endpoint": "string",
  "selected_by": "auto_business",
  "selected_flow_id": 10,
  "selected_flow_version_id": 22,
  "flow_selected_by": "latest_published_flow",
  "active_calls_before": 2,
  "max_concurrent_calls": 50,
  "business_active_calls_before": 4,
  "business_max_concurrent_calls": 10,
  "system_active_calls_before": 25,
  "system_max_concurrent_calls": 100,
  "auth_type": "access_token",
  "response": "string"
}
```

### `POST /api/v2/runtime/calls/:call_id/hangup`

Access token scope required: `calls:hangup`

Request (optional body):

```json
{
  "reason": "manual_stop",
  "channel": "PJSIP/8801631596698-00000012"
}
```

Response `200` (accepted):

```json
{
  "status": "hangup_requested",
  "call_session_id": 123,
  "action_id": "0f4d9f84-3152-4a2f-9a4d-b53d24d9f7ce",
  "channel": "PJSIP/8801631596698-00000012",
  "response": "Response: Success"
}
```

Response `200` (idempotent already-ended):

```json
{
  "status": "already_ended",
  "call_session_id": 123,
  "ended_at": "2026-05-11T20:45:11.221000",
  "message": "Call already ended"
}
```

Safety notes:
- channel resolution order:
  1. request body `channel`
  2. `call_sessions.channel`
  3. live channel lookup by `call_sessions.uniqueid`
  4. early-call fallback by recent dialed number correlation
- number-fallback safety:
  - if multiple live channels match the same number, request is rejected with `409` and code `AMBIGUOUS_CHANNEL_MATCH`

### `POST /api/v2/runtime/calls/:call_id/retry`

Access token scope required: `calls:originate`

Behavior:
- only ended calls are retry-eligible
- retry-eligible statuses: `failed`, `busy`, `no_answer`, `cancelled`
- max retry attempts controlled by `CALL_RETRY_MAX_ATTEMPTS` (default `3`)
- creates a new call session and call log (does not mutate old one into active)

Response `200`:

```json
{
  "status": "retry_initiated",
  "source_call_session_id": 123,
  "retry_count": 1,
  "call_session_id": 124,
  "call_log_uuid": "string",
  "action_id": "string",
  "sip_trunk_id": 2,
  "sip_endpoint": "string",
  "selected_by": "requested",
  "response": "string"
}
```

### `GET /api/v2/runtime/calls/events`

Access token scope required: `calls:read`

Query params (optional):
- `status`: `pending|processed|failed`
- `page`: integer (default `1`)
- `page_size`: integer (default `20`, max `200`)

Response `200`:

```json
{
  "items": [
    {
      "id": 501,
      "business_id": 2,
      "call_log_id": 300,
      "call_session_id": 123,
      "event_name": "Hangup",
      "event_fingerprint": "string",
      "action_id": "string|null",
      "uniqueid": "string|null",
      "linkedid": "string|null",
      "processing_status": "processed",
      "process_attempts": 1,
      "last_error": null,
      "processed_at": "ISO_DATETIME|null",
      "created_at": "ISO_DATETIME",
      "updated_at": "ISO_DATETIME"
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 20,
    "total": 1
  }
}
```

### `GET /api/v2/runtime/calls/webhook-jobs`

Access token scope required: `calls:read`

Query params (optional):
- `call_session_id`: string
- `action_id`: string
- `status`: `pending|processing|retry_scheduled|completed|failed`
- `page`: integer (default `1`)
- `page_size`: integer (default `20`, max `200`)

Response `200`:

```json
{
  "items": [
    {
      "id": 1,
      "business_id": 2,
      "call_action_id": "string",
      "call_session_id": "151",
      "call_log_uuid": "string|null",
      "node_id": 77,
      "node_key": "confirm_webhook",
      "sequence_no": 1,
      "method": "POST",
      "url": "https://example.com/webhook",
      "status": "completed",
      "attempt_count": 1,
      "next_retry_at": null,
      "last_error": null,
      "last_response_code": 200,
      "last_attempt_at": "ISO_DATETIME|null",
      "completed_at": "ISO_DATETIME|null",
      "created_at": "ISO_DATETIME",
      "updated_at": "ISO_DATETIME"
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 20,
    "total": 1
  }
}
```

### `GET /api/v2/runtime/calls/:call_id/webhook-jobs`

Access token scope required: `calls:read`

Behavior:
- same response shape as `/runtime/calls/webhook-jobs`
- `:call_id` is matched to `call_session_id`

### `GET /api/v2/runtime/calls/webhook-worker/health`

Access token scope required: `calls:read`

Response `200`:

```json
{
  "enabled": true,
  "worker_alive": true,
  "wake_queue_depth": 0,
  "poll_sec": 1.5,
  "batch_size": 20,
  "max_attempts": 4,
  "retry_schedule_sec": "10,30,120"
}
```

### `GET /api/v2/runtime/calls/webhook-jobs/:job_id`

Access token scope required: `calls:read`

Response `200`:
- same job object shape used in `/runtime/calls/webhook-jobs` list.

### `POST /api/v2/runtime/calls/webhook-jobs/:job_id/retry`

Access token scope required: `calls:read`

Behavior:
- sets job status to `pending`
- clears previous response/error fields
- wakes webhook worker for immediate pickup

Response `200`:

```json
{
  "status": "retry_queued",
  "job": {
    "id": 1,
    "status": "pending",
    "attempt_count": 2
  }
}
```

### `POST /api/v2/runtime/calls/webhook-jobs/retry`

Access token scope required: `calls:read`

Request (all optional):

```json
{
  "status": "failed",
  "call_session_id": "151",
  "action_id": "uuid-string",
  "limit": 100
}
```

### `GET /api/v2/runtime/calls/webhook-jobs/summary`

Access token scope required: `calls:read`

Query params (optional):
- `action_id`
- `call_session_id`

Response `200`:

```json
{
  "business_id": 2,
  "action_id": "uuid-string-or-null",
  "call_session_id": "151-or-null",
  "total": 4,
  "counts": {
    "pending": 1,
    "processing": 0,
    "retry_scheduled": 1,
    "completed": 1,
    "failed": 1
  }
}
```

Behavior:
- bulk resets matching jobs to `pending`
- clears retry scheduling/error metadata
- wakes webhook worker once for immediate pickup
- `status` allowed values: `failed`, `retry_scheduled`
- `limit` max: `500`

Response `200`:

```json
{
  "status": "bulk_retry_queued",
  "retried_count": 2,
  "items": [
    { "id": 11, "status": "pending" },
    { "id": 12, "status": "pending" }
  ]
}
```


Capacity warning responses (`409`):
- `NO_TRUNK_CAPACITY`
- `NO_BUSINESS_CAPACITY`
- `NO_SYSTEM_CAPACITY`
- `NO_FLOW_AVAILABLE`

Observability + audit:
- audit events are written for originate/hangup/retry lifecycle:
  - `call.originate.validation_failed`
  - `call.originate.failed`
  - `call.originate.accepted`
  - `call.originate.ami_error`
  - `call.hangup.failed`
  - `call.hangup.accepted`
  - `call.hangup.ami_error`
  - `call.retry.failed`
  - `call.retry.accepted`
  - `call.retry.ami_error`
- metadata includes IDs (`call_session_id`, `call_log_uuid`, `action_id`), routing (`sip_trunk_id`, `selected_by`), and capacity snapshot fields where available.

### `GET /api/v2/calls`

Response `200`:

```json
{
  "items": [],
  "business_id": 1,
  "auth_type": "access_token"
}
```

### `GET /api/v2/calls/:call_id`

Response `200`:

```json
{
  "id": "string",
  "business_id": 1,
  "auth_type": "access_token",
  "status": "not_implemented"
}
```

### `GET /api/v2/calls/history/:call_id`

JWT auth (`stuff` or `superuser`).

Path behavior (legacy):
- if no selector query is provided, `:call_id` is resolved as `call_logs.id` (previous behavior).

Selector query mode (optional):
- `action_id`
- `call_session_id`
- `call_id` (query alias for call log id)

When selector query is provided on `/api/v2/calls/history`, precedence is:
1. `action_id`
2. `call_session_id`
3. `call_id` (query)

Equivalent examples:
- `/api/v2/calls/history/123` (legacy by path id)
- `/api/v2/calls/history?action_id=152824b0-013a-43f1-8d27-65cab1499ac6`
- `/api/v2/calls/history?call_session_id=162`
- `/api/v2/calls/history?call_id=123`

Response `200`:
- same call history payload as before, plus:
  - `resolved_by`: `action_id | call_session_id | call_log_id`
  - `resolved_input`: the value used for final lookup

### `GET /api/v2/calls/:call_id/events`

JWT auth (`stuff` or `superuser`).

Purpose:
- returns ordered events for one call
- unified-first read from `call_events`
- fallback to `timeline.actions` for older records

Optional query params:
- `action_id`
- `call_session_id`
- `page` (default `1`)
- `page_size` (default `50`, max `500`)

Selector resolution behavior:
- same as call history resolver
- `action_id` and `call_session_id` can override `:call_id` resolution

Response `200` (unified source):

```json
{
  "call_id": 151,
  "call_session_id": 149,
  "source": "call_events",
  "items": [
    {
      "id": 9012,
      "event_type": "dtmf.received",
      "event_source": "dtmf",
      "node_id": 275,
      "edge_id": null,
      "audio_asset_id": null,
      "dtmf_digits": "1",
      "timestamp": "2026-05-17T14:06:20.635676",
      "payload": {
        "digits": "1",
        "trace_id": "49076450-46c1-4180-9786-c4d7492e127d"
      },
      "processing_status": "processed"
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 50,
    "total": 1
  }
}
```

Response `200` (fallback source):

```json
{
  "call_id": 151,
  "call_session_id": 149,
  "source": "timeline_fallback",
  "items": [],
  "pagination": {
    "page": 1,
    "page_size": 50,
    "total": 0
  }
}
```

### `GET /api/v2/calls/dtmf`

JWT auth (`stuff` or `superuser`).

Purpose:
- returns DTMF-focused event timeline for one call/session
- selector-based lookup for operational debugging and IVR analytics

Query params:
- `action_id` (optional)
- `call_session_id` (optional)
- `call_id` (optional, call log id)
- `page` (optional, default `1`)
- `page_size` (optional, default `50`, max `200`)

Selector requirement:
- at least one of `action_id | call_session_id | call_id` is required

Selector resolution behavior:
1. `action_id`
2. `call_session_id`
3. `call_id`

Response `200`:

```json
{
  "call_session_id": 149,
  "items": [
    {
      "id": 10931,
      "event_type": "dtmf.wait_started",
      "call_session_id": 149,
      "node_id": 275,
      "timestamp": "2026-05-17T14:06:20.213973",
      "payload": {
        "timeout_seconds": 5,
        "max_digits": 1,
        "allowed_inputs": ["1", "2"]
      }
    },
    {
      "id": 10932,
      "event_type": "dtmf.received",
      "call_session_id": 149,
      "node_id": 275,
      "timestamp": "2026-05-17T14:06:20.635676",
      "payload": {
        "digits": "1",
        "trace_id": "49076450-46c1-4180-9786-c4d7492e127d"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 50,
    "total": 2
  }
}
```

Canonical aliases (same behavior, no breaking changes):
- `GET /api/v2/call-sessions/:call_session_id`
  - equivalent to: `GET /api/v2/calls/history?call_session_id=:call_session_id`
- `GET /api/v2/call-sessions/:call_session_id/events`
  - equivalent to: `GET /api/v2/calls/:call_id/events?call_session_id=:call_session_id`
- `GET /api/v2/call-sessions/:call_session_id/dtmf`
  - equivalent to: `GET /api/v2/calls/dtmf?call_session_id=:call_session_id`

### `GET /api/v2/calls/audit`

JWT auth (`stuff` or `superuser`).

Query params:
- `call_session_id` required
- `action` optional (for example `call.retry.failed`)
- `date_from` optional ISO datetime
- `date_to` optional ISO datetime
- `page` optional (default `1`)
- `page_size` optional (default `20`, max `200`)

Response `200`:

```json
{
  "items": [
    {
      "id": 1901,
      "business_id": 11,
      "actor_user_id": 5,
      "action": "call.originate.accepted",
      "metadata": {
        "call_session_id": 333,
        "call_log_uuid": "d66a...",
        "action_id": "ami-action-id",
        "sip_trunk_id": 2
      },
      "created_at": "2026-05-11T12:31:11.002000"
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 20,
    "total": 1
  }
}
```

---

## Audio Assets

### `POST api/v2/audio-assets/upload`

```json
{
  "business_id":"1",
  "file": filename.wav,
  "name": "final voice",
  "category": "hold_music"
}
```

### `GET api/v2/audio-assets/:id/stream`
direct audio file response

### `PUT api/v2/audio-assets/:id

```json
{
    "name": "call hold",
    "category": "ivr_prompt"
}
```

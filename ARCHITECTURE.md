# Architecture -- PulseDesk

## Overview
PulseDesk is a hybrid project with:
- Backend API: Laravel 11, Sanctum auth, MySQL data store
- Frontend SPA: React 19, Vite, Tailwind
- Local agent orchestration: Python agents using file-backed channel logs and OpenClaw/Slack config
- CI automation: GitHub Actions plus local `agents/ci/ci_runner.py`

## Multi-tenancy
- Every `Ticket`, `User`, `Tag`, `SlaPolicy`, and `Notification` is scoped by `organization_id`
- Tenant selection is derived from `auth()->user()->organization_id`
- Global query scopes enforce tenant isolation across backend models
- Customers and agents only see data from their own organization

## Data model
- Organization
- User (belongs_to Organization; role = admin | agent | customer)
- Ticket (subject, description, status, priority, requester_id, assignee_id, organization_id, timestamps)
- TicketMessage (ticket_id, user_id, body, type=public|internal, timestamps)
- ActivityLog (ticket_id, user_id, action, description, metadata, created_at)
- SlaPolicy (organization_id, name, priority, response_time_minutes, resolution_time_minutes, is_active)
- Notification (user_id, ticket_id, type, title, body, is_read, created_at)

## API routes
| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| POST | /api/auth/register | public | Register a new user and organization |
| POST | /api/auth/login | public | Login and return Sanctum token |
| GET | /api/auth/me | auth | Current user info |
| GET | /api/tickets | auth | Tenant-scoped list, filterable |
| POST | /api/tickets | auth | Create ticket |
| GET | /api/tickets/{id} | auth | Ticket detail |
| PUT | /api/tickets/{id} | agent/admin | Update status, priority, assignee |
| GET | /api/tickets/{id}/messages | auth | Public + internal note list |
| POST | /api/tickets/{id}/messages | agent/admin | Add reply or internal note |
| GET | /api/tickets/{id}/activity | auth | Ticket audit timeline |
| GET | /api/dashboard/metrics | auth | Analytics metrics |
| GET | /api/notifications | auth | User notifications |
| PUT | /api/notifications/{id}/read | auth | Mark one read |

## Agent workflow
- `Hermes` is the orchestrator that splits big tasks and posts them to `#agent-coder`
- `OpenClaw` is the worker that reads `#agent-coder`, processes tasks, and writes results to `#agent-log`
- `ci-runner` watches `#ci-cd`, executes tests, and calls `.github/create_pr.py`
- Slack config is stored in `agents/openclaw/openclaw.json` with env interpolation for `SLACK_BOT_TOKEN`

## Key decisions
- Use env-based secrets for Slack tokens and GitHub PATs
- Keep OpenClaw config redacted in repo; local `.env` stores secret tokens
- Use file logs under `agents/channels/` for deterministic agent communication
- Keep GitHub PR helper separate from agent logic to allow manual or CI execution

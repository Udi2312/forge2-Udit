# Sprint 4 — Conversations, Activity Log & Dashboard

**Project:** PulseDesk — Multi-tenant Customer Support SaaS
**Stack:** Laravel 11 · MySQL · Sanctum · React 19 + Vite + Tailwind · GitHub Actions CI
**Prerequisites:** Sprints 1-3 complete (project foundation, tenancy/auth, ticket CRUD)

---

## Goal

Add threaded conversations and internal notes to tickets, build an activity log for audit trails, and deliver a metrics dashboard with real-time ticket stats. After this sprint, agents have everything they need to work a ticket end-to-end.

---

## User Stories

1. **As an agent**, I want to reply to a ticket with a public message so the customer sees my response.

2. **As an agent**, I want to add internal notes that are invisible to the customer so I can collaborate with my team.

3. **As an agent**, I want to see the full conversation thread (public replies + internal notes) in chronological order so I have full context.

4. **As an agent**, I want to see an activity log for each ticket (status changes, assignments, edits) so I can audit what happened.

5. **As a manager**, I want a dashboard showing key metrics (open tickets, resolved tickets, avg resolution time, tickets by priority) so I can monitor team performance.

6. **As a manager**, I want to filter dashboard metrics by date range and agent so I can drill into performance.

7. **As an agent**, I want real-time updates on the dashboard so I don't have to refresh manually.

---

## Technical Tasks

### Backend (Laravel 11)

- [ ] Create `ticket_messages` table migration:
  - `id`, `ticket_id`, `user_id`, `body` (text), `type` (enum: public, internal), `created_at`, `updated_at`
  - Indexes on `ticket_id`, `type`
- [ ] Create `TicketMessage` model with relationships (belongsTo Ticket, belongsTo User)
- [ ] Create `activity_logs` table migration:
  - `id`, `ticket_id`, `user_id`, `action` (string), `description` (text, nullable), `metadata` (json, nullable), `created_at`
  - Indexes on `ticket_id`, `created_at`
- [ ] Create `ActivityLog` model
- [ ] API Routes (`routes/api.php`, all under `auth:sanctum` + tenant scope):
  - `GET /api/tickets/{ticket}/messages` — list messages (public + internal based on role)
  - `POST /api/tickets/{ticket}/messages` — create message (public or internal)
  - `GET /api/tickets/{ticket}/activity-log` — list activity entries
  - `GET /api/dashboard/metrics` — aggregate metrics (accepts `date_from`, `date_to`, `agent_id` query params)
- [ ] Controllers: `TicketMessageController`, `ActivityLogController`, `DashboardController`
- [ ] Form requests for message creation (validate body, type)
- [ ] Activity log automatically records: ticket created, status changed, assigned, priority changed, message posted
- [ ] Dashboard metrics endpoint returns:
  - Total tickets (open, pending, resolved, closed)
  - Tickets created in date range
  - Tickets resolved in date range
  - Average resolution time (hours)
  - Tickets by priority breakdown
  - Tickets by tag breakdown
  - Per-agent ticket counts
- [ ] Policy: only agents/admins can see internal notes; customers can only see public messages
- [ ] Tests for all endpoints

### Frontend (React 19 + Vite + Tailwind)

- [ ] Ticket detail view with conversation thread:
  - Chronological message list (public replies + internal notes visually distinct)
  - Reply composer with toggle: "Public Reply" / "Internal Note"
  - Markdown support for message body
  - Attachments display (if Sprint 3 included attachments)
- [ ] Activity log panel (sidebar or tab in ticket detail):
  - Timeline of actions with timestamps and user avatars
  - Color-coded by action type
- [ ] Dashboard page:
  - Metric cards: Open, Pending, Resolved, Avg Resolution Time
  - Bar chart: Tickets by Priority
  - Bar chart: Tickets by Tag
  - Table: Per-agent performance (tickets resolved, avg time)
  - Date range picker
  - Agent filter dropdown
- [ ] Real-time updates via polling (every 30s) or WebSocket broadcast (optional)
- [ ] Loading states and empty states for all views
- [ ] Responsive layout (works on tablet + desktop)

### CI/CD

- [ ] Ensure existing CI pipeline runs new tests
- [ ] Add frontend build check for new dashboard components

---

## Expected Deliverables

| Deliverable | Location |
|---|---|
| Ticket messages migration & model | `backend/database/migrations/`, `backend/app/Models/` |
| Activity log migration & model | `backend/database/migrations/`, `backend/app/Models/` |
| Message & ActivityLog controllers | `backend/app/Http/Controllers/` |
| Dashboard controller with metrics | `backend/app/Http/Controllers/DashboardController.php` |
| API routes for messages, activity, dashboard | `backend/routes/api.php` |
| Ticket conversation view | `frontend/src/components/tickets/` |
| Activity log panel | `frontend/src/components/tickets/` |
| Dashboard page with charts | `frontend/src/pages/Dashboard.jsx` |
| Backend tests for all new endpoints | `backend/tests/` |

---

## Acceptance Criteria

1. **Public Reply** — An agent can post a public reply on a ticket; it appears in the conversation thread with a "Public" badge.

2. **Internal Note** — An agent can post an internal note; it is visually distinct (e.g., yellow/amber background) and only visible to team members, not customers.

3. **Conversation Thread** — All messages (public + internal) render in chronological order with author name, timestamp, and avatar.

4. **Message Validation** — Empty messages are rejected. Message type must be `public` or `internal`.

5. **Activity Log — Ticket Events** — The following actions are logged automatically: ticket created, status changed, priority changed, ticket assigned, message posted.

6. **Activity Log — Display** — Activity log shows action description, user, and timestamp in a timeline format on the ticket detail page.

7. **Dashboard Metrics** — `GET /api/dashboard/metrics` returns correct counts for open/pending/resolved tickets, average resolution time, and breakdowns by priority and tag.

8. **Dashboard Filters** — Date range and agent filters work correctly on the metrics endpoint and frontend.

9. **Dashboard UI** — Dashboard displays metric cards, charts (priority + tag breakdown), and per-agent table. Charts render correctly.

10. **Role-Based Visibility** — Internal notes return 403 for customer-level API tokens. Public messages are visible to all.

11. **Tests Pass** — Backend tests cover all new endpoints. CI pipeline is green.

12. **Pull Request** — All changes in a single PR on branch `feature/sprint-04-conversations-dashboard` targeting `main`.

---

## Dependencies

- Sprints 1-3 complete (tickets, users, roles, tenancy must exist)
- Charting library: `recharts` or `chart.js` (add to frontend deps)

---

## Out of Scope (Deferred to Sprint 5)

- SLA policies and breach alerts
- Email/push notifications
- Real-time WebSocket infrastructure (polling is fine for now)
- Customer-facing portal
- @mentions in internal notes

---

**Estimated effort:** 1 pull request
**Branch:** `feature/sprint-04-conversations-dashboard`

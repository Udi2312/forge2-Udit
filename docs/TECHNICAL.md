# PulseDesk — Technical Documentation

## Architecture Overview

PulseDesk is a full-stack multi-tenant helpdesk application built with:
- **Backend:** Laravel 11 (PHP 8.2+), Sanctum SPA auth
- **Frontend:** React 19, Vite, Tailwind CSS v4, Recharts
- **Database:** MySQL (production), SQLite in-memory (testing)
- **CI/CD:** GitHub Actions

## Project Structure
```
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php          # Register, login, logout, me
│   │   │   ├── TicketController.php        # CRUD + SLA + notifications
│   │   │   ├── TicketMessageController.php # Conversations (public + internal)
│   │   │   ├── CommentController.php       # Legacy comments
│   │   │   ├── TagController.php           # Tag CRUD
│   │   │   ├── OrganizationController.php  # Org members
│   │   │   ├── ActivityLogController.php   # Audit timeline
│   │   │   ├── DashboardController.php     # Analytics metrics
│   │   │   ├── SlaPolicyController.php     # SLA policy CRUD (admin)
│   │   │   └── NotificationController.php  # In-app notifications
│   │   └── Models/
│   │       ├── User.php                    # Auth, roles, notifications
│   │       ├── Organization.php            # Tenant root
│   │       ├── Ticket.php                  # Core entity + SLA logic
│   │       ├── Tag.php                     # Labels with colors
│   │       ├── Comment.php                 # Legacy comments
│   │       ├── TicketMessage.php           # Conversation messages
│   │       ├── ActivityLog.php             # Audit entries
│   │       ├── SlaPolicy.php               # Response/resolution targets
│   │       └── Notification.php            # In-app alerts
│   ├── database/
│   │   ├── migrations/                     # 10 migration files
│   │   ├── factories/                      # 9 factories
│   │   └── seeders/DatabaseSeeder.php      # Full seed with relationships
│   ├── routes/api.php                      # API v5.0.0 routes
│   └── tests/Feature/                      # 10 test files, 64+ tests
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   │   └── NotificationBell.jsx        # Nav notification dropdown
│   │   ├── lib/
│   │   │   ├── api.js                      # Axios instance + interceptor
│   │   │   └── auth.jsx                    # AuthContext + provider
│   │   ├── pages/
│   │   │   ├── Login.jsx
│   │   │   ├── Register.jsx
│   │   │   ├── Dashboard.jsx               # Ticket list + filters + SLA badges
│   │   │   ├── TicketDetail.jsx            # Conversation + Activity + SLA
│   │   │   └── Insights.jsx               # Charts + metrics dashboard
│   │   ├── App.jsx                         # Router + auth routes
│   │   ├── main.jsx                        # React entry point
│   │   └── index.css                       # Tailwind CSS v4
│   └── vite.config.js                      # Vite + Tailwind plugin
├── .github/workflows/ci.yml                # CI: PHP + Node tests, builds
└── docs/                                    # Documentation
```

## API Endpoints (v5.0.0)

### Auth
| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/register` | Register new user + org |
| POST | `/api/auth/login` | Login, return Sanctum token |
| POST | `/api/auth/logout` | Logout (revoke token) |
| GET | `/api/auth/me` | Current user info |

### Tickets
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/tickets` | List (paginated, filterable) |
| POST | `/api/tickets` | Create |
| GET | `/api/tickets/{id}` | Show detail |
| PUT | `/api/tickets/{id}` | Update (status, priority, assignee, tags) |
| DELETE | `/api/tickets/{id}` | Delete |

### Ticket Messages
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/tickets/{id}/messages` | List messages |
| POST | `/api/tickets/{id}/messages` | Add reply / internal note |

### Activity Log
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/tickets/{id}/activity` | Timeline for ticket |

### Tags
| Method | Path | Description |
|--------|------|-------------|
| GET/POST/PUT/DELETE | `/api/tags[/{id}]` | Full CRUD |

### SLA Policies
| Method | Path | Description |
|--------|------|-------------|
| GET/POST | `/api/sla-policies` | List (org-scoped), Create (admin) |
| PUT/DELETE | `/api/sla-policies/{id}` | Update (admin), Delete (admin) |

### Notifications
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/notifications` | List user's notifications |
| GET | `/api/notifications/unread-count` | Unread count |
| PUT | `/api/notifications/{id}/read` | Mark one read |
| PUT | `/api/notifications/read-all` | Mark all read |

### Dashboard
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/dashboard/metrics` | Aggregated analytics |

### Organization
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/org/members` | List org members |

## Data Model

```
Organization 1───n User
Organization 1───n Ticket
Organization 1───n Tag
Organization 1───n SlaPolicy

User 1───n Ticket (as requester)
User 1───n Ticket (as assignee)
User 1───n TicketMessage
User 1───n ActivityLog
User 1───n Notification

Ticket 1───n TicketMessage
Ticket 1───n ActivityLog
Ticket 1───n Notification
Ticket n───n Tag (via ticket_tag)
```

## Key Design Decisions

### Multi-Tenancy
- Global scope on `Ticket` and `Tag` models: `static::addGlobalScope('tenant', ...)`
- Every query is automatically scoped to `auth()->user()->organization_id`
- `OrganizationController::members()` returns only users in the same org

### SLA Engine
- `Ticket::slaStatus()` computes status by comparing `created_at + policy minutes` against `now()`
- Warning threshold: 80% of resolution time elapsed
- Breached tickets show even after resolution (wasBreached check)
- Policies are per-org, per-priority, with `is_active` flag

### Notification System
- Created inside `TicketController` on state changes
- Actor excluded from receiving notifications about their own actions
- `NotificationBell` component polls every 30 seconds
- Three notification triggers: ticket_created, status_changed, assignee_changed

### Auth
- Sanctum token-based for API
- Frontend stores token in localStorage
- Axios interceptor adds `Authorization: Bearer` header
- 401 responses trigger redirect to `/login`

### Testing
- SQLite in-memory for isolation
- Fresh migration per test (Laravel `RefreshDatabase` trait)
- Factories for all models
- 10 test suites: Auth, Ticket, TicketFilter, Assignment, Tag, Conversation, ActivityLog, DashboardMetrics, SlaPolicy, Notification

## Running Tests
```bash
cd backend
php artisan test
```

## CI/CD Pipeline
GitHub Actions (`.github/workflows/ci.yml`):
1. Backend: `composer install` → `php artisan test`
2. Frontend: `npm ci` → `npm run build`
3. Triggers on push to any branch + PRs to main

---

*PulseDesk v5.0.0 — Technical Documentation*

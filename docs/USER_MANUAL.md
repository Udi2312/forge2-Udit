# PulseDesk — User Manual

## Table of Contents
1. [Getting Started](#getting-started)
2. [Authentication](#authentication)
3. [Ticket Management](#ticket-management)
4. [Conversations & Internal Notes](#conversations--internal-notes)
5. [Tags & Filtering](#tags--filtering)
6. [SLA Policies](#sla-policies)
7. [Notifications](#notifications)
8. [Analytics & Insights](#analytics--insights)
9. [Activity Log](#activity-log)
10. [Roles & Permissions](#roles--permissions)

---

## Getting Started

PulseDesk is a multi-tenant helpdesk/ticketing platform.

**System Requirements:**
- PHP 8.2+, Composer 2.x
- Node.js 18+, npm
- MySQL (production) / SQLite (testing)

**Installation:**
```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend
cd frontend
npm install
npm run dev
```

The frontend runs on `http://localhost:5173`, backend API on `http://localhost:8000`.

---

## Authentication

- **Register:** Click "Register" → enter name, email, password, organization name
- **Login:** Enter email + password
- **Logout:** Click "Sign out" in the top-right corner

Your session is managed via Sanctum tokens. If your token expires, you'll be redirected to the login page automatically.

---

## Ticket Management

### Creating Tickets
1. Click **"New Ticket"** on the Dashboard
2. Enter subject, description, priority (low/medium/high/urgent)
3. Optionally select tags
4. Click **Create**

### Editing Tickets
- **Status:** Click any status button (open, pending, resolved, closed) on the ticket detail page
- **Priority:** Click a priority button in the right sidebar
- **Assignee:** Use the dropdown in the sidebar (only org members appear)
- **Tags:** Click "+ Edit tags" to toggle tags on/off

### Ticket List
The Dashboard shows all tickets for your organization. Use the filter bar to narrow by:
- Status, Priority, Assignee, Tag, or free-text search

---

## Conversations & Internal Notes

Each ticket has a **Conversation** tab with threaded messages.

### Public Replies
- Visible to all users including customers
- Shown with gray background

### Internal Notes
- Visible only to **agents and admins** (customers get 403)
- Shown with yellow background + 🔒 badge
- Toggle "Internal note" checkbox when writing

---

## Tags & Filtering

### Creating Tags
Tags are managed via the Tags API (`POST /api/tags`). Each tag has:
- Name, Color (gray/blue/green/yellow/red/purple/orange)

### Filtering
Use the Dashboard filter bar to filter by any combination of fields. Click "Clear" to reset all filters.

---

## SLA Policies

SLA (Service Level Agreement) policies define target response and resolution times per priority level.

### How SLA Works
- Each policy is tied to an organization + priority level
- When a ticket is created, the SLA clock starts
- **Response time:** Time to first agent response
- **Resolution time:** Time to ticket resolution

### SLA Statuses
| Status | Meaning |
|--------|---------|
| ✅ On Track | Within response & resolution targets |
| ⚠ Warning | Approaching deadline (80% of resolution time elapsed) |
| 🔴 Breached | Past resolution deadline |
| ✓ Met | Ticket resolved within SLA |

### Managing Policies (Admin only)
Admins can create/edit SLA policies via the API:
```
POST /api/sla-policies
{ "name": "Urgent SLA", "priority": "urgent", "response_time_minutes": 60, "resolution_time_minutes": 480 }
```

SLA badges appear on the Dashboard ticket list and on individual ticket detail pages.

---

## Notifications

### In-App Notifications
- **Bell icon** in the navigation bar shows unread count
- Click the bell to see recent notifications
- Notifications are generated when:
  - A new ticket is created (notifies org agents/admins)
  - Ticket status changes (notifies requester + assignee)
  - Ticket priority changes (notifies requester + assignee)
  - Ticket is assigned/reassigned (notifies assignee)
- The actor who made the change does NOT receive a notification

### Managing Notifications
- Click "Mark read" on individual notifications
- Click "Mark all read" to clear all
- Bell auto-refreshes every 30 seconds

---

## Analytics & Insights

Navigate to **Insights** (link in nav bar) to view:

### Metric Cards
- Total tickets, Open, Pending, Resolved, Average Resolution Time

### Charts
- **Pie Chart:** Tickets by Status
- **Bar Chart:** Tickets by Priority
- **Line Chart:** 14-day ticket volume trend
- **Agent Performance Table:** Per-agent totals (assigned, open, resolved)

### Filters
Use the date range filter (From/To) and click Apply. Data auto-refreshes every 30 seconds.

---

## Activity Log

Every ticket has an **Activity** tab showing a chronological audit trail:

- Ticket created
- Status changed (old → new)
- Priority changed (old → new)
- Assignee changed (old → new)
- Tags updated
- Messages sent

Each entry shows who made the change and when.

---

## Roles & Permissions

| Capability | Customer | Agent | Admin |
|-----------|----------|-------|-------|
| Create tickets | ✅ | ✅ | ✅ |
| View org tickets | ✅ | ✅ | ✅ |
| Edit ticket status/priority | ✅ | ✅ | ✅ |
| Assign tickets | ❌ | ✅ | ✅ |
| Post internal notes | ❌ | ✅ | ✅ |
| View internal notes | ❌ | ✅ | ✅ |
| Manage SLA policies | ❌ | ❌ | ✅ |
| Manage org members | ❌ | ❌ | ✅ |

---

*PulseDesk v5.0.0 — © 2026*

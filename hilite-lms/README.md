# HiLITE LMS

HiLITE LMS is a robust Lead Management System designed for efficiency and speed. It provides powerful lead intake, intelligent routing, and pipeline management capabilities for real estate and sales teams.

## Recent Big Changes & Features

### 1. High-Performance Front-End (SPA-like Experience)
- **Hotwire Turbo Drive Integration**: The entire application now utilizes Turbo Drive, intercepting all link clicks and form submissions. This eliminates full-page reloads and makes the traditional Multi-Page Application (MPA) feel as incredibly fast and responsive as a Single-Page Application (React/Vue).
- **Alpine.js Interactivity**: Globally integrated Alpine.js to handle all lightweight front-end logic (tabs, modals, edit modes) without writing heavy custom Javascript.

### 2. Lead Intake & Bulk Import Engine
- **Intelligent CSV Bulk Import**: 
  - Drag-and-drop UI on the `/leads/import` page.
  - Automatically parses `name`, `phone`, `email`, `source`, `region`, and `notes`.
  - Intelligently formats phone numbers and detects country codes using `libphonenumber`.
  - Skips duplicate leads automatically based on normalized E.164 phone numbers.
  - Automatically translates CSV `notes` into immediate `Activities` bound to the newly created lead.
- **Manual Lead Entry**: Fully integrated manual entry form using the exact same routing and normalization logic as the bulk importer.

### 3. Dynamic Notifications
- Rebuilt the TopNavBar Notification dropdown using Alpine.js.
- Individual notifications can be instantly marked as read and removed from the list via a subtle hover checkmark.
- "Mark all as read" button instantly clears the inbox.

### 4. Secure & Dynamic Profile Settings
- Redesigned the `/profile` page with Alpine.js Edit Mode toggling.
- All fields are strictly read-only by default to prevent accidental changes.
- Avatar changing and password viewing/reset capabilities have been restricted/secured per user requirements.
- Settings save instantly via background AJAX (Turbo), maintaining the fast SPA experience.

### 5. Backend Architecture
- **Strict MVC Paradigm**: Logic heavily contained within Services (`LeadIntakeService`) and Controllers (`LeadsController`, `ProfileController`).
- **Database Architecture**: Completely fresh, hierarchal database (Companies > Branches > Teams > Users) tailored explicitly for lead ownership and SLA tracking.

---
*Generated automatically by Antigravity*

# Project Control – React + Laravel

A role-based **Project Control and Daily Target vs Plan Management System** built with **React.js**, **Laravel/PHP**, and **MySQL**.

The application provides project configuration, project planning/control, master-data administration, user and role management, and permission-based access control.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Technology Stack](#technology-stack)
- [Key Features](#key-features)
- [Application Modules](#application-modules)
- [Role-Based Access Control](#role-based-access-control)
- [Permission Matrix](#permission-matrix)
- [Demo Credentials](#demo-credentials)
- [Project Master Data Hierarchy](#project-master-data-hierarchy)
- [Application Workflow](#application-workflow)
- [Project Configuration](#project-configuration)
- [Project Control / Daily Target vs Plan](#project-control--daily-target-vs-plan)
- [Master Data Management](#master-data-management)
- [User & Role Management](#user--role-management)
- [System Requirements](#system-requirements)
- [Project Structure](#project-structure)
- [Backend Configuration](#backend-configuration)
- [Frontend Configuration](#frontend-configuration)
- [Database Setup](#database-setup)
- [Running the Application](#running-the-application)
- [Build for Production](#build-for-production)
- [Role Testing Guide](#role-testing-guide)
- [Security and Environment Variables](#security-and-environment-variables)
- [Troubleshooting](#troubleshooting)
- [Development Notes](#development-notes)
- [AI-Assisted Development](#ai-assisted-development)
- [Assignment Submission](#assignment-submission)
- [Repository](#repository)
- [Author](#author)

---

## Project Overview

**Project Control** is designed to manage project structure, construction/work activities, apartment-level planning, daily targets, productivity, manpower, and plan submission workflows.

The application follows a role-based architecture so that administrators can maintain master data and users while operational roles receive only the permissions required for their responsibilities.

### Main objectives

- Maintain project and project-structure master data.
- Configure activities and sub-activities against apartments.
- Manage UOM, priority, typology, and gap-reason masters.
- Create and manage daily project plans.
- Compare planned targets and completion information.
- Track manpower/productivity-related planning information.
- Provide role-specific screens and permissions.
- Protect important operations with backend/server-side authorization.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Frontend | React.js 18.3.1 |
| Frontend Build Tool | Vite 6.4.4 |
| Backend | PHP / Laravel 12 |
| Database | MySQL |
| API | Laravel REST API |
| Authentication | Laravel/PHP API authentication |
| Authorization | Custom Role & Permission middleware |
| Styling | CSS |
| Local Server | XAMPP / Apache or Laravel development server |
| Package Management | npm and Composer |

---

## Key Features

### Authentication

- Login using email and password.
- Role-based navigation.
- Role-specific application access.
- Backend permission validation.

### Project Configuration

- Project filtering.
- Division filtering.
- Sub-Division filtering.
- Tower filtering.
- Level filtering.
- Activity/sub-activity configuration.
- Apartment selection.
- Apartment typology.
- Quantity.
- UOM.
- Priority.
- Save configuration.
- Export configuration.
- Bulk upload workflow.

### Project Control

- Daily Target vs Plan view.
- Project selection.
- Division/Sub-Division filtering.
- Tower filtering.
- Date-based planning.
- Search.
- Expand All.
- Activity-level planning.
- Target/productivity/manpower information.
- Apartment-level planning.
- Required quantity.
- Planned quantity.
- Completion quantity.
- Manpower.
- Gap/reason information.
- Save Plan.
- Submit Plan.

### Administration

- Project management.
- Division management.
- Sub-Division management.
- Tower management.
- Level management.
- Activity management.
- Sub-Activity management.
- Apartment management.
- Apartment Typology management.
- UOM management.
- Priority management.
- Gap Reason management.
- User management.
- Role management.
- Permission management.

---

# Application Modules

## 1. Login / Authentication

Users log in using their assigned credentials.

After authentication, the application determines the user's role and permissions and displays the appropriate navigation and functionality.

---

## 2. Project Configuration

Project Configuration is used to define the relationship between project structure, activities, sub-activities, apartments, typologies, UOMs, priorities, and quantities.

### Main filters

- Project
- Division
- Sub-Division
- Tower
- Level

### Configuration columns

- Sub-Activity Code
- Activity
- Sub-Activity
- Apartment
- Typology
- Quantity
- UOM
- Priority

### Actions

Actions are intentionally presented in this order:

1. **Save**
2. **Export Config**
3. **Bulk Upload**

---

## 3. Project Control / Daily Target vs Plan

Project Control is used for day-to-day planning and monitoring.

### Main controls

- Project
- Division
- Sub-Division
- Tower
- Date
- Search
- Expand All
- Save Plan
- Submit Plan

### Activity planning

Each activity can display planning metrics such as:

- Target
- Productivity
- Manpower
- Planned quantity
- Completion quantity

### Apartment-level planning

Apartment-level details can include:

- Apartment
- Priority
- Required quantity
- Planned quantity
- Completion
- Manpower
- Reason / gap reason

---

# Master Data Management

The Administrator manages all master data.

## Project Structure

The following hierarchy is maintained:

```text
Project
└── Division
    └── Sub-Division
        └── Tower
            └── Level
                └── Apartment
```

### Project

Defines the top-level project.

### Division

Defines major project divisions.

### Sub-Division

Defines subdivisions under a division.

### Tower

Defines towers/buildings under a sub-division.

### Level

Defines floors/levels under a tower.

---

## Work Master

### Activity

Defines the primary work/activity category.

### Sub-Activity

Defines detailed work under an activity.

Sub-activities can contain scheduling/planning-related fields used by Project Control.

---

## Apartment Master

### Apartment

Defines individual apartment/unit records.

### Apartment Typology

Defines the apartment type/typology used in configuration and planning.

---

## Planning Master

### UOM

Defines units of measurement used for quantities.

Examples may include:

- Nos
- Sqft
- Sqm
- Running Feet
- Other project-specific units

### Priority

Defines planning priority values.

Priority is maintained separately by the Administrator.

### Gap Reasons

Defines reasons used when there is a planning/completion gap.

---

# User & Role Management

The Administrator manages application users and their roles.

The application contains the following predefined roles:

| Role | Code |
|---|---|
| Administrator | `admin` |
| Project Manager | `project_manager` |
| Site Engineer | `engineer` |
| QS Team | `qs` |
| Read Only User | `viewer` |

---

# Role-Based Access Control

Permissions are controlled on the backend and are not intended to rely only on frontend visibility.

Available permissions:

```text
master.view
master.manage

configuration.view
configuration.edit
configuration.import
configuration.export

plan.view
plan.create
plan.submit

user.manage
role.manage
```

---

# Permission Matrix

| Permission | Administrator | Project Manager | Site Engineer | QS Team | Read Only |
|---|:---:|:---:|:---:|:---:|:---:|
| `master.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `master.manage` | ✓ | — | — | — | — |
| `configuration.view` | ✓ | ✓ | — | ✓ | ✓ |
| `configuration.edit` | ✓ | ✓ | — | ✓ | — |
| `configuration.import` | ✓ | ✓ | — | ✓ | — |
| `configuration.export` | ✓ | ✓ | — | ✓ | ✓ |
| `plan.view` | ✓ | ✓ | ✓ | — | ✓ |
| `plan.create` | ✓ | ✓ | ✓ | — | — |
| `plan.submit` | ✓ | ✓ | ✓ | — | — |
| `user.manage` | ✓ | — | — | — | — |
| `role.manage` | ✓ | — | — | — | — |

### Important access rule

**Administrator is the only role with master-data management access.**

Therefore:

- Projects are created/managed by Administrator.
- Divisions are created/managed by Administrator.
- Sub-Divisions are created/managed by Administrator.
- Towers are created/managed by Administrator.
- Levels are created/managed by Administrator.
- Activities are created/managed by Administrator.
- Sub-Activities are created/managed by Administrator.
- Apartments are created/managed by Administrator.
- Apartment Typologies are created/managed by Administrator.
- UOMs are created/managed by Administrator.
- Priorities are created/managed by Administrator.
- Gap Reasons are created/managed by Administrator.

Other roles consume the master data according to their assigned permissions.

---

# Demo Credentials

The seeded development/demo accounts are:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@example.com` | `password` |
| Project Manager | `manager@example.com` | `password` |
| Site Engineer | `engineer@example.com` | `password` |
| QS Team | `qs@example.com` | `password` |
| Read Only User | `viewer@example.com` | `password` |

> **Important:** These credentials are intended for local/demo/testing purposes only. Change or remove demo passwords before using the application in a real production environment.

---

# Project Master Data Hierarchy

The complete logical relationship is:

```text
Project
│
├── Division
│   │
│   └── Sub-Division
│       │
│       └── Tower
│           │
│           └── Level
│               │
│               └── Apartment
│
├── Activity
│   │
│   └── Sub-Activity
│
├── Apartment Typology
├── UOM
├── Priority
└── Gap Reason
```

Project Configuration combines these masters to create configuration records used by the planning module.

---

# Application Workflow

Recommended initial setup sequence:

```text
1. Login as Administrator
       ↓
2. Create Project
       ↓
3. Create Divisions
       ↓
4. Create Sub-Divisions
       ↓
5. Create Towers
       ↓
6. Create Levels
       ↓
7. Create Apartments
       ↓
8. Create Apartment Typologies
       ↓
9. Create Activities
       ↓
10. Create Sub-Activities
       ↓
11. Create UOM
       ↓
12. Create Priority
       ↓
13. Create Gap Reasons
       ↓
14. Configure Project
       ↓
15. Create Daily Plan
       ↓
16. Save Plan
       ↓
17. Submit Plan
```

---

# System Requirements

For local development, install:

### Required

- PHP 8.2 or higher
- Composer
- MySQL
- Node.js
- npm
- Git
- XAMPP (recommended for Windows/local MySQL and Apache)

### Verify installations

```bash
php -v
composer -V
node -v
npm -v
git --version
```

---

# Project Structure

```text
project-control-react-laravel/
│
├── .gitignore
├── README.md
│
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Providers/
│   │   └── Services/
│   │
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/
│   ├── routes/
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   └── .env.example
│
└── frontend/
    ├── src/
    │   ├── App.jsx
    │   ├── api.js
    │   ├── index.css
    │   ├── main.jsx
    │   ├── master-data.css
    │   └── user-role-admin.css
    ├── index.html
    ├── package.json
    └── package-lock.json
```

---

# Backend Configuration

Open a terminal in the backend directory:

```bash
cd backend
```

Install PHP dependencies:

```bash
composer install
```

Create the Laravel environment file.

### Windows CMD

```cmd
copy .env.example .env
```

### PowerShell

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

---

# Database Configuration

Create a MySQL database.

Recommended database name:

```text
project_control
```

Using MySQL command line:

```sql
CREATE DATABASE project_control;
```

Then update:

```text
backend/.env
```

Example:

```env
APP_NAME="Project Control"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_control
DB_USERNAME=root
DB_PASSWORD=
```

Adjust `DB_USERNAME` and `DB_PASSWORD` according to the local MySQL/XAMPP configuration.

---

# Run Database Migrations and Seed Data

From:

```text
backend/
```

run:

```bash
php artisan migrate:fresh --seed
```

This creates the required database tables and seeds the demo roles, permissions, users, and development data.

If the database already contains data and you only want to run pending migrations:

```bash
php artisan migrate
```

To seed:

```bash
php artisan db:seed
```

For a clean development reset:

```bash
php artisan migrate:fresh --seed
```

> `migrate:fresh --seed` deletes existing tables/data before rebuilding the database. Do not use it on a production database containing important data.

---

# Clear Laravel Cache

If configuration, routes, or permissions appear stale:

```bash
php artisan optimize:clear
```

You can also run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

# Start Laravel Backend

From the `backend` directory:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Backend will be available at:

```text
http://127.0.0.1:8000
```

The API is exposed through the Laravel routes under:

```text
/api
```

---

# Frontend Configuration

Open another terminal.

From the project root:

```bash
cd frontend
```

Install JavaScript dependencies:

```bash
npm install
```

Create a frontend environment file if required by the local setup.

Example:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Do not commit your local `.env` file if it contains private configuration.

---

# Start React Frontend

From:

```text
frontend/
```

run:

```bash
npm run dev
```

Vite normally starts the frontend at:

```text
http://localhost:5173
```

Open the URL in a browser.

---

# Run the Complete Application

Two terminals are recommended.

### Terminal 1 – Laravel

```bash
cd C:\xampp\htdocs\latienm_task\project-control-ready\backend
php artisan serve --host=127.0.0.1 --port=8000
```

### Terminal 2 – React

```bash
cd C:\xampp\htdocs\latienm_task\project-control-ready\frontend
npm install
npm run dev
```

Then open:

```text
http://localhost:5173
```

---

# Alternative XAMPP Setup

If using XAMPP Apache for Laravel:

1. Start Apache.
2. Start MySQL.
3. Configure the Laravel application/environment.
4. Point Apache's document root/virtual host to:

```text
backend/public
```

The React development server can still run separately with:

```bash
npm run dev
```

For development, using:

```bash
php artisan serve
```

is generally simpler.

---

# Project Configuration Usage

## Administrator

1. Login as Administrator.
2. Open **Master Data**.
3. Create the required project structure.
4. Create Activities and Sub-Activities.
5. Create Apartment Typologies.
6. Create UOMs.
7. Create Priorities.
8. Create Gap Reasons.
9. Open **Project Configuration**.
10. Select the project.
11. Select Division/Sub-Division/Tower/Level as required.
12. Configure Sub-Activities against apartments.
13. Select Typology, Quantity, UOM, and Priority.
14. Click **Save**.

### Configuration actions

```text
Save
Export Config
Bulk Upload
```

---

# Daily Planning Usage

After the project configuration is available:

1. Login using a planning-enabled account.
2. Open **Project Control / Daily Target V/S Plan**.
3. Select the project.
4. Select optional project structure filters.
5. Select the planning date.
6. Review activities.
7. Expand activity sections as required.
8. Enter/update planning information.
9. Review apartment-level details.
10. Save the plan.
11. Submit the plan when ready.

---

# User & Role Management

Only the Administrator has user/role management permissions.

The Administrator can manage:

- Users
- Roles
- Permissions

The application uses the following role codes:

```text
admin
project_manager
engineer
qs
viewer
```

---

# Role Testing Guide

For assignment/demo testing, test each account separately.

## 1. Administrator

Login:

```text
admin@example.com
password
```

Expected:

- Master Data management available.
- Project creation available.
- Project structure management available.
- UOM management available.
- Priority management available.
- Configuration management available.
- Planning available.
- User management available.
- Role management available.

---

## 2. Project Manager

Login:

```text
manager@example.com
password
```

Expected permissions:

```text
master.view
configuration.view
configuration.edit
configuration.import
configuration.export
plan.view
plan.create
plan.submit
```

The Project Manager can work with configuration and planning but cannot administer users/roles or modify master data.

---

## 3. Site Engineer

Login:

```text
engineer@example.com
password
```

Expected permissions:

```text
master.view
plan.view
plan.create
plan.submit
```

The Site Engineer can view required master information and work with project planning.

---

## 4. QS Team

Login:

```text
qs@example.com
password
```

Expected permissions:

```text
master.view
configuration.view
configuration.edit
configuration.import
configuration.export
```

The QS Team is focused on project configuration and related data.

---

## 5. Read Only User

Login:

```text
viewer@example.com
password
```

Expected permissions:

```text
master.view
configuration.view
configuration.export
plan.view
```

The Read Only User should not be able to create, edit, submit, or administer restricted data.

---

# Security and Environment Variables

Do not commit real environment files or credentials.

The repository should contain:

```text
backend/.env.example
```

but local environment files should remain outside Git.

Do not commit:

```text
backend/.env
frontend/.env
```

Also avoid committing:

```text
node_modules/
vendor/
dist/
storage/logs/
storage/framework/cache/
```

The `.gitignore` file is included to prevent common local/dependency files from being committed.

### Production security

Before production deployment:

- Change all demo passwords.
- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Use a strong database password.
- Use HTTPS.
- Configure secure CORS settings.
- Store secrets in environment variables.
- Do not expose API keys in source code.
- Review authentication and authorization rules.
- Enable appropriate GitHub security features for the repository.

---

# Troubleshooting

## 1. Composer command not found

Install Composer and verify:

```bash
composer -V
```

---

## 2. PHP command not found

If using XAMPP on Windows, make sure the PHP executable is available.

Example XAMPP PHP path:

```text
C:\xampp\php
```

Add it to the Windows PATH if required.

Verify:

```bash
php -v
```

---

## 3. Database connection error

Check:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_control
DB_USERNAME=root
DB_PASSWORD=
```

Also confirm MySQL is running in XAMPP.

After changing `.env`:

```bash
php artisan optimize:clear
```

---

## 4. Migration/seed issue

For a clean development database:

```bash
php artisan migrate:fresh --seed
```

---

## 5. Frontend cannot connect to backend

Check that Laravel is running:

```text
http://127.0.0.1:8000
```

Check the frontend environment:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Then restart Vite:

```bash
npm run dev
```

---

## 6. Permission changes are not reflected

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Then log out and log back in.

For a complete development reset:

```bash
php artisan migrate:fresh --seed
```

---

## 7. npm dependency issue

Delete local dependencies and reinstall.

Windows CMD:

```cmd
rmdir /s /q node_modules
del package-lock.json
npm install
```

Only delete `package-lock.json` if dependency regeneration is actually required. Prefer `npm ci` when the existing lock file is valid:

```bash
npm ci
```

---

# Development Notes

## Backend

The Laravel backend contains:

- Controllers
- Models
- Middleware
- Services
- Migrations
- Seeders
- API routes

Important backend areas include:

```text
backend/app/Http/Controllers/
backend/app/Http/Middleware/
backend/app/Models/
backend/app/Services/
backend/database/migrations/
backend/database/seeders/
backend/routes/
```

## Frontend

The React frontend contains:

```text
frontend/src/App.jsx
frontend/src/api.js
frontend/src/main.jsx
frontend/src/index.css
frontend/src/master-data.css
frontend/src/user-role-admin.css
```

Vite is used for frontend development and production builds.

---

# Production Frontend Build

To create a production build:

```bash
cd frontend
npm run build
```

The generated production files are normally placed in:

```text
frontend/dist/
```

Do not commit `dist/` unless the deployment process specifically requires it.

To test a production build locally, use an appropriate static server or the deployment platform's configured frontend hosting.

---

# Production Laravel Optimization

Before production deployment, review the environment settings and run:

```bash
php artisan optimize
```

Do not run destructive commands such as:

```bash
php artisan migrate:fresh --seed
```

against a production database unless you intentionally want to delete and rebuild the database.

---

# Assignment Notes

This repository was prepared as a technical assignment demonstrating:

- React.js frontend development.
- Laravel/PHP backend development.
- MySQL database design.
- REST API integration.
- Authentication.
- Role-based authorization.
- Master data management.
- Project configuration.
- Project planning.
- Daily Target vs Plan workflow.
- Responsive application UI.
- Server-side permission enforcement.

---

# AI-Assisted Development

AI-assisted development tools were used during the implementation of this assignment for activities such as:

- Code generation and refinement.
- Debugging.
- UI implementation assistance.
- API/controller development assistance.
- Database and migration assistance.
- Role/permission design assistance.
- Error analysis.
- Documentation preparation.

The resulting application was reviewed, integrated, configured, and tested as part of the development process.

---

# Assignment Submission

### Repository

GitHub:

https://github.com/saurabh-98/project-control-react-laravel

### Technology

```text
Frontend: React.js
Backend: PHP / Laravel
Database: MySQL
```

### Main demonstrated modules

```text
Login / Role Selection
Project Configuration
Project Control / Daily Target V/S Plan
Master Data Management
User & Role Management
Role Permissions
Save / Submit Plan
Configuration Import / Export
```

---

# Author

**Saurabh Kumar Jha**

GitHub: `saurabh-98`

Repository:

https://github.com/saurabh-98/project-control-react-laravel

---

## License

This project was developed as a technical assignment/demo application. Unless a separate license is added to the repository, all rights to the submitted implementation remain with the author.

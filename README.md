# 🏫 Campus Issue Tracker – Smart Maintenance & Resolution Platform

[![Deployment Status](https://img.shields.io/badge/Status-Active_&_Deployed-00C853?style=for-the-badge&logo=render&logoColor=white)](https://campus-issue-tracker-main.onrender.com)
[![Live Demo](https://img.shields.io/badge/🚀_Live_Demo-Campus_Issue_Tracker-00D4FF?style=for-the-badge&logo=render&logoColor=white)](https://campus-issue-tracker-main.onrender.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Containerized-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

> A centralized, role-based Web application designed to streamline campus facility management, automate issue dispatching, track repair progress, and provide real-time updates across educational institutions.

---

## 📌 Project Overview

**Campus Issue Tracker** (FixMyCampus) is a full-stack, enterprise-grade Web application developed to modernize and digitize facility maintenance within academic environments. Traditional maintenance reporting relies on informal communication channels, manual paper logs, or fragmented emails, leading to delayed repairs, lack of accountability, and poor visibility for stakeholders.

This platform bridges the communication gap between **Students/Faculty**, **Administrative Overseers**, and **Maintenance Technicians**. By offering dedicated portal interfaces, dynamic photo evidence upload, automated work order allocation, real-time in-app notifications, and audit-ready status timelines, the system transforms physical campus maintenance into a transparent, data-driven workflow.

---

## 🎯 Problem Statement

Educational institutions often face significant operational friction in maintaining infrastructure across expansive facilities:

* **Unstructured Reporting Channels:** Maintenance complaints are reported verbally, via unstructured emails, or on physical paper forms, leading to lost or miscommunicated requests.
* **Lack of Status Visibility:** Students and staff who report infrastructural faults remain uninformed about repair progress, causing duplicate reports and frustration.
* **Inefficient Task Allocation:** Administrators struggle to manually track technician workloads, prioritize critical emergencies, and dispatch staff efficiently.
* **No Historical Audit Trail:** Absence of structured status logs makes it impossible to evaluate department response times, technician productivity, or recurrent hardware failures.

---

## 💡 The Solution

**Campus Issue Tracker** delivers an end-to-end digital transformation for facility management:

* **Centralized Digital Portal:** A unified web platform where students, staff, administrators, and maintenance personnel log in to manage campus facilities seamlessly.
* **Multi-Role Workspace Isolation:** Customized dashboards tailored to specific operational needs—Reporters submit and track, Admins manage and assign, Technicians execute and resolve.
* **Visual Evidence Attachment:** Secure multi-file image upload system allowing reporters to submit photos of damage for immediate visual assessment.
* **Automated Notification Engine:** Real-time event notifications notifying reporters upon assignment/status shifts, and dispatching instant alerts to assigned technicians.
* **Complete Status Lifecycle Auditing:** Immutably logged status transitions capturing timestamps, responsible personnel, and resolution remarks.

---

## ✨ Key Features

- [x] **Role-Based Authentication & Access Control (RBAC):** Distinct authorization scopes for `Student`, `Staff`, `Maintenance`, and `Admin` users.
- [x] **Multi-Category Issue Reporting:** Pre-configured categories covering Electrical, Plumbing, IT/Network, Cleanliness, Infrastructure, Furniture, and Safety.
- [x] **Photo Evidence Upload:** Support for attaching multiple images (`.jpg`, `.png`, `.webp`) up to 5MB with server-side validation.
- [x] **Priority Levels & Urgency Matrix:** Issue classification across `Low`, `Medium`, `High`, and `Critical` priorities for rapid triage.
- [x] **Administrative Task Dispatching:** Admin review panel to evaluate pending complaints, attach notes, and assign specialized technicians.
- [x] **Technician Execution Portal:** Work order management interface enabling maintenance staff to update progress and attach completion notes.
- [x] **Audit Trail & Timeline:** Sequential log tracking every status transition (`Pending` → `In Progress` → `Resolved` / `Closed` / `Rejected`) with full change history.
- [x] **In-App Notification Center:** Dynamic unread notification counter and event logs alerting users of assignment and status updates.
- [x] **Comprehensive Admin Analytics:** Executive dashboard reporting total complaints, resolution rates, category distributions, monthly trends, and top reporters.
- [x] **Secure Authentication & Password Hashing:** User security enforced via BCRYPT hashing and protected PHP session handling.

---

## 🔥 Project Highlights

* **Production-Grade Architecture:** Built with native PHP 8.2 and MySQL PDO, zero unnecessary bloat, fully containerized via Docker for instant deployment on cloud platforms like Render.
* **End-to-End Auditability:** Every status change inserts an immutable log entry into the `status_history` relational schema, guaranteeing accountability.
* **Dynamic Environment Resilience:** Custom database initialization engine (`config/db.php`) automatically provisions schema tables and seed data upon first deployment.
* **Responsive Modern UI:** Designed with a dark-mode glassmorphism interface using custom CSS variables, flexbox/grid architecture, and Bootstrap Icons.

---

## 👥 User Roles & Responsibilities

| Role | Operational Scope | System Access & Permissions | Primary Responsibilities |
| :--- | :--- | :--- | :--- |
| **Student / Reporter** | Facilities End-User | • Access to Reporter Portal<br>• Submit new issues with image attachments<br>• View status timeline of personal reports | • Report campus faults accurately<br>• Provide location details and photo evidence<br>• Track repair progress |
| **Faculty / Staff** | Facilities End-User | • Access to Reporter Portal<br>• Submit department/classroom issues<br>• Monitor personal submission history | • Report institutional infrastructure damage<br>• Flag critical academic hardware faults |
| **Maintenance Staff** | Field Technician | • Access to Maintenance Portal<br>• View assigned work orders<br>• Transition issue statuses (`In Progress` / `Resolved`) | • Execute field repairs<br>• Log work progress & technical remarks<br>• Mark assigned tasks as resolved |
| **Administrator** | System Manager | • Access to Admin Master Portal<br>• Global issue filtering & status override<br>• Assign maintenance technicians<br>• User account management & deletion<br>• System analytics & reporting | • Triage incoming complaints<br>• Allocate technicians based on priority<br>• Oversee overall campus resolution rates<br>• Manage user accounts and system integrity |

---

## 🔄 System Workflow

The lifecycle of an issue within the Campus Issue Tracker follows a structured, 7-stage workflow:

```
[ 1. Issue Reporting ] ──► [ 2. Admin Review ] ──► [ 3. Staff Assignment ]
                                                             │
[ 6. Verification & Closure ] ◄── [ 5. Resolution ] ◄── [ 4. Work Execution ]
```

1. **Issue Reporting:** A student or staff member logs into the Reporter Portal and fills out the report form, providing a title, category, location, priority rating, detailed description, and photo evidence. The issue enters the system with a `Pending` status.
2. **Review & Triage:** Administrators receive an instant warning notification. The admin reviews the complaint details, urgency, and attached photos from the Admin Dashboard.
3. **Staff Assignment:** The administrator selects an available maintenance technician (e.g., IT department staff for network faults, maintenance staff for plumbing/electrical) and submits administrative instructions. The issue transitions to `In Progress`.
4. **Work Execution & Status Updates:** The assigned technician receives a work notification, views the task details in their Maintenance Portal, inspects the physical location, and updates status remarks as work progresses.
5. **Resolution:** Upon completing repairs, the technician updates the status to `Resolved` and enters resolution remarks.
6. **Notification & Verification:** Automated success notifications are dispatched to the original reporter and administrators. The reporter inspects the resolution.
7. **Closure / Rejection:** The administrator validates completion and transitions the issue to `Closed` (or `Rejected` if the report was invalid/duplicate), concluding the issue lifecycle.

---

## 🏗️ System Architecture

The project adheres to a clean 3-Tier Layered Architecture with decoupled role modules and secure database communications.

```
┌────────────────────────────────────────────────────────────────────────┐
│                          PRESENTATION LAYER                            │
│  ┌────────────────────┐  ┌─────────────────────┐  ┌─────────────────┐  │
│  │  Reporter Portal   │  │ Maintenance Portal  │  │   Admin Portal  │  │
│  └─────────┬──────────┘  └──────────┬──────────┘  └────────┬────────┘  │
└────────────┼────────────────────────┼──────────────────────┼───────────┘
             │                        │                      │
┌────────────┼────────────────────────┼──────────────────────┼───────────┐
│            ▼                        ▼                      ▼           │
│                            APPLICATION LAYER                           │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │   Authentication Check & Session Manager (auth_check.php)        │  │
│  ├──────────────────────────────────────────────────────────────────┤  │
│  │   Notification & Status Audit Dispatcher (notification_helper)   │  │
│  ├──────────────────────────────────────────────────────────────────┤  │
│  │   File Storage Engine & Upload Validator (uploads/issues)        │  │
│  └──────────────────────────────────┬───────────────────────────────┘  │
└─────────────────────────────────────┼──────────────────────────────────┘
                                      │
┌─────────────────────────────────────┼──────────────────────────────────┐
│                                     ▼                                  │
│                             DATA ACCESS LAYER                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │            PDO Database Connection Guard (config/db.php)         │  │
│  └──────────────────────────────────┬───────────────────────────────┘  │
│                                     ▼                                  │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    MySQL Database (Relational)                   │  │
│  │     [users] ─── [issues] ─── [categories] ─── [issue_images]     │  │
│  │                    │ └── [status_history]                        │  │
│  │                    └── [notifications]                           │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────────┘
```

### Request & Data Flow
1. **HTTP Request:** The client sends an HTTP request to Apache, routed through custom PHP controllers.
2. **Session Verification:** `auth_check.php` verifies active session variables and validates role permissions (`requireRole()`).
3. **Business Logic Execution:** Controller processes user inputs, performs server-side validations, and executes file upload handlers.
4. **Data Access via PDO:** Data transactions execute via PDO prepared statements over an encrypted TCP connection to MySQL.
5. **Event Logging & Notification:** Status modifications trigger automatic creation of audit records (`status_history`) and user notifications (`notifications`).
6. **Dynamic Render:** PHP constructs HTML output decorated with custom CSS components and Bootstrap Icons before serving the response.

---

## 🛠️ Technology Stack

| Domain | Technology | Purpose & Details |
| :--- | :--- | :--- |
| **Frontend** | HTML5 / CSS3 / JavaScript | Modern semantic markup, dynamic layout styles, and interactive DOM handlers |
| **Styling & UI** | Custom Vanilla CSS | Glassmorphism UI tokens, CSS Grid/Flexbox, custom dark mode design system |
| **Iconography** | Bootstrap Icons v1.11.3 | Scalable vector icons for navigational elements and status badges |
| **Backend Core** | PHP 8.2 | Object-oriented procedural backend scripts handling authentication and API routing |
| **Database Engine** | MySQL 8.0 / MariaDB | Relational database management system storing relational transactional data |
| **Database Access** | PHP Data Objects (PDO) | Prepared statements interface ensuring safe database interactions and SQL execution |
| **Server / Environment**| Apache Web Server | HTTP web server running `mod_rewrite` and PHP-FPM integration |
| **Containerization** | Docker | Infrastructure-as-code container build (`Dockerfile`) based on `php:8.2-apache` |
| **Cloud Hosting** | Render Platform | PaaS cloud host executing continuous containerized web services |
| **Cloud Database** | Aiven Cloud MySQL | Fully managed cloud database instance with SSL encryption |
| **Version Control** | Git & GitHub | Distributed version control and source code repository management |

---

## 📊 Database Design Summary

The relational database schema (`fixmycampus`) comprises 6 normalized tables connected via strict foreign key constraints:

```
                  ┌──────────────┐
                  │    users     │
                  └──────┬───────┘
                         │ 1
                         │
                         │ *
                  ┌──────┴───────┐           ┌──────────────┐
                  │    issues    ├───────────┤  categories  │
                  └──────┬───────┘ *       1 └──────────────┘
                         │ 1
        ┌────────────────┼────────────────┐
        │ *              │ *              │ *
┌───────┴──────┐ ┌───────┴──────┐ ┌───────┴──────┐
│ issue_images │ │status_history│ │ notifications│
└──────────────┘ └──────────────┘ └──────────────┘
```

### Entity Overview

* **`users`**: Stores user credentials, hashed passwords, contact details, assigned department, and assigned role (`student`, `staff`, `admin`, `maintenance`).
* **`categories`**: Maintains pre-seeded issue categories (e.g., Electrical, Plumbing, IT, Safety) along with Bootstrap icon references.
* **`issues`**: The central transactional table storing complaint title, detailed description, category reference, location string, priority rating (`low`, `medium`, `high`, `critical`), current status (`pending`, `in_progress`, `resolved`, `closed`, `rejected`), reporter ID (`reported_by`), assigned technician ID (`assigned_to`), and administrative remarks.
* **`issue_images`**: Stores file paths of photo evidence linked to specific issues (`issue_id`), allowing 1-to-many image attachments.
* **`status_history`**: Immutably records every status transition for an issue, capturing `old_status`, `new_status`, `changed_by` user ID, resolution remarks, and automated timestamps.
* **`notifications`**: Contains targeted system alerts linked to specific users, tracking message text, notification type (`info`, `warning`, `success`, `danger`), linkable `issue_id`, and `is_read` flag.

---

## 🔒 Security & Reliability Features

* **Password Security:** All user passwords are encrypted at rest using industry-standard BCRYPT hashing (`password_hash($password, PASSWORD_BCRYPT)`).
* **SQL Injection Prevention:** 100% of database operations use PDO prepared statements with parameterized queries; `PDO::ATTR_EMULATE_PREPARES` is set to `false`.
* **Cross-Site Scripting (XSS) Protection:** All dynamic outputs rendered in HTML templates are sanitized using `htmlspecialchars()` to sanitize user inputs.
* **Role-Based Access Control (RBAC):** Strict server-side route guards (`requireRole()`) enforce authorization before executing page controllers.
* **File Upload Validation & Sanitization:**
  * Strict MIME-type checking (`image/jpeg`, `image/png`, `image/jpg`, `image/webp`).
  * File size restriction enforced at 5MB (`MAX_FILE_SIZE`).
  * Files re-indexed using `uniqid()` to prevent file path injection and overwrite attacks.
* **Session Management:** PHP sessions initialized securely (`session_start()`), with login state validated on all protected pages.
* **Automatic Database Provisioning:** Connection handler (`config/db.php`) automatically checks table existence and executes initial SQL schema migration on cold start.

---

## 📁 Project Structure

```
Fixmycampus/
├── admin/                     # Administrator Portal Controllers & Views
│   ├── dashboard.php          # System metrics overview & recent activity feeds
│   ├── issues.php             # Master issue repository & multi-parameter filter
│   ├── notifications.php      # Administrator notification center
│   ├── reports.php            # Analytics dashboard & trend charts
│   ├── users.php              # User management & account control
│   └── view_issue.php         # Issue detail inspection, assignment & status editor
├── assets/                    # Static UI Resources
│   └── css/
│       └── style.css          # Custom glassmorphic styling & responsive framework
├── config/                    # System & Environment Configuration
│   └── db.php                 # Dynamic PDO database connector & schema auto-installer
├── includes/                  # Reusable Backend Modules & Layout Partials
│   ├── auth_check.php         # Authentication guard & RBAC helper functions
│   ├── notification_helper.php# Notification dispatcher & timeline audit logger
│   ├── sidebar.php            # Context-aware navigation sidebar
│   └── topbar.php             # Top navigation bar & unread notification counter
├── maintenance/               # Maintenance Technician Portal
│   ├── dashboard.php          # Work order summary & task metrics
│   ├── my_assignments.php     # Assigned work orders list
│   ├── notifications.php      # Technician alert center
│   └── update_issue.php       # Task status transition & work log editor
├── reporter/                  # Student & Faculty Portal
│   ├── dashboard.php          # Reporter dashboard & submission metrics
│   ├── my_issues.php          # Personal report history & status monitor
│   ├── notifications.php      # User notification center
│   ├── report_issue.php       # Issue creation form with image evidence uploader
│   └── view_issue.php         # Issue progress tracker & audit timeline view
├── uploads/                   # Media Storage Directory
│   └── issues/                # Storage destination for uploaded photo evidence
├── database.sql               # Relational MySQL Schema & initial seed datasets
├── Dockerfile                 # Docker container image build manifest
├── index.php                  # Application entry point & login portal
├── logout.php                 # Session destruction & logout controller
├── register.php               # Account registration handler for Students & Staff
└── team_members.txt           # Project development team roster
```

---

## 🌐 Live Application

The project is fully deployed and accessible on Render. You can explore all user roles and functionalities directly on the live instance without local setup.

🚀 **Live Application Link:** [https://campus-issue-tracker-main.onrender.com](https://campus-issue-tracker-main.onrender.com)

---

## 🎓 Learning Outcomes

This project demonstrates practical competence in core software engineering principles:

* **Requirements Engineering:** Analyzing real-world institutional maintenance challenges to model functional system requirements.
* **System Architecture & Design:** Designing a modular 3-tier web application using MVC-inspired separation of concerns.
* **Database Modeling & Normalization:** Crafting a normalized relational schema with strict data integrity, foreign key relations, and indexing.
* **Role-Based Access Control (RBAC):** Implementing multi-tenant user access controls across four distinct operational personas.
* **Audit Trail Implementation:** Structuring immutable status history tracking for compliance and performance analysis.
* **Full-Stack PHP Engineering:** Developing clean, maintainable backend PHP code alongside custom CSS interfaces.
* **Containerization & Deployment:** Building Docker images for automated cloud deployment with external database integrations.

---

## 🔮 Future Enhancements

* 📱 **Mobile Application & Push Notifications:** Progressive Web App (PWA) wrapper or native Flutter app with Firebase Push Notifications.
* 📍 **Interactive Campus Map Integration:** Geotagging infrastructure complaints on an interactive 2D campus floor plan.
* ⏱️ **SLA & Escalation Engine:** Automated priority escalation if an issue remains unassigned past defined Service Level Agreement thresholds.
* 📊 **Exportable Analytics Reports:** Capability for administrators to export analytical summaries in PDF or Excel formats.

---

## 👥 Project Team & Contributors

This project was designed and developed as an engineering endeavor by:

* 👨‍💻 **Kris Rodrigues**
* 👩‍💻 **Jeolita Parpatekar**
* 👩‍💻 **Krisha Shet** ([@krishashetdz](https://github.com/krishashetdz))

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

```
MIT License

Copyright (c) 2026 Kris Rodrigues, Jeolita Parpatekar, Krisha Shet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

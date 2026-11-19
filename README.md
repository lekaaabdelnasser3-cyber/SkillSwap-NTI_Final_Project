# Skills. — Skill-Sharing Platform

A PHP and MySQL web application where users can create profiles, share skills, send skill-related requests, and communicate with other members. The project also includes an admin area for managing the platform.

## Features

### User Features
- **Account Registration & Login** with server-side input validation.
- **User Profiles** with personal information and profile images.
- **Browse the Community** and view other users' profiles.
- **Skills Management:** Add and manage the skills listed on your profile.
- **Skill Requests:** Send requests to other users and manage incoming or outgoing requests.
- **Request Reviews:** Review functionality is included in the requests workflow.
- **Chat:** Communicate with another user when the related request has been accepted.

### Admin Features
- **Dashboard** with counts for users, skills, and requests.
- **User Management.**
- **Skills Management.**
- **Requests Management.**

## Tech Stack

- **Backend:** PHP
- **Database:** MySQL
- **Database Access:** PHP Data Objects (PDO)
- **Frontend:** HTML, CSS, JavaScript
- **Icons:** Bootstrap Icons
- **Fonts:** Google Fonts

## Project Structure

```text
nti_project/
│
├── Admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── skills.php
│   ├── Requests.php
│   ├── init.php
│   ├── validation/
│   │   └── admin.php
│   └── includes/
│       ├── db/
│       ├── temp/
│       └── assets/
│
├── includes/
│   ├── db/
│   ├── temp/
│   └── assets/
│
├── uploads/
│
├── index.php
├── register.php
├── login.php
├── logout.php
├── profile.php
├── MySkills.php
├── MyRequests.php
├── chat.php
├── validation.php
└── validation_profile.php
```

## Getting Started

### Prerequisites

Install a local PHP/MySQL environment such as [XAMPP](https://www.apachefriends.org/) or an equivalent setup.

You will need:
- PHP with PDO and the PDO MySQL driver enabled.
- MySQL or MariaDB.
- A web browser.

### Installation

**1. Clone the repository**

Clone the project into your web server's document root (for XAMPP, this is usually `htdocs`).

```bash
git clone <repository-url>
```

**2. Navigate to the project directory**

```bash
cd nti_project
```

**3. Create the database**

Open phpMyAdmin or your MySQL client and run:

```sql
CREATE DATABASE nti_project
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

**4. Set up the database tables**

The current repository snapshot does not include a database export or SQL migration file. The application expects tables for users, skills, requests, messages, and reviews. Create or import the matching database schema before running the application.

**5. Configure the database connection**

Update the connection settings in:

- `includes/db/db.php`
- `Admin/includes/db/db.php`

Current development configuration:

- Host: `localhost`
- Database: `nti_project`
- Username: `root`
- Password: empty

Change these values to match your local MySQL configuration. Do not use development credentials in production.

**6. Run the application**

Start Apache and MySQL, then open:

```text
http://localhost/nti_project/
```

Register a user account to explore the user features. Admin pages require an account with the admin role.

## Validation and Security

The project includes server-side validation helpers in `validation.php` and profile-related validation in `validation_profile.php`.

Database operations use PDO prepared statements, and registration hashes passwords using PHP's `password_hash()` function.

For production deployment, review the authentication and authorization flows, configure secure session and cookie settings, validate uploaded files, escape all output, and move database credentials into environment-based configuration.

Also review the legacy plaintext-password fallback currently present in the login flow before deploying publicly.

## Main Application Flow

1. A visitor creates an account through the registration page.
2. The user logs in and accesses the community.
3. Users can view profiles and manage their own skills.
4. A user can send a skill-related request to another member.
5. Requests can be managed by the participants; accepted requests enable the chat workflow.
6. Admins use the admin dashboard to oversee users, skills, and requests.

## Notes

- The database schema is not included in this repository snapshot, so the project is not ready to run until the required tables are created or imported.
- The application currently uses local development database settings. Configure them before running the project on another machine.
- The `uploads/` directory contains user-uploaded images used by the application.

## Contributors

Developed as a team project as part of the **NTI Full Stack PHP Training Program**.

---

<p align="center">
  Built to connect people through skills and knowledge.
</p>

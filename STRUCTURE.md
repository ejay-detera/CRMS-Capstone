# Project Structure

This document outlines the file structure of the CRMS-Capstone project and explains the purpose of the key directories and files.

## High-Level Directory Layout

```text
crms-capstone/              # root directory
├── docker/                 # Custom docker configurations (if any)
├── frontend/               # User interface applications
│   └── web/                # Main Vue 3 / Vite frontend
├── services/               # Backend microservices (Laravel)
│   ├── contract-management/# Handles contract logic
│   ├── notification/       # Handles system alerts and messages
│   ├── search/             # Handles Elasticsearch integration
│   └── vendor-management/  # Handles vendor profiles and data
├── docker-compose.yml      # Docker orchestration file for all services
├── README.md               # Initialization and usage instructions
└── STRUCTURE.md            # This file
```

---

## Detailed Directory Breakdown

### 1. `frontend/web/`
This directory contains the main web application built using Vue 3, Vite, and TypeScript.
- `src/`: Contains the actual source code of the frontend application.
  - Components, Views, Router, Store, etc.
- `public/`: Static assets like images and favicons that do not require building.
- `package.json`: Lists the frontend dependencies (like Vue, Vite, Tailwind) and scripts (like `dev`, `build`).
- `vite.config.ts`: Configuration file for the Vite build tool.
- `.env.example`: Template for environment variables (API URLs).

### 2. `services/`
The application logic is split into multiple independent Laravel microservices. Each directory here represents a full Laravel project with its own dependencies, database connection, and API routes.

#### General Structure of a Laravel Service
Inside each service (e.g., `services/vendor-management/`), you will find the standard Laravel structure:
- `app/`: Contains the core code of the service (Controllers, Models, Middleware).
- `config/`: Configuration files for the service.
- `database/`: Migrations and seeders specific to this service's database tables.
- `routes/`: API route definitions (`api.php`).
- `tests/`: Automated tests for the service.
- `Dockerfile`: The specific Docker configuration to build the PHP/Laravel environment for this service.
- `.env.example` / `.env`: Environment variables specific to this service (DB credentials, API keys).

#### Specific Services
- **`vendor-management/`**: Responsible for creating, updating, and managing vendor information.
- **`contract-management/`**: Responsible for the lifecycle of contracts, tying vendors to specific agreements.
- **`search/`**: A dedicated service that likely syncs data from other services to Elasticsearch for fast, global querying.
- **`notification/`**: A service that listens for events across the system and dispatches emails, in-app notifications, etc.

### 3. Root Files
- **`docker-compose.yml`**: The central orchestration file. It defines how all the microservices, the frontend, and the infrastructure (Databases, Redis, Elasticsearch) run together on a single network. It maps ports from the host machine to the containers.

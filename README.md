# CMS Capstone

Contract Management System. This project is built using a microservices architecture with Laravel for the backend services and Vue 3 (Vite) for the frontend.

## Prerequisites

Before you begin, ensure you have the following installed on your machine:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Project Architecture

The application is composed of several independent services coordinated via Docker Compose:

### Frontend
- **web**: Vue 3 + Vite frontend application (Port 5173).

### Backend Services (Laravel)
- **vendor-management**: Manages vendor-related data (Port 8001).
- **contract-management**: Manages contract-related data (Port 8002).
- **search**: Handles global search using Elasticsearch (Port 8003).
- **notification**: Handles system notifications (Port 8004).

### Databases & Supporting Infrastructure
- **mysql**: Primary relational database for services (Port 3307).
- **mongodb**: NoSQL database for unstructured or high-volume data (Port 27017).
- **redis**: In-memory data store for caching and queues (Port 6379).
- **elasticsearch**: Search and analytics engine (Port 9200).

---

## 🚀 Getting Started & Initialization

Follow these steps to initialize and run the entire system.

### 1. Environment Setup

First, you need to set up the environment variables for each backend service. Open your terminal in the project root and copy the `.env.example` file to `.env` for all services:

```bash
# Vendor Management Service
cp services/vendor-management/.env.example services/vendor-management/.env

# Contract Management Service
cp services/contract-management/.env.example services/contract-management/.env

# Search Service
cp services/search/.env.example services/search/.env

# Notification Service
cp services/notification/.env.example services/notification/.env

# Frontend (Vue)
cp frontend/web/.env.example frontend/web/.env
```

### ⚠️ Important: Docker Database Configuration
In your `.env` files for the backend services, you **must** use the following settings to connect to the Docker MySQL instance:

- `DB_HOST=mysql` (This is the name of the service in `docker-compose.yml`)
- `DB_PORT=3306`
- `DB_DATABASE=contracts`
- `DB_USERNAME=root`
- `DB_PASSWORD=secret`

*Do not use `127.0.0.1` as the `DB_HOST` when running inside Docker.*

*Note: The frontend (`frontend/web`) currently runs without a `.env` file, but if you add one later, follow a similar process.*

### 2. Build and Start the Containers

Run the following command to build the Docker images and start the containers in detached mode:

```bash
docker-compose up -d --build
```

*(This step may take several minutes the first time as it downloads base images and installs dependencies).*

### 3. Initialize Laravel Services

Because the host directories are mounted as volumes, you need to run Composer to install dependencies and initialize Laravel specific configurations **inside** each container.

Run the following commands one by one to initialize all services:

#### Initialize `vendor-management`:
```bash
docker-compose exec vendor-management composer install
docker-compose exec vendor-management php artisan key:generate
docker-compose exec vendor-management php artisan migrate
```

#### Initialize `contract-management`:
```bash
docker-compose exec contract-management composer install
docker-compose exec contract-management php artisan key:generate
docker-compose exec contract-management php artisan migrate
```

#### Initialize `search`:
```bash
docker-compose exec search composer install
docker-compose exec search php artisan key:generate
docker-compose exec search php artisan migrate
```

#### Initialize `notification`:
```bash
docker-compose exec notification composer install
docker-compose exec notification php artisan key:generate
docker-compose exec notification php artisan migrate
```

#### Initialize `frontend`:
The frontend container automatically runs `npm install` and `npm run dev` on startup, but you should ensure the `.env` is created:
```bash
cp frontend/web/.env.example frontend/web/.env
```

### 4. Access the Application

Once everything is up and running, you can access the application at:

- **Frontend Application**: [http://localhost:5173](http://localhost:5173)
- **Vendor API**: [http://localhost:8001](http://localhost:8001)
- **Contract API**: [http://localhost:8002](http://localhost:8002)
- **Search API**: [http://localhost:8003](http://localhost:8003)
- **Notification API**: [http://localhost:8004](http://localhost:8004)

---

## 🛠️ Common Docker Commands

Here are some helpful commands for managing your project containers:

**Stop all containers:**
```bash
docker-compose down
```

**View logs for all services:**
```bash
docker-compose logs -f
```

**View logs for a specific service (e.g., frontend):**
```bash
docker-compose logs -f web
```

**Access a container's shell (e.g., vendor-management):**
```bash
docker-compose exec vendor-management bash
```

**Run a single artisan command (e.g., clear cache):**
```bash
docker-compose exec vendor-management php artisan cache:clear
```

---

## 🛠️ Troubleshooting

### ❌ `SQLSTATE[HY000] [2002] Connection refused`
If you see this error when running migrations, it is usually caused by one of two things:

1.  **Incorrect DB_HOST**: Ensure your `.env` file has `DB_HOST=mysql`. When running inside Docker, services refer to each other by their service names, not `127.0.0.1`.
2.  **MySQL is not ready**: MySQL takes about 10-15 seconds to fully initialize the first time the container starts. Even if the container is "Running", the database engine might still be starting up. 
    - **Fix**: Wait 20 seconds and try the command again.
    - **Check Logs**: Run `docker-compose logs -f mysql` to see if the database is ready for connections.

### ❌ `Access denied for user 'root'@'%'`
Ensure your `DB_PASSWORD` in `.env` matches the `MYSQL_ROOT_PASSWORD` in `docker-compose.yml` (default is `secret`).

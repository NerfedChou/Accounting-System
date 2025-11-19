# Accounting System

A modern accounting system built with PHP, MySQL, and Nginx running on Docker.

## 🏗️ Architecture

This project uses a LEMP stack (Linux, Nginx, MySQL, PHP) containerized with Docker:

- **Nginx**: Web server (Port 8080)
- **PHP 8.2-FPM**: Application runtime with essential extensions
- **MySQL 8.0**: Database server (Port 3306)
- **phpMyAdmin**: Database management interface (Port 8081)

## 📋 Prerequisites

- Docker (20.10+)
- Docker Compose (2.0+)

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd Accounting
```

### 2. Create environment file

```bash
cp .env.example .env
```

### 3. Start the Docker containers

```bash
docker-compose up -d
```

### 4. Verify the installation

Open your browser and navigate to:
- **Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

### 5. Access phpMyAdmin

- **Server**: mysql
- **Username**: accounting_user
- **Password**: accounting_pass

---

## 📚 Documentation

### Main Documentation
- **[Admin Portal Guide](ADMIN.md)** - Complete admin portal documentation
- **[Admin Quick Reference](ADMIN-QUICK-REFERENCE.md)** - Fast lookup guide
- **[Documentation Index](DOCUMENTATION-INDEX.md)** - All documentation files

### Technical Documentation
- **[System Architecture](md/SelfPrompt-UPDATED.md)** - Current system state & design
- **[Database Schema](md/DATABASE-UPDATED.md)** - Complete database documentation
- **[API Endpoints](ADMIN.md#api-endpoints)** - All API endpoints with examples

### Visual Diagrams 🎨
- **[System Visual Diagrams](SYSTEM-VISUAL-DIAGRAMS.md)** - Complete use case, ERD & flow charts (Mermaid)
- **[Interactive Diagrams](system-diagrams.html)** - Open in browser for interactive visualization
- **[ERD Comprehensive](FINAL-ERD-COMPREHENSIVE.md)** - Detailed entity relationship diagram
- **[System Flowchart](FINAL-SYSTEM-FLOWCHART.md)** - Complete system workflow diagrams

---

## 🔐 Default Credentials

### Admin Portal
- **URL:** http://localhost:8080/admin/login.html
- **Username:** admin
- **Password:** admin

### Tenant Portal
- **URL:** http://localhost:8080/tenant/login.html
- **Username:** demo
- **Password:** demo

---

## ✨ Key Features

### Admin Portal
- ✅ Complete company management (CRUD)
- ✅ Tenant lifecycle management
- ✅ Transaction voiding capability
- ✅ Activity logs with export to CSV
- ✅ Real-time statistics dashboard
- ✅ Profile & settings management

### Tenant Portal
- ✅ Interactive dashboard with charts
- ✅ Chart of accounts (view-only)
- ✅ Transaction creation (double-entry)
- ✅ Financial reports (Balance Sheet, Income Statement)
- ✅ Company profile management
- ✅ User settings

### System Features
- ✅ Double-entry accounting
- ✅ Multi-company support
- ✅ Role-based access control
- ✅ Activity logging & audit trail
- ✅ Session security with real-time validation
- ✅ Professional UI/UX

---

Or use root credentials:
- **Username**: root
- **Password**: root_password

## 🛠️ Docker Commands

### Start containers
```bash
docker-compose up -d
```

### Stop containers
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f
```

### Rebuild containers (after configuration changes)
```bash
docker-compose up -d --build
```

### Access PHP container shell
```bash
docker exec -it accounting_php sh
```

### Access MySQL container shell
```bash
docker exec -it accounting_mysql mysql -u root -p
```

## 📁 Project Structure

```
Accounting/
├── docker/
│   ├── nginx/
│   │   └── default.conf        # Nginx configuration
│   ├── php/
│   │   ├── Dockerfile          # PHP-FPM Dockerfile
│   │   └── php.ini             # PHP configuration
│   └── mysql/
│       └── init/               # Database initialization scripts
├── src/
│   └── index.php               # Application entry point
├── docker-compose.yml          # Docker orchestration
├── .env.example                # Environment variables template
├── .gitignore
└── README.md
```

## 🗄️ Database

### Connection Details

- **Host**: mysql (from within Docker network) or localhost (from host machine)
- **Port**: 3306
- **Database**: accounting_db
- **User**: accounting_user
- **Password**: accounting_pass

### Connect from PHP

```php
<?php
$pdo = new PDO(
    'mysql:host=mysql;dbname=accounting_db',
    'accounting_user',
    'accounting_pass'
);
?>
```

## 📦 Installed PHP Extensions

- PDO & PDO_MySQL
- MySQLi
- MBString
- XML
- GD
- BCMath
- PCNTL
- EXIF

## 🔧 Configuration

### Change Ports

Edit `docker-compose.yml` to modify exposed ports:

```yaml
nginx:
  ports:
    - "YOUR_PORT:80"
```

### Update PHP Settings

Modify `docker/php/php.ini` and rebuild containers:

```bash
docker-compose up -d --build
```

### Add Database Initialization Scripts

Place `.sql` files in `docker/mysql/init/` directory. They will be executed automatically when the MySQL container is first created.

## 🎯 Next Steps

1. ✅ Docker environment setup complete
2. ✅ Design database schema (see DATABASE.md)
3. 🔄 Implement database tables (in progress)
4. 💼 Build accounting features
5. 🔒 Add authentication & authorization
6. 📈 Develop reporting features

## 📚 Additional Documentation

- **DATABASE.md** - Complete database schema with all tables and relationships
- **DATABASE-APPROACH.md** - Detailed explanation of design decisions and rationale
- **DATABASE-VISUAL.md** - Quick reference with visual diagrams and examples
- **TODO.md** - Comprehensive development roadmap and task breakdown
- **Reference.md** - Project requirements and guidelines

## 🤝 Development Workflow

1. Make changes to files in the `src/` directory
2. Refresh your browser (changes are reflected immediately)
3. For Docker configuration changes, rebuild containers
4. Use phpMyAdmin for database management

## 📝 License

[Free to use and modify for educational purposes.]

## 👨‍💻 Author

[Jan Rhian Angulo]


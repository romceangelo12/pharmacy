# JVGH Pharmacy Inventory System (React + PHP/MySQL)

A modern, responsive, and secure Pharmacy Inventory Management System built with **React JS** (frontend) and **PHP / MySQL** (backend REST API).

---

## 🚀 Running the Project

You have two convenient ways to run the application:

### Option 1: Direct in XAMPP (Production Build)
1. Open your **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open your browser and go directly to:
   ```text
   http://localhost/pharmacy/
   ```
*(The pre-compiled React build in `dist/` is automatically served by Apache!)*

---

### Option 2: Live React Development Mode (Vite + Hot Module Reload)
1. Open a terminal or PowerShell in `c:\xampp\htdocs\pharmacy`.
2. Start the Vite React development server:
   ```powershell
   npm run dev
   ```
3. Open your browser at:
   ```text
   http://localhost:3000/
   ```
*(Vite proxies all API requests automatically to your local XAMPP backend).*

---

## 🔑 Default Accounts

| Role | Username | Password |
|---|---|---|
| **Administrator** | `admin` | `admin123` |
| **Pharmacist** | `pharmacist` | `pharma123` |

---

## 🛠️ Project Structure

```text
c:\xampp\htdocs\pharmacy\
├── src/                        # Modular React JSX Source Code
│   ├── components/
│   │   ├── Login.jsx           # Clean JVGH Pharmacy Login card
│   │   ├── Register.jsx        # User Registration
│   │   ├── Dashboard.jsx       # Inventory KPI metrics & real-time search
│   │   └── Sidebar.jsx         # Modern dark sidebar navigation
│   ├── App.jsx                 # Root React application & state manager
│   └── main.jsx                # React DOM mount point
├── dist/                       # Vite Production Build
├── api/                        # Backend REST APIs (JSON)
│   ├── auth.php                # Login, register, session verification, logout
│   └── inventory.php           # Real-time stock counters, alerts, search
├── assets/css/style.css        # Modern pharmacy stylesheet
├── config.php                  # PDO database configuration
├── database.sql                # MySQL schema and seed data
├── install.php                 # 1-Click Database Setup Wizard
├── package.json                # React & Vite dependencies
└── vite.config.js              # Vite bundler & reverse proxy configuration
```

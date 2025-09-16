## Bazario - E-commerce Multivendor

A simple multivendor e-commerce application built with PHP (procedural + mysqli), Bootstrap, and plain JS.

### Prerequisites
- PHP 8+
- MySQL/MariaDB
- Web server (XAMPP/WAMP/LAMP or Apache/Nginx)
- Git

### Local Setup
1. Clone the repository into your web root (e.g., `htdocs` in XAMPP):
   ```bash
   git clone <your-repo-url> ecommerce_multivendor
   ```
2. Create database and import schema/data as needed.
3. Copy config and set credentials:
   ```
   cp config/database.example.php config/database.php
   ```
   Edit `config/database.php` with your DB user, password, and database name.
4. Start your server and visit:
   - Front store: `http://localhost/ecommerce_multivendor/store.php`
   - Admin: `http://localhost/ecommerce_multivendor/admin/dashboard.php`

### Database SQL dump
- Place your SQL dump at `database/ecommerce_multivendor.sql` (or any name inside the `database/` folder).
- Import via phpMyAdmin:
  - Open phpMyAdmin → create database `ecommerce_multivendor` → Import → select the `.sql` file → Go.
- Or via CLI:
  ```bash
  mysql -u <user> -p ecommerce_multivendor < database/ecommerce_multivendor.sql
  ```
  Replace `<user>` with your DB user. You’ll be prompted for the password.

### Deployment to GitHub
From the project folder:
```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/<your-username>/<your-repo>.git
git push -u origin main
```

### Notes
- Sensitive config `config/database.php` is ignored by `.gitignore`. Commit `config/database.example.php` instead.
- `assets/uploads/` is excluded; a `.gitkeep` placeholder keeps the folder structure.

### License
MIT



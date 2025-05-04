# 🗳️ iVoting System

A modern, real-time online voting system built with **PHP** and **MySQL**. It features a clean UI, live vote updates, admin management, and secure voting mechanics.

---

## 🚀 Features

- ✅ Voter can vote directly from the main page (`index`)
- 🔐 Admin panel for managing candidates, positions, and viewing live results
- ⚡ AJAX-based interactions (fast, no page reloads)
- 💾 Database setup included
- 📱 Fully responsive design

---

## 📁 Project Structure

```
iVoting/
├── assets/           # CSS, JS, images
├── database/         # SQL file for DB setup
├── admin/            # Admin dashboard and management
├── index.php         # Main voting interface for users
└── api/              # apis backend PHP files
└── includes/         # Other backend PHP files
```

---

## 🛠️ How to Set Up

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Muindi6602/iVoting.git
   cd iVoting
   ```

2. **Import the Database**
   - Open **phpMyAdmin**
   - Create a new database (`evoting_system`)
   - Import the SQL file from database folder:  
     `database/evoting_system.sql`


## 🗳️ How Voting Works

- Open `index` in your browser.
- Choose your candidates for each position.
- Click "Vote" to submit.
- Votes are recorded instantly and reflected in real-time (AJAX-powered).

---

## 🔐 Admin Panel

- URL: `admin/index`
- **Login Credentials** (default):
  ```
  Username: admin
  Password: 12345
  ```
> ⚠️ Change the default password after first login for security.

---

## ✨ Technologies Used

- PHP (Latest)
- JavaScript
- MySQL (with PDO)
- jQuery (AJAX)
- SweetAlert2
- HTML5/CSS (Responsive)

---

## 📄 License

This project is open-source and free to use.

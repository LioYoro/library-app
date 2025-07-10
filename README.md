Here's a version tailored for your `library-app` project, with steps to import the database and set up everything locally:

---

# 📚 Library App

A student-friendly library system with book viewing, recommendations, likes/dislikes, and a basic feedback mechanism. Built with **PHP**, **MySQL**, and a lightweight recommendation logic based on user activity.

---

## 🚀 Features

* Book listing, detail viewing, and category browsing
* Smart recommendations:

  * Based on last viewed book
  * Trending by category
  * Other works by the same author
* Like/Dislike feedback with session-based tracking
* Clean and responsive UI for desktop
* Built for XAMPP or similar localhost environments

---

## 🔧 Installation Guide (Local / XAMPP)

### 1. 📦 Clone the Repository

```bash
git clone https://github.com/LioYoro/library-app.git
cd library-app
```

---

### 2. 💻 Move to XAMPP `htdocs`

Place the entire folder inside your XAMPP `htdocs` directory:

```
E:\XAMPP\htdocs\library-app
```

---

### 3. 🗃️ Import the MySQL Database

1. Open **phpMyAdmin**:
   [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

2. Create a new database:
   `library_test_db`

3. Click **Import**, then select the SQL file:

   ```
   library-app/library_test_db.sql
   ```

---

### 4. 🌐 Access the App in Browser

Open in your browser:

```
http://localhost/library-app/index.php
```

---

## 📁 Folder Structure

```
library-app/
├── assets/                 # CSS/JS/Images  
├── models/                # BookModel class  
├── views/                 # PHP views (header, footer, book_detail, etc.)  
├── library_test_db.sql    # ✅ Import this in phpMyAdmin  
├── index.php              # Homepage entry  
└── README.md
```

---

## ⚠️ Notes

* This app **does not use login accounts**, but session-based tracking (e.g., last viewed, feedback).
* Ensure `PDO` is enabled in your PHP configuration (`php.ini`)
* Don't expose this app publicly with real data unless security measures are added.

---

by [LioYoro](https://github.com/LioYoro)


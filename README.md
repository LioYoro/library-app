---

# 📚 Library App

A student-friendly library system with book viewing, recommendations, likes/dislikes, reservations, and profile editing. Built with **PHP**, **MySQL**, and a lightweight recommendation logic based on user activity.

---

## 🚀 Features

* Book listing, detail viewing, and category browsing
* Smart recommendations:

  * Based on last viewed book
  * Trending by category
  * Other works by the same author
  * Major/strand-based suggestions
* Like/Dislike feedback with session-based tracking
* Profile editing with OTP confirmation and profile picture upload
* Book reservation system with email notifications
* Clean and responsive UI for desktop
* Built for XAMPP or similar localhost environments

---

## 🔧 Installation Guide (Local / XAMPP)

### 1. 📦 Clone the Repository / CODE - DOWNLOAD ZIP

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

2. Create a database (if not yet created):
   `library_test_db`

3. **When updating database**:

   * Go to your `library_test_db` in phpMyAdmin
   * **Drop all existing tables**
   * Re-import the **latest** SQL file (ask whoever has the most updated copy):

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
├── admin/                # Admin-side functions and tools
├── assets/               # Images (e.g., default profile picture)
├── book_reservation/     # Book reservation (user + admin functions)
├── comments/             # Commenting system
├── css/                  # Stylesheets
├── data/                 # .pkl and CSV files (recommender data)
├── debug/                # Test files for debugging recommender/intuitive
├── includes/             # Database connection, PHPMailer, reservation mailer
├── js/                   # JavaScript files
├── login/                # Login and registration pages
├── models/               # Book model (search logic by Abet)
├── profile/              # Profile editing (with OTP + uploads)
├── recommender_service/  # Python microservice for recommendations
├── static/               # JSON files
├── uploads/              # User profile picture uploads
├── vendor/               # PHPMailer, PhpSpreadsheet dependencies
├── views/                # Shared PHP views (home, header, footer, search, book detail, results)
│
├── ask.php               # UI for Intuitive Q&A bot
├── book.php              # Connects to Abet’s book model
├── index.php             # Homepage entry
├── requirements.txt      # Python dependencies for recommender service
└── README.md             # Project documentation
```

---

## ⚠️ Notes

* Put the **.env** file inside the `recommender_service/` folder (get from Drive).
* The `.env` in this repo has no values.
* The **Verify OTP password** is also in Drive.

---

## ⚙️ Running the Python Recommender Service

### First-time setup (only once):

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Every time you need to run it:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
venv\Scripts\activate
cd recommender_service
python app.py
```

----------------------------------------------------
## ✅ Standard Workflow
----------------------------------------------------

### 1. Make sure you’re on the latest version (always pull first)

```git pull origin main
```

### 2. Stage your changes (after coding/editing files)
```
git add .
```
### 3. Commit your work
```
git commit -m "Describe what you changed here"
```
### 4. Push to GitHub
```
git push origin main
```

----------------------------------------------------
## 🚩 Common Issues & Fixes
----------------------------------------------------

### 1. Accidentally committed venv/ or big files (GitHub rejects files over 100 MB)
```
git rm -r --cached venv
echo "venv/" >> .gitignore
git add .gitignore
git commit -m "Remove venv from tracking"
git push origin main
```

### 2. “non-fast-forward” error when pushing (someone else pushed first)
```
git pull origin main --rebase
git push origin main
```
### 3. Force push (ONLY if rewriting history, e.g. cleaning venv/big files)
❌ Do NOT use for normal updates
```
git push origin main --force
```

## ⚙️ Running the Python Recommender Service

### First-time setup (only once):

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Every time you need to run it:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
venv\Scripts\activate
cd recommender_service
python app.py
```

Then proceed to **Step 4 (open in browser)**.

---

## 🍼 Git Guide for Groupmates

### If you ALREADY cloned the repo before

1. **Open Terminal in project folder**

   ```bash
   git status
   git pull origin main
   ```

   This updates your local project.

---

### If you NEVER cloned the repo before

```bash
cd htdocs
git clone https://github.com/LioYoro/library-app.git
```

---

## 🌿 Branching Workflow (FOR NAITHAN, GAB, ABET, ETC.)

> 🔑 **Important:** Only the repo owner (Lio) merges to `main`.
> Others must create branches → push → open Pull Requests.

1. **Create and switch to a new branch**

   ```bash
   git checkout -b yourname-feature
   ```

   Examples:

   ```bash
   git checkout -b naithan-ui-update
   git checkout -b gab-recommendations
   git checkout -b abet-bookmodel-fix
   ```

2. **Do your edits** (code changes, commits).

   ```bash
   git add .
   git commit -m "Added new feature X"
   ```

3. **Push your branch to GitHub**

   ```bash
   git push origin yourname-feature
   ```

4. **On GitHub → Open a Pull Request (PR)**

   * Go to [Library App Repo](https://github.com/LioYoro/library-app)
   * Click **Compare & pull request**
   * Assign to **LioYoro** for review
   * Wait for approval before merging

---

## 🏷️ Tags (Optional: for milestones)

To mark a stable version:

```bash
git tag v1.0
git push origin v1.0
```

This creates a tagged version in GitHub releases.

---

by [LioYoro](https://github.com/LioYoro)

---

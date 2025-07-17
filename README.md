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

* LAGAY NIYO YUNG .ENV SA RECOMMENDER SERVICE FOLDER, upload ko sa drive
* YUNG ENV NA NANDITO WALANG LAMAN
* YUNG PASSWORD SA VERIFY OTP NASA DRIVE DIN

## ⚠️ RUN NIYO SA TERMINAL NG VSCODE ONE BY ONE

- Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
- python -m venv venv
- venv\Scripts\activate
- pip install -r requirements.txt

## ⚠️ PAG OKAY NA, ITO NAMAN

- cd recommender_service
- python app.py
- then Step 4

## ⚠️ IF YOU WILL OPEN FROM SCRATCH (If installation is done)

- Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
- venv\Scripts\activate
- cd recommender_service
- python app.py
- then Step 4

---

Here’s a **super baby-step Git guide** for your groupmates to update the project **using Git**, assuming they already cloned the repository before:

---

### 🍼 **IF THEY ALREADY CLONED THE PROJECT BEFORE (Git installed)**

#### ✅ 1. **Open Git Bash / Terminal**

* Go to the folder of the project (`ark-library`)
* Right-click inside the folder > “Git Bash Here”
  *(Or open Terminal and `cd` into the project folder)*

---

#### ✅ 2. **Check for changes first (optional)**

```bash
git status
```

> 🔎 This will show any files they changed locally that might be overwritten.

---

#### ✅ 3. **Pull the latest update from GitHub**

```bash
git pull origin main
```

> ✅ This downloads the latest version of the project from your GitHub and replaces old files.

---

#### ✅ 4. **Done! They do NOT need to reinstall requirements**

* The `venv/` folder or `requirements.txt` didn’t change, so **no need to run `pip install` again**.
* They can now test the new version normally in their localhost.

---

### 🛠 Optional: If they didn’t clone before

If they **never cloned the GitHub repo**, here’s what they do instead:

#### 🔹 1. **Open Git Bash or Terminal**

```bash
cd htdocs
git clone https://github.com/your-username/ark-library.git
```

Replace with your actual repo link.

---


by [LioYoro](https://github.com/LioYoro)


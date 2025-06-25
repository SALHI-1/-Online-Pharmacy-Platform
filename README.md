# 💊 Online Pharmacy Platform

This is a Symfony-based web application that allows customers to order medications online without needing to visit the physical pharmacy. It includes a full **administration dashboard** that manages medicines and handles both **normal** and **special orders** (which require a medical certificate). Email notifications are sent automatically to clients upon special order decisions.

---

## 🌟 Key Features

### 👥 Customer Side
- Account registration and login
- Browse available medications
- Place two types of orders:
  - ✅ **Normal Order**: for over-the-counter medicines
  - ⚠️ **Special Order**: requires uploading a medical certificate
- Track order status and history

### 🧑‍⚕️ Admin Panel
- Manage medication inventory (add, update, delete)
- Handle normal and special orders
- Review and accept/reject **special orders**
- Send automatic **email notifications** to clients with delivery date info

---

## 🛠️ Tech Stack

- **Framework**: Symfony
- **Database**: MySQL
- **Frontend**: TailwindCSS + Twig
- **Tools**: Composer, Doctrine ORM, Symfony Mailer

---

## 🧩 Database Structure

The main tables used:
- `admin`: administrator accounts
- `client`: customer accounts
- `medicament`: list of available drugs
- `commande`: customer orders
- `commande_spec`: special orders with medical certificate
- `messenger_messages`: used for async email sending

> You can view the full schema in [`pharmacie1.sql`](pharmacie1.sql)

---

## ⚙️ Installation Guide

### 1. Clone the Repository
git clone https://github.com/SALHI-1/-Online-Pharmacy-Platform.git
cd Online-Pharmacy-Platform

### 2. Install Dependencies
composer install

### 3. Configure Environment Variables
DATABASE_URL="mysql://username:password@127.0.0.1:3306/pharmacie1"
MAILER_DSN=smtp://your-smtp-provider

### 4.  Import the Database
mysql -u root -p pharmacie1 < pharmacie1.sql

### 5.  Import the Database
symfony server:start

## 📬 Email Notifications
The application uses Symfony Messenger and Mailer to send automatic emails when a special order is reviewed.

To use it:

### Make sure MAILER_DSN is configured correctly in .env.local

### Example:

MAILER_DSN=smtp://smtp.mailtrap.io:2525?encryption=tls&auth_mode=login&username=your_user&password=your_password

## 👤 Author
[Send me an email](https://mail.google.com/mail/?view=cm&fs=1&to=mohammedsalhisam@gmail.com&su=Hello)




## 📸Screenshots : 
![Client - Homepage](public/images/Client_Home.jpg)
![Client - Storepage](public/images/Client_Store.jpg)
![Client - Shop](public/images/Client_shop.jpg)
![Admin - Medicament management](public/images/Admin_Medicaments.jpg)
![Admin - Login ](public/images/Admin_Login.jpg)





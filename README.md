# School Book All

A simple school library system built with plain PHP (PDO) and MySQL.
No frameworks. It has two kinds of accounts:

- **Admin** manages books and students and oversees all borrow records.
- **User** registers their own account, browses books, borrows and returns
  books, and sees only their own borrowed books.

## Roles

When someone registers on `register.php`, the app creates two linked rows:
a `users` row (with `role = 'user'`) so they can log in, and a `students`
row (with `user_id` pointing back at the account) so they can borrow books
as themselves. The admin account is seeded in the SQL script with
`role = 'admin'`.

| Action                         | Admin | User            |
|--------------------------------|-------|-----------------|
| Browse / search books          | Yes   | Yes             |
| Add / edit / delete books      | Yes   | No              |
| Manage students                | Yes   | No              |
| Borrow a book                  | Yes (for any student) | Yes (as self) |
| Return a book                  | Yes (any)             | Yes (own only) |
| See borrow records             | All   | Own only        |

## Features

- Login / register / logout (PHP sessions).
- Two roles (admin and user) with different menus and permissions.
- Books: add, list (with search + pagination), view, edit, delete.
- Students: add, list, edit, delete.
- Borrow a book (lowers quantity by 1) and return it (raises quantity by 1).
- Users borrow as themselves; admins can borrow for any student.
- Overdue books are highlighted in red on the Borrowed page.
- All queries use PDO prepared statements (safe from SQL injection).
- All output is escaped with `htmlspecialchars()` (safe from XSS).
- Friendly error messages instead of blank pages or PHP errors.

## Requirements

- XAMPP (Apache + MySQL + PHP) or Laragon.
- A web browser.

## Setup with XAMPP

1. **Install and start XAMPP.** Open the XAMPP Control Panel and click
   **Start** for **Apache** and **MySQL**.

2. **Copy the project into the web folder.**
   Place the whole `school-book-management` folder inside XAMPP's `htdocs`:

   ```
   C:\xampp\htdocs\school-book-management
   ```

3. **Create the database.**
   - Open your browser and go to `http://localhost/phpmyadmin`.
   - Click the **Import** tab.
   - Choose the file `database/school_books_db.sql` from this project.
   - Click **Go / Import**.
   - This creates the `school_books_db` database, all tables, and sample data.

4. **Open the app.**
   Go to:

   ```
   http://localhost/school-book-management/login.php
   ```

5. **Log in as admin, or register a user.**
   - Admin account &rarr; username: `admin`, password: `admin123`
   - New users can click **Create one here** on the login page to register
     their own account (`register.php`).

> **Important:** `admin123` is only a default password for testing.
> Change it as soon as possible (see below).

## Setup with Laragon

1. Start Laragon (Apache/Nginx + MySQL).
2. Put the `school-book-management` folder inside Laragon's `www` folder.
3. Import `database/school_books_db.sql` using phpMyAdmin or HeidiSQL.
4. Visit `http://localhost/school-book-management/login.php` and log in.

## Database Connection Settings

The connection is in `config/connect.php`. The default XAMPP values are:

- host: `localhost`
- database: `school_books_db`
- username: `root`
- password: `` (empty)

If your MySQL uses a different username or password, edit that file.

## Changing the Default Admin Password

The stored password is **hashed** with bcrypt, so you cannot just type a new
one into the database. To change it:

1. Create a small PHP file (for example `makehash.php`) in the project:

   ```php
   <?php
   echo password_hash('your-new-password', PASSWORD_DEFAULT);
   ?>
   ```

2. Open it in the browser, copy the long hash it prints.
3. In phpMyAdmin, edit the `users` table row for `admin` and paste the hash
   into the `password` column.
4. Delete `makehash.php` afterwards.

## Project Structure

```
school-book-management/
├── config/connect.php          PDO database connection
├── includes/
│   ├── auth.php                session check (redirects to login)
│   ├── header.php              HTML head + navbar
│   └── footer.php              closing tags
├── books/
│   ├── index.php               list + search + pagination
│   ├── create.php              add a book
│   ├── edit.php                edit a book
│   ├── delete.php              delete a book (POST only)
│   └── view.php                view one book
├── students/
│   ├── index.php               list students
│   ├── create.php              add a student
│   ├── edit.php                edit a student
│   └── delete.php              delete a student (POST only)
├── borrowing/
│   ├── borrow.php              borrow a book
│   ├── return.php              return a book (POST only)
│   └── borrowed.php            list all borrow records
├── assets/css/style.css        simple stylesheet
├── database/school_books_db.sql  schema + sample data
├── login.php                   login form
├── logout.php                  destroys the session
├── index.php                   dashboard with counts
└── README.md
```

## How Each Part Works

### Login / Register / Logout
`login.php` looks up the username with a prepared statement and checks the
password with `password_verify()`. On success it stores `user_id`,
`username`, and `role` in the session. `register.php` lets a new user create
an account: it hashes the password with `password_hash()` and, in a single
transaction, inserts a `users` row (`role = 'user'`) plus a linked `students`
row. `logout.php` clears and destroys the session.

`includes/auth.php` is required at the top of every protected page. It
redirects to `login.php` if no one is logged in, and it provides
`is_admin()` and `require_admin()`. Admin-only pages call `require_admin()`,
which bounces regular users back to the dashboard.

### Books CRUD
- **Create** (`books/create.php`, admin only): validates that title and author
  are filled and quantity is a number 0 or more, then runs an `INSERT`. A
  duplicate ISBN is caught and shown as a friendly message.
- **Read** (`books/index.php`): everyone can list books 10 per page with
  Previous/Next links and search by title or author. Admins see Edit/Delete
  buttons; users see a Borrow button. `books/view.php` shows a single book.
- **Update** (`books/edit.php`, admin only): loads the current values,
  validates, then runs an `UPDATE`.
- **Delete** (`books/delete.php`, admin only): POST only, asks for JS
  confirmation, and refuses to delete a book that still has active borrow
  records.

### Students CRUD (admin only)
Same pattern as books. `student_no` must be unique, and email (if given) is
validated. A student with active borrow records cannot be deleted. Note that
students created through user registration are linked to a login account via
`user_id`.

### Borrow / Return
- **Borrow** (`borrowing/borrow.php`): an admin picks any student and a book;
  a user borrows as themselves (no student picker). Due date defaults to 7
  days out. If the book has 0 copies, it refuses with a friendly message.
  Otherwise it inserts a `borrowed` record and reduces the book quantity by 1
  (in a transaction). From the books list, a user can click Borrow to prefill
  the chosen book.
- **Return** (`borrowing/return.php`): POST only. It marks the record as
  `returned`, sets `return_date` to today, and adds 1 back to the book
  quantity (in a transaction). A user may only return their own book; an admin
  may return any.
- **List** (`borrowing/borrowed.php`): admins see all records; users see only
  their own. It joins the three tables to show student name, book title, and
  dates. Records still borrowed and past their due date show a red **Overdue**
  label.

## Security Notes

- Every database query uses PDO prepared statements.
- Every value printed to the page is escaped with `htmlspecialchars()`.
- Deletes and returns use POST forms with a hidden id, not GET links.
- Passwords are stored as bcrypt hashes, never in plain text.

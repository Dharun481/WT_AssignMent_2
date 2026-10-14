# College Admin Student Records

Standalone PHP and MySQL pages for managing student records and their profile images and documents.

## Setup

1. Import `schema.sql` into MySQL.
2. Configure the connection using `DB_DSN`, `DB_USER`, and `DB_PASSWORD`. Defaults are `mysql:host=127.0.0.1;dbname=college_admin;charset=utf8mb4`, `root`, and an empty password.
3. Serve this directory with PHP 8.1+, PDO MySQL, Fileinfo, and mbstring enabled. For a local development server, run `php -S 127.0.0.1:8000` from this directory.
4. Ensure the web server can write to the `uploads` directory. The upload directory is created automatically when needed.

Uploaded files are limited to 5 MB each and accepted only when their detected MIME type is JPEG, PNG, WebP, or PDF. Profile image uploads accept image types only. Stored names are random, so a new upload never overwrites an existing file. File metadata is stored in `student_files` and tied to its student by a foreign key.

This starter does not provide login or authorization. Integrate the college's admin authentication and restrict access before deploying student details or identity documents. For production, also use a least-privilege MySQL account, HTTPS, and web-server rules equivalent to `uploads/.htaccess` if the server does not use Apache.

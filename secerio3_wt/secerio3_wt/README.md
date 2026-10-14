# Student Portal

A Jakarta Servlet/JSP application for authenticated viewing of student profiles and their uploaded images/documents. It targets Java 17 and a Servlet 6 container such as Tomcat 10.1.

## Configure and run

1. Create the database and tables with `database/schema.sql`.
2. Configure the application process environment:
   - `DB_URL`, for example `jdbc:mysql://localhost:3306/student_portal`
   - `DB_USER`
   - `DB_PASSWORD`
   - `UPLOAD_DIR`, an absolute directory containing uploaded files
3. Add portal user records with BCrypt hashes in `portal_users.password_hash`; do not store plaintext passwords. Signed-in users can add student profiles with an optional document up to 10 MB and delete student profiles. Uploaded files are displayed on the corresponding student profile. `student_files.stored_path` is a relative path beneath `UPLOAD_DIR` (for example, `students/42/report.pdf`).
4. Build with `mvn package` and deploy `target/student-portal.war` to Tomcat 10.1 or another Jakarta Servlet 6 container.
5. Open `/student-portal/login`.

The application does not include a user-registration endpoint. Seed accounts out-of-band with BCrypt hashes. File responses and student management are available only to authenticated sessions, and file paths are normalized and constrained to the configured upload directory. Deleting a student also deletes its file records and uploaded files. Session cookies are marked `HttpOnly`; enable the `Secure` cookie setting when serving the application over HTTPS.

## Endpoints

- `GET/POST /login`: sign in against `portal_users`
- `GET /portal`: display all student profiles and their associated files
- `GET/POST /students/add`: display and save a student profile (authenticated)
- `POST /students/upload`: upload a file to a student's profile (authenticated; 10 MB maximum)
- `POST /students/delete`: delete a student profile and its associated files (authenticated)
- `GET /student-file?id={fileId}`: stream a file from `UPLOAD_DIR`
- `POST /logout`: invalidate the current session

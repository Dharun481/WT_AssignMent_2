package com.example.portal;import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.MultipartConfig;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.Part;import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Types;
import java.util.UUID;
import java.util.regex.Pattern;@WebServlet("/students/add")
@MultipartConfig(
        fileSizeThreshold = 1024 * 1024,
        maxFileSize = 10L * 1024 * 1024,
        maxRequestSize = 11L * 1024 * 1024)
public class AddStudentServlet extends HttpServlet {
    private static final Pattern EMAIL_PATTERN =
            Pattern.compile("^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$");    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        request.getRequestDispatcher("/WEB-INF/views/add-student.jsp").forward(request, response);
    }    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        request.setCharacterEncoding("UTF-8");        Part document;
        try {
            document = request.getPart("document");
        } catch (IllegalStateException exception) {
            request.setAttribute("formError", "The document must be 10 MB or smaller.");
            request.getRequestDispatcher("/WEB-INF/views/add-student.jsp").forward(request, response);
            return;
        }        String fullName = trim(request.getParameter("fullName"));
        String email = trim(request.getParameter("email"));
        String gradeLevel = trim(request.getParameter("gradeLevel"));
        String profileText = trim(request.getParameter("profileText"));
        String originalName = document == null ? "" : safeOriginalName(document.getSubmittedFileName());
        if (!originalName.isEmpty() && document.getSize() == 0) {
            request.setAttribute("formError", "The selected document is empty. Choose another file.");
            request.getRequestDispatcher("/WEB-INF/views/add-student.jsp").forward(request, response);
            return;
        }
        if (originalName.length() > 255) {
            request.setAttribute("formError", "The document name must be 255 characters or fewer.");
            request.getRequestDispatcher("/WEB-INF/views/add-student.jsp").forward(request, response);
            return;
        }        String validationError = validate(fullName, email, gradeLevel, profileText);
        if (validationError != null) {
            request.setAttribute("formError", validationError);
            request.getRequestDispatcher("/WEB-INF/views/add-student.jsp").forward(request, response);
            return;
        }        Path storedFile = null;
        try (Connection connection = Database.getConnection();
             PreparedStatement statement = connection.prepareStatement(
                     "INSERT INTO students (full_name, email, grade_level, profile_text) VALUES (?, ?, ?, ?)",
                     PreparedStatement.RETURN_GENERATED_KEYS)) {
            connection.setAutoCommit(false);
            try {
                statement.setString(1, fullName);
                if (email.isEmpty()) {
                    statement.setNull(2, Types.VARCHAR);
                } else {
                    statement.setString(2, email);
                }
                statement.setString(3, gradeLevel);
                statement.setString(4, profileText);
                statement.executeUpdate();                long studentId;
                try (ResultSet keys = statement.getGeneratedKeys()) {
                    if (!keys.next()) {
                        throw new SQLException("The database did not return the new student id.");
                    }
                    studentId = keys.getLong(1);
                }                if (!originalName.isEmpty()) {
                    Path uploadRoot = Path.of(requiredSetting("UPLOAD_DIR"))
                            .toAbsolutePath().normalize();
                    Files.createDirectories(uploadRoot);
                    Path rootReal = uploadRoot.toRealPath();
                    Path relativePath = Path.of(
                            "students", Long.toString(studentId),
                            UUID.randomUUID() + extensionOf(originalName));
                    Path destination = rootReal.resolve(relativePath).normalize();
                    if (!destination.startsWith(rootReal)) {
                        throw new ServletException("Unable to store the uploaded student document.");
                    }
                    Files.createDirectories(destination.getParent());
                    if (!destination.getParent().toRealPath().startsWith(rootReal)) {
                        throw new ServletException("The student upload directory is outside UPLOAD_DIR.");
                    }
                    storedFile = destination;
                    try (var input = document.getInputStream()) {
                        Files.copy(input, destination);
                    }                    try (PreparedStatement fileStatement = connection.prepareStatement("""
                            INSERT INTO student_files
                                (student_id, original_name, stored_path, content_type)
                            VALUES (?, ?, ?, ?)
                            """)) {
                        fileStatement.setLong(1, studentId);
                        fileStatement.setString(2, originalName);
                        fileStatement.setString(3, relativePath.toString());
                        fileStatement.setString(4, safeContentType(document.getContentType()));
                        fileStatement.executeUpdate();
                    }
                }
                connection.commit();
            } catch (SQLException | IOException | RuntimeException | ServletException exception) {
                try {
                    connection.rollback();
                } catch (SQLException rollbackException) {
                    exception.addSuppressed(rollbackException);
                }
                if (storedFile != null) {
                    try {
                        Files.deleteIfExists(storedFile);
                    } catch (IOException cleanupException) {
                        exception.addSuppressed(cleanupException);
                    }
                }
                throw exception;
            }
        } catch (SQLException | IllegalStateException exception) {
            throw new ServletException("Unable to save the student profile.", exception);
        }        request.getSession(false).setAttribute("studentAdded", Boolean.TRUE);
        response.sendRedirect(request.getContextPath() + "/portal");
    }    private static String trim(String value) {
        return value == null ? "" : value.trim();
    }    private static String safeOriginalName(String submittedName) {
        if (submittedName == null) {
            return "";
        }
        String name = submittedName.replace('\\', '/');
        name = name.substring(name.lastIndexOf('/') + 1);
        return name.replaceAll("[\\p{Cntrl}]", "_").trim();
    }    private static String extensionOf(String name) {
        int dot = name.lastIndexOf('.');
        if (dot <= 0 || name.length() - dot > 16) {
            return "";
        }
        return name.substring(dot).matches("\\.[A-Za-z0-9]{1,15}") ? name.substring(dot) : "";
    }    private static String safeContentType(String contentType) {
        return contentType != null && contentType.length() <= 100
                && contentType.matches("[\\w.+-]+/[\\w.+-]+")
                ? contentType : "application/octet-stream";
    }    private static String requiredSetting(String name) throws ServletException {
        String value = System.getenv(name);
        if (value == null || value.isBlank()) {
            throw new ServletException("Required environment variable is not set: " + name);
        }
        return value;
    }    private static String validate(String fullName, String email, String gradeLevel, String profileText) {
        if (fullName.isEmpty()) {
            return "Enter the student's full name.";
        }
        if (fullName.length() > 200) {
            return "The student's name must be 200 characters or fewer.";
        }
        if (email.length() > 254 || (!email.isEmpty() && !EMAIL_PATTERN.matcher(email).matches())) {
            return "Enter a valid email address with no more than 254 characters.";
        }
        if (gradeLevel.length() > 50) {
            return "The course or year must be 50 characters or fewer.";
        }
        if (profileText.length() > 5000) {
            return "The introduction must be 5,000 characters or fewer.";
        }
        return null;
    }
}

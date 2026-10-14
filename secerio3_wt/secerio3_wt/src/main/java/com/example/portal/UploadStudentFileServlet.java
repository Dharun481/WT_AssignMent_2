package com.example.portal;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.MultipartConfig;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.Part;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.UUID;

@WebServlet("/students/upload")
@MultipartConfig(
        fileSizeThreshold = 1024 * 1024,
        maxFileSize = 10L * 1024 * 1024,
        maxRequestSize = 11L * 1024 * 1024)
public class UploadStudentFileServlet extends HttpServlet {
    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        Part document;
        try {
            document = request.getPart("document");
        } catch (IllegalStateException exception) {
            setNotice(request, "studentError",
                    "The file is too large. Choose a file that is 10 MB or smaller.");
            redirectToPortal(request, response);
            return;
        }

        long studentId = parseStudentId(request.getParameter("studentId"), response);
        if (studentId < 1) {
            return;
        }

        String originalName = safeOriginalName(document.getSubmittedFileName());
        if (originalName.isBlank() || document.getSize() == 0) {
            setNotice(request, "studentError", "Choose a file to upload.");
            redirectToPortal(request, response);
            return;
        }
        if (originalName.length() > 255) {
            setNotice(request, "studentError", "The file name must be 255 characters or fewer.");
            redirectToPortal(request, response);
            return;
        }

        Path uploadRoot = Path.of(requiredSetting("UPLOAD_DIR")).toAbsolutePath().normalize();
        Files.createDirectories(uploadRoot);
        Path rootReal = uploadRoot.toRealPath();
        String generatedName = UUID.randomUUID().toString();
        String extension = extensionOf(originalName);
        Path relativePath = Path.of("students", Long.toString(studentId), generatedName + extension);
        Path destination = rootReal.resolve(relativePath).normalize();
        if (!destination.startsWith(rootReal)) {
            throw new ServletException("Unable to store the uploaded student file.");
        }
        Files.createDirectories(destination.getParent());

        try (var input = document.getInputStream()) {
            Files.copy(input, destination);
        } catch (IOException exception) {
            try {
                Files.deleteIfExists(destination);
            } catch (IOException cleanupException) {
                exception.addSuppressed(cleanupException);
            }
            throw exception;
        }

        String sql = """
                INSERT INTO student_files (student_id, original_name, stored_path, content_type)
                VALUES (?, ?, ?, ?)
                """;
        try (Connection connection = Database.getConnection()) {
            connection.setAutoCommit(false);
            try {
                try (PreparedStatement lockStudent = connection.prepareStatement(
                        "SELECT id FROM students WHERE id = ? FOR UPDATE")) {
                    lockStudent.setLong(1, studentId);
                    try (ResultSet result = lockStudent.executeQuery()) {
                        if (!result.next()) {
                            connection.rollback();
                            Files.deleteIfExists(destination);
                            response.sendError(HttpServletResponse.SC_NOT_FOUND, "Student not found.");
                            return;
                        }
                    }
                }

                try (PreparedStatement statement = connection.prepareStatement(sql)) {
                    statement.setLong(1, studentId);
                    statement.setString(2, originalName);
                    statement.setString(3, relativePath.toString());
                    statement.setString(4, safeContentType(document.getContentType()));
                    statement.executeUpdate();
                }
                connection.commit();
            } catch (SQLException | RuntimeException exception) {
                connection.rollback();
                throw exception;
            }
        } catch (SQLException | RuntimeException exception) {
            try {
                Files.deleteIfExists(destination);
            } catch (IOException cleanupException) {
                exception.addSuppressed(cleanupException);
            }
            throw new ServletException("Unable to save the uploaded student file.", exception);
        }

        setNotice(request, "studentNotice", "Document uploaded successfully.");
        redirectToPortal(request, response);
    }

    private static long parseStudentId(String rawId, HttpServletResponse response) throws IOException {
        try {
            long id = Long.parseLong(rawId);
            if (id > 0) {
                return id;
            }
        } catch (NumberFormatException | NullPointerException ignored) {
            // Invalid identifiers are reported below.
        }
        response.sendError(HttpServletResponse.SC_BAD_REQUEST, "Invalid student id.");
        return -1;
    }

    private static String safeOriginalName(String submittedName) {
        if (submittedName == null) {
            return "";
        }
        String name = submittedName.replace('\\', '/');
        name = name.substring(name.lastIndexOf('/') + 1);
        return name.replaceAll("[\\p{Cntrl}]", "_").trim();
    }

    private static String extensionOf(String name) {
        int dot = name.lastIndexOf('.');
        if (dot <= 0 || name.length() - dot > 16) {
            return "";
        }
        return name.substring(dot).matches("\\.[A-Za-z0-9]{1,15}") ? name.substring(dot) : "";
    }

    private static String safeContentType(String contentType) {
        return contentType != null && contentType.length() <= 100
                && contentType.matches("[\\w.+-]+/[\\w.+-]+")
                ? contentType : "application/octet-stream";
    }

    private static String requiredSetting(String name) throws ServletException {
        String value = System.getenv(name);
        if (value == null || value.isBlank()) {
            throw new ServletException("Required environment variable is not set: " + name);
        }
        return value;
    }

    private static void setNotice(HttpServletRequest request, String key, String message) {
        request.getSession(false).setAttribute(key, message);
    }

    private static void redirectToPortal(HttpServletRequest request, HttpServletResponse response)
            throws IOException {
        response.sendRedirect(request.getContextPath() + "/portal");
    }
}

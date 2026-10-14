package com.example.portal;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

@WebServlet("/students/delete")
public class DeleteStudentServlet extends HttpServlet {
    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        long studentId = parseStudentId(request.getParameter("studentId"), response);
        if (studentId < 1) {
            return;
        }

        List<String> storedPaths = new ArrayList<>();
        try (Connection connection = Database.getConnection()) {
            connection.setAutoCommit(false);
            try {
                try (PreparedStatement lockStudent = connection.prepareStatement(
                        "SELECT id FROM students WHERE id = ? FOR UPDATE")) {
                    lockStudent.setLong(1, studentId);
                    try (ResultSet result = lockStudent.executeQuery()) {
                        if (!result.next()) {
                            connection.rollback();
                            response.sendError(HttpServletResponse.SC_NOT_FOUND, "Student not found.");
                            return;
                        }
                    }
                }
                try (PreparedStatement files = connection.prepareStatement(
                        "SELECT stored_path FROM student_files WHERE student_id = ?")) {
                    files.setLong(1, studentId);
                    try (ResultSet result = files.executeQuery()) {
                        while (result.next()) {
                            storedPaths.add(result.getString("stored_path"));
                        }
                    }
                }
                try (PreparedStatement delete = connection.prepareStatement(
                        "DELETE FROM students WHERE id = ?")) {
                    delete.setLong(1, studentId);
                    delete.executeUpdate();
                }
                connection.commit();
            } catch (SQLException exception) {
                connection.rollback();
                throw exception;
            }
        } catch (SQLException | IllegalStateException exception) {
            throw new ServletException("Unable to delete the student profile.", exception);
        }

        deleteStoredFiles(storedPaths);
        request.getSession(false).setAttribute("studentNotice", "Student profile deleted successfully.");
        response.sendRedirect(request.getContextPath() + "/portal");
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

    private static void deleteStoredFiles(List<String> storedPaths) throws ServletException, IOException {
        if (storedPaths.isEmpty()) {
            return;
        }
        Path root = Path.of(requiredSetting("UPLOAD_DIR")).toAbsolutePath().normalize();
        if (!Files.exists(root)) {
            return;
        }
        Path rootReal = root.toRealPath();
        for (String storedPath : storedPaths) {
            Path relativePath = Path.of(storedPath);
            if (relativePath.isAbsolute()) {
                throw new ServletException("A student file path is outside the upload directory.");
            }
            Path file = rootReal.resolve(relativePath).normalize();
            if (!file.startsWith(rootReal)) {
                throw new ServletException("A student file path is outside the upload directory.");
            }
            if (Files.exists(file) && !file.toRealPath().startsWith(rootReal)) {
                throw new ServletException("A student file path is outside the upload directory.");
            }
            Files.deleteIfExists(file);
        }
    }

    private static String requiredSetting(String name) throws ServletException {
        String value = System.getenv(name);
        if (value == null || value.isBlank()) {
            throw new ServletException("Required environment variable is not set: " + name);
        }
        return value;
    }
}

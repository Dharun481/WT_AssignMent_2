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

@WebServlet("/student-file")
public class StudentFileServlet extends HttpServlet {
    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        String rawId = request.getParameter("id");
        long fileId;
        try {
            fileId = Long.parseLong(rawId);
            if (fileId < 1) {
                throw new NumberFormatException();
            }
        } catch (NumberFormatException | NullPointerException exception) {
            response.sendError(HttpServletResponse.SC_BAD_REQUEST, "Invalid file id.");
            return;
        }

        String storedPath;
        String contentType;
        String originalName;
        try (Connection connection = Database.getConnection();
             PreparedStatement statement = connection.prepareStatement(
                     "SELECT stored_path, content_type, original_name FROM student_files WHERE id = ?")) {
            statement.setLong(1, fileId);
            try (ResultSet result = statement.executeQuery()) {
                if (!result.next()) {
                    response.sendError(HttpServletResponse.SC_NOT_FOUND);
                    return;
                }
                storedPath = result.getString("stored_path");
                contentType = result.getString("content_type");
                originalName = result.getString("original_name");
            }
        } catch (SQLException | IllegalStateException exception) {
            throw new ServletException("Unable to find requested student file.", exception);
        }

        Path root = Path.of(requiredSetting("UPLOAD_DIR")).toAbsolutePath().normalize();
        Path relativePath = Path.of(storedPath);
        if (relativePath.isAbsolute()) {
            response.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }
        Path rootReal = root.toRealPath();
        Path file = rootReal.resolve(relativePath).normalize();
        if (!file.startsWith(rootReal) || !Files.isRegularFile(file) || !Files.isReadable(file)) {
            response.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }
        file = file.toRealPath();
        if (!file.startsWith(rootReal)) {
            response.sendError(HttpServletResponse.SC_NOT_FOUND);
            return;
        }

        String safeType = contentType != null && contentType.matches("[\\w.+-]+/[\\w.+-]+")
                ? contentType : "application/octet-stream";
        boolean inlineImage = java.util.Set.of(
                "image/jpeg", "image/png", "image/gif", "image/webp", "image/avif")
                .contains(safeType.toLowerCase(java.util.Locale.ROOT));
        response.setContentType(safeType);
        response.setHeader("X-Content-Type-Options", "nosniff");
        response.setHeader("Cache-Control", "private, no-store");
        response.setHeader("Content-Disposition",
                (inlineImage ? "inline" : "attachment")
                        + "; filename=\"" + safeFilename(originalName) + "\"");
        response.setContentLengthLong(Files.size(file));
        try (var input = Files.newInputStream(file); var output = response.getOutputStream()) {
            input.transferTo(output);
        }
    }

    private static String requiredSetting(String name) throws ServletException {
        String value = System.getenv(name);
        if (value == null || value.isBlank()) {
            throw new ServletException("Required environment variable is not set: " + name);
        }
        return value;
    }

    private static String safeFilename(String name) {
        if (name == null || name.isBlank()) {
            return "download";
        }
        return name.replaceAll("[^A-Za-z0-9._-]", "_");
    }
}

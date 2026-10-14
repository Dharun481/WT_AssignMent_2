package com.example.portal;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

@WebServlet("/portal")
public class PortalServlet extends HttpServlet {
    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        List<Student> students = new ArrayList<>();
        String sql = """
                SELECT s.id, s.full_name, s.email, s.grade_level, s.profile_text,
                       f.id AS file_id, f.original_name, f.content_type
                FROM students s
                LEFT JOIN student_files f ON f.student_id = s.id
                ORDER BY s.full_name, f.id
                """;

        try (Connection connection = Database.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet result = statement.executeQuery()) {
            Student current = null;
            while (result.next()) {
                long studentId = result.getLong("id");
                if (current == null || current.getId() != studentId) {
                    current = new Student(
                            studentId,
                            result.getString("full_name"),
                            result.getString("email"),
                            result.getString("grade_level"),
                            result.getString("profile_text"));
                    students.add(current);
                }
                long fileId = result.getLong("file_id");
                if (!result.wasNull()) {
                    current.addFile(new StudentFile(
                            fileId,
                            result.getString("original_name"),
                            result.getString("content_type")));
                }
            }
        } catch (SQLException | IllegalStateException exception) {
            throw new ServletException("Unable to load student profiles.", exception);
        }

        request.setAttribute("students", students);
        request.setAttribute("studentAdded", request.getSession(false).getAttribute("studentAdded"));
        request.getSession(false).removeAttribute("studentAdded");
        request.getRequestDispatcher("/WEB-INF/views/portal.jsp").forward(request, response);
    }

    public static final class Student {
        private final long id;
        private final String fullName;
        private final String email;
        private final String gradeLevel;
        private final String profileText;
        private final List<StudentFile> files = new ArrayList<>();

        Student(long id, String fullName, String email, String gradeLevel, String profileText) {
            this.id = id;
            this.fullName = fullName;
            this.email = email;
            this.gradeLevel = gradeLevel;
            this.profileText = profileText;
        }

        void addFile(StudentFile file) {
            files.add(file);
        }

        public long getId() { return id; }
        public String getFullName() { return fullName; }
        public String getEmail() { return email; }
        public String getGradeLevel() { return gradeLevel; }
        public String getProfileText() { return profileText; }
        public List<StudentFile> getFiles() { return files; }
    }

    public static final class StudentFile {
        private final long id;
        private final String originalName;
        private final String contentType;

        StudentFile(long id, String originalName, String contentType) {
            this.id = id;
            this.originalName = originalName;
            this.contentType = contentType;
        }

        public long getId() { return id; }
        public String getOriginalName() { return originalName; }
        public String getContentType() { return contentType; }
        public boolean isImage() {
            return contentType != null && List.of(
                    "image/jpeg", "image/png", "image/gif", "image/webp", "image/avif")
                    .contains(contentType.toLowerCase(java.util.Locale.ROOT));
        }
    }
}

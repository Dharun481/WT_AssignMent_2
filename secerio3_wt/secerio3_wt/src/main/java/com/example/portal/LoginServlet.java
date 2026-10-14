package com.example.portal;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.HttpSession;
import org.mindrot.jbcrypt.BCrypt;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

@WebServlet("/login")
public class LoginServlet extends HttpServlet {
    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        request.getRequestDispatcher("/WEB-INF/views/login.jsp").forward(request, response);
    }

    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        String username = request.getParameter("username");
        String password = request.getParameter("password");
        if (username == null || password == null || username.isBlank() || password.isEmpty()) {
            request.setAttribute("error", "Enter your username and password.");
            doGet(request, response);
            return;
        }

        String passwordHash = null;
        try (Connection connection = Database.getConnection();
             PreparedStatement statement = connection.prepareStatement(
                     "SELECT password_hash FROM portal_users WHERE username = ?")) {
            statement.setString(1, username.trim());
            try (ResultSet result = statement.executeQuery()) {
                if (result.next()) {
                    passwordHash = result.getString("password_hash");
                }
            }
        } catch (SQLException | IllegalStateException exception) {
            throw new ServletException("Unable to authenticate user.", exception);
        }

        if (passwordHash == null || !BCrypt.checkpw(password, passwordHash)) {
            request.setAttribute("error", "Invalid username or password.");
            doGet(request, response);
            return;
        }

        HttpSession session = request.getSession(true);
        request.changeSessionId();
        session.setAttribute("loggedInUser", username.trim());
        response.sendRedirect(request.getContextPath() + "/portal");
    }
}

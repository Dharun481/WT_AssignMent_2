package com.example.portal;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
public final class Database {
    private Database() {
    }
    public static Connection getConnection() throws SQLException {
        String url = requiredSetting("DB_URL");
        String user = requiredSetting("DB_USER");
        String password = System.getenv().getOrDefault("DB_PASSWORD", "");
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException exception) {
            throw new SQLException("MySQL JDBC driver is not available.", exception);
        }
        return DriverManager.getConnection(url, user, password);
    }
    private static String requiredSetting(String name) {
        String value = System.getenv(name);
        if (value == null || value.isBlank()) {
            throw new IllegalStateException("Required environment variable is not set: " + name);
        }
        return value;
    }
}

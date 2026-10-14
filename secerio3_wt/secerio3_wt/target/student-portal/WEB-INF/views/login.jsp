<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Sign in | Northstar Student Portal</title>
    <link rel="stylesheet" href="${pageContext.request.contextPath}/assets/portal.css">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-story" aria-labelledby="welcome-title">
        <a class="brand brand-light" href="${pageContext.request.contextPath}/login" aria-label="Northstar home">
            <span class="brand-mark" aria-hidden="true">N</span>
            <span>northstar<span class="brand-dot">.</span></span>
        </a>
        <div class="story-content">
            <span class="eyebrow eyebrow-light">YOUR SCHOOL, IN FOCUS</span>
            <h1 id="welcome-title">Every student's story deserves to be seen.</h1>
            <p>A thoughtful space for the people, progress, and moments that make your school community special.</p>
            <div class="story-note">
                <span class="story-note-icon" aria-hidden="true">✦</span>
                <span>One place for the people who matter.</span>
            </div>
        </div>
        <div class="story-orbit orbit-one" aria-hidden="true"></div>
        <div class="story-orbit orbit-two" aria-hidden="true"></div>
        <span class="story-footer">A brighter view of your school community</span>
    </section>

    <section class="login-panel" aria-labelledby="signin-title">
        <div class="login-card">
            <div class="mobile-brand brand">
                <span class="brand-mark" aria-hidden="true">N</span>
                <span>northstar<span class="brand-dot">.</span></span>
            </div>
            <span class="eyebrow">WELCOME BACK</span>
            <h2 id="signin-title">Sign in to your portal</h2>
            <p class="login-subtitle">Enter your account details to continue.</p>

            <% if (request.getAttribute("error") != null) { %>
            <div class="alert alert-error" role="alert">${requestScope.error}</div>
            <% } %>

            <form class="login-form" method="post" action="${pageContext.request.contextPath}/login">
                <div class="form-field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username"
                           placeholder="Enter your username" required autofocus>
                </div>
                <div class="form-field">
                    <div class="field-heading">
                        <label for="password">Password</label>
                        <span class="field-lock" aria-hidden="true">PRIVATE &amp; SECURE</span>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                           placeholder="Enter your password" required>
                </div>
                <button class="button button-primary button-wide" type="submit">
                    Sign in <span aria-hidden="true">→</span>
                </button>
            </form>
            <p class="login-footnote"><span aria-hidden="true">◈</span> Your student community, just a sign in away.</p>
        </div>
        <footer class="panel-footer">© 2026 Northstar Student Portal</footer>
    </section>
</main>
</body>
</html>

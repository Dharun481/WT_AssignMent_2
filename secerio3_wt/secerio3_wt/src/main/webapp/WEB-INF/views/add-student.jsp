<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Add a student | Northstar</title>
    <link rel="stylesheet" href="${pageContext.request.contextPath}/assets/portal.css">
</head>
<body class="portal-page form-page">
<header class="topbar">
    <a class="brand" href="${pageContext.request.contextPath}/portal" aria-label="Northstar home">
        <span class="brand-mark" aria-hidden="true">N</span>
        <span>northstar<span class="brand-dot">.</span></span>
    </a>
    <div class="topbar-right">
        <span class="signed-in"><span class="status-dot" aria-hidden="true"></span> Signed in as
            <strong><c:out value="${sessionScope.loggedInUser}"/></strong>
        </span>
        <form method="post" action="${pageContext.request.contextPath}/logout">
            <button class="button button-outline" type="submit">Sign out <span aria-hidden="true">↗</span></button>
        </form>
    </div>
</header>
<main class="dashboard form-dashboard">
    <a class="back-link" href="${pageContext.request.contextPath}/portal"><span aria-hidden="true">←</span> Back to directory</a>    <section class="form-banner">
        <div class="form-banner-mark" aria-hidden="true">✦</div>
        <div>
            <span class="eyebrow eyebrow-light">GROW YOUR COMMUNITY</span>
            <h1>Add a student profile</h1>
            <p>Capture the details that help everyone get to know this student.</p>
        </div>
    </section>
    <section class="student-form-card" aria-labelledby="form-title">
        <div class="form-card-heading">
            <div class="form-section-icon" aria-hidden="true">01</div>
            <div>
                <h2 id="form-title">Student information</h2>
                <p>Fields marked <span class="required-mark">*</span> are required.</p>
            </div>
        </div>
        <c:if test="${not empty requestScope.formError}">
            <div class="alert alert-error form-alert" role="alert"><c:out value="${requestScope.formError}"/></div>
        </c:if>        <form class="student-form" method="post" enctype="multipart/form-data"
              action="${pageContext.request.contextPath}/students/add">
            <div class="form-field">
                <label for="fullName">Full name <span class="required-mark">*</span></label>
                <input id="fullName" name="fullName" type="text" maxlength="200" autocomplete="name"
                       placeholder="e.g. Asha Kumar" value="<c:out value='${param.fullName}'/>" required>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" maxlength="254" autocomplete="email"
                           placeholder="student@college.edu" value="<c:out value='${param.email}'/>">
                    <span class="field-hint">Optional — used to contact the student.</span>
                </div>
                <div class="form-field">
                    <label for="gradeLevel">Course or year</label>
                    <input id="gradeLevel" name="gradeLevel" type="text" maxlength="50"
                           placeholder="e.g. Computer Science · Year 2"
                           value="<c:out value='${param.gradeLevel}'/>">
                </div>
            </div>
            <div class="form-field">
                <div class="field-heading">
                    <label for="profileText">Introduction</label>
                    <span class="field-hint">Up to 5,000 characters</span>
                </div>
                <textarea id="profileText" name="profileText" rows="5" maxlength="5000"
                          placeholder="Share a few details, interests, or achievements that make this student unique..."><c:out value="${param.profileText}"/></textarea>
            </div>
            <div class="form-field">
                <label for="document">Student document <span class="field-hint">(optional)</span></label>
                <input class="student-document-input" id="document" name="document" type="file">
                <span class="field-hint">Attach one document, up to 10 MB. It will appear on the student profile.</span>
            </div>
            <div class="form-actions">
                <a class="button button-outline" href="${pageContext.request.contextPath}/portal">Cancel</a>
                <button class="button button-primary" type="submit">Save student profile <span aria-hidden="true">→</span></button>
            </div>
        </form>
    </section>
    <p class="form-privacy"><span aria-hidden="true">◈</span> Student details are visible only to signed-in portal users.</p>
</main>
</body>
</html>

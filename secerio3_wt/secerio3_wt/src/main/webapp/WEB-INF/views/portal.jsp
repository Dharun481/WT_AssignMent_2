<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Student profiles | Northstar</title>
    <link rel="stylesheet" href="${pageContext.request.contextPath}/assets/portal.css">
</head>
<body class="portal-page">
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

<main class="dashboard">
    <section class="welcome-banner">
        <div class="welcome-copy">
            <span class="eyebrow eyebrow-light">YOUR COMMUNITY, AT A GLANCE</span>
            <h1>Student profiles</h1>
            <p>Get to know the students who make your school community shine.</p>
            <a class="button button-banner" href="${pageContext.request.contextPath}/students/add">
                <span aria-hidden="true">＋</span> Add student
            </a>
        </div>
        <div class="welcome-art" aria-hidden="true">
            <span class="art-spark spark-a">✦</span>
            <span class="art-spark spark-b">✧</span>
            <span class="art-disc disc-back"></span>
            <span class="art-disc disc-front"></span>
            <span class="art-star">✳</span>
        </div>
    </section>

    <section class="directory-section" aria-labelledby="directory-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">THE DIRECTORY</span>
                <h2 id="directory-title">Meet our students</h2>
            </div>
            <span class="count-pill"><c:out value="${students.size()}"/> <c:choose><c:when test="${students.size() == 1}">student</c:when><c:otherwise>students</c:otherwise></c:choose></span>
        </div>

        <c:choose>
            <c:when test="${empty students}">
                <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true">✦</div>
                    <h3>Your directory starts here</h3>
                    <p>No student profiles have been added yet. Add the first student to start building your directory.</p>
                    <a class="button button-primary empty-action" href="${pageContext.request.contextPath}/students/add">Add the first student <span aria-hidden="true">→</span></a>
                </div>
            </c:when>
            <c:otherwise>
                <div class="student-grid">
                    <c:forEach var="student" items="${students}">
                        <article class="student-card">
                            <div class="student-card-top">
                                <span class="student-avatar" aria-hidden="true">✦</span>
                                <c:if test="${not empty student.gradeLevel}">
                                    <span class="grade-pill"><c:out value="${student.gradeLevel}"/></span>
                                </c:if>
                            </div>
                            <h3><c:out value="${student.fullName}"/></h3>
                            <c:if test="${not empty student.email}">
                                <p class="student-email"><span aria-hidden="true">✉</span> <c:out value="${student.email}"/></p>
                            </c:if>
                            <c:if test="${not empty student.profileText}">
                                <p class="student-bio"><c:out value="${student.profileText}"/></p>
                            </c:if>

                            <div class="student-files">
                                <span class="files-label">DOCUMENTS</span>
                                <c:if test="${not empty student.files}">
                                    <c:forEach var="file" items="${student.files}">
                                        <div class="file-item">
                                            <c:choose>
                                                <c:when test="${file.image}">
                                                    <a class="file-image" href="${pageContext.request.contextPath}/student-file?id=${file.id}">
                                                        <img src="${pageContext.request.contextPath}/student-file?id=${file.id}"
                                                             alt="Uploaded student image"
                                                             loading="lazy">
                                                    </a>
                                                    <a class="file-link" href="${pageContext.request.contextPath}/student-file?id=${file.id}">
                                                        <c:out value="${file.originalName}"/> <span aria-hidden="true">↗</span>
                                                    </a>
                                                </c:when>
                                                <c:otherwise>
                                                    <a class="file-link file-download" href="${pageContext.request.contextPath}/student-file?id=${file.id}">
                                                        <span class="file-icon" aria-hidden="true">↧</span>
                                                        <c:out value="${file.originalName}"/> <span aria-hidden="true">↗</span>
                                                    </a>
                                                </c:otherwise>
                                            </c:choose>
                                        </div>
                                    </c:forEach>
                                </c:if>
                            </div>
                            <div class="student-card-actions">
                                <form method="post" action="${pageContext.request.contextPath}/students/delete"
                                      onsubmit="return confirm('Delete this student profile and all associated documents?');">
                                    <input type="hidden" name="studentId" value="${student.id}">
                                    <button class="button button-danger" type="submit">Delete student</button>
                                </form>
                            </div>
                        </article>
                    </c:forEach>
                </div>
            </c:otherwise>
        </c:choose>
    </section>
    <footer class="dashboard-footer">Made for the people who make your school special <span aria-hidden="true">✦</span></footer>
</main>
<c:if test="${requestScope.studentAdded}">
    <div class="success-toast" role="status"><span aria-hidden="true">✓</span> Student profile added successfully.</div>
    <c:remove var="studentAdded" scope="session"/>
</c:if>
<c:if test="${not empty sessionScope.studentNotice}">
    <div class="success-toast" role="status"><span aria-hidden="true">✓</span> <c:out value="${sessionScope.studentNotice}"/></div>
    <c:remove var="studentNotice" scope="session"/>
</c:if>
<c:if test="${not empty sessionScope.studentError}">
    <div class="student-error-toast" role="alert"><c:out value="${sessionScope.studentError}"/></div>
    <c:remove var="studentError" scope="session"/>
</c:if>
</body>
</html>

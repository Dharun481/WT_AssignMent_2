const registrationForm = document.getElementById('registrationForm');
const registrationStatus = document.getElementById('registrationStatus');
const storageKey = 'studentRegistrations';

registrationForm.addEventListener('submit', function(event) {
    event.preventDefault();
    registrationStatus.textContent = '';
    registrationStatus.className = 'status';

    let isValid = true;
    const showError = (id, message) => {
        document.getElementById(id).innerText = message;
        isValid = false;
    };
    const clearError = (id) => {
        document.getElementById(id).innerText = '';
    };

    const errorIds = ['nameError', 'rollNoError', 'emailError', 'passwordError', 'courseError', 'documentError'];
    errorIds.forEach(clearError);

    const name = document.getElementById('name').value.trim();
    if (name === '') {
        showError('nameError', 'Full Name is required.');
    }

    const rollNo = document.getElementById('rollNo').value.trim();
    if (rollNo === '') {
        showError('rollNoError', 'Roll Number is required.');
    }

    const email = document.getElementById('email').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === '') {
        showError('emailError', 'Email Address is required.');
    } else if (!emailRegex.test(email)) {
        showError('emailError', 'Please enter a valid email address.');
    }

    const password = document.getElementById('password').value;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
    if (password === '') {
        showError('passwordError', 'Password is required.');
    } else if (!passwordRegex.test(password)) {
        showError('passwordError', 'Password does not meet strength requirements.');
    }

    const course = document.getElementById('course').value;
    if (course === '') {
        showError('courseError', 'Please select a course.');
    }

    const documentFile = document.getElementById('document').files[0];
    if (!documentFile) {
        showError('documentError', 'Please upload your ID proof.');
    }

    if (!isValid) {
        return;
    }

    try {
        const storedRegistrations = localStorage.getItem(storageKey);
        const registrations = storedRegistrations ? JSON.parse(storedRegistrations) : [];
        if (!Array.isArray(registrations)) {
            throw new Error('Saved registration data is not a list.');
        }

        registrations.push({
            name,
            rollNo,
            email,
            course,
            documentName: documentFile.name,
            registeredAt: new Date().toISOString()
        });
        localStorage.setItem(storageKey, JSON.stringify(registrations));
    } catch (error) {
        console.error('Unable to save the student registration.', error);
        registrationStatus.textContent = 'Registration could not be saved in this browser. Check browser storage settings and try again.';
        registrationStatus.className = 'status error';
        return;
    }

    registrationStatus.textContent = 'Registration successful!!';
    registrationStatus.className = 'status success';
    this.reset();
});
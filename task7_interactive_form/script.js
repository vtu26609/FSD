/**
 * Task 7: Interactive Feedback Form Logic
 * Features:
 * - Real-time validation on keypress (input event)
 * - Field highlighting on mouse hover
 * - Double-click submission
 * - Reusable validation functions
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('feedbackForm');
    const inputs = form.querySelectorAll('input, textarea, select');
    const submitBtn = document.getElementById('submitBtn');
    const successModal = document.getElementById('successModal');
    const closeModal = document.getElementById('closeModal');

    // --- 1. Reusable Validation Logic ---

    /**
     * Validates a generic string for minimum length
     */
    const isValidLength = (val, min) => val.trim().length >= min;

    /**
     * Validates email format using regex
     */
    const isValidEmail = (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    };

    /**
     * Map of validation logic per field ID
     */
    const validatorMap = {
        fullName: (val) => isValidLength(val, 3),
        email: (val) => isValidEmail(val),
        message: (val) => isValidLength(val, 10),
        subject: (val) => val !== ""
    };

    // --- 2. Real-Time Validation (Keypress/Input) ---

    const validateField = (input) => {
        const validator = validatorMap[input.id];
        if (!validator) return true;

        const isValid = validator(input.value);
        const group = input.closest('.input-group');

        if (input.value === "" && input.id !== 'subject') {
            group.classList.remove('valid', 'invalid');
            return false;
        }

        if (isValid) {
            group.classList.add('valid');
            group.classList.remove('invalid');
        } else {
            group.classList.add('invalid');
            group.classList.remove('valid');
        }

        return isValid;
    };

    inputs.forEach(input => {
        // Validation on input (covers keypress real-time usage)
        input.addEventListener('input', () => validateField(input));
        
        // --- 3. Highlight fields on mouse hover (JS event variant) ---
        input.addEventListener('mouseover', () => {
            input.style.backgroundColor = 'rgba(255, 255, 255, 0.12)';
        });

        input.addEventListener('mouseout', () => {
            input.style.backgroundColor = ''; // Reset to CSS default
        });
    });

    // --- 4. Double-Click Submit ---

    submitBtn.addEventListener('dblclick', (e) => {
        e.preventDefault();
        
        let allValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                allValid = false;
            }
        });

        if (allValid) {
            handleSubmission();
        } else {
            // Shake effect for feedback
            form.style.animation = 'none';
            form.offsetHeight; // trigger reflow
            form.style.animation = 'shake 0.4s ease-in-out';
            alert('Please fix the errors before submitting.');
        }
    });

    // Prevent regular click from submitting
    submitBtn.addEventListener('click', (e) => {
        const tip = document.querySelector('.info-tip');
        tip.style.color = 'var(--primary)';
        tip.style.fontWeight = '600';
        setTimeout(() => {
            tip.style.color = '';
            tip.style.fontWeight = '';
        }, 500);
    });

    const handleSubmission = () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-text">Sending...</span>';
        
        // Simulate network delay
        setTimeout(() => {
            successModal.classList.add('active');
            form.reset();
            inputs.forEach(i => i.closest('.input-group')?.classList.remove('valid', 'invalid'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="btn-text">Send Feedback</span>';
        }, 1500);
    };

    // Modal Close
    closeModal.addEventListener('click', () => {
        successModal.classList.remove('active');
    });

});

// Add shake animation dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }
`;
document.head.appendChild(style);

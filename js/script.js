document.addEventListener('DOMContentLoaded', (event) => {
  const userTypeSelect = document.getElementById('userTypeSelect');
  const srCodeField = document.getElementById('srCodeField');
  const srCodeInput = document.getElementById('srcode');
  const checkInForm = document.getElementById('checkInForm'); 
  
  // Create and insert the feedback message element
  const formMsg = document.createElement('div');
  formMsg.id = 'formMsg';
  // Check if form exists before trying to insert message element
  if (checkInForm) {
      checkInForm.parentNode.insertBefore(formMsg, checkInForm.nextSibling);
  }

  // Function to show/hide the Sr-Code field
  function toggleSrCodeField() {
    if (srCodeField && userTypeSelect) {
        if (userTypeSelect.value === 'student') {
            srCodeField.style.display = 'block';
            srCodeInput.required = true;
        } else {
            srCodeField.style.display = 'none';
            srCodeInput.required = false;
        }
    }
  }

  // UI Feedback Function
  function showFeedback(text, isError = false) {
    if (!formMsg) return;
    formMsg.textContent = text;
    formMsg.style.cssText = `
        padding: 10px; 
        margin-top: 10px;
        border-radius: 4px;
        font-weight: bold;
        background-color: ${isError ? '#ffdede' : '#e6ffe6'};
        color: ${isError ? '#cc0000' : '#116811'};
    `;
    setTimeout(() => {
        formMsg.textContent = '';
        formMsg.style.cssText = '';
    }, 4000);
  }

  // Initial call and event listener for the Type selector
  if (userTypeSelect) {
      toggleSrCodeField();
      userTypeSelect.addEventListener('change', toggleSrCodeField);
  }

  // --- FORM SUBMISSION HANDLING ---
  if (checkInForm) {
      checkInForm.addEventListener('submit', async function(e) {
        e.preventDefault(); 

        // Get form values
        const firstname = document.getElementById('firstname').value.trim();
        const lastname = document.getElementById('lastname').value.trim();
        const userType = document.getElementById('userTypeSelect').value;
        const srcode = document.getElementById('srcode').value.trim();
        const contact = document.getElementById('contact').value.trim();
        const purpose = document.getElementById('purpose').value.trim();

        // Client-side validation
        if (!firstname || !lastname || !userType || !contact || !purpose) {
            showFeedback('Note:Please fill in all required fields.', true);
            return;
        }

        // Validate student must have SR-Code
        if (userType === 'student' && !srcode) {
            showFeedback('Note: SR-Code is required for students.', true);
            return;
        }

        // Create FormData
        const fd = new FormData();
        fd.append('firstname', firstname);
        fd.append('lastname', lastname);
        fd.append('userType', userType);
        fd.append('srcode', srcode);
        fd.append('contact', contact);
        fd.append('purpose', purpose);
        
        const PHP_ENDPOINT = 'src/regAction.php'; 

        try {
            const response = await fetch(PHP_ENDPOINT, { 
                method: 'POST', 
                body: fd
            });

            const result = await response.json(); 

            if (response.ok && result.success) {
                // Simply show the modal - no need to populate any fields
                document.getElementById('receiptModal').style.display = 'block';
                
                // Reset form
                checkInForm.reset();
                toggleSrCodeField(); 
            } else {
                const msgText = result.message || 'An unknown server error occurred.';
                showFeedback(`Note: Submission Failed: ${msgText}`, true);
            }
        } catch (error) {
            console.error('Network Error:', error);
            showFeedback('Warning! Network Error. Could not connect to the server.', true);
        }
      });
  }
});

// Modal functions
function closeModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('receiptModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
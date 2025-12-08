// Edit Log Book JavaScript - Inline Editing & Bulk Delete

// Store original values for cancel functionality
let originalValues = {};

function enableEdit(visitorId) {
    const row = document.getElementById('row-' + visitorId);
    const editableCells = row.querySelectorAll('.editable-cell');
    
    // Store original values
    originalValues[visitorId] = {};
    
    editableCells.forEach(cell => {
        const field = cell.getAttribute('data-field');
        const value = cell.textContent.trim();
        originalValues[visitorId][field] = value;
        
        // Replace cell content with input fields
        if (field === 'user_type') {
            cell.innerHTML = `
                <select id="edit-${field}-${visitorId}">
                    <option value="Student" ${value === 'Student' ? 'selected' : ''}>Student</option>
                    <option value="Visitor" ${value === 'Visitor' ? 'selected' : ''}>Visitor</option>
                </select>
            `;
        } else if (field === 'purpose') {
            cell.innerHTML = `<textarea id="edit-${field}-${visitorId}">${value}</textarea>`;
        } else {
            const displayValue = value === 'N/A' ? '' : value;
            cell.innerHTML = `<input type="text" id="edit-${field}-${visitorId}" value="${displayValue}">`;
        }
        
        cell.classList.add('editing');
    });
    
    // Highlight the row
    row.classList.add('editing-row');
    
    // Toggle buttons
    row.querySelector('.edit-log-btn').style.display = 'none';
    row.querySelector('.save-log-btn').style.display = 'inline-block';
    row.querySelector('.cancel-log-btn').style.display = 'inline-block';
    row.querySelector('.delete-log-btn').style.display = 'none';
}

function saveEdit(visitorId) {
    const row = document.getElementById('row-' + visitorId);
    
    // Get values from inputs
    const full_name = document.getElementById('edit-full_name-' + visitorId).value.trim();
    const user_type = document.getElementById('edit-user_type-' + visitorId).value;
    const srcode = document.getElementById('edit-srcode-' + visitorId).value.trim();
    const contact = document.getElementById('edit-contact-' + visitorId).value.trim();
    const purpose = document.getElementById('edit-purpose-' + visitorId).value.trim();
    
    // Validation
    if (!full_name || !purpose) {
        alert('Full Name and Purpose are required fields!');
        return;
    }
    
    if (user_type === 'Student' && !srcode) {
        alert('SR-Code is required for students!');
        return;
    }
    
    // Confirm save
    if (!confirm('Save changes to this record?')) {
        return;
    }
    
    // Submit form
    document.getElementById('edit_id').value = visitorId;
    document.getElementById('edit_full_name').value = full_name;
    document.getElementById('edit_user_type').value = user_type;
    document.getElementById('edit_srcode').value = srcode;
    document.getElementById('edit_contact').value = contact;
    document.getElementById('edit_purpose').value = purpose;
    
    document.getElementById('editForm').submit();
}

function cancelEdit(visitorId) {
    const row = document.getElementById('row-' + visitorId);
    const editableCells = row.querySelectorAll('.editable-cell');
    
    // Restore original values
    editableCells.forEach(cell => {
        const field = cell.getAttribute('data-field');
        const originalValue = originalValues[visitorId][field];
        cell.textContent = originalValue;
        cell.classList.remove('editing');
    });
    
    // Remove highlight
    row.classList.remove('editing-row');
    
    // Toggle buttons back
    row.querySelector('.edit-log-btn').style.display = 'inline-block';
    row.querySelector('.save-log-btn').style.display = 'none';
    row.querySelector('.cancel-log-btn').style.display = 'none';
    row.querySelector('.delete-log-btn').style.display = 'inline-block';
    
    // Clear stored values
    delete originalValues[visitorId];
}

function confirmDelete(id, name) {
    if (confirm('!!! WARNING !!!\n\nAre you sure you want to permanently delete the record for:\n\n"' + name + '"\n\nThis action CANNOT be undone!')) {
        // Second confirmation
        if (confirm('Please confirm again:\n\nDelete "' + name + '" from the database permanently?')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
}

// Auto-hide success messages after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const statusMsg = document.querySelector('.status-message.success');
    if (statusMsg) {
        setTimeout(() => {
            statusMsg.style.transition = 'opacity 0.5s';
            statusMsg.style.opacity = '0';
            setTimeout(() => statusMsg.remove(), 500);
        }, 3000);
    }

    // ===== BULK DELETE FUNCTIONALITY =====
    const selectAllCheckbox = document.getElementById('selectAll');
    const visitorCheckboxes = document.querySelectorAll('.visitor-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');

    function updateSelectedCount() {
        const checkedBoxes = document.querySelectorAll('.visitor-checkbox:checked');
        const count = checkedBoxes.length;
        if (selectedCount) {
            selectedCount.textContent = count;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            visitorCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    visitorCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            
            const allChecked = Array.from(visitorCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(visitorCheckboxes).some(cb => cb.checked);
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.visitor-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (count === 0) {
                e.preventDefault();
                alert('Please select at least one record to delete.');
                return false;
            }
            
            const confirmMsg = count === 1 
                ? '!!! WARNING !!!\n\nAre you sure you want to permanently delete 1 record?\n\nThis action CANNOT be undone!' 
                : `!!! WARNING !!!\n\nAre you sure you want to permanently delete ${count} records?\n\nThis action CANNOT be undone!`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
            
            // Second confirmation for bulk delete
            if (!confirm('Please confirm again:\n\nDelete these records permanently?')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Initialize count
    updateSelectedCount();
});
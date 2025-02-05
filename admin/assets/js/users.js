// Function to handle modal operations
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// View user details
function viewUser(userId) {
    $.ajax({
        url: 'ajax/get-user.php',
        type: 'GET',
        data: { id: userId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                // Populate user details in the view modal
                const user = data.user;
                $('#viewUserModal .user-details').html(`
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <p class="mt-1 text-sm text-gray-900">${user.username}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900">${user.email}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <p class="mt-1 text-sm text-gray-900">${user.role}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <p class="mt-1 text-sm text-gray-900">${user.status}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Login</label>
                            <p class="mt-1 text-sm text-gray-900">${user.last_login_at || 'Never'}</p>
                        </div>
                    </div>
                `);
                openModal('viewUserModal');
            } else {
                Swal.fire('Error', data.message || 'Failed to load user details', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load user details', 'error');
        }
    });
}

// Edit user
function editUser(userId) {
    $.ajax({
        url: 'ajax/get-user.php',
        type: 'GET',
        data: { id: userId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                const user = data.user;
                $('#editUserId').val(user.id);
                $('#editUsername').val(user.username);
                $('#editEmail').val(user.email);
                $('#editRole').val(user.role);
                openModal('editUserModal');
            } else {
                Swal.fire('Error', data.message || 'Failed to load user details', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load user details', 'error');
        }
    });
}

// Reset password
function resetPassword(userId) {
    Swal.fire({
        title: 'Reset Password?',
        text: "This will reset the user's password to the default password. Continue?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/reset-password.php',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        Swal.fire(
                            'Reset!',
                            'Password has been reset successfully.',
                            'success'
                        );
                    } else {
                        Swal.fire('Error', data.message || 'Failed to reset password', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to reset password', 'error');
                }
            });
        }
    });
}

// Toggle user status
function toggleStatus(userId) {
    Swal.fire({
        title: 'Change Status?',
        text: "This will toggle the user's active/inactive status. Continue?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/toggle-status.php',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to change status', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to change status', 'error');
                }
            });
        }
    });
}

// Delete user
function deleteUser(userId) {
    Swal.fire({
        title: 'Delete User?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/delete-user.php',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete user', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete user', 'error');
                }
            });
        }
    });
}

// Handle form submissions
$(document).ready(function() {
    // Edit user form
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/update-user.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    closeModal('editUserModal');
                    Swal.fire({
                        title: 'Success!',
                        text: 'User updated successfully',
                        icon: 'success',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to update user', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to update user', 'error');
            }
        });
    });
    
    // Create user form
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/create-user.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.message || 'Failed to create user', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to create user', 'error');
            }
        });
    });
});

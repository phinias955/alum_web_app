<?php
$pageTitle = 'My Profile';
require_once 'includes/layout.php';
require_once 'config/Database.php';
require_once 'includes/Security.php';

// Check if user is logged in
if (!Security::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user data
$conn = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, username, email, role, status, created_at, last_login_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (!empty($username) && !empty($email)) {
        $updateStmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $username, $email, $userId);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
        }
        $updateStmt->close();
        header('Location: profile.php');
        exit;
    }
}

// Get activity logs
$activityStmt = $conn->prepare("SELECT activity, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activityStmt->bind_param("i", $userId);
$activityStmt->execute();
$activities = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$activityStmt->close();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<main class="profile-page">
    <div class="page-header">
        <div class="header-overlay"></div>
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="header-content animate__animated animate__fadeIn">
                        <div class="profile-header">
                            <div class="profile-image">
                                <?php if (!empty($userData['profile_picture']) && file_exists('../uploads/profile_pictures/' . $userData['profile_picture'])): ?>
                                    <img src="../uploads/profile_pictures/<?php echo $userData['profile_picture']; ?>" alt="Profile Picture" class="rounded-circle profile-pic">
                                <?php else: ?>
                                    <div class="default-avatar">
                                        <?php echo strtoupper(substr($userData['username'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="status-indicator <?php echo $userData['status'] === 'active' ? 'active' : 'inactive'; ?>"></span>
                            </div>
                            <div class="profile-info">
                                <h1 class="profile-name"><?php echo strtoupper($userData['username']); ?></h1>
                                <p class="profile-role"><?php echo ucfirst($userData['role']); ?></p>
                                <div class="profile-stats">
                                    <span class="status-badge <?php echo $userData['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                        <?php echo $userData['status'] === 'active' ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <span class="last-active">Last active: <?php echo date('M d, H:i', strtotime($userData['last_login_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-content">
        <div class="container-fluid">
            <div class="row justify-content-center" style="margin-left: 10px;">
                <div class="col-xl-11" style="margin-left: 10px;">
                    <?php include 'includes/alerts.php'; ?>
                    
                    <div class="row g-4" style="margin-left: 10px;">
                        <!-- Left Column -->
                        <div class="col-lg-4">
                            <!-- Stats Cards -->
                            <div class="stats-grid animate__animated animate__fadeInLeft" style="margin-left: 10px;">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3>Status</h3>
                                        <p class="status-badge <?php echo $userData['status']; ?>">
                                            <?php echo ucfirst($userData['status']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3>Last Active</h3>
                                        <p><?php echo $userData['last_login_at'] ? date('M d, H:i', strtotime($userData['last_login_at'])) : 'Never'; ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="quick-actions animate__animated animate__fadeInLeft animate__delay-1s">
                                <h2>Quick Actions</h2>
                                <div class="action-buttons">
                                    <button class="action-btn" onclick="window.print()">
                                        <i class="fas fa-print"></i>
                                        <span>Print Profile</span>
                                    </button>
                                    <button class="action-btn" data-bs-toggle="modal" data-bs-target="#activityModal">
                                        <i class="fas fa-history"></i>
                                        <span>Activity Log</span>
                                    </button>
                                    <button class="action-btn" onclick="location.href='settings.php'">
                                        <i class="fas fa-cog"></i>
                                        <span>Settings</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Recent Activity Preview -->
                            <div class="recent-activity animate__animated animate__fadeInLeft animate__delay-2s">
                                <h2>Recent Activity</h2>
                                <div class="activity-timeline">
                                    <?php foreach (array_slice($activities, 0, 3) as $activity): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-icon">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <p><?php echo htmlspecialchars($activity['activity']); ?></p>
                                            <span class="time"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-8">
                            <div class="edit-profile-card animate__animated animate__fadeInRight">
                                <div class="card-header">
                                    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" class="needs-validation" novalidate>
                                        <div class="row g-4">
                                            <!-- Username -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="username" name="username"
                                                           value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                                    <label for="username">Username</label>
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="email" name="email"
                                                           value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                                    <label for="email">Email Address</label>
                                                </div>
                                            </div>

                                            <!-- Role -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="role"
                                                           value="<?php echo htmlspecialchars(ucfirst($userData['role'])); ?>" readonly>
                                                    <label for="role">Role</label>
                                                </div>
                                            </div>

                                            <!-- Status -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="status"
                                                           value="<?php echo htmlspecialchars(ucfirst($userData['status'])); ?>" readonly>
                                                    <label for="status">Account Status</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-actions">
                                            <button type="reset" class="btn btn-light">
                                                <i class="fas fa-undo"></i>
                                                <span>Reset</span>
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i>
                                                <span>Save Changes</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
:root {
    --primary-color: #4f46e5;
    --secondary-color: #818cf8;
    --success-color: #22c55e;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --info-color: #3b82f6;
    --background-color: #f8fafc;
    --card-background: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}

/* Modern Glassmorphism */
.profile-page {
    background-color: var(--background-color);
    min-height: 100vh;
    padding-bottom: 2rem;
    margin-left: 250px; /* Adjust based on your sidebar width */
    width: calc(100% - 250px); /* Full width minus sidebar */
    position: relative;
    overflow-x: hidden;
}

/* Stunning Header */
.page-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    padding: 3rem 0;
    margin: 0 0 2rem 0;
    position: relative;
    overflow: hidden;
    width: 100%;
}

.header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="%23FFFFFF" d="M42.7,-62.9C50.9,-52.8,50.1,-34.4,51.7,-18.7C53.4,-3,57.4,10,54.4,21.8C51.4,33.6,41.4,44.2,29.4,48.9C17.4,53.6,3.4,52.3,-12.8,50.6C-28.9,48.9,-47.2,46.7,-57.7,36.2C-68.2,25.7,-71,6.9,-65.3,-7.2C-59.6,-21.3,-45.4,-30.7,-33.1,-40.4C-20.8,-50,-10.4,-59.9,4.3,-65.5C19,-71.2,38,-72.9,42.7,-62.9Z" transform="translate(100 100)" /></svg>') no-repeat center center;
    opacity: 0.1;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
    100% { transform: translateY(0) rotate(0deg); }
}

.profile-header {
    position: relative;
    z-index: 1;
    color: white;
}

/* Profile Image and Avatar Styles */
.profile-image {
    position: relative;
    width: 150px;
    height: 150px;
    margin-right: 2rem;
}

.profile-pic {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 4px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.default-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    border: 4px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.profile-name {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: 1px;
}

/* Animation for avatar */
.default-avatar {
    animation: avatarPulse 2s infinite;
}

@keyframes avatarPulse {
    0% {
        transform: scale(1);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .profile-image {
        width: 120px;
        height: 120px;
        margin: 0 auto 1.5rem auto;
    }
    
    .default-avatar {
        font-size: 2.5rem;
    }
    
    .profile-name {
        font-size: 2rem;
    }
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-background);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-info h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--text-secondary);
}

.stat-info p {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-primary);
}

/* Quick Actions */
.quick-actions {
    background: var(--card-background);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.action-btn {
    background: transparent;
    border: 2px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.action-btn:hover {
    border-color: var(--primary-color);
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(129, 140, 248, 0.1));
    transform: translateY(-2px);
}

.action-btn i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.action-btn span {
    font-size: 0.9rem;
    color: var(--text-primary);
}

/* Recent Activity */
.recent-activity {
    background: var(--card-background);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.activity-timeline {
    margin-top: 1rem;
}

.timeline-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
    position: relative;
}

.timeline-icon {
    color: var(--primary-color);
    position: relative;
}

.timeline-icon::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    width: 2px;
    height: calc(100% + 1rem);
    background: var(--border-color);
    transform: translateX(-50%);
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-item:last-child .timeline-icon::after {
    display: none;
}

.timeline-content {
    flex: 1;
}

.timeline-content p {
    margin: 0;
    color: var(--text-primary);
}

.timeline-content .time {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Edit Profile Card */
.edit-profile-card {
    background: var(--card-background);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.edit-profile-card .card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    padding: 1.5rem;
    color: white;
}

.edit-profile-card .card-header h2 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edit-profile-card .card-body {
    padding: 2rem;
}

/* Form Styling */
.form-floating {
    margin-bottom: 1rem;
}

.form-control {
    border: 2px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1rem;
    height: auto;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
}

.form-control:read-only {
    background-color: var(--background-color);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
    color: white;
}

.btn-light {
    background: var(--background-color);
    border: 2px solid var(--border-color);
    color: var(--text-primary);
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 0.25rem 1rem;
    border-radius: 2rem;
    font-weight: 500;
}

.status-badge.active {
    background-color: rgba(34, 197, 94, 0.1);
    color: var(--success-color);
}

.status-badge.inactive {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-in {
    animation: slideIn 0.5s ease forwards;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-stats {
        justify-content: center;
    }

    .action-buttons {
        grid-template-columns: 1fr;
    }
}

/* Content container adjustments */
.profile-content {
    padding: 0 2rem;
    max-width: 1600px; /* Maximum width for larger screens */
    margin: 0 auto;
}

/* Responsive adjustments */
@media (max-width: 1400px) {
    .profile-content {
        padding: 0 1.5rem;
    }
}

@media (max-width: 992px) {
    .profile-page {
        margin-left: 0;
        width: 100%;
    }
    
    .profile-content {
        padding: 0 1rem;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 2rem 1rem;
    }
    
    .profile-stats {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
}

/* Ensure all content stays within bounds */
.container-fluid {
    max-width: 100%;
    padding-right: 15px;
    padding-left: 15px;
    margin-right: auto;
    margin-left: auto;
}

/* Card layout improvements */
.edit-profile-card,
.quick-actions,
.recent-activity,
.stat-card {
    width: 100%;
    margin-bottom: 1.5rem;
}

/* Form layout adjustments */
.form-actions {
    margin-top: 2rem;
    padding: 1rem 0;
    border-top: 1px solid var(--border-color);
}

/* Timeline adjustments */
.activity-timeline {
    margin: 1.5rem 0;
    padding: 0 0.5rem;
}

/* Status badge positioning */
.status-indicator {
    position: absolute;
    bottom: 5px;
    right: 5px;
    transform: translate(0, 0);
}

/* Header content spacing */
.header-content {
    padding: 0 1rem;
}

/* Profile header adjustments */
.profile-header {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Animation timing adjustments */
.animate__animated {
    animation-duration: 0.8s;
}

.animate__delay-1s {
    animation-delay: 0.3s;
}

.animate__delay-2s {
    animation-delay: 0.6s;
}

/* Ensure buttons don't overflow */
.action-btn {
    min-width: 120px;
    max-width: 100%;
}

/* Additional spacing utilities */
.g-4 {
    --bs-gutter-x: 1.5rem;
    --bs-gutter-y: 1.5rem;
}

/* Ensure modal appears above sidebar */
.modal {
    z-index: 1060;
}

.modal-backdrop {
    z-index: 1050;
}

/* Fix for Firefox scrollbar */
* {
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) var(--background-color);
}

/* Fix for Chrome scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background-color: var(--primary-color);
    border-radius: 4px;
}

/* Prevent text overflow */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Ensure form inputs don't overflow */
.form-control {
    max-width: 100%;
}

/* Fix for Safari */
@supports (-webkit-touch-callout: none) {
    .profile-page {
        min-height: -webkit-fill-available;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
<?php
if (!defined('ADMIN_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

// Initialize security
Security::initSession();

// Check if user is logged in
if (!Security::isLoggedIn()) {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - Alumni Portal Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        .dropdown {
            display: none;
        }
        .dropdown.show {
            display: block;
        }
        
        /* SVG Icon styles */
        .icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            stroke-width: 0;
            stroke: currentColor;
            fill: currentColor;
            vertical-align: middle;
        }
        
        .icon-sm {
            width: 18px;
            height: 18px;
        }
        
        .icon-lg {
            width: 28px;
            height: 28px;
        }
    </style>
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body class="bg-gray-100">
    <?php 
    require_once __DIR__ . '/header.php';
    require_once __DIR__ . '/sidebar.php';
    ?>

    <main class="ml-64 p-8">
        <div class="container mx-auto">
            <!-- Page header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <?php if (isset($headerButtons)) echo $headerButtons; ?>
            </div>

            <!-- Error messages -->
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- Success messages -->
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <!-- Main content -->
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <?php 
    if (defined('INCLUDED_FROM_ADMIN')) {
        if (file_exists(__DIR__ . '/user-modals.php')) {
            require_once __DIR__ . '/user-modals.php';
        }
    }
    ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        var notificationDropdown = document.getElementById('notificationDropdown');
        var profileDropdown = document.getElementById('profileDropdown');
        
        if (!event.target.closest('.relative')) {
            notificationDropdown?.classList.remove('show');
            profileDropdown?.classList.remove('show');
        }
    });

    // Toggle dropdown
    function toggleDropdown(dropdownId) {
        event.stopPropagation();
        var dropdown = document.getElementById(dropdownId);
        var otherDropdownId = dropdownId === 'notificationDropdown' ? 'profileDropdown' : 'notificationDropdown';
        var otherDropdown = document.getElementById(otherDropdownId);
        
        // Close other dropdown if open
        otherDropdown?.classList.remove('show');
        
        // Toggle current dropdown
        dropdown?.classList.toggle('show');
    }
    </script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
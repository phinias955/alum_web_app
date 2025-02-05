<?php
require_once 'config/config.php';
require_once 'includes/Security.php';
require_once 'includes/header.php';

Security::redirectIfNotLoggedIn();

global $db;

// Get user data
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
$notificationCount = count($notifications);
?>

<style>
.dashboard {
    background: #1d1e24;
    color: #fff;
    min-height: 100vh;
    padding: 20px;
}

.stats-bar {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    background: #1d1e24;
    padding: 10px;
    border-radius: 4px;
}

.stat-item {
    padding: 10px;
    border-radius: 4px;
    flex: 1;
}

.stat-item.total { background: #1d1e24; }
.stat-item.honeytrap { background: #8b0000; }
.stat-item.cowrie { background: #856404; }
.stat-item.dionaea { background: #004d1a; }
.stat-item.mailoney { background: #1a472a; }
.stat-item.rdphoneypot { background: #2c5282; }
.stat-item.adbhoney { background: #322659; }
.stat-item.ciscoasa { background: #702459; }
.stat-item.conpot { background: #285e61; }
.stat-item.heralding { background: #744210; }

.stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.chart-container {
    background: #25262c;
    border-radius: 4px;
    padding: 15px;
}

.chart-title {
    color: #fff;
    font-size: 16px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #363636;
}

.map-container {
    height: 400px;
    background: #25262c;
}

.tag-cloud {
    height: 300px;
    background: #25262c;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: #25262c;
    border-radius: 4px;
}

.header-title {
    font-size: 24px;
    font-weight: bold;
}

.header-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.refresh-button {
    background: #0077cc;
    color: white;
    padding: 5px 15px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}

.refresh-button:hover {
    background: #0066b3;
}

.top-header {
    background: #25262c;
    padding: 0.5rem 1rem;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    border-bottom: 1px solid #363636;
}

.notification-bell {
    position: relative;
    margin-right: 1.5rem;
    cursor: pointer;
}

.notification-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e53e3e;
    color: white;
    border-radius: 50%;
    padding: 0.25rem;
    font-size: 0.75rem;
    min-width: 1.5rem;
    text-align: center;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: #25262c;
    border: 1px solid #363636;
    border-radius: 0.375rem;
    width: 320px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: none;
    z-index: 50;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #363636;
}

.notification-item:last-child {
    border-bottom: none;
}

.profile-menu {
    position: relative;
    margin-left: 1rem;
}

.profile-button {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 0.375rem;
}

.profile-button:hover {
    background: #363636;
}

.profile-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: #25262c;
    border: 1px solid #363636;
    border-radius: 0.375rem;
    width: 200px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: none;
    z-index: 50;
}

.profile-dropdown a {
    display: block;
    padding: 0.75rem 1rem;
    color: white;
    text-decoration: none;
}

.profile-dropdown a:hover {
    background: #363636;
}

.show {
    display: block;
}
</style>

<div class="top-header">
    <div class="notification-bell">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <?php if ($notificationCount > 0): ?>
            <span class="notification-count"><?php echo $notificationCount; ?></span>
        <?php endif; ?>
        <div class="notification-dropdown">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-semibold">Notifications</h3>
            </div>
            <?php if ($notifications): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-item">
                    No new notifications
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-menu">
        <div class="profile-button">
            <span class="mr-2"><?php echo htmlspecialchars($user['username']); ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="profile-dropdown">
            <a href="profile.php">Profile Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="header">
        <div class="header-title">Honeypot Attacks</div>
        <div class="header-controls">
            <span>Last 24 hours</span>
            <button class="refresh-button">Refresh</button>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-item total">
            <div class="stat-value">20,896</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-item honeytrap">
            <div class="stat-value">12,941</div>
            <div class="stat-label">Honeytrap</div>
        </div>
        <div class="stat-item cowrie">
            <div class="stat-value">5,209</div>
            <div class="stat-label">Cowrie</div>
        </div>
        <div class="stat-item dionaea">
            <div class="stat-value">1,233</div>
            <div class="stat-label">Dionaea</div>
        </div>
        <div class="stat-item mailoney">
            <div class="stat-value">601</div>
            <div class="stat-label">Mailoney</div>
        </div>
        <div class="stat-item rdphoneypot">
            <div class="stat-value">267</div>
            <div class="stat-label">RDPHoneypot</div>
        </div>
        <div class="stat-item adbhoney">
            <div class="stat-value">241</div>
            <div class="stat-label">ADBHoney</div>
        </div>
        <div class="stat-item ciscoasa">
            <div class="stat-value">186</div>
            <div class="stat-label">CiscoASA</div>
        </div>
        <div class="stat-item conpot">
            <div class="stat-value">112</div>
            <div class="stat-label">Conpot</div>
        </div>
        <div class="stat-item heralding">
            <div class="stat-value">31</div>
            <div class="stat-label">Heralding</div>
        </div>
    </div>

    <div class="chart-grid">
        <div>
            <div class="chart-container">
                <div class="chart-title">Honeypot Attacks Bar</div>
                <canvas id="attacks-bar"></canvas>
            </div>
            <div class="chart-container" style="margin-top: 20px;">
                <div class="chart-title">Attacks by Destination Port Histogram</div>
                <canvas id="port-histogram"></canvas>
            </div>
            <div class="chart-container" style="margin-top: 20px;">
                <div class="chart-title">Attacker Source IP Reputation</div>
                <canvas id="ip-reputation"></canvas>
            </div>
        </div>
        <div>
            <div class="chart-container">
                <div class="chart-title">Attack Map - Dynamic</div>
                <div id="attack-map" class="map-container"></div>
            </div>
            <div class="chart-container" style="margin-top: 20px;">
                <div class="chart-title">POI OS Distribution</div>
                <canvas id="os-distribution"></canvas>
            </div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-container">
            <div class="chart-title">Username Tagcloud</div>
            <div id="username-cloud" class="tag-cloud"></div>
        </div>
        <div class="chart-container">
            <div class="chart-title">Password Tagcloud</div>
            <div id="password-cloud" class="tag-cloud"></div>
        </div>
    </div>
</div>

<!-- Required JS libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js"></script>
<link href="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Attack Map
    const map = L.map('attack-map', {
        center: [20, 0],
        zoom: 2,
        minZoom: 2,
        maxZoom: 5,
        scrollWheelZoom: false,
        zoomControl: false,
        attributionControl: false
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);

    // Sample attack data
    const attackData = {
        'Honeytrap': 12941,
        'Cowrie': 5209,
        'Dionaea': 1233,
        'Mailoney': 601,
        'RDPHoneypot': 267,
        'ADBHoney': 241,
        'CiscoASA': 186,
        'Conpot': 112,
        'Heralding': 31
    };

    // Bar Chart
    const barCtx = document.getElementById('attacks-bar').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(attackData),
            datasets: [{
                data: Object.values(attackData),
                backgroundColor: [
                    '#8b0000', '#856404', '#004d1a', '#1a472a',
                    '#2c5282', '#322659', '#702459', '#285e61', '#744210'
                ]
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: '#363636' },
                    ticks: { color: '#fff' }
                },
                y: {
                    grid: { color: '#363636' },
                    ticks: { color: '#fff' }
                }
            }
        }
    });

    // Initialize other charts similarly...
    // Port Histogram
    const portCtx = document.getElementById('port-histogram').getContext('2d');
    new Chart(portCtx, {
        type: 'line',
        data: {
            labels: Array.from({length: 24}, (_, i) => i),
            datasets: [{
                label: 'Port 8000',
                data: Array.from({length: 24}, () => Math.random() * 2000),
                borderColor: '#4299e1',
                fill: false
            }, {
                label: 'Port 7000',
                data: Array.from({length: 24}, () => Math.random() * 1500),
                borderColor: '#48bb78',
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#fff' }
                }
            },
            scales: {
                x: {
                    grid: { color: '#363636' },
                    ticks: { color: '#fff' }
                },
                y: {
                    grid: { color: '#363636' },
                    ticks: { color: '#fff' }
                }
            }
        }
    });

    // Initialize tag clouds
    const words = [
        {text: 'root', size: 60},
        {text: 'admin', size: 40},
        {text: 'user', size: 30},
        {text: 'test', size: 25},
        {text: 'guest', size: 20}
    ];

    ['username-cloud', 'password-cloud'].forEach(id => {
        const width = document.getElementById(id).offsetWidth;
        const height = document.getElementById(id).offsetHeight;

        const svg = d3.select(`#${id}`)
            .append('svg')
            .attr('width', width)
            .attr('height', height);

        const layout = d3.layout.cloud()
            .size([width, height])
            .words(words)
            .padding(5)
            .rotate(() => 0)
            .fontSize(d => d.size)
            .on('end', draw);

        layout.start();

        function draw(words) {
            svg.append('g')
                .attr('transform', `translate(${width/2},${height/2})`)
                .selectAll('text')
                .data(words)
                .enter().append('text')
                .style('font-size', d => `${d.size}px`)
                .style('fill', '#fff')
                .attr('text-anchor', 'middle')
                .attr('transform', d => `translate(${d.x},${d.y})rotate(${d.rotate})`)
                .text(d => d.text);
        }
    });
});

// Add notification and profile dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.querySelector('.notification-bell');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    const profileButton = document.querySelector('.profile-button');
    const profileDropdown = document.querySelector('.profile-dropdown');

    // Toggle notification dropdown
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
        profileDropdown.classList.remove('show');
        
        if (notificationDropdown.classList.contains('show')) {
            // Mark notifications as read
            fetch('mark_notifications_read.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            
            // Remove notification count
            const countBadge = document.querySelector('.notification-count');
            if (countBadge) {
                countBadge.remove();
            }
        }
    });

    // Toggle profile dropdown
    profileButton.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
        
        notificationDropdown.classList.remove('show');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        notificationDropdown.classList.remove('show');
        profileDropdown.classList.remove('show');
    });

    // Prevent dropdown close when clicking inside
    notificationDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    profileDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

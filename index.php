<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$username = $_SESSION['admin_username'] ?? 'Admin';

function dashboardEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $stats = [
        'projects' => (int) $pdo->query(
            'SELECT COUNT(*) FROM projects'
        )->fetchColumn(),

        'published' => (int) $pdo->query(
            "SELECT COUNT(*) FROM projects WHERE status = 'published'"
        )->fetchColumn(),

        'skills' => (int) $pdo->query(
            'SELECT COUNT(*) FROM skills'
        )->fetchColumn(),

        'services' => (int) $pdo->query(
            "SELECT COUNT(*) FROM services WHERE status = 'active'"
        )->fetchColumn(),

        'messages' => (int) $pdo->query(
            'SELECT COUNT(*) FROM messages'
        )->fetchColumn(),

        'unread' => (int) $pdo->query(
            "SELECT COUNT(*) FROM messages WHERE status = 'unread'"
        )->fetchColumn()
    ];

    $recentMessages = $pdo->query(
        'SELECT id, name, email, subject, status, created_at
         FROM messages
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    )->fetchAll();

    $recentProjects = $pdo->query(
        'SELECT id, title, status, created_at
         FROM projects
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    )->fetchAll();

    // Latest unread messages for the notification dropdown.
    $notifications = $pdo->query(
        "SELECT id, name, subject, created_at
         FROM messages
         WHERE status = 'unread'
         ORDER BY created_at DESC, id DESC
         LIMIT 5"
    )->fetchAll();

} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load dashboard data. Please check your database tables.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | DARIOS Admin</title>

    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=1">
</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <a href="index.php" class="brand">
            DARIOS<span>.</span>
        </a>

        <p class="sidebar-subtitle">Portfolio administration</p>

        <div class="sidebar-section-label">WORKSPACE</div>

        <nav class="sidebar-nav">

            <a href="index.php" class="nav-link active">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="projects.php" class="nav-link">
                <span class="nav-icon">▦</span>
                <span>Projects</span>
            </a>

            <a href="skills.php" class="nav-link">
                <span class="nav-icon">◇</span>
                <span>Skills</span>
            </a>

            <a href="services.php" class="nav-link">
                <span class="nav-icon">✧</span>
                <span>Services</span>
            </a>

            <a href="messages.php" class="nav-link">
                <span class="nav-icon">✉</span>
                <span>Messages</span>

                <?php if ($stats['unread'] > 0): ?>
                    <span class="nav-badge">
                        <?= $stats['unread'] ?>
                    </span>
                <?php endif; ?>
            </a>

        </nav>

        <div class="sidebar-section-label">WEBSITE</div>

        <nav class="sidebar-nav">

            <a href="../index.php" target="_blank" rel="noopener" class="nav-link">
                <span class="nav-icon">↗</span>
                <span>View Portfolio</span>
            </a>

            <a href="logout.php" class="nav-link logout-link">
                <span class="nav-icon">⇥</span>
                <span>Logout</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-profile">
                <div class="profile-avatar">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>

                <div>
                    <strong><?= dashboardEscape($username) ?></strong>
                    <small>Administrator</small>
                </div>

                <span class="online-dot"></span>
            </div>
        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                    ☰
                </button>

                <div class="breadcrumb">
                    <span>Workspace</span>
                    <span class="breadcrumb-divider">/</span>
                    <strong>Dashboard</strong>
                </div>
            </div>

            <div class="topbar-right">

                <div class="welcome-small">
                    Welcome back, <?= dashboardEscape($username) ?>
                </div>

                <!-- NOTIFICATIONS -->
                <div class="notification-wrapper">

                    <button
                        class="notification-button"
                        id="notificationButton"
                        aria-label="Notifications"
                        aria-expanded="false"
                    >
                        ♧

                        <?php if ($stats['unread'] > 0): ?>
                            <span class="notification-count">
                                <?= $stats['unread'] > 99 ? '99+' : $stats['unread'] ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div class="notification-dropdown" id="notificationDropdown">

                        <div class="notification-header">
                            <div>
                                <strong>Notifications</strong>
                                <small><?= $stats['unread'] ?> unread messages</small>
                            </div>

                            <a href="messages.php">Open inbox</a>
                        </div>

                        <div class="notification-list">

                            <?php if (!$notifications): ?>

                                <div class="notification-empty">
                                    <span>✓</span>
                                    <p>You're all caught up!</p>
                                    <small>No unread messages.</small>
                                </div>

                            <?php else: ?>

                                <?php foreach ($notifications as $notification): ?>

                                    <a
                                        href="messages.php?view=<?= (int) $notification['id'] ?>"
                                        class="notification-item"
                                    >

                                        <div class="notification-avatar">
                                            <?= strtoupper(substr($notification['name'], 0, 1)) ?>
                                        </div>

                                        <div class="notification-content">
                                            <strong>
                                                <?= dashboardEscape($notification['name']) ?>
                                            </strong>

                                            <p>
                                                <?= dashboardEscape(
                                                    $notification['subject'] ?: 'Sent you a message'
                                                ) ?>
                                            </p>

                                            <small>
                                                <?= dashboardEscape($notification['created_at']) ?>
                                            </small>
                                        </div>

                                        <span class="notification-dot"></span>

                                    </a>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                        <a href="messages.php" class="notification-footer">
                            View all messages →
                        </a>

                    </div>

                </div>

                <div class="topbar-avatar">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>

            </div>

        </header>


        <!-- PAGE HEADING -->
        <section class="page-heading">

            <div>
                <span class="eyebrow">OVERVIEW</span>
                <h1>Dashboard</h1>
                <p>Manage your portfolio and track what's happening.</p>
            </div>

            <a href="../index.php" target="_blank" rel="noopener" class="primary-button">
                View Live Website <span>↗</span>
            </a>

        </section>


        <!-- STATISTICS -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Projects</span>
                    <span class="stat-icon blue-icon">▦</span>
                </div>

                <strong class="stat-number"><?= $stats['projects'] ?></strong>

                <div class="stat-bottom">
                    <span class="stat-positive">● <?= $stats['published'] ?> published</span>
                    <span>Portfolio</span>
                </div>
            </div>


            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Technical Skills</span>
                    <span class="stat-icon purple-icon">◇</span>
                </div>

                <strong class="stat-number"><?= $stats['skills'] ?></strong>

                <div class="stat-bottom">
                    <span class="stat-positive">● Updated database</span>
                    <span>Skills</span>
                </div>
            </div>


            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Active Services</span>
                    <span class="stat-icon green-icon">✧</span>
                </div>

                <strong class="stat-number"><?= $stats['services'] ?></strong>

                <div class="stat-bottom">
                    <span class="stat-positive">● Currently active</span>
                    <span>Services</span>
                </div>
            </div>


            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">All Messages</span>
                    <span class="stat-icon orange-icon">✉</span>
                </div>

                <strong class="stat-number"><?= $stats['messages'] ?></strong>

                <div class="stat-bottom">
                    <span>Contact submissions</span>
                    <span>Inbox</span>
                </div>
            </div>


            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Unread Messages</span>
                    <span class="stat-icon red-icon">●</span>
                </div>

                <strong class="stat-number"><?= $stats['unread'] ?></strong>

                <div class="stat-bottom">
                    <span class="stat-warning">Needs attention</span>
                    <a href="messages.php">Open inbox →</a>
                </div>
            </div>


            <div class="stat-card admin-stat-card">
                <div class="stat-top">
                    <span class="stat-label">Account Status</span>
                    <span class="stat-icon green-icon">✓</span>
                </div>

                <strong class="stat-number account-status">Protected</strong>

                <div class="stat-bottom">
                    <span class="stat-positive">● Authenticated</span>
                    <span>Administrator</span>
                </div>
            </div>

        </section>


        <!-- MANAGEMENT AND ACTIVITY -->
        <section class="content-grid">

            <div class="panel management-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Quick Management</h2>
                        <p>Everything you need to manage your portfolio.</p>
                    </div>

                    <span class="panel-heading-icon">⚙</span>
                </div>


                <div class="management-grid">

                    <a href="projects.php" class="management-card">
                        <span class="management-icon blue-icon">▦</span>
                        <div>
                            <strong>Projects</strong>
                            <small>Add, edit and publish projects.</small>
                        </div>
                        <span class="management-arrow">→</span>
                    </a>


                    <a href="skills.php" class="management-card">
                        <span class="management-icon purple-icon">◇</span>
                        <div>
                            <strong>Skills</strong>
                            <small>Manage technical skills.</small>
                        </div>
                        <span class="management-arrow">→</span>
                    </a>


                    <a href="services.php" class="management-card">
                        <span class="management-icon green-icon">✧</span>
                        <div>
                            <strong>Services</strong>
                            <small>Manage your professional services.</small>
                        </div>
                        <span class="management-arrow">→</span>
                    </a>


                    <a href="messages.php" class="management-card">
                        <span class="management-icon orange-icon">✉</span>
                        <div>
                            <strong>Messages</strong>
                            <small>Read and manage enquiries.</small>
                        </div>
                        <span class="management-arrow">→</span>
                    </a>

                </div>

            </div>


            <!-- ACTIVITY SUMMARY -->
            <div class="panel activity-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Portfolio Activity</h2>
                        <p>Your current content overview.</p>
                    </div>
                </div>

                <div class="activity-stat">
                    <div class="activity-stat-top">
                        <span>Published projects</span>
                        <strong><?= $stats['published'] ?>/<?= $stats['projects'] ?></strong>
                    </div>

                    <div class="progress-track">
                        <div
                            class="progress-fill blue-fill"
                            style="width: <?= $stats['projects'] > 0
                                ? min(100, ($stats['published'] / $stats['projects']) * 100)
                                : 0 ?>%;"
                        ></div>
                    </div>
                </div>


                <div class="activity-stat">
                    <div class="activity-stat-top">
                        <span>Active services</span>
                        <strong><?= $stats['services'] ?></strong>
                    </div>

                    <div class="activity-description">
                        Services currently displayed on your portfolio.
                    </div>
                </div>


                <div class="activity-stat">
                    <div class="activity-stat-top">
                        <span>Unread messages</span>
                        <strong><?= $stats['unread'] ?></strong>
                    </div>

                    <div class="activity-description">
                        Messages waiting for your attention.
                    </div>
                </div>

                <a href="messages.php" class="activity-link">
                    Manage incoming messages →
                </a>

            </div>

        </section>


        <!-- RECENT TABLES -->
        <section class="tables-grid">

            <div class="panel table-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Recent Messages</h2>
                        <p>Latest contact form submissions.</p>
                    </div>

                    <a href="messages.php" class="view-all-link">View all →</a>
                </div>


                <?php if (!$recentMessages): ?>

                    <div class="empty-state">
                        <span>✉</span>
                        <p>No messages have been received yet.</p>
                    </div>

                <?php else: ?>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>SENDER</th>
                                    <th>SUBJECT</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($recentMessages as $message): ?>

                                    <tr>
                                        <td>
                                            <div class="table-user">
                                                <span class="table-avatar">
                                                    <?= strtoupper(substr($message['name'], 0, 1)) ?>
                                                </span>

                                                <span>
                                                    <?= dashboardEscape($message['name']) ?>
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <?= dashboardEscape(
                                                $message['subject'] ?: 'No subject'
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= dashboardEscape($message['status']) ?>">
                                                <?= dashboardEscape($message['status']) ?>
                                            </span>
                                        </td>

                                        <td><?= dashboardEscape($message['created_at']) ?></td>

                                        <td>
                                            <a
                                                class="table-action"
                                                href="messages.php?view=<?= (int) $message['id'] ?>"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </div>


            <div class="panel table-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Recent Projects</h2>
                        <p>Recently added portfolio projects.</p>
                    </div>

                    <a href="projects.php" class="view-all-link">View all →</a>
                </div>


                <?php if (!$recentProjects): ?>

                    <div class="empty-state">
                        <span>▦</span>
                        <p>No projects have been added yet.</p>
                    </div>

                <?php else: ?>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>PROJECT</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($recentProjects as $project): ?>

                                    <tr>
                                        <td><?= dashboardEscape($project['title']) ?></td>

                                        <td>
                                            <span class="status-badge <?= dashboardEscape($project['status']) ?>">
                                                <?= dashboardEscape($project['status']) ?>
                                            </span>
                                        </td>

                                        <td><?= dashboardEscape($project['created_at']) ?></td>

                                        <td>
                                            <a
                                                class="table-action"
                                                href="projects.php?edit=<?= (int) $project['id'] ?>"
                                            >
                                                Edit
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </div>

        </section>


        <footer class="dashboard-footer">
            <span>© <?= date('Y') ?> DARIOS. All rights reserved.</span>
            <span>Built with <strong>♥</strong> by Darios</span>
        </footer>

    </main>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    notificationButton.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = notificationDropdown.classList.toggle('show');

        notificationButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
        if (!notificationDropdown.contains(event.target) &&
            !notificationButton.contains(event.target)) {

            notificationDropdown.classList.remove('show');
            notificationButton.setAttribute('aria-expanded', 'false');
        }
    });

    menuToggle.addEventListener('click', function () {
        sidebar.classList.toggle('sidebar-open');
    });

});
</script>

</body>
</html>
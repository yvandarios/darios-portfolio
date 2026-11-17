
<?php

require_once __DIR__ . '/auth.php';
require_admin();

// Dashboard statistics
$totalProjects = (int) $pdo->query(
    "SELECT COUNT(*) FROM projects"
)->fetchColumn();

$publishedProjects = (int) $pdo->query(
    "SELECT COUNT(*) FROM projects WHERE status = 'published'"
)->fetchColumn();

$totalSkills = (int) $pdo->query(
    "SELECT COUNT(*) FROM skills"
)->fetchColumn();

$totalServices = (int) $pdo->query(
    "SELECT COUNT(*) FROM services"
)->fetchColumn();

$totalMessages = (int) $pdo->query(
    "SELECT COUNT(*) FROM messages"
)->fetchColumn();

$unreadMessages = (int) $pdo->query(
    "SELECT COUNT(*) FROM messages WHERE status = 'unread'"
)->fetchColumn();

// Recent projects
$recentProjects = $pdo->query(
    "SELECT id, title, status, created_at
     FROM projects
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

// Recent messages
$recentMessages = $pdo->query(
    "SELECT id, name, email, subject, status, created_at
     FROM messages
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

// Secure logout token
if (empty($_SESSION['admin_logout_csrf'])) {
    $_SESSION['admin_logout_csrf'] = bin2hex(random_bytes(32));
}

$adminName = $_SESSION['admin_username'] ?? 'Administrator';

function dashboard_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dashboard_date($date): string
{
    if (empty($date)) {
        return '—';
    }

    return date('M d, Y', strtotime($date));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | DARIOS Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=1">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <a href="index.php" class="admin-logo">
            DARIOS<span>.</span>
            <small>ADMIN PANEL</small>
        </a>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="sidebar-nav">

            <a href="index.php" class="sidebar-link active">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <a href="projects.php" class="sidebar-link">
                <i class="fa-solid fa-folder-open"></i>
                <span>Projects</span>
            </a>

            <a href="skills.php" class="sidebar-link">
                <i class="fa-solid fa-code"></i>
                <span>Skills</span>
            </a>

            <a href="services.php" class="sidebar-link">
                <i class="fa-solid fa-briefcase"></i>
                <span>Services</span>
            </a>

            <a href="messages.php" class="sidebar-link">
                <i class="fa-solid fa-envelope"></i>
                <span>Messages</span>

                <?php if ($unreadMessages > 0): ?>
                    <span class="sidebar-badge">
                        <?= $unreadMessages ?>
                    </span>
                <?php endif; ?>
            </a>

        </nav>

        <div class="sidebar-label sidebar-label-bottom">WEBSITE</div>

        <nav class="sidebar-nav">

            <a href="../index.php" target="_blank" class="sidebar-link">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>View Website</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-profile">
                <div class="profile-avatar">
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>

                <div class="profile-info">
                    <strong><?= dashboard_escape($adminName) ?></strong>
                    <span>Administrator</span>
                </div>
            </div>

            <form action="logout.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= dashboard_escape($_SESSION['admin_logout_csrf']) ?>"
                >

                <button type="submit" class="logout-button">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>

        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">

        <header class="topbar">

            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle"
                        type="button" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?= dashboard_escape($adminName) ?>.</p>
                </div>
            </div>

            <div class="topbar-right">
                <span class="admin-status">
                    <span class="status-dot"></span>
                    System Online
                </span>

                <div class="topbar-avatar">
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>
            </div>

        </header>

        <section class="dashboard-content">

            <div class="welcome-banner">
                <div>
                    <span class="welcome-tag">OVERVIEW</span>
                    <h2>Your Portfolio at a Glance</h2>
                    <p>Manage your digital presence from one place.</p>
                </div>

                <div class="welcome-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>

            <!-- STATISTICS -->
            <div class="section-heading">
                <div>
                    <h2>Website Statistics</h2>
                    <p>Here is what's happening with your portfolio.</p>
                </div>
            </div>

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Total Projects</span>
                        <div class="stat-icon blue">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                    </div>
                    <h3><?= $totalProjects ?></h3>
                    <p><?= $publishedProjects ?> published projects</p>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Technical Skills</span>
                        <div class="stat-icon purple">
                            <i class="fa-solid fa-code"></i>
                        </div>
                    </div>
                    <h3><?= $totalSkills ?></h3>
                    <p>Skills in your portfolio</p>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Services</span>
                        <div class="stat-icon cyan">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                    </div>
                    <h3><?= $totalServices ?></h3>
                    <p>Services in your database</p>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Messages</span>
                        <div class="stat-icon orange">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                    </div>
                    <h3><?= $totalMessages ?></h3>
                    <p><?= $unreadMessages ?> unread messages</p>
                </div>

            </div>

            <!-- TABLES -->
            <div class="dashboard-tables">

                <section class="dashboard-panel">

                    <div class="panel-header">
                        <div>
                            <h2>Recent Projects</h2>
                            <p>Your latest portfolio projects.</p>
                        </div>

                        <a href="projects.php" class="panel-link">
                            View All <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($recentProjects)): ?>

                                    <?php foreach ($recentProjects as $project): ?>
                                        <tr>
                                            <td>
                                                <div class="table-primary">
                                                    <?= dashboard_escape($project['title']) ?>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="status-badge <?= $project['status'] === 'published' ? 'published' : 'draft' ?>">
                                                    <?= dashboard_escape(ucfirst($project['status'])) ?>
                                                </span>
                                            </td>

                                            <td><?= dashboard_date($project['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="3" class="empty-state">
                                            No projects added yet.
                                        </td>
                                    </tr>

                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </section>

                <section class="dashboard-panel">

                    <div class="panel-header">
                        <div>
                            <h2>Recent Messages</h2>
                            <p>Latest messages from visitors.</p>
                        </div>

                        <a href="messages.php" class="panel-link">
                            View All <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="message-list">

                        <?php if (!empty($recentMessages)): ?>

                            <?php foreach ($recentMessages as $message): ?>

                                <div class="message-item">

                                    <div class="message-avatar">
                                        <?= strtoupper(substr($message['name'], 0, 1)) ?>
                                    </div>

                                    <div class="message-details">
                                        <strong><?= dashboard_escape($message['name']) ?></strong>

                                        <span>
                                            <?= dashboard_escape($message['subject'] ?: 'No subject') ?>
                                        </span>

                                        <small>
                                            <?= dashboard_date($message['created_at']) ?>
                                        </small>
                                    </div>

                                    <?php if ($message['status'] === 'unread'): ?>
                                        <span class="unread-dot" title="Unread"></span>
                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="empty-state">
                                No messages received yet.
                            </div>

                        <?php endif; ?>

                    </div>

                </section>

            </div>

            <!-- QUICK ACTIONS -->
            <section class="quick-actions">

                <div class="section-heading">
                    <div>
                        <h2>Quick Actions</h2>
                        <p>Manage your portfolio faster.</p>
                    </div>
                </div>

                <div class="actions-grid">

                    <a href="projects.php" class="action-card">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add New Project</span>
                        <small>Showcase your work</small>
                    </a>

                    <a href="skills.php" class="action-card">
                        <i class="fa-solid fa-code"></i>
                        <span>Manage Skills</span>
                        <small>Update your expertise</small>
                    </a>

                    <a href="services.php" class="action-card">
                        <i class="fa-solid fa-briefcase"></i>
                        <span>Manage Services</span>
                        <small>Update your offerings</small>
                    </a>

                    <a href="messages.php" class="action-card">
                        <i class="fa-solid fa-comments"></i>
                        <span>Read Messages</span>
                        <small>Connect with visitors</small>
                    </a>

                </div>

            </section>

            <footer class="dashboard-footer">
                <p>&copy; <?= date('Y') ?> DARIOS Admin Panel. All rights reserved.</p>
                <span>Built by Yvan Darios</span>
            </footer>

        </section>

    </main>

</div>

<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');

sidebarToggle.addEventListener('click', function () {
    sidebar.classList.toggle('show');
});

document.addEventListener('click', function (event) {
    if (
        window.innerWidth <= 900 &&
        !sidebar.contains(event.target) &&
        !sidebarToggle.contains(event.target)
    ) {
        sidebar.classList.remove('show');
    }
});
</script>

</body>
</html>

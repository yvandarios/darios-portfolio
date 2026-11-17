
<?php

require_once __DIR__ . '/auth.php';
require_admin();

$pageTitle = 'Manage Projects';

$uploadDirectory = __DIR__ . '/../assets/images/projects/';
$uploadUrl = '../assets/images/projects/';
$maxFileSize = 3 * 1024 * 1024;

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

$error = '';
$success = '';

/* -------------------------------------------------
   SECURITY AND HELPER FUNCTIONS
------------------------------------------------- */

if (empty($_SESSION['projects_csrf'])) {
    $_SESSION['projects_csrf'] = bin2hex(random_bytes(32));
}

function project_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function project_slugify(string $value): string
{
    $value = trim($value);

    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);

    return trim($value, '-');
}

function project_delete_image(?string $filename, string $directory): void
{
    if (!$filename) {
        return;
    }

    $filename = basename($filename);
    $path = $directory . $filename;

    if (is_file($path)) {
        unlink($path);
    }
}

function project_validate_url(string $url): bool
{
    if ($url === '') {
        return true;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

    return in_array($scheme, ['http', 'https'], true);
}

/* -------------------------------------------------
   CREATE UPLOAD DIRECTORY
------------------------------------------------- */

if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        $error = 'Unable to create the project image directory.';
    }
}

/* -------------------------------------------------
   LOAD PROJECT FOR EDITING
------------------------------------------------- */

$editingProject = null;

if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $pdo->prepare(
        "SELECT * FROM projects WHERE id = :id LIMIT 1"
    );

    $stmt->execute(['id' => $editId]);

    $editingProject = $stmt->fetch();

    if (!$editingProject) {
        $error = 'The selected project could not be found.';
    }
}

/* -------------------------------------------------
   HANDLE FORM SUBMISSIONS
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['projects_csrf'], $token)
    ) {
        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        /* -----------------------------------------
           DELETE PROJECT
        ----------------------------------------- */

        if ($action === 'delete') {

            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                $error = 'Invalid project selected.';

            } else {

                $stmt = $pdo->prepare(
                    "SELECT image FROM projects WHERE id = :id LIMIT 1"
                );

                $stmt->execute(['id' => $id]);
                $project = $stmt->fetch();

                if (!$project) {
                    $error = 'Project not found.';

                } else {

                    try {
                        $stmt = $pdo->prepare(
                            "DELETE FROM projects WHERE id = :id"
                        );

                        $stmt->execute(['id' => $id]);

                        project_delete_image(
                            $project['image'],
                            $uploadDirectory
                        );

                        header('Location: projects.php?deleted=1');
                        exit;

                    } catch (PDOException $e) {
                        error_log('Project deletion error: ' . $e->getMessage());
                        $error = 'Unable to delete this project.';
                    }
                }
            }
        }

        /* -----------------------------------------
           CREATE OR UPDATE PROJECT
        ----------------------------------------- */

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? 0,
                FILTER_VALIDATE_INT
            );

            $id = $id ?: 0;

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $technologies = trim($_POST['technologies'] ?? '');
            $projectUrl = trim($_POST['project_url'] ?? '');
            $githubUrl = trim($_POST['github_url'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            $featured = isset($_POST['featured']) ? 1 : 0;

            $oldImage = '';
            $currentProject = null;

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    "SELECT * FROM projects WHERE id = :id LIMIT 1"
                );

                $stmt->execute(['id' => $id]);
                $currentProject = $stmt->fetch();

                if (!$currentProject) {
                    $error = 'The project you are editing does not exist.';
                } else {
                    $oldImage = $currentProject['image'] ?? '';
                }
            }

            if ($error === '') {

                if ($title === '' || $description === '' || $technologies === '') {
                    $error = 'Please complete all required project fields.';

                } elseif (!in_array($status, ['published', 'draft'], true)) {
                    $error = 'Invalid project status.';

                } elseif (
                    !project_validate_url($projectUrl) ||
                    !project_validate_url($githubUrl)
                ) {
                    $error = 'Please enter valid HTTP or HTTPS project links.';
                }
            }

            /* Generate unique slug */

            if ($error === '') {

                $slugBase = project_slugify($title);

                if ($slugBase === '') {
                    $error = 'Please use a title containing letters or numbers.';
                } else {

                    $slug = $slugBase;
                    $counter = 1;

                    while (true) {

                        $sql = "SELECT id FROM projects WHERE slug = :slug";

                        $params = ['slug' => $slug];

                        if ($id > 0) {
                            $sql .= " AND id != :id";
                            $params['id'] = $id;
                        }

                        $check = $pdo->prepare($sql);
                        $check->execute($params);

                        if (!$check->fetch()) {
                            break;
                        }

                        $counter++;
                        $slug = $slugBase . '-' . $counter;
                    }
                }
            }

            /* Handle image upload */

            $imageName = $oldImage;

            if (
                $error === '' &&
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES['image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Image upload failed. Please try again.';

                } elseif ($file['size'] > $maxFileSize) {
                    $error = 'Image size must not exceed 3 MB.';

                } else {

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($file['tmp_name']);

                    if (!isset($allowedMimeTypes[$mimeType])) {
                        $error = 'Only JPG, PNG and WEBP images are allowed.';

                    } elseif (getimagesize($file['tmp_name']) === false) {
                        $error = 'The uploaded file is not a valid image.';

                    } else {

                        $extension = $allowedMimeTypes[$mimeType];

                        $newImageName = bin2hex(random_bytes(16))
                            . '.' . $extension;

                        if (
                            move_uploaded_file(
                                $file['tmp_name'],
                                $uploadDirectory . $newImageName
                            )
                        ) {
                            $imageName = $newImageName;
                        } else {
                            $error = 'Unable to save the uploaded image.';
                        }
                    }
                }
            }

            /* Save project to database */

            if ($error === '') {

                try {

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE projects SET
                                title = :title,
                                slug = :slug,
                                description = :description,
                                technologies = :technologies,
                                image = :image,
                                project_url = :project_url,
                                github_url = :github_url,
                                featured = :featured,
                                status = :status,
                                updated_at = CURRENT_TIMESTAMP
                             WHERE id = :id"
                        );

                        $stmt->execute([
                            'title' => $title,
                            'slug' => $slug,
                            'description' => $description,
                            'technologies' => $technologies,
                            'image' => $imageName,
                            'project_url' => $projectUrl ?: null,
                            'github_url' => $githubUrl ?: null,
                            'featured' => $featured,
                            'status' => $status,
                            'id' => $id
                        ]);

                        if ($imageName !== $oldImage) {
                            project_delete_image(
                                $oldImage,
                                $uploadDirectory
                            );
                        }

                        header('Location: projects.php?updated=1');
                        exit;

                    } else {

                        $stmt = $pdo->prepare(
                            "INSERT INTO projects (
                                title,
                                slug,
                                description,
                                technologies,
                                image,
                                project_url,
                                github_url,
                                featured,
                                status
                            ) VALUES (
                                :title,
                                :slug,
                                :description,
                                :technologies,
                                :image,
                                :project_url,
                                :github_url,
                                :featured,
                                :status
                            )"
                        );

                        $stmt->execute([
                            'title' => $title,
                            'slug' => $slug,
                            'description' => $description,
                            'technologies' => $technologies,
                            'image' => $imageName,
                            'project_url' => $projectUrl ?: null,
                            'github_url' => $githubUrl ?: null,
                            'featured' => $featured,
                            'status' => $status
                        ]);

                        header('Location: projects.php?created=1');
                        exit;
                    }

                } catch (PDOException $e) {

                    error_log('Project save error: ' . $e->getMessage());

                    if ($imageName !== $oldImage) {
                        project_delete_image(
                            $imageName,
                            $uploadDirectory
                        );
                    }

                    $error = 'Unable to save the project. Please try again.';
                }
            }

            // Keep submitted values available if validation fails.
            $editingProject = [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'technologies' => $technologies,
                'image' => $imageName,
                'project_url' => $projectUrl,
                'github_url' => $githubUrl,
                'featured' => $featured,
                'status' => $status
            ];
        }
    }
}

/* -------------------------------------------------
   SUCCESS NOTIFICATIONS
------------------------------------------------- */

if (isset($_GET['created'])) {
    $success = 'Project created successfully.';
} elseif (isset($_GET['updated'])) {
    $success = 'Project updated successfully.';
} elseif (isset($_GET['deleted'])) {
    $success = 'Project deleted successfully.';
}

/* -------------------------------------------------
   FETCH ALL PROJECTS
------------------------------------------------- */

$projects = $pdo->query(
    "SELECT id, title, slug, image, technologies, featured,
            status, created_at
     FROM projects
     ORDER BY created_at DESC"
)->fetchAll();

$adminName = $_SESSION['admin_username'] ?? 'Administrator';

if (empty($_SESSION['admin_logout_csrf'])) {
    $_SESSION['admin_logout_csrf'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Projects | DARIOS Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=1">

    <style>
        .page-content {
            padding: 35px 38px;
            max-width: 1600px;
            margin: auto;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-heading h2 {
            font-size: 23px;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #8995ad;
            font-size: 12px;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(120deg, #347cff, #7655ed);
            color: white;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .project-form-panel,
        .projects-panel {
            background: #111a2d;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .form-panel-header,
        .projects-panel-header {
            padding: 22px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        .form-panel-header h3,
        .projects-panel-header h3 {
            font-size: 15px;
        }

        .form-panel-header p,
        .projects-panel-header p {
            color: #8995ad;
            font-size: 11px;
            margin-top: 6px;
        }

        .project-form {
            padding: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #c8d2e5;
            font-size: 12px;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 13px;
            border: 1px solid #28334b;
            border-radius: 8px;
            outline: none;
            background: #080e1e;
            color: white;
            font-family: inherit;
            font-size: 12px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #4385ff;
        }

        .form-group textarea {
            min-height: 125px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-help {
            color: #8995ad;
            font-size: 10px;
        }

        .current-image {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 5px;
        }

        .current-image img {
            width: 85px;
            height: 60px;
            object-fit: cover;
            border-radius: 7px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            color: #c8d2e5;
            font-size: 12px;
        }

        .checkbox-group input {
            width: 16px;
            height: 16px;
            accent-color: #4385ff;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border: 1px solid #34415a;
            border-radius: 8px;
            color: #c8d2e5;
            font-size: 12px;
            text-decoration: none;
        }

        .notice {
            padding: 14px 17px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        .notice.success {
            color: #69e0b1;
            background: rgba(53,214,155,.10);
            border: 1px solid rgba(53,214,155,.2);
        }

        .notice.error {
            color: #ff929f;
            background: rgba(255,70,90,.10);
            border: 1px solid rgba(255,70,90,.2);
        }

        .project-thumb {
            width: 65px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            background: #202b40;
        }

        .project-title {
            color: #f1f5ff;
            font-weight: 600;
            font-size: 12px;
        }

        .project-technologies {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #8995ad;
            font-size: 10px;
        }

        .featured-label {
            color: #f6c66c;
            font-size: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons a,
        .action-buttons button {
            display: grid;
            place-items: center;
            width: 31px;
            height: 31px;
            border: 1px solid #34415a;
            border-radius: 7px;
            background: transparent;
            color: #aab5ca;
            cursor: pointer;
            text-decoration: none;
            font-size: 11px;
        }

        .action-buttons a:hover {
            color: #75a4ff;
            border-color: #4385ff;
        }

        .action-buttons button:hover {
            color: #ff8290;
            border-color: #ff8290;
        }

        .delete-form {
            display: inline;
        }

        @media(max-width: 900px) {
            .page-content {
                padding: 28px 22px;
            }
        }

        @media(max-width: 600px) {
            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .project-form {
                padding: 18px;
            }

            .form-actions {
                flex-wrap: wrap;
            }
        }
    </style>
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

            <a href="index.php" class="sidebar-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <a href="projects.php" class="sidebar-link active">
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
                    <strong><?= project_escape($adminName) ?></strong>
                    <span>Administrator</span>
                </div>
            </div>

            <form action="logout.php" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= project_escape($_SESSION['admin_logout_csrf']) ?>">

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
                    <h1>Projects</h1>
                    <p>Manage your portfolio projects.</p>
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

        <section class="page-content">

            <?php if ($success): ?>
                <div class="notice success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= project_escape($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="notice error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= project_escape($error) ?>
                </div>
            <?php endif; ?>

            <div class="page-heading">

                <div>
                    <h2>Project Management</h2>
                    <p>Add, edit, publish and organize your work.</p>
                </div>

                <a href="#projectForm" class="primary-button">
                    <i class="fa-solid fa-plus"></i>
                    Add New Project
                </a>

            </div>

            <!-- PROJECT FORM -->

            <section class="project-form-panel" id="projectForm">

                <div class="form-panel-header">

                    <h3>
                        <?= $editingProject ? 'Edit Project' : 'Create New Project' ?>
                    </h3>

                    <p>
                        <?= $editingProject
                            ? 'Update the information of this project.'
                            : 'Fill in the details of your new portfolio project.' ?>
                    </p>

                </div>

                <form class="project-form"
                      method="POST"
                      enctype="multipart/form-data">

                    <input type="hidden" name="csrf_token"
                           value="<?= project_escape($_SESSION['projects_csrf']) ?>">

                    <input type="hidden" name="action" value="save">

                    <input type="hidden" name="id"
                           value="<?= (int) ($editingProject['id'] ?? 0) ?>">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="title">Project Title *</label>

                            <input type="text"
                                   id="title"
                                   name="title"
                                   placeholder="e.g. CAMSELLS B2B E-Commerce"
                                   value="<?= project_escape($editingProject['title'] ?? '') ?>"
                                   maxlength="255"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="technologies">Technologies *</label>

                            <input type="text"
                                   id="technologies"
                                   name="technologies"
                                   placeholder="PHP, MySQL, JavaScript, Bootstrap"
                                   value="<?= project_escape($editingProject['technologies'] ?? '') ?>"
                                   required>

                            <span class="form-help">
                                Separate technologies with commas.
                            </span>
                        </div>

                        <div class="form-group full">
                            <label for="description">Project Description *</label>

                            <textarea id="description"
                                      name="description"
                                      placeholder="Describe your project, its purpose and the features you developed..."
                                      required><?= project_escape($editingProject['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="project_url">Live Project URL</label>

                            <input type="url"
                                   id="project_url"
                                   name="project_url"
                                   placeholder="https://example.com"
                                   value="<?= project_escape($editingProject['project_url'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="github_url">GitHub Repository URL</label>

                            <input type="url"
                                   id="github_url"
                                   name="github_url"
                                   placeholder="https://github.com/username/project"
                                   value="<?= project_escape($editingProject['github_url'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="image">Project Image</label>

                            <input type="file"
                                   id="image"
                                   name="image"
                                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                            <span class="form-help">
                                JPG, PNG or WEBP. Maximum size: 3 MB.
                            </span>

                            <?php if (!empty($editingProject['image'])): ?>
                                <div class="current-image">
                                    <img
                                        src="<?= project_escape($uploadUrl . basename($editingProject['image'])) ?>"
                                        alt="Current project image">

                                    <span class="form-help">
                                        Current project image
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="status">Publication Status</label>

                            <select id="status" name="status">
                                <option value="draft"
                                    <?= ($editingProject['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                                    Draft
                                </option>

                                <option value="published"
                                    <?= ($editingProject['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                                    Published
                                </option>
                            </select>
                        </div>

                    </div>

                    <label class="checkbox-group">
                        <input type="checkbox"
                               name="featured"
                               value="1"
                               <?= !empty($editingProject['featured']) ? 'checked' : '' ?>>

                        Feature this project on the homepage.
                    </label>

                    <div class="form-actions">

                        <button type="submit" class="primary-button">
                            <i class="fa-solid fa-floppy-disk"></i>

                            <?= $editingProject ? 'Update Project' : 'Save Project' ?>
                        </button>

                        <?php if ($editingProject): ?>
                            <a href="projects.php" class="secondary-button">
                                Cancel Editing
                            </a>
                        <?php endif; ?>

                    </div>

                </form>

            </section>

            <!-- PROJECTS TABLE -->

            <section class="projects-panel">

                <div class="projects-panel-header">
                    <h3>All Projects (<?= count($projects) ?>)</h3>
                    <p>Manage the projects displayed in your portfolio.</p>
                </div>

                <div class="table-responsive">

                    <table>

                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Project</th>
                                <th>Technologies</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($projects)): ?>

                                <?php foreach ($projects as $project): ?>

                                    <tr>

                                        <td>
                                            <?php if (!empty($project['image'])): ?>

                                                <img
                                                    class="project-thumb"
                                                    src="<?= project_escape($uploadUrl . basename($project['image'])) ?>"
                                                    alt="Project image">

                                            <?php else: ?>

                                                <div class="project-thumb"
                                                     style="display:grid;place-items:center;color:#8995ad;">
                                                    <i class="fa-solid fa-image"></i>
                                                </div>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="project-title">
                                                <?= project_escape($project['title']) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="project-technologies">
                                                <?= project_escape($project['technologies']) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= $project['status'] === 'published' ? 'published' : 'draft' ?>">
                                                <?= project_escape(ucfirst($project['status'])) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($project['featured']): ?>
                                                <span class="featured-label">
                                                    <i class="fa-solid fa-star"></i>
                                                    Featured
                                                </span>
                                            <?php else: ?>
                                                <span class="form-help">No</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= !empty($project['created_at'])
                                                ? project_escape(date('M d, Y', strtotime($project['created_at'])))
                                                : '—' ?>
                                        </td>

                                        <td>
                                            <div class="action-buttons">

                                                <a href="projects.php?edit=<?= (int) $project['id'] ?>"
                                                   title="Edit Project">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>

                                                <form class="delete-form"
                                                      method="POST"
                                                      onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">

                                                    <input type="hidden"
                                                           name="csrf_token"
                                                           value="<?= project_escape($_SESSION['projects_csrf']) ?>">

                                                    <input type="hidden"
                                                           name="action"
                                                           value="delete">

                                                    <input type="hidden"
                                                           name="id"
                                                           value="<?= (int) $project['id'] ?>">

                                                    <button type="submit"
                                                            title="Delete Project">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>

                                                </form>

                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="empty-state">
                                        No projects found. Add your first project above.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

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

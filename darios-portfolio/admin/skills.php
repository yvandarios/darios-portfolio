
<?php
require_once __DIR__ . '/auth.php';
require_admin();

$pageTitle = 'Manage Skills';
$activePage = 'skills.php';

function skill_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['skills_csrf'])) {
    $_SESSION['skills_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['skills_csrf'];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'save') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
                $name = trim($_POST['name'] ?? '');

                if ($name === '') {
                    throw new RuntimeException('Please enter a skill name.');
                }

                if (mb_strlen($name) > 100) {
                    throw new RuntimeException('The skill name must not exceed 100 characters.');
                }

                // Prevent duplicate skill names
                $check = $pdo->prepare(
                    'SELECT id FROM skills WHERE name = :name AND id != :id LIMIT 1'
                );
                $check->execute([
                    'name' => $name,
                    'id' => $id
                ]);

                if ($check->fetch()) {
                    throw new RuntimeException('This skill already exists.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE skills SET name = :name WHERE id = :id'
                    );
                    $stmt->execute([
                        'name' => $name,
                        'id' => $id
                    ]);

                    $success = 'Skill updated successfully.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO skills (name) VALUES (:name)'
                    );
                    $stmt->execute(['name' => $name]);

                    $success = 'New skill added successfully.';
                }

                // Clear edit mode after saving
                $_GET['edit'] = null;
            } elseif ($action === 'delete') {
                $id = filter_var(
                    $_POST['id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new RuntimeException('Invalid skill selected.');
                }

                $stmt = $pdo->prepare('DELETE FROM skills WHERE id = :id');
                $stmt->execute(['id' => $id]);

                $success = $stmt->rowCount() > 0
                    ? 'Skill deleted successfully.'
                    : 'The selected skill was not found.';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please try again.';
        }
    }
}

// Load skill being edited
$editingSkill = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM skills WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editingSkill = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editingSkill) {
        $error = 'The selected skill could not be found.';
    }
}

// Load all skills
$stmt = $pdo->query('SELECT id, name FROM skills ORDER BY name ASC');
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSkills = count($skills);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= skill_escape($pageTitle) ?> | DARIOS Admin</title>

<link rel="stylesheet" href="../assets/css/admin-skills.css">
    <style>
        .skills-page {
            padding: 30px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .page-heading h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .page-heading p {
            margin: 0;
            color: #9298aa;
        }

        .skill-summary {
            background: #171b2b;
            border: 1px solid #292f45;
            padding: 22px;
            border-radius: 14px;
            margin-bottom: 25px;
        }

        .skill-summary span {
            display: block;
            color: #9298aa;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .skill-summary strong {
            font-size: 28px;
            color: #fff;
        }

        .skills-layout {
            display: grid;
            grid-template-columns: minmax(260px, 350px) 1fr;
            gap: 25px;
            align-items: start;
        }

        .skill-panel {
            background: #171b2b;
            border: 1px solid #292f45;
            border-radius: 14px;
            padding: 24px;
        }

        .skill-panel h2 {
            font-size: 18px;
            margin: 0 0 22px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #dce0eb;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            background: #101423;
            border: 1px solid #343b52;
            border-radius: 8px;
            color: #fff;
            outline: none;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #7771ff;
        }

        .btn-primary,
        .btn-secondary,
        .btn-delete,
        .btn-edit {
            display: inline-block;
            border: none;
            border-radius: 8px;
            padding: 11px 16px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6965ff, #9b59ff);
            color: #fff;
        }

        .btn-secondary {
            background: #30364b;
            color: #fff;
            margin-left: 6px;
        }

        .btn-edit {
            background: #263b62;
            color: #91baff;
        }

        .btn-delete {
            background: #48252f;
            color: #ff9ba9;
        }

        .skill-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 14px;
        }

        .skill-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #101423;
            border: 1px solid #292f45;
            border-radius: 10px;
            padding: 15px;
        }

        .skill-name {
            color: #f1f2f8;
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .skill-actions {
            display: flex;
            gap: 7px;
            flex-shrink: 0;
        }

        .skill-actions a,
        .skill-actions button {
            padding: 7px 9px;
            font-size: 11px;
        }

        .alert {
            padding: 14px 17px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #173b30;
            border: 1px solid #246b50;
            color: #8ce2b8;
        }

        .alert-error {
            background: #48252f;
            border: 1px solid #813d4b;
            color: #ffb0bb;
        }

        .empty-state {
            color: #9298aa;
            text-align: center;
            padding: 35px 15px;
            background: #101423;
            border-radius: 10px;
            grid-column: 1 / -1;
        }

        @media (max-width: 900px) {
            .skills-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .skills-page {
                padding: 18px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .skill-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <h2>DARIOS<span>.</span></h2>
            <small>ADMIN PANEL</small>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php">Dashboard</a>
            <a href="projects.php">Projects</a>
            <a href="skills.php" class="active">Skills</a>
            <a href="services.php">Services</a>
            <a href="messages.php">Messages</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../index.php" target="_blank">View Website</a>

            <form action="logout.php" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= skill_escape($_SESSION['logout_csrf'] ?? '') ?>">
                <button type="submit">Logout</button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <span>Administration</span>
                <h2>Skills Management</h2>
            </div>
        </header>

        <section class="skills-page">

            <div class="page-heading">
                <div>
                    <h1>Manage Skills</h1>
                    <p>Add and maintain the technical skills displayed on your portfolio.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= skill_escape($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= skill_escape($error) ?>
                </div>
            <?php endif; ?>

            <div class="skill-summary">
                <span>Total Technical Skills</span>
                <strong><?= $totalSkills ?></strong>
            </div>

            <div class="skills-layout">

                <section class="skill-panel">
                    <h2>
                        <?= $editingSkill ? 'Edit Skill' : 'Add New Skill' ?>
                    </h2>

                    <form method="POST" action="skills.php<?= $editingSkill ? '?edit=' . (int)$editingSkill['id'] : '' ?>">
                        <input type="hidden" name="csrf_token"
                               value="<?= skill_escape($csrfToken) ?>">
                        <input type="hidden" name="action" value="save">

                        <input type="hidden" name="id"
                               value="<?= (int)($editingSkill['id'] ?? 0) ?>">

                        <div class="form-group">
                            <label for="name">Skill Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                maxlength="100"
                                placeholder="e.g. PHP, JavaScript, MySQL"
                                value="<?= skill_escape($editingSkill['name'] ?? '') ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="btn-primary">
                            <?= $editingSkill ? 'Update Skill' : 'Add Skill' ?>
                        </button>

                        <?php if ($editingSkill): ?>
                            <a href="skills.php" class="btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </section>

                <section class="skill-panel">
                    <h2>Your Technical Skills</h2>

                    <div class="skill-list">
                        <?php if (empty($skills)): ?>
                            <div class="empty-state">
                                No skills added yet. Start by adding your first skill.
                            </div>
                        <?php else: ?>
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill-item">
                                    <span class="skill-name">
                                        <?= skill_escape($skill['name']) ?>
                                    </span>

                                    <div class="skill-actions">
                                        <a
                                            class="btn-edit"
                                            href="skills.php?edit=<?= (int)$skill['id'] ?>"
                                        >Edit</a>

                                        <form method="POST" action="skills.php"
                                              onsubmit="return confirm('Are you sure you want to delete this skill?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= skill_escape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id"
                                                   value="<?= (int)$skill['id'] ?>">

                                            <button type="submit" class="btn-delete">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </section>
    </main>

</div>

</body>
</html>

<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

require '../php/db_connect.php';

// Handle actions (mark as read, delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        if ($_POST['action'] == 'mark_read') {
            $stmt = $conn->prepare("UPDATE contact_form SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'delete') {
            $stmt = $conn->prepare("DELETE FROM contact_form WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: admin_dashboard.php" . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
    exit();
}

// Fetch Stats
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM contact_form")->fetch_assoc()['count'];
$stats['unread'] = $conn->query("SELECT COUNT(*) as count FROM contact_form WHERE is_read = 0")->fetch_assoc()['count'];
$stats['replied'] = $conn->query("SELECT COUNT(*) as count FROM contact_form WHERE is_replied = 1")->fetch_assoc()['count'];

// Filter logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql_where = '';
switch ($filter) {
    case 'unread': $sql_where = 'WHERE is_read = 0'; break;
    case 'read': $sql_where = 'WHERE is_read = 1'; break;
    case 'replied': $sql_where = 'WHERE is_replied = 1'; break;
    default: $sql_where = ''; break;
}

// Fetch messages
$sql = "SELECT * FROM contact_form $sql_where ORDER BY Time DESC";
$result = $conn->query($sql);
$messages = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Lung TFDB</title>
    <link rel="icon" href="../favicon_io/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: 0.8rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 4px solid var(--primary-color);
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.8;
        }

        .stat-info h3 { font-size: 0.9rem; opacity: 0.7; margin: 0; }
        .stat-info p { font-size: 1.8rem; font-weight: 700; margin: 0; }

        .filter-nav {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 2rem;
            background-color: var(--card-bg);
            color: var(--text-color);
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
        }

        .message-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 6px solid #ccc;
            transition: var(--transition);
        }

        .message-card.unread { border-left-color: #2ecc71; }
        .message-card.replied { border-left-color: var(--primary-color); }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .message-subject { color: var(--primary-color); font-size: 1.2rem; margin: 0; }
        .message-meta { font-size: 0.85rem; opacity: 0.6; }

        .message-body {
            background-color: rgba(0,0,0,0.03);
            padding: 1.2rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            white-space: pre-wrap;
        }
        .dark-mode .message-body { background-color: rgba(255,255,255,0.05); }

        .message-actions { display: flex; justify-content: flex-end; gap: 1rem; }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 0.4rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
        }

        .btn-read { background: #3498db; color: white; }
        .btn-reply { background: #2ecc71; color: white; }
        .btn-delete { background: #e74c3c; color: white; }

        .reply-box {
            display: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dotted #ccc;
        }

        .reply-box textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.8rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            background: var(--bg-color);
            color: var(--text-color);
            border: 1px solid rgba(0,0,0,0.1);
        }

        .logout-link {
            color: #e74c3c;
            font-weight: 600;
            margin-left: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="../index.html" class="logo">
                <i class="fa-solid fa-lungs"></i>
                <span>Lung TFDB Admin</span>
            </a>
            <div class="nav-container">
                <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
                <form action="admin_logout.php" method="post" style="display:inline;">
                    <button type="submit" style="background:none; border:none; cursor:pointer;" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="admin-container">
        <h1 class="section-title">Admin Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                <div class="stat-info"><h3>Total</h3><p><?php echo $stats['total']; ?></p></div>
            </div>
            <div class="stat-card" style="border-bottom-color: #2ecc71;">
                <div class="stat-icon" style="color:#2ecc71;"><i class="fas fa-envelope"></i></div>
                <div class="stat-info"><h3>Unread</h3><p><?php echo $stats['unread']; ?></p></div>
            </div>
            <div class="stat-card" style="border-bottom-color: #3498db;">
                <div class="stat-icon" style="color:#3498db;"><i class="fas fa-reply-all"></i></div>
                <div class="stat-info"><h3>Replied</h3><p><?php echo $stats['replied']; ?></p></div>
            </div>
        </div>

        <div class="filter-nav">
            <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=unread" class="filter-btn <?php echo $filter == 'unread' ? 'active' : ''; ?>">Unread</a>
            <a href="?filter=read" class="filter-btn <?php echo $filter == 'read' ? 'active' : ''; ?>">Read</a>
            <a href="?filter=replied" class="filter-btn <?php echo $filter == 'replied' ? 'active' : ''; ?>">Replied</a>
        </div>

        <div class="message-list">
            <?php foreach ($messages as $msg): ?>
                <div class="message-card <?php echo $msg['is_read'] ? 'read' : 'unread'; ?> <?php echo $msg['is_replied'] ? 'replied' : ''; ?>">
                    <div class="message-header">
                        <div>
                            <h3 class="message-subject"><?php echo htmlspecialchars($msg['Subject']); ?></h3>
                            <p><strong>From:</strong> <?php echo htmlspecialchars($msg['Name']); ?> (<?php echo htmlspecialchars($msg['Email']); ?>)</p>
                        </div>
                        <div class="message-meta">
                            <p><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y H:i', strtotime($msg['Time'])); ?></p>
                        </div>
                    </div>
                    <div class="message-body"><?php echo htmlspecialchars($msg['Message']); ?></div>
                    <div class="message-actions">
                        <?php if (!$msg['is_read']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                <input type="hidden" name="action" value="mark_read">
                                <button type="submit" class="btn-action btn-read"><i class="fas fa-check"></i> Read</button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn-action btn-reply" data-id="<?php echo $msg['id']; ?>"><i class="fas fa-reply"></i> Reply</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete permanently?');">
                            <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <div class="reply-box" id="reply-<?php echo $msg['id']; ?>">
                        <form class="reply-form" data-id="<?php echo $msg['id']; ?>">
                            <textarea placeholder="Write reply..."></textarea>
                            <button type="submit" class="btn-action btn-reply">Send Reply</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('.btn-reply').click(function() {
                const id = $(this).data('id');
                $(`#reply-${id}`).slideToggle();
            });

            $('.reply-form').submit(function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                $.ajax({
                    url: 'admin_update_reply_status.php',
                    type: 'POST',
                    data: { id: id, action: 'mark_replied' },
                    success: function(res) {
                        if(res === 'Success') { alert('Replied!'); location.reload(); }
                    }
                });
            });
        });
    </script>
</body>
</html>
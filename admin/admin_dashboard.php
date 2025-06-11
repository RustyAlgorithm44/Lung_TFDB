<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

require 'db_connect_admin.php';

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
    // Redirect to prevent form re-submission on refresh
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch contact form entries, ordered by unread first
$sql = "SELECT id, Name, Email, Subject, Message, Time, is_read, is_replied, read_at, replied_at FROM contact_form ORDER BY is_read ASC, Time DESC";
$result = $conn->query($sql);

$messages = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

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
        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--card-bg);
            border-radius: 0.8rem;
            box-shadow: var(--shadow);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: var(--primary-color);
            font-size: 2.2rem;
        }

        .logout-btn {
            background-color: #d9534f;
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #c9302c;
            transform: translateY(-2px);
        }

        .message-list {
            display: grid;
            gap: 1.5rem;
        }

        .message-card {
            background-color: var(--bg-color);
            padding: 1.5rem;
            border-radius: 0.8rem;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary-color);
            position: relative;
        }

        .message-card.read {
            border-left: 5px solid #6c757d; /* Grey for read messages */
            opacity: 0.8;
        }
        
        .message-card.unread {
            border-left: 5px solid #28a745; /* Green for unread messages */
        }


        .message-card h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }

        .message-card p {
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .message-card .email, .message-card .time {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .message-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .message-actions button {
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .message-actions .mark-read-btn {
            background-color: #007bff;
            color: white;
        }

        .message-actions .mark-read-btn:hover {
            background-color: #0056b3;
        }

        .message-actions .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .message-actions .delete-btn:hover {
            background-color: #c82333;
        }

        .message-actions .reply-btn {
            background-color: #28a745;
            color: white;
        }

        .message-actions .reply-btn:hover {
            background-color: #218838;
        }

        .reply-form-container {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            display: none; /* Hidden by default */
        }

        .reply-form-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .reply-form-container input[type="text"],
        .reply-form-container input[type="email"],
        .reply-form-container textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .reply-form-container textarea {
            min-height: 120px;
            resize: vertical;
        }

        .reply-form-container .send-email-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .reply-form-container .send-email-btn:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="../index.html" class="logo">
                <i class="fas fa-dna"></i>
                <span>Lung TFDB</span>
            </a>
            <div class="nav-container">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <form action="admin_logout.php" method="post">
                    <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
        </div>

        <div class="message-list">
            <?php if (empty($messages)): ?>
                <p style="text-align: center; color: var(--text-color);">No contact form entries found.</p>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message-card <?php echo $message['is_read'] ? 'read' : 'unread'; ?>">
                        <h3>Subject: <?php echo htmlspecialchars($message['Subject']); ?></h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($message['Name']); ?></p>
                        <p class="email"><strong>Email:</strong> <?php echo htmlspecialchars($message['Email']); ?></p>
                        <p><strong>Message:</strong><br><?php echo nl2br(htmlspecialchars($message['Message'])); ?></p>
                        <p class="time"><strong>Received:</strong> <?php echo htmlspecialchars($message['Time']); ?></p>
                        <?php if ($message['is_read']): ?>
                            <p class="time"><strong>Read At:</strong> <?php echo htmlspecialchars($message['read_at']); ?></p>
                        <?php endif; ?>
                        <?php if ($message['is_replied']): ?>
                            <p class="time"><strong>Replied At:</strong> <?php echo htmlspecialchars($message['replied_at']); ?></p>
                        <?php endif; ?>

                        <div class="message-actions">
                            <?php if (!$message['is_read']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                    <input type="hidden" name="action" value="mark_read">
                                    <button type="submit" class="mark-read-btn"><i class="fas fa-eye"></i> Mark as Read</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                            <button type="button" class="reply-btn" data-email="<?php echo htmlspecialchars($message['Email']); ?>" data-subject="Re: <?php echo htmlspecialchars($message['Subject']); ?>">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                        </div>

                        <div class="reply-form-container">
                            <form class="reply-email-form" data-message-id="<?php echo $message['id']; ?>">
                                <div class="form-group">
                                    <label for="reply_to_<?php echo $message['id']; ?>">To:</label>
                                    <input type="email" id="reply_to_<?php echo $message['id']; ?>" class="reply-to" value="<?php echo htmlspecialchars($message['Email']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="reply_from_<?php echo $message['id']; ?>">From:</label>
                                    <input type="email" id="reply_from_<?php echo $message['id']; ?>" class="reply-from" value="your_email@example.com" readonly> </div>
                                <div class="form-group">
                                    <label for="reply_cc_<?php echo $message['id']; ?>">CC:</label>
                                    <input type="email" id="reply_cc_<?php echo $message['id']; ?>" class="reply-cc">
                                </div>
                                <div class="form-group">
                                    <label for="reply_subject_<?php echo $message['id']; ?>">Subject:</label>
                                    <input type="text" id="reply_subject_<?php echo $message['id']; ?>" class="reply-subject" value="Re: <?php echo htmlspecialchars($message['Subject']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="reply_message_<?php echo $message['id']; ?>">Your Message:</label>
                                    <textarea id="reply_message_<?php echo $message['id']; ?>" class="reply-message" required></textarea>
                                </div>
                                <button type="submit" class="send-email-btn"><i class="fas fa-paper-plane"></i> Send Email</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Lung TFDB</h3>
                <p>A comprehensive database for lung cancer transcription factors with integrated multi-omics data and analysis tools.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../download.html">Download</a></li>
                    <li><a href="../contact.html">Contact Us</a></li>
                    <li><a href="#">Documentation</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="#">API Access</a></li>
                    <li><a href="#">Tutorials</a></li>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Publications</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; 2024 SSNCCPR. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle reply form visibility
            $('.reply-btn').on('click', function() {
                $(this).closest('.message-card').find('.reply-form-container').slideToggle();
            });

            // Handle email sending (frontend part)
            $('.reply-email-form').on('submit', function(event) {
                event.preventDefault();
                var messageId = $(this).data('message-id');
                var toEmail = $(this).find('.reply-to').val();
                var fromEmail = $(this).find('.reply-from').val();
                var ccEmail = $(this).find('.reply-cc').val();
                var subject = $(this).find('.reply-subject').val();
                var messageBody = $(this).find('.reply-message').val();

                // For now, just log the email details and update UI
                console.log('--- Sending Email ---');
                console.log('To:', toEmail);
                console.log('From:', fromEmail);
                console.log('CC:', ccEmail);
                console.log('Subject:', subject);
                console.log('Message:', messageBody);
                console.log('---------------------');

                // In a real application, you would send this data to a backend PHP script
                // that uses a mail library (like PHPMailer) to send the email.
                // For demonstration, we'll just show an alert and mark as replied.

                alert('Email simulated to be sent! (Check console for details)');

                // Mark as replied in the database
                $.ajax({
                    url: 'admin_update_reply_status.php', // A new PHP file to handle this
                    type: 'POST',
                    data: {
                        id: messageId,
                        action: 'mark_replied'
                    },
                    success: function(response) {
                        if (response === 'Success') {
                            location.reload(); // Reload to reflect changes
                        } else {
                            alert('Error updating reply status: ' + response);
                        }
                    },
                    error: function() {
                        alert('Error communicating with server to update reply status.');
                    }
                });
            });
        });
    </script>
</body>
</html>
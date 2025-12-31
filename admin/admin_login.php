<?php
session_start();
// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require '../php/db_connect.php';

    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars(trim($_POST['password']));

    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $login_error = "Invalid username or password.";
        }
    } else {
        $login_error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Lung TFDB</title>
    <link rel="icon" href="../favicon_io/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            /* Removed centering styles from body */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-color);
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-container {
			width: 100%;
			max-width: 600px; /* wider on large screens */
			padding: 2rem;
			background-color: var(--card-bg);
			border-radius: 0.8rem;
			box-shadow: var(--shadow);
			text-align: center;
			margin: 1rem; /* Add margin for spacing */
		}

		/* Make it a bit narrower on small screens */
		@media (max-width: 480px) {
			.login-container {
				max-width: 90%;
				padding: 1.5rem;
			}
		}

        .login-container h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .error-message {
            color: #d9534f;
            margin-top: 1rem;
            font-weight: 500;
        }
        
        .nav-menu-admin {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .nav-menu-admin {
                display: none;
                flex-direction: column;
                gap: 1.5rem;
                background-color: var(--nav-bg);
                position: absolute;
                top: 70px;
                right: 1rem;
                padding: 1.5rem;
                border-radius: 0.5rem;
                box-shadow: var(--shadow);
                width: 200px;
                align-items: stretch;
            }
            .nav-menu-admin.active {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="../index.html" class="logo">
                <i class="fa-solid fa-lungs"></i>
                <span>Lung TFDB</span>
            </a>
            <div class="nav-container">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="nav-menu-admin" id="navMenu">
                    <a href="../protein_seq.html" class="nav-link">Protein Sequence</a>
                    <a href="../mutations.html" class="nav-link">Mutations</a>
                    <a href="../binding.html" class="nav-link">Binding Sites</a>
                    <a href="../scrna.html" class="nav-link">scRNA-seq</a>
                    <a href="../expr.html" class="nav-link">Expression</a>
                    <a href="../survR.html" class="nav-link">Survival</a>
                    <a href="../contact.html" class="nav-link">Contact</a>
                </div>
            </div>
        </div>
    </header>
    <main>
        <div class="login-container">
            <h1>Admin Login</h1>
            <?php if ($login_error): ?>
                <p class="error-message"><?php echo $login_error; ?></p>
            <?php endif; ?>
            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </main>

        <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Lung TFDB</h3>
                <p>A comprehensive database for lung cancer transcription factors with integrated multi-omics data and analysis tools.</p>
                <div class="social-links">
                    <a href="https://akkis-lab.github.io/ott-lab/index.html"><i class="fa-solid fa-globe"></i></a>
                    <a href="https://www.linkedin.com/in/guruguhan-s/"><i class="fab fa-linkedin"></i></a>
                    <a href="https://github.com/RustyAlgorithm44/Lung_TFDB"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                    <li><a href="documentation.html">Documentation</a></li>
                    <li><a href="admin/admin_login.php">Admin</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="tutorials.html">Tutorials</a></li>
                    <li><a href="faq.html">FAQ</a></li>
                    <li><a href="sitemap.html">Sitemap</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; 2025 SSNCCPR. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Mobile menu toggle
            $('#menuToggle').on('click', function() {
                $('#navMenu').toggleClass('active');
            });
        });
    </script>
</body>
</html>
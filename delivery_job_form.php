<?php
session_start();
include 'db_connect_delivery.php';

$successMessage = '';
$errorMessage = '';

// Check database connection
if (!isset($delivery_conn) || $delivery_conn->connect_error) {
    $errorMessage = 'Service unavailable. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errorMessage)) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $experience = $_POST['experience'] ?? 'None';
    $availability = $_POST['availability'] ?? 'Flexible';
    $transport = trim($_POST['transport'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '' || $city === '' || $transport === '') {
        $errorMessage = 'Please complete all required fields.';
    } elseif (strlen($fullName) > 30) {
        $errorMessage = 'Full name must be 30 characters or less.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errorMessage = 'Phone number must be exactly 10 digits.';
    } else {
        $insertSql = "
            INSERT INTO delivery_applications
                (full_name, email, phone, city, experience, availability, transport, message, status)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ";
        $stmt = $delivery_conn->prepare($insertSql);

        if ($stmt) {
            $stmt->bind_param(
                'ssssssss',
                $fullName,
                $email,
                $phone,
                $city,
                $experience,
                $availability,
                $transport,
                $message
            );

            if ($stmt->execute()) {
                $successMessage = 'Thanks for applying! Our team will contact you soon.';
            } else {
                $errorMessage = 'We could not save your application. Please try again.';
            }
            $stmt->close();
        } else {
            $errorMessage = 'We could not prepare your application. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner Application</title>
    <style>
        :root {
            --primary: #005792;
            --primary-dark: #003d5c;
            --accent: #00b8a9;
            --background: #f5f6f8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background);
            color: #333;
        }

        header {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1100px;
            margin: 0 auto;
        }

        nav a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link {
            color: #555;
            font-weight: 500;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--primary);
        }

        main {
            max-width: 760px;
            margin: 3rem auto;
            padding: 0 1rem 4rem;
        }

        .form-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: var(--primary);
            font-size: 2rem;
        }

        p.subtitle {
            color: #666;
            margin-top: -0.5rem;
        }

        form {
            display: grid;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        input, select, textarea {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #dcdfe5;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 184, 169, 0.15);
            outline: none;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            padding: 0.9rem 1.5rem;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, transform 0.1s;
        }

        button:hover {
            background: var(--primary-dark);
        }

        button:active {
            transform: translateY(1px);
        }

        .alert {
            padding: 0.85rem 1.1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            border: 1px solid;
        }

-        .alert-success {
-            background: #d4edda;
-            color: #155724;
-            border-color: #c3e6cb;
-        }
-
-        .alert-error {
-            background: #f8d7da;
-            color: #721c24;
-            border-color: #f5c6cb;
-        }
+        .alert-success {
+            background: #d4edda;
+            color: #155724;
+            border-color: #c3e6cb;
+        }
+
+        .alert-error {
+            background: #f8d7da;
+            color: #721c24;
+            border-color: #f5c6cb;
+        }

        @media (max-width: 768px) {
            header {
                padding: 1rem;
            }

            nav {
                flex-direction: column;
                gap: 0.75rem;
            }

            .form-card {
                padding: 1.75rem;
            }

            .thanks-page {
                padding: 2rem 1rem;
            }

            .thanks-page h1 {
                font-size: 2rem;
            }

            .thanks-page .check-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="food.html">&larr; Back to Home</a>
        </nav>
    </header>

    <main>
        <div class="form-card">
            <?php if ($successMessage !== ''): ?>
                <!-- Thank You Page -->
                <div class="thanks-page">
                    <div class="check-icon">✓</div>
                    <h1>Thank You!</h1>
                    <p>Your application has been submitted successfully. We've received your information and our team will review it shortly.</p>
                    <p style="font-size: 1rem; color: #888; margin-top: 1rem;">We'll contact you soon via email or phone regarding your delivery partner application.</p>
                    <a href="food.html" class="back-home-btn">← Back to Home</a>
                </div>
            <?php else: ?>
                <!-- Application Form -->
                <h1>Delivery Partner Application</h1>
                <p class="subtitle">Tell us a little about yourself. We'll get in touch if there's a fit.</p>

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name * (Max 30 characters)</label>
                    <input type="text" id="full_name" name="full_name" maxlength="30" required value="<?php echo isset($fullName) ? htmlspecialchars($fullName) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email * </label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *(Max 10 digits)</label>
                    <input type="tel" id="phone" name="phone" maxlength="10" pattern="[0-9]{10}" required value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="city">City / Service Area * (Max 50 characters)</label>
                    <input type="text" id="city" name="city" required value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="experience">Previous Delivery Experience</label>
                    <select id="experience" name="experience">
                        <?php
                            $experienceOptions = ['None', '0-1 years', '1-3 years', '3+ years'];
                            $selectedExperience = $experience ?? 'None';
                            foreach ($experienceOptions as $option) {
                                $selected = ($option === $selectedExperience) ? 'selected' : '';
                                echo "<option value=\"{$option}\" {$selected}>{$option}</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <?php
                            $availabilityOptions = ['Full-time', 'Part-time', 'Weekends', 'Flexible'];
                            $selectedAvailability = $availability ?? 'Flexible';
                            foreach ($availabilityOptions as $option) {
                                $selected = ($option === $selectedAvailability) ? 'selected' : '';
                                echo "<option value=\"{$option}\" {$selected}>{$option}</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="transport">Do you have your own transport? *</label>
                    <input type="text" id="transport" name="transport" placeholder="e.g., Bike, Scooter, Van" required value="<?php echo isset($transport) ? htmlspecialchars($transport) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="message">Anything else we should know?</label>
                    <textarea id="message" name="message" placeholder="Optional"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                </div>

                    <button type="submit">Submit Application</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>


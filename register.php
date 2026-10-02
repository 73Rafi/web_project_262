<?php
require __DIR__ . '/backend/common.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Form data নেওয়া
    $name = $_POST["fullName"];
    $role = $_POST["role"];
    $department = $_POST["department"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Empty check
    if (
        empty($name) ||
        empty($department) ||
        empty($email) ||
        empty($password)
    ) {
        $error = "Please fill all fields.";
    }

    // Email check
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email.";
    }

    // Password check
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    }

    else {

        // Check email already exists
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "Email already registered.";

        } else {

            // Password encrypt/hash
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user
            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, role, department, email, password, cv_path)
                VALUES (?, ?, ?, ?, ?, '')"
            );

            $stmt->bind_param(
                "sssss",
                $name,
                $role,
                $department,
                $email,
                $hashedPassword
            );

            if ($stmt->execute()) {

                header("Location: signIn.php");
                exit;

            } else {

                $error = "Registration failed.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register</title>

    <link rel="stylesheet" href="portal.css">
</head>

<body>

<div class="Panel"
     style="display:flex; width:100%; height:100vh;">

    <!-- LEFT SIDE -->
    <div class="left-panel"
         style="
         background-color:#EA5F34;
         width:50%;
         height:100vh;
         display:flex;
         padding:0 8%;
         box-sizing:border-box;
         justify-content:center;
         align-items:flex-start;
         flex-direction:column;
         ">

        <h1 style="color:white;">
            Advanced Research,<br>
            Together.
        </h1>

        <p style="color:white;">
            Join UIU's research community to discover papers,
            collaborate on projects and make an impact.
        </p>

        <ul>
            <li>Access thousands of Research Papers</li>
            <li>Collaborate with UIU Researchers</li>
            <li>Publish and share your work</li>
        </ul>

    </div>


    <!-- RIGHT SIDE -->
    <div class="right-panel"
         style="
         width:50%;
         height:100vh;
         display:flex;
         justify-content:center;
         align-items:center;
         flex-direction:column;
         ">

        <h1 style="color:#c05332;">
            Create Account
        </h1>

        <p>Join the UIU Research Community</p>


        <!-- Error Message -->

        <?php
        if ($error != "") {
            echo "<p style='color:red;'>$error</p>";
        }
        ?>


        <!-- Registration Form -->

        <form method="POST">

            <label>Full Name</label>
            <br>

            <input
                type="text"
                name="fullName"
                required
            >

            <br><br>


            <label>Role</label>
            <br>

            <select name="role">

                <option value="student">
                    Student
                </option>

                <option value="teacher">
                    Teacher
                </option>

            </select>

            <br><br>


            <label>Department</label>
            <br>

            <input
                type="text"
                name="department"
                required
            >

            <br><br>


            <label>Email</label>
            <br>

            <input
                type="email"
                name="email"
                required
            >

            <br><br>


            <label>Password</label>
            <br>

            <input
                type="password"
                name="password"
                required
            >

            <br><br>


            <button type="submit">
                Create Account
            </button>

        </form>


        <p>
            Already registered?

            <a href="signIn.php">
                Sign In
            </a>
        </p>

    </div>

</div>

</body>
</html>
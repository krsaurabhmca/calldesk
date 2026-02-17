<?php
// login_debug.php
require_once 'config/db.php';
require_once 'includes/auth.php';

echo "<h2>Login Debugger</h2>";

$mobile = '9999999999';
$password = 'admin123';

echo "Checking mobile: <strong>$mobile</strong><br>";

$sql = "SELECT * FROM users WHERE mobile = '$mobile'";
$result = mysqli_query($conn, $sql);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo "User found!<br>";
        echo "Stored Hash: <code>" . $user['password'] . "</code><br>";
        
        $verify = password_verify($password, $user['password']);
        echo "Verification: " . ($verify ? "<span style='color:green'>SUCCESS</span>" : "<span style='color:red'>FAILED</span>") . "<br>";
        
        if (!$verify) {
            echo "<hr>Generating new hash for 'admin123'...<br>";
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "New Hash: <code>$new_hash</code><br>";
            
            $update = mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE mobile = '$mobile'");
            if ($update) {
                echo "<span style='color:green'>Database updated with new hash!</span><br>";
                echo "<a href='login.php'>Try logging in now</a>";
            } else {
                echo "Error updating: " . mysqli_error($conn);
            }
        }
    } else {
        echo "<span style='color:red'>User NOT found in database.</span><br>";
        echo "Creating user now...<br>";
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = mysqli_query($conn, "INSERT INTO users (name, mobile, password, role) VALUES ('System Admin', '$mobile', '$new_hash', 'admin')");
        if ($insert) {
            echo "<span style='color:green'>User created! Try logging in.</span>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
} else {
    echo "Query Error: " . mysqli_error($conn);
}
?>

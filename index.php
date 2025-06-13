<?php
include 'configuration.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $mail = $_POST['mail'];
    $dev_exp = $_POST['dev_exp'];
    $java_exp = $_POST['java_exp'];
    $refactoring_exp = $_POST['refactoring_exp'];

    // Check if the email already exists
    $check_sql = "SELECT id FROM reviewers WHERE mail = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $mail);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->bind_result($reviewer_id);
        $check_stmt->fetch();
        // Armazena o reviewer_id em um cookie
        setcookie('reviewer_id', $reviewer_id, time() + 3600, "/", "", true, true); // Cookie válido por 1 hora, com flags de segurança
        header("Location: pair_analysis.php");
    } else {
        // Insert new record
        $sql = "INSERT INTO reviewers (name, mail, dev_exp, java_exp, refactoring_exp) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $mail, $dev_exp, $java_exp, $refactoring_exp);

        if ($stmt->execute()) {
            // Armazena o novo reviewer_id em um cookie
            setcookie('reviewer_id', $stmt->insert_id, time() + 3600, "/", "", true, true); // Cookie válido por 1 hora, com flags de segurança
            header("Location: pair_analysis.php");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        $stmt->close();
    }

    $check_stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Form</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Reviewer Form</h2>
    <p>(*) Required fields.</p>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <div class="form-group">
            <label for="mail">Email*</label>
            <input type="email" class="form-control" id="mail" name="mail" required>
            <small id="emailHelp" class="form-text text-muted text-danger">If you have already filled out this form
                using your email, please enter the same email address to pick up where you left off.</small>
        </div>
        <div class="form-group">
            <label for="name">Name*</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="dev_exp">Programming Experience*</label>
            <select class="form-control" id="dev_exp" name="dev_exp" required>
                <option value="">Select</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
        </div>
        <div class="form-group">
            <label for="java_exp">Java Experience*</label>
            <select class="form-control" id="java_exp" name="java_exp" required>
                <option value="">Select</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
        </div>
        <div class="form-group">
            <label for="refactoring_exp">Refactoring Experience*</label>
            <select class="form-control" id="refactoring_exp" name="refactoring_exp" required>
                <option value="">Select</option>
                <option value="little">Little</option>
                <option value="moderate">Moderate</option>
                <option value="experienced">Experienced</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

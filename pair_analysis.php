<?php
include 'configuration.php';

if (!isset($_COOKIE['reviewer_id'])) {
    header("Location: index.php");
}

$reviewer_id = $_COOKIE['reviewer_id'];

## Save the annotation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['rating']) {
        case 'agree':
            $annotation = 1;
            break;
        case 'disagree':
            $annotation = 0;
            break;
        case 'idk':
            $annotation = -1;
            break;
    }
    
    ## Update pair status
    /*$sql_update = "INSERT INTO annotations (reviewers_id, refactorings_id, annotation) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iii", $reviewer_id, $_POST['refactorings_id'], $annotation);
    $stmt->execute();
    $stmt->close();*/

    $sql_update = "UPDATE annotations SET annotation = ? WHERE reviewers_id = ? AND refactorings_id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iii", $annotation, $reviewer_id, $_POST['refactorings_id']);
    $stmt->execute();
    $stmt->close();
}

## Check if the reviewer has already evaluated 20 pairs of code
/*$check_sql = "SELECT count(*) as count FROM annotations WHERE reviewers_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $reviewer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();

if ($check_row['count'] > MAX_PAIRS) {
    echo "You already evaluated 30 pairs of code. Thank you for your contribution!";
    die();
}*/

## Get the refactoring type with the least amount of evaluations
$sql_select = "SELECT 
    refactorings.refactoring_type, 
    COUNT(annotations.id) AS refactoring_count
FROM 
    annotations
INNER JOIN 
    refactorings 
ON 
    refactorings.id = annotations.refactorings_id
GROUP BY 
    refactorings.refactoring_type
ORDER BY 
    refactoring_count
LIMIT 1";

$stmt = $conn->prepare($sql_select);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$least_evaluated_type = $row['refactoring_type'];
$stmt->close();

## Check if some reviewer has already evaluated a pair of code, to complete a multiple annotation
$check_sql = "SELECT refactorings_id, count(*) as count
FROM annotations
WHERE refactorings_id NOT IN (
    SELECT DISTINCT refactorings_id
    FROM annotations
    WHERE reviewers_id = ?
)
GROUP BY refactorings_id
HAVING count = 1
ORDER BY RAND()
LIMIT 1";

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $reviewer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();

$refactoring_id = "";

## If a reviewer has already evaluated a pair of code, complete the multiple annotation
if($check_row){
    $refactorings_id = $check_row['refactorings_id'];

    $sql_select = "SELECT id, path_original, path_refactored, refactoring_type, description, repository, sha, file_path
    FROM refactorings 
    WHERE id = ?";
    $stmt = $conn->prepare($sql_select);
    $stmt->bind_param("i", $refactorings_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $refactoring_id = $row['id'];
    $original = $row['path_original'];
    $refactored = $row['path_refactored'];
    $refactoring_type = $row['refactoring_type'];
    $description = $row['description'];
    $repository = $row['repository'];
    $sha = $row['sha'];
    $file_path = $row['file_path'];

    $stmt->close();
## Get a pair of code without annotation
} else {

   $sql_select = "SELECT id, path_original, path_refactored, refactoring_type, description, repository, sha, file_path
    FROM refactorings
    WHERE id NOT IN (
        SELECT DISTINCT refactorings_id
        FROM annotations
    )
    AND refactoring_type = '$least_evaluated_type'
    ORDER BY RAND()";

    $stmt = $conn->prepare($sql_select);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $refactoring_id = $row['id'];
    $original = $row['path_original'];
    $refactored = $row['path_refactored'];
    $refactoring_type = $row['refactoring_type'];
    $description = $row['description'];
    $repository = $row['repository'];
    $sha = $row['sha'];
    $file_path = $row['file_path'];

    $stmt->close();

    /**
     * Ao escolher um novo par, deve haver uma flag que impeça de um terceiro
     * revisor de avaliar o mesmo par.
     */
}

    $sql_update = "INSERT INTO annotations (reviewers_id, refactorings_id, annotation)
     VALUES (?, ?, ?)";
    $tmp_annotation = -2;
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iii", $reviewer_id, $refactoring_id, $tmp_annotation);
    $stmt->execute();
    $stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Including Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Including Prism.js CSS for dark theme -->
    <link href="https://prismjs.com/themes/prism.css" rel="stylesheet">
    <link href="https://prismjs.com/plugins/diff-highlight/prism-diff-highlight.css" rel="stylesheet">

    <!--link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css"-->
    <!--link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css" rel="stylesheet" /-->
    <link rel="stylesheet" href="styles.css">
    <!-- Including Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Code Evaluation</title>
</head>
<body>
    <header class="bg-dark text-white text-center py-3">
        <h1>Refactoring Evaluation</h1>
    </header>
    <main class="container-fluid mt-4">

        <section id="code-samples">
            <p><b>Description:</b> <?=$description?></p>
            <p><b>Commit link:</b> <a href='https://github.com/<?=$repository?>/commit/<?=$sha?>' target='_blank'>https://github.com/<?=$repository?>/commit/<?=$sha?></a></p>
            <p><b>Commit file:</b> <?=$file_path?></p>
            <div class="row">
                <div class="col-lg-6 col-md-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            Original Code
                            <button class="btn btn-info btn-sm float-right" data-toggle="modal" data-target="#originalCodeModal">View</button>
                        </div>
                        <div class="card-body">
                            <pre><code class="language-diff-java diff-highlight">
<?php
    // Check if the file exists
    if (file_exists($original)) {
        // Open the file for reading
        $handle = fopen($original, 'r');

        if ($handle) {
            // Read the file line by line
            while (($line = fgets($handle)) !== false) {
                // Display the line
                echo htmlspecialchars($line) . "<br>";
            }

            // Close the file
            fclose($handle);
        } else {
            echo "Error opening the file.";
        }
    } else {
        echo "File not found.";
    }
?>
                            </code></pre>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            Refactored Code
                            <button class="btn btn-info btn-sm float-right" data-toggle="modal" data-target="#refactoredCodeModal">View</button>
                        </div>
                        <div class="card-body">
                            <pre><code class="language-diff-java diff-highlight">
<?php
    // Check if the file exists
    if (file_exists($refactored)) {
        // Open the file for reading
        $handle = fopen($refactored, 'r');

        if ($handle) {
            // Read the file line by line
            while (($line = fgets($handle)) !== false) {
                // Display the line
                echo htmlspecialchars($line) . "<br>";
            }

            // Close the file
            fclose($handle);
        } else {
            echo "Error opening the file.";
        }
    } else {
        echo "File not found.";
    }
?>
                            </code></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <p><b>In your opinion, do these code pairs accurately represent the <?=$refactoring_type?> refactoring?</b></p>
            </div>
            <form id="form_annotation" name="form_annotation" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <div class="text-center btn-group">
                    <button class="btn btn-success btn-spacing" name="rating" value="agree" id="submit-agree">
                        <i class="fas fa-check"></i> Agree
                    </button>
                    <button class="btn btn-danger btn-spacing" name="rating" value="disagree" id="submit-disagree">
                        <i class="fas fa-times"></i> Disagree
                    </button>
                    <button class="btn btn-primary btn-spacing" name="rating" value="idk" id="submit-idk">
                        <i class="fas fa-question"></i> I don't know
                    </button>
                    <input id="refactorings_id" name="refactorings_id" type="hidden" value="<?=$refactoring_id?>">
                </div>
            </form>
        </section>
    </main>
    <footer class="text-center mt-4">
        <p>Developed by Henrique from <a target='_blank' href='https://labsoft-ufmg.github.io/'>Labsoft</a></p>
    </footer>
    
    <!-- Modal for Original Code -->
    <div class="modal fade" id="originalCodeModal" tabindex="-1" role="dialog" aria-labelledby="originalCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="originalCodeModalLabel">Original Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
<pre><code class="language-diff-java diff-highlight">
<?php
    // Check if the file exists
    if (file_exists($original)) {
        // Open the file for reading
        $handle = fopen($original, 'r');

        if ($handle) {
            // Read the file line by line
            while (($line = fgets($handle)) !== false) {
                // Display the line
                echo htmlspecialchars($line) . "<br>";
            }

            // Close the file
            fclose($handle);
        } else {
            echo "Error opening the file.";
        }
    } else {
        echo "File not found.";
    }
?>
                            </code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Refactored Code -->
    <div class="modal fade" id="refactoredCodeModal" tabindex="-1" role="dialog" aria-labelledby="refactoredCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refactoredCodeModalLabel">Refactored Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
<pre><code class="language-diff-java diff-highlight">
<?php
    // Check if the file exists
    if (file_exists($refactored)) {
        // Open the file for reading
        $handle = fopen($refactored, 'r');

        if ($handle) {
            // Read the file line by line
            while (($line = fgets($handle)) !== false) {
                // Display the line
                echo htmlspecialchars($line) . "<br>";
            }

            // Close the file
            fclose($handle);
        } else {
            echo "Error opening the file.";
        }
    } else {
        echo "File not found.";
    }
?>
</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Prism.js for syntax highlighting -->
    <script src="https://prismjs.com/prism.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-java.min.js"></script>
    <!--script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-diff.min.js"></script-->
    <script src="https://prismjs.com/plugins/diff-highlight/prism-diff-highlight.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <!-- Including Bootstrap JS and dependencies -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

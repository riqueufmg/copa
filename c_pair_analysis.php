<?php
include 'configuration.php';

$reviewer_id = 29;

## Save the annotation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    switch ($_POST['rating']) {
        case 'agree': $annotation = 1; break;
        case 'disagree': $annotation = 0; break;
        case 'idk': $annotation = -1; break;
        default: $annotation = -2;
    }


    $sql_update = "UPDATE annotations SET annotation = ? WHERE reviewers_id = ? AND refactorings_id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iii", $annotation, $reviewer_id, $_POST['refactorings_id']);
    $stmt->execute();
    $stmt->close();
}

## Get only refactorings with draw annotations
## Disconsidering refactorings already evaluate by reviewers_id

$sql_select = "SELECT id, path_original, path_refactored, refactoring_type, description, repository, sha, file_path
FROM refactorings
WHERE id IN (
    SELECT refactorings_id
    FROM `annotations`
    WHERE refactorings_id NOT IN (
        SELECT DISTINCT refactorings_id
        FROM `annotations`
        WHERE reviewers_id = ".$reviewer_id."
    )
    GROUP BY refactorings_id
    HAVING COUNT(DISTINCT annotation) > 1
       AND COUNT(refactorings_id) = 2
)";

$stmt = $conn->prepare($sql_select);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "Error! Please, press F5 and refresh the page.";
    $stmt->close();
    $conn->close();
    exit;
}

$refactoring_id = $row['id'];
$original = $row['path_original'];
$refactored = $row['path_refactored'];
$refactoring_type = $row['refactoring_type'];
$description = $row['description'];
$repository = $row['repository'];
$sha = $row['sha'];
$file_path = $row['file_path'];

$stmt->close();


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

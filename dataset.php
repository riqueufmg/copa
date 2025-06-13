<?php
include('configuration.php');

// Query to fetch data
$query = "
    SELECT 
        a.id AS annotation_id,
        a.annotation,
        r.id AS refactoring_id,
        r.repository,
        r.refactoring_type,
        r.sha,
        r.description,
        r.path_original,
        r.path_refactored,
        r.file_path
    FROM annotations a
    LEFT JOIN refactorings r ON a.refactorings_id = r.id
";

$result = $conn->query($query);

if (!$result) {
    die("Error executing query: " . $conn->error);
}

$data = [];

// Fetch data
while ($row = $result->fetch_assoc()) {
    $type = $row['refactoring_type'];

    if (!isset($data[$type])) {
        $data[$type] = [];
    }

    $refactoringIndex = null;
    foreach ($data[$type] as $index => $entry) {
        if ($entry['refactoring_id'] === $row['refactoring_id']) {
            $refactoringIndex = $index;
            break;
        }
    }

    if ($refactoringIndex === null) {
        $data[$type][] = [
            'refactoring_id' => $row['refactoring_id'],
            'commit_sha' => $row['sha'],
            'commit_link' => "https://github.com/{$row['repository']}/commit/{$row['sha']}",
            'file_path' => $row['file_path'],
            'description' => $row['description'],
            'code_before' => file_get_contents($row['path_original']),
            'code_after' => file_get_contents($row['path_refactored']),
            'evaluations' => [],
        ];
        $refactoringIndex = array_key_last($data[$type]);
    }

    if ($row['annotation'] !== null) {
        $data[$type][$refactoringIndex]['evaluations'][] = [
            'vote' => (int)$row['annotation'],
        ];
    }
}

// Convert data to JSON
$jsonFilePath = './MaRV.json';
$jsonData = json_encode($data, JSON_PRETTY_PRINT);

if (file_put_contents($jsonFilePath, $jsonData) === false) {
    die("Error saving JSON to $jsonFilePath.");
}

echo "JSON saved to $jsonFilePath.";

$conn->close();
?>

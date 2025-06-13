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

$xml = new SimpleXMLElement('<root/>');

foreach ($data as $type => $refactorings) {
    $typeNode = $xml->addChild(str_replace(' ', '_', $type));

    foreach ($refactorings as $refactoring) {
        $refactoringNode = $typeNode->addChild('refactoring');
        $refactoringNode->addChild('refactoring_id', $refactoring['refactoring_id']);
        $refactoringNode->addChild('commit_sha', $refactoring['commit_sha']);
        $refactoringNode->addChild('commit_link', $refactoring['commit_link']);
        $refactoringNode->addChild('file_path', $refactoring['file_path']);
        $refactoringNode->addChild('description', $refactoring['description']);
        $refactoringNode->addChild('code_before', htmlspecialchars($refactoring['code_before']));
        $refactoringNode->addChild('code_after', htmlspecialchars($refactoring['code_after']));

        $evaluationsNode = $refactoringNode->addChild('evaluations');
        foreach ($refactoring['evaluations'] as $evaluation) {
            $evaluationNode = $evaluationsNode->addChild('evaluation');
            $evaluationNode->addChild('vote', $evaluation['vote']);
        }
    }
}

// Save XML to file
$xmlFilePath = './MaRV.xml';
$xml->asXML($xmlFilePath);

echo "XML saved to $xmlFilePath.";

$conn->close();
?>

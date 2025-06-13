<?php
// Path to the JSON file
$jsonFile = './MaRV.json';

try {
    // Load the JSON
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);

    // Check if JSON was loaded correctly
    if ($data === null) {
        throw new Exception('Failed to load JSON.');
    }

    // Initialize counters
    $totalRefactorings = 0;
    $refactoringsWithOneVote = 0;
    $refactoringsWithTwoVotes = 0;

    // Initialize global vote pair counts
    $globalVotePairsCount = [
        '-1,-1' => 0, '-1,0' => 0, '-1,1' => 0,
        '0,0' => 0, '0,1' => 0,
        '1,1' => 0,
    ];

    // Initialize per-type vote pair counts
    $perTypeVotePairsCount = [];

    // Iterate over refactoring types
    foreach ($data as $refactoringType => $refactorings) {
        // Initialize this type's counters
        $perTypeVotePairsCount[$refactoringType] = [
            '-1,-1' => 0, '-1,0' => 0, '-1,1' => 0,
            '0,0' => 0, '0,1' => 0,
            '1,1' => 0,
        ];

        // Iterate over each refactoring
        foreach ($refactorings as $refactoring) {
            $totalRefactorings++;

            // Count the number of votes
            $votes = [];
            foreach ($refactoring['evaluations'] as $evaluation) {
                $votes[] = (int)$evaluation['vote'];
            }

            $votesCount = count($votes);

            if ($votesCount === 1) {
                $refactoringsWithOneVote++;
            } elseif ($votesCount === 2) {
                $refactoringsWithTwoVotes++;

                // Register the pair of votes without considering order (sort the votes)
                sort($votes);
                $pairKey = $votes[0] . ',' . $votes[1];

                // Only count pairs that are in the predefined list
                if (isset($globalVotePairsCount[$pairKey])) {
                    $globalVotePairsCount[$pairKey]++;
                    $perTypeVotePairsCount[$refactoringType][$pairKey]++;
                }
            }
        }
    }

    // Display general summary
    echo "<h2>Resumo Geral:</h2>";
    echo "Total refactorings: $totalRefactorings<br>";
    echo "Refactorings with 1 vote: $refactoringsWithOneVote<br>";
    echo "Refactorings with 2 votes: $refactoringsWithTwoVotes<br><br>";

    echo "<h3>Vote pair counts (global):</h3>";
    foreach ($globalVotePairsCount as $pair => $count) {
        if ($count > 0) { // Only display pairs with non-zero count
            echo "($pair): $count<br>";
        }
    }

    echo "<br><h2>Vote pair counts by Refactoring Type:</h2>";
    foreach ($perTypeVotePairsCount as $type => $pairs) {
        echo "<h3>$type:</h3>";
        foreach ($pairs as $pair => $count) {
            if ($count > 0) { // Only show non-zero pairs
                echo "($pair): $count<br>";
            }
        }
        echo "<br>";
    }

} catch (Exception $e) {
    // If there is an error loading the JSON or processing data
    echo "Error: " . $e->getMessage();
}
?>

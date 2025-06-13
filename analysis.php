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

    // Initialize vote pair counts for all unique vote combinations (unordered)
    $votePairsCount = [
        '-1,-1' => 0, '-1,0' => 0, '-1,1' => 0,
        '0,-1' => 0, '0,0' => 0, '0,1' => 0,
        '1,-1' => 0, '1,0' => 0, '1,1' => 0,
    ];

    // Iterate over refactoring types
    foreach ($data as $refactoringType => $refactorings) {
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
                if (isset($votePairsCount[$pairKey])) {
                    $votePairsCount[$pairKey]++;
                }
            }
        }
    }

    // Display results
    echo "Total refactorings: $totalRefactorings<br>";
    echo "Refactorings with 1 vote: $refactoringsWithOneVote<br>";
    echo "Refactorings with 2 votes: $refactoringsWithTwoVotes<br><br>";

    echo "Vote pair counts:<br>";
    foreach ($votePairsCount as $pair => $count) {
        if ($count > 0) { // Only display pairs with non-zero count
            echo "($pair): $count<br>";
        }
    }

} catch (Exception $e) {
    // If there is an error loading the JSON or processing data
    echo "Error: " . $e->getMessage();
}

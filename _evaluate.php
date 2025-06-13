<?php
// Path to the XML file
$xmlFile = './MaRV.xml';

try {
    // Load the XML
    $xml = simplexml_load_file($xmlFile);

    // Check if XML was loaded correctly
    if ($xml === false) {
        throw new Exception('Failed to load XML.');
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
    foreach ($xml->children() as $refactoringType) {
        // Iterate over each refactoring
        foreach ($refactoringType->refactoring as $refactoring) {
            $totalRefactorings++;

            // Count the number of votes
            $votes = [];
            foreach ($refactoring->evaluations->evaluation as $evaluation) {
                $votes[] = (int)$evaluation->vote;
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
    // If there is an error loading the XML or processing data
    echo "Error: " . $e->getMessage();
}

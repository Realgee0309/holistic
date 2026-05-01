<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

requireLogin();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../assessment.php');
    exit;
}

csrfVerify();

$assessmentType = cleanStr($_POST['assessment_type'] ?? '', 50);

if ($assessmentType !== 'gad7') {
    setFlash('error', 'Invalid assessment type.');
    header('Location: ../assessment.php');
    exit;
}

// Calculate GAD-7 score
$score = 0;
$responses = [];

for ($i = 1; $i <= 7; $i++) {
    $value = (int)($_POST["q{$i}"] ?? 0);
    $score += $value;
    $responses["q{$i}"] = $value;
}

// Interpret score
$interpretation = '';
$description = '';

if ($score <= 4) {
    $interpretation = 'Minimal Anxiety';
    $description = 'Your anxiety levels appear to be in the minimal range. This suggests you may not be experiencing significant anxiety symptoms.';
} elseif ($score <= 9) {
    $interpretation = 'Mild Anxiety';
    $description = 'Your score suggests mild anxiety. You may experience anxiety occasionally, but it doesn\'t significantly interfere with your daily life.';
} elseif ($score <= 14) {
    $interpretation = 'Moderate Anxiety';
    $description = 'Your score indicates moderate anxiety. You may find that anxiety affects your daily activities and would benefit from professional support.';
} elseif ($score <= 21) {
    $interpretation = 'Severe Anxiety';
    $description = 'Your score suggests severe anxiety. This level of anxiety likely significantly impacts your daily functioning and quality of life. Professional help is recommended.';
}

try {
    $pdo = getDB();

    // Store assessment results
    $stmt = $pdo->prepare("
        INSERT INTO assessments (user_id, type, responses, score)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $assessmentType,
        json_encode($responses),
        $score
    ]);

    // Store results in session for display
    $_SESSION['assessment_result'] = [
        'type' => $assessmentType,
        'score' => $score,
        'interpretation' => $interpretation,
        'description' => $description,
        'responses' => $responses
    ];

    header('Location: ../assessment.php?result=1');
    exit;

} catch (PDOException $e) {
    error_log('Assessment submission error: ' . $e->getMessage());
    setFlash('error', 'Something went wrong. Please try again.');
    header('Location: ../assessment.php');
    exit;
}
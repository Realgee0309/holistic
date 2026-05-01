<?php
$pageTitle = 'GAD-7 Anxiety Assessment';
$metaDesc  = 'Take the GAD-7 anxiety assessment to evaluate your anxiety symptoms.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/user_auth.php';
requireLogin();
$user = getCurrentUser();
?>
<style>
.assessment-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.assessment-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 2rem;
    text-align: center;
}

.assessment-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.assessment-header p {
    opacity: 0.9;
    font-size: 1.1rem;
}

.assessment-content {
    padding: 2rem;
}

.question {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    background: #fafafa;
}

.question h3 {
    margin: 0 0 1rem 0;
    color: var(--primary);
    font-size: 1.1rem;
}

.options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
}

.option {
    position: relative;
}

.option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    margin: 0;
}

.option label {
    display: block;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
    font-size: 0.9rem;
}

.option input[type="radio"]:checked + label {
    border-color: var(--accent);
    background: var(--accent);
    color: white;
}

.assessment-info {
    background: #e8f4fd;
    border: 1px solid #b3d9ff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.assessment-info h3 {
    margin: 0 0 0.5rem 0;
    color: #1e40af;
}

.assessment-info p {
    margin: 0;
    color: #1e40af;
    font-size: 0.9rem;
}

.score-display {
    background: linear-gradient(135deg, var(--secondary), var(--primary));
    color: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    margin-top: 2rem;
}

.score-number {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.score-interpretation {
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.score-description {
    font-size: 0.9rem;
    opacity: 0.9;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .options {
        grid-template-columns: 1fr;
    }

    .assessment-header {
        padding: 1.5rem;
    }

    .assessment-content {
        padding: 1.5rem;
    }
}
</style>

<div class="page-header">
    <div class="container">
        <h1>GAD-7 Anxiety Assessment</h1>
        <p>Evaluate your anxiety symptoms over the past 2 weeks</p>
    </div>
</div>

<section>
    <div class="container">
        <?php renderFlash(); ?>

        <div class="assessment-container">
            <div class="assessment-header">
                <h1>🧠 GAD-7 Assessment</h1>
                <p>Generalized Anxiety Disorder - 7 Questions</p>
            </div>

            <?php if (isset($_GET['result']) && isset($_SESSION['assessment_result'])): ?>
            <?php $result = $_SESSION['assessment_result']; unset($_SESSION['assessment_result']); ?>
            <div class="score-display">
                <div class="score-number"><?= $result['score'] ?>/21</div>
                <div class="score-interpretation"><?= htmlspecialchars($result['interpretation']) ?></div>
                <div class="score-description">
                    <?= htmlspecialchars($result['description']) ?>
                </div>
                <div style="margin-top: 1.5rem;">
                    <a href="dashboard.php?tab=assessments" class="btn btn-light">View in Dashboard</a>
                    <a href="assessment.php" class="btn btn-primary" style="margin-left: 1rem;">Take Again</a>
                </div>
            </div>
            <?php else: ?>

            <div class="assessment-content">
                <div class="assessment-info">
                    <h3>📋 About This Assessment</h3>
                    <p>The GAD-7 is a validated screening tool for anxiety disorders. Answer each question based on how often you've been bothered by each symptom during the past 2 weeks. Your responses are confidential and help you understand your anxiety levels.</p>
                </div>

                <form id="gad7Form" method="POST" action="actions/submit_assessment.php">
                    <input type="hidden" name="assessment_type" value="gad7">

                    <div class="question">
                        <h3>1. Feeling nervous, anxious, or on edge</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q1_0" name="q1" value="0" required>
                                <label for="q1_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q1_1" name="q1" value="1">
                                <label for="q1_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q1_2" name="q1" value="2">
                                <label for="q1_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q1_3" name="q1" value="3">
                                <label for="q1_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div class="question">
                        <h3>2. Not being able to stop or control worrying</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q2_0" name="q2" value="0" required>
                                <label for="q2_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q2_1" name="q2" value="1">
                                <label for="q2_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q2_2" name="q2" value="2">
                                <label for="q2_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q2_3" name="q2" value="3">
                                <label for="q2_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div class="question">
                        <h3>3. Worrying too much about different things</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q3_0" name="q3" value="0" required>
                                <label for="q3_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q3_1" name="q3" value="1">
                                <label for="q3_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q3_2" name="q3" value="2">
                                <label for="q3_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q3_3" name="q3" value="3">
                                <label for="q3_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div class="question">
                        <h3>4. Trouble relaxing</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q4_0" name="q4" value="0" required>
                                <label for="q4_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q4_1" name="q4" value="1">
                                <label for="q4_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q4_2" name="q4" value="2">
                                <label for="q4_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q4_3" name="q4" value="3">
                                <label for="q4_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div class="question">
                        <h3>5. Being so restless that it's hard to sit still</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q5_0" name="q5" value="0" required>
                                <label for="q5_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q5_1" name="q5" value="1">
                                <label for="q5_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q5_2" name="q5" value="2">
                                <label for="q5_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q5_3" name="q5" value="3">
                                <label for="q5_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div class="question">
                        <h3>6. Becoming easily annoyed or irritable</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q6_0" name="q6" value="0" required>
                                <label for="q6_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q6_1" name="q6" value="1">
                                <label for="q6_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q6_2" name="q6" value="2">
                                <label for="q6_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q6_3" name="q6" value="3">
                                <label for="q6_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div class="question">
                        <h3>7. Feeling afraid as if something awful might happen</h3>
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q7_0" name="q7" value="0" required>
                                <label for="q7_0">Not at all</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q7_1" name="q7" value="1">
                                <label for="q7_1">Several days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q7_2" name="q7" value="2">
                                <label for="q7_2">More than half the days</label>
                            </div>
                            <div class="option">
                                <input type="radio" id="q7_3" name="q7" value="3">
                                <label for="q7_3">Nearly every day</label>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            📊 Calculate My Score
                        </button>
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                            Your responses are confidential and stored securely.
                        </p>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

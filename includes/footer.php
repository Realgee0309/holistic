<?php
// Depth detection for root-relative links
$depth = 0;
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) $depth = 1;
$root = str_repeat('../', $depth);
?>
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>Holistic Wellness</h3>
                <p>Professional online therapy services for individuals, couples, and families — from the comfort of your home.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://wa.me/254797582384" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="<?= $root ?>index.php">Home</a></li>
                    <li><a href="<?= $root ?>about.php">About</a></li>
                    <li><a href="<?= $root ?>services.php">Services</a></li>
                    <li><a href="<?= $root ?>book.php">Book a Session</a></li>
                    <li><a href="<?= $root ?>faq.php">FAQ</a></li>
                    <li><a href="<?= $root ?>contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact</h3>
                <ul class="footer-links">
                    <li><i class="fas fa-envelope" style="margin-right:6px;color:var(--accent)"></i> contact@holisticwellness.com</li>
                    <li><i class="fab fa-whatsapp" style="margin-right:6px;color:#25D366"></i> +254 797 582 384</li>
                    <li><i class="fas fa-clock" style="margin-right:6px;color:var(--accent)"></i> Mon–Fri: 9am–9pm</li>
                    <li><a href="<?= $root ?>contact.php">Send us a message →</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="<?= $root ?>privacy.php">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?= date('Y') ?> Holistic Wellness. All rights reserved. &nbsp;|&nbsp; Built with ❤ for your wellbeing.</p>
        </div>
    </div>
</footer>
<script>
/* Shared JS: Accordion */
document.querySelectorAll('.accordion-header').forEach(function(header) {
    header.addEventListener('click', function() {
        const content = this.nextElementSibling;
        const isActive = this.classList.contains('active');
        // Close all
        document.querySelectorAll('.accordion-header').forEach(function(h) {
            h.classList.remove('active');
            h.querySelector('.toggle-icon').textContent = '+';
            h.nextElementSibling.style.maxHeight = null;
        });
        // Open clicked if it wasn't open
        if (!isActive) {
            this.classList.add('active');
            this.querySelector('.toggle-icon').textContent = '−';
            content.style.maxHeight = content.scrollHeight + 'px';
        }
    });
});
</script>
</body>
</html>

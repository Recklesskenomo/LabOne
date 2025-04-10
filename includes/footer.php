<?php
// No direct access
if(!defined('INCLUDED')) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}
?>
    </div> <!-- Close main content div -->
    
    <footer class="footer p-10 bg-primary text-primary-content rounded-none mt-16">
        <div class="container mx-auto grid-flow-col">
            <div>
                <img src="AVlogo.png" alt="Agro Vision" class="w-24 mb-4">
                <p class="opacity-90">Agro Vision<br/>Modern Farm Management Solution<br/>Since 2023</p>
            </div> 
        </div>
        <div>
            <span class="footer-title">Quick Links</span> 
            <a href="/index.php" class="link link-hover">Home</a>
            <a href="/about.php" class="link link-hover">About Us</a>
            <a href="/contactus.php" class="link link-hover">Contact</a>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="/dashboard.php" class="link link-hover">Dashboard</a>
                <a href="/farmregistration.php" class="link link-hover">Farms</a>
            <?php endif; ?>
        </div> 
        <div>
            <span class="footer-title">Legal</span> 
            <a href="#" class="link link-hover">Terms of use</a>
            <a href="#" class="link link-hover">Privacy policy</a>
            <a href="#" class="link link-hover">Cookie policy</a>
        </div> 
        <div>
            <span class="footer-title">Connect</span> 
            <div class="grid grid-flow-col gap-4">
                <a href="#" class="hover:text-neutral transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"></path></svg></a>
                <a href="#" class="hover:text-neutral transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"></path></svg></a>
                <a href="#" class="hover:text-neutral transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"></path></svg></a>
            </div>
        </div>
    </footer>
    
    <div class="bg-secondary text-secondary-content py-4">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date("Y"); ?> Agro Vision - Powered by FarmTech. All rights reserved.</p>
        </div>
    </div>
    
    <!-- JavaScript for interactive elements -->
    <script>
        // Close alerts when the close button is clicked
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to close buttons in alerts
            const closeButtons = document.querySelectorAll('.alert .close-btn');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.alert').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html> 
<footer class="footer bg-white border-t border-gray-200 mt-auto">
    <div class="container py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Monastery Healthcare System</h3>
                <p class="text-gray-600 mb-4">Modern healthcare and donation management system designed specifically for monastery communities.</p>
                <p class="text-sm text-gray-500">Version 2.0 &copy; <?= date('Y') ?> All rights reserved.</p>
            </div>
            
            <div>
                <h4 class="font-semibold text-gray-900 mb-3">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/" class="text-gray-600 hover:text-primary">Home</a></li>
                    <li><a href="/donate" class="text-gray-600 hover:text-primary">Make Donation</a></li>
                    <li><a href="/transparency" class="text-gray-600 hover:text-primary">Transparency Report</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li><a href="/dashboard" class="text-gray-600 hover:text-primary">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="/login" class="text-gray-600 hover:text-primary">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold text-gray-900 mb-3">Contact</h4>
                <div class="space-y-2 text-sm text-gray-600">
                    <p>Email: info@monastery.lk</p>
                    <p>Phone: +94 11 234 5678</p>
                    <p>Emergency: +94 77 123 4567</p>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-200 mt-6 pt-6 text-center text-sm text-gray-500">
            <p>Built with modern web technologies for efficient monastery management.</p>
        </div>
    </div>
</footer>
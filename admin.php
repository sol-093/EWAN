<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
ensureSessionStarted();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?page=home');
    exit;
}

// Include the shared header/styles or a simplified version
require __DIR__ . '/../lib/layout_header.php'; 
?>

<main class="app-shell">
    <section id="admin-panel">
        <h2>Admin: Manage Programs <span class="module-tag">Admin Flow</span></h2>
        <div class="row">
            <label for="program-name">Program name</label>
            <input id="program-name" placeholder="e.g. BS Computer Science" />
            <button onclick="addProgram()">Add Program</button>
        </div>
        <!-- ... rest of Admin UI code from homepage.php ... -->
        <h3 style="margin-top:1.1rem;">Manage Users</h3>
        <div id="admin-users-message" class="message"></div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr><th>Email</th><th>Role</th><th>Created</th><th>Action</th></tr>
            </thead>
            <tbody id="users-table"></tbody>
          </table>
        </div>
    </section>
</main>

<script>
    // Pass session data to JS
    const sessionUser = { email: "<?php echo $_SESSION['email']; ?>", role: "admin" };
    const csrfToken = "<?php echo getCsrfToken(); ?>";
    
    // Include the shared JS logic for API calls, addProgram, renderUsers, etc.
    // (In a real refactor, you'd move the JS to a shared file in /static/js/)
</script>

<?php
require __DIR__ . '/../lib/layout_footer.php';
?>
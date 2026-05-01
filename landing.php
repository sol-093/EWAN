<?php
declare(strict_types=1);
require_once __DIR__ . '/src/core/portal.php';

portalRenderPublicStart('School Records Database');
?>
    <nav class="public-nav">
      <a class="brand" href="index.php">
        <div class="brand-mark"><img src="public/uploads/44f71a208c5b1c5ff972166d1ea9d26b.jpg" alt="" /></div>
        <div class="brand-text">
          <strong>School Records Database</strong>
          <span>Academic Data Management</span>
        </div>
      </a>
      <input class="nav-toggle" type="checkbox" id="public-nav-toggle" aria-label="Toggle navigation" />
      <label class="nav-toggle-button" for="public-nav-toggle" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
      </label>
      <div class="nav-menu">
        <div class="public-actions">
          <a class="btn btn-secondary" href="index.php?page=login">Sign In</a>
          <a class="btn" href="index.php?page=register">Create Account</a>
        </div>
      </div>
    </nav>

    <section class="marketing-hero" style="padding:0; background:transparent; border:none; box-shadow:none;">
      <div class="marketing-card">
        <div>
          <p class="eyebrow">School Records Database</p>
          <h1 style="margin-top:0.55rem;">A structured school database for grade tracking, review, and prospectus work.</h1>
          <p class="marketing-copy" style="margin-top:0.9rem;">School Records Database keeps academic records organized in one green, professional workspace for students, teachers, and administrators.</p>
        </div>
        <div class="hero-actions">
          <a class="btn" href="index.php?page=register">Get Started</a>
          <a class="btn btn-secondary" href="index.php?page=login">Open Portal</a>
        </div>
      </div>

      <aside class="marketing-card">
        <p class="eyebrow">Built For Daily Use</p>
        <div class="hero-points">
          <div class="hero-point">
            <strong>Students</strong>
            <span>Estimate GWA, submit actual grades, and review prospectus progress from one consistent workspace.</span>
          </div>
          <div class="hero-point">
            <strong>Teachers</strong>
            <span>Track queue activity, validate submissions, and manage placement without jumping between mismatched screens.</span>
          </div>
          <div class="hero-point">
            <strong>Administrators</strong>
            <span>Maintain programs, courses, and users with the same spacing, controls, and hierarchy used across the app.</span>
          </div>
        </div>
      </aside>
    </section>

    <section>
      <div class="section-head">
        <div>
          <p class="eyebrow">Shared Primitives</p>
          <h2 style="margin-top:0.35rem;">A restrained system that feels like one product</h2>
        </div>
      </div>
      <div class="feature-grid">
        <a href="index.php?page=student_calculator">
          <h3>Focused task surfaces</h3>
          <p>Cards, forms, tables, and badges now follow a single rhythm instead of competing layouts.</p>
        </a>
        <a href="index.php?page=teacher_dashboard">
          <h3>Clear hierarchy</h3>
          <p>Headings, summaries, and actions read more quickly because spacing and contrast are doing the work.</p>
        </a>
        <a href="index.php?page=admin">
          <h3>Consistent controls</h3>
          <p>Buttons, inputs, nav states, and data tables stay predictable across public and authenticated pages.</p>
        </a>
      </div>
    </section>

<?php portalRenderPublicEnd(); ?>

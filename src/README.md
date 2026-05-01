# Source Structure

This project now follows the WebSys-style structure:

- core/: foundational runtime/config/database files
  - db.php
- actions/: action handlers and API endpoints
  - api.php
- pages/: page render files
  - homepage.php, login.php, register.php, logout.php
- services/: reserved for service-layer logic
- lib/: reserved for reusable helpers

Entry point:
- index.php

Route mapping:
- index.php?page=login
- index.php?page=register
- index.php?page=home
- index.php?page=logout
- index.php?page=api&action=...

System architecture alignment (based on role flow):

- Admin:
  - Manage programs and courses
  - Manage users and role assignments
- Teacher:
  - Validate grade submissions
  - Teacher dashboard for submission overview
- Student:
  - Encode grades
  - View approved GWA
- Core engine:
  - GWA computation from approved grades
- Shared dashboard:
  - Unified role-aware summary view
- Output:
  - Prospectus generator text output

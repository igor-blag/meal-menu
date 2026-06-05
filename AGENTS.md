# AI Instructions
This is a WordPress plugin for meal menu publication, plugin has simlink for local WordPress site C:\Users\igorb\Studio\edu-site\AGENTS.md
Complete description of this plugin in folder /docs

## Database access (SQLite)
Site: edu-site (C:\Users\igorb\Studio\edu-site)
DB file: C:\Users\igorb\Studio\edu-site\wp-content\database\.ht.sqlite

Use direct sqlite3 CLI (no site start needed):
  & "$env:LOCALAPPDATA\Microsoft\WinGet\Links\sqlite3.exe" -header -column "C:\Users\igorb\Studio\edu-site\wp-content\database\.ht.sqlite" "SQL_QUERY"

Alternative via WP-CLI (site must be running):
  1. Write PHP file to site root using $wpdb->get_results()
  2. Run: wp eval-file script.php


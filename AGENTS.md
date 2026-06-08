# AI Instructions
This is a WordPress plugin for meal menu publication.
Complete description of this plugin in folder /docs

Symlink: `/Users/igorblagovesenskij/Studio/edu-site/wp-content/plugins/meal-menu`
  → `/Users/igorblagovesenskij/projects/plugins/meal-menu`

## Database access (SQLite)
Site path: `/Users/igorblagovesenskij/Studio/edu-site`
DB file: `/Users/igorblagovesenskij/Studio/edu-site/wp-content/database/.ht.sqlite`

Direct SQLite (site not needed):
  sqlite3 -header -column "/Users/igorblagovesenskij/Studio/edu-site/wp-content/database/.ht.sqlite" "SQL_QUERY"

Via WP-CLI (site must be running):
  1. Write PHP eval-file script using $wpdb->get_results()
  2. Run: wp eval-file script.php


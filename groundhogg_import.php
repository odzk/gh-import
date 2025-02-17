<?php
/**
 * Plugin Name: Groundhogg Importer & Exporter
 * Description: Import and Export Groundhogg tables from an SQL backup file.
 * Version: 1.6
 * Author: Odysseus Ambut
 * Author URI: https://web-mech.net/support
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Add a menu item in the WordPress dashboard
function gh_importer_menu() {
    add_menu_page('GH Import / Export', 'GH Import / Export', 'manage_options', 'gh-importer', 'gh_importer_page');
}
add_action('admin_menu', 'gh_importer_menu');


// Admin Page with GH Table List, Import & Export Options
function gh_importer_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $default_prefix = $wpdb->prefix;

    // Get all Groundhogg tables dynamically
    $tables = $wpdb->get_results($wpdb->prepare("
        SELECT TABLE_NAME AS name, 
               ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = %s 
        AND TABLE_NAME LIKE %s", 
        DB_NAME, $wpdb->esc_like($default_prefix . 'gh_') . '%'
    ));

    ?>
    
    <div class="wrap">
        <h1>Groundhogg Importer & Exporter</h1>

        <!-- Show Existing GH Tables -->
        <h2>Groundhogg Tables</h2>
        <?php if (!empty($tables)) : ?>
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; background: #fff;">
                <thead>
                    <tr style="background: #f7f7f7;">
                        <th style="border: 1px solid #ddd; padding: 10px;">Table Name</th>
                        <th style="border: 1px solid #ddd; padding: 10px;">File Size (MB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table) : ?>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?php echo esc_html($table->name); ?></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?php echo esc_html($table->size); ?> MB</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p style="color: red; font-weight: bold;">No Groundhogg tables found in the database!</p>
        <?php endif; ?>

        <h2>Export Groundhogg Data</h2>
        <form method="post">
            <input type="submit" name="gh_export_submit" value="Export Groundhogg Data" class="button button-primary">
        </form>

        <h2>Import Groundhogg Data</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="gh_sql_file" accept=".sql" required>
            <br><br>

            <label for="source_prefix">Source Table Prefix:</label>
            <input type="text" name="source_prefix" value="wp_" required>
            <p style="font-size: 12px; color: #666;">Enter the prefix used in the source SQL file (default: wp_).</p>

            <label for="target_prefix">Target Table Prefix:</label>
            <input type="text" name="target_prefix" value="<?php echo esc_attr($default_prefix); ?>" required>
            <p style="font-size: 12px; color: #666;">Enter your WordPress table prefix (e.g., wp_, tsg_).</p>

            <br>
            <label>
                <input type="radio" name="import_option" value="drop" checked> Drop existing tables & restore
            </label><br>
            <label>
                <input type="radio" name="import_option" value="skip"> Skip import if tables exist
            </label><br>
            <label>
                <input type="radio" name="import_option" value="cancel"> Cancel if tables exist
            </label><br>
            <label>
                <input type="radio" name="import_option" value="overwrite"> Overwrite data without deleting tables
            </label><br><br>
            <input type="submit" name="gh_import_submit" value="Import Backup" class="button button-primary">
        </form>
    </div>
    <?php

    // Handle Export
    if (isset($_POST['gh_export_submit'])) {
        gh_export_sql_file();
    }

    // Handle Import
    if (isset($_POST['gh_import_submit']) && !empty($_FILES['gh_sql_file']['tmp_name'])) {
        gh_import_sql_file($_FILES['gh_sql_file'], sanitize_text_field($_POST['source_prefix']), sanitize_text_field($_POST['target_prefix']), sanitize_text_field($_POST['import_option']));
    }
}

// Function to export the Groundhogg tables
function gh_export_sql_file() {
    global $wpdb;

    $backup_file = WP_CONTENT_DIR . "/uploads/groundhogg_backup_" . date("Y-m-d_H-i-s") . ".sql";
    $tables = $wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($wpdb->prefix . 'gh_') . '%'));

    if (empty($tables)) {
        echo "<p style='color: red;'>No Groundhogg tables found!</p>";
        return;
    }

    $sql_dump = "-- Groundhogg Database Export\n-- Generated on " . date("Y-m-d H:i:s") . "\n\n";

    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
        $sql_dump .= $create_table[1] . ";\n\n";

        // Get table data
        $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
        foreach ($rows as $row) {
            $values = array_map([$wpdb, 'prepare'], array_values($row));
            $values = implode(", ", array_map(fn($v) => is_null($v) ? "NULL" : "'{$v}'", $values));
            $sql_dump .= "INSERT INTO `{$table}` VALUES ($values);\n";
        }
        $sql_dump .= "\n\n";
    }

    // Save to file
    file_put_contents($backup_file, $sql_dump);

    echo "<p style='color: green;'>Export completed successfully! <a href='" . content_url("/uploads/" . basename($backup_file)) . "'>Download Backup</a></p>";
}



// Function to import the SQL file
function gh_import_sql_file($file, $source_prefix, $target_prefix, $import_option) {
    global $wpdb;
    $backup_file = $file['tmp_name'];

    // Create a temporary modified SQL file
    $modified_sql = WP_CONTENT_DIR . "/uploads/temp_import.sql";
    $sql_content = file_get_contents($backup_file);

    // Replace source prefix with target prefix
    $sql_content = str_replace("`{$source_prefix}gh_", "`{$target_prefix}gh_", $sql_content);
    file_put_contents($modified_sql, $sql_content);

    // Log file path
    $log_file = WP_CONTENT_DIR . "/uploads/gh_import.log";
    file_put_contents($log_file, "Starting import...\n", FILE_APPEND);

    // Check if tables exist
    $existing_tables = $wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($target_prefix . 'gh_') . '%'));

    if (!empty($existing_tables)) {
        if ($import_option == "skip") {
            file_put_contents($log_file, "Skipping import: Tables already exist.\n", FILE_APPEND);
            echo "<p style='color: red;'>Tables already exist. Skipping import.</p>";
            return;
        } elseif ($import_option == "cancel") {
            file_put_contents($log_file, "Import canceled: Tables already exist.\n", FILE_APPEND);
            echo "<p style='color: red;'>Import canceled as tables exist.</p>";
            return;
        } elseif ($import_option == "drop") {
            echo "<p style='color: red;'>Dropping existing tables...</p>";
            file_put_contents($log_file, "Dropping existing tables...\n", FILE_APPEND);
            foreach ($existing_tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
            }
        }
    }

    // Read SQL file and process queries
    $sql_statements = explode(";\n", file_get_contents($modified_sql));
    $success_count = 0;
    $error_count = 0;

    foreach ($sql_statements as $query) {
        $query = trim($query);
        if (!empty($query)) {
            // Apply "overwrite" option: Convert INSERT INTO to "INSERT ... ON DUPLICATE KEY UPDATE"
            if ($import_option == "overwrite" && stripos($query, "INSERT INTO") === 0) {
                $query = preg_replace_callback('/INSERT INTO (`[^`]+`) \(([^)]+)\) VALUES (.+)/i', function ($matches) {
                    return "INSERT INTO " . $matches[1] . " (" . $matches[2] . ") VALUES " . $matches[3] . 
                           " ON DUPLICATE KEY UPDATE " . implode(", ", array_map(function ($column) {
                               return "$column = VALUES($column)";
                           }, explode(",", $matches[2])));
                }, $query);
            }

            // Execute query
            $result = $wpdb->query($query);
            if ($result === false) {
                file_put_contents($log_file, "Error: " . $wpdb->last_error . "\nQuery: $query\n\n", FILE_APPEND);
                $error_count++;
            } else {
                $success_count++;
            }
        }
    }

    // Log completion
    file_put_contents($log_file, "Import completed: $success_count queries executed, $error_count errors.\n", FILE_APPEND);

    // Delete temporary file
    unlink($modified_sql);

    if ($error_count > 0) {
        echo "<p style='color: red;'>Import completed with errors! See <a href='" . content_url('/uploads/gh_import.log') . "' target='_blank'>debug log</a> for details.</p>";
    } else {
        echo "<p style='color: green;'>Import completed successfully! ($success_count queries executed)</p>";
    }
}


<?php
/*
Plugin Name: Patient Report Manager
Description: A WordPress plugin to manage patients and generate reports.
Version: 1.6
Author: Your Name
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Activation hook: Create database tables
register_activation_hook(__FILE__, 'prm_create_tables');
function prm_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for patients
    $patients_table = $wpdb->prefix . 'prm_patients';
    $sql1 = "CREATE TABLE IF NOT EXISTS $patients_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        age int NOT NULL,
        mobile varchar(15) NOT NULL,
        email varchar(255) NOT NULL,
        date date NOT NULL,
        time time NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Table for reports
    $reports_table = $wpdb->prefix . 'prm_reports';
    $sql2 = "CREATE TABLE IF NOT EXISTS $reports_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        patient_id mediumint(9) NOT NULL,
        report text NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY (patient_id) REFERENCES $patients_table(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

// Add admin menu
add_action('admin_menu', 'prm_add_admin_menu');
function prm_add_admin_menu() {
    // Add main menu page
    add_menu_page(
        'Patient Report Manager',
        'Patient Manager',
        'manage_options',
        'prm-add-patient',
        'prm_add_patient_page', // Callback function for Add Patient page
        'dashicons-admin-users',
        6
    );

    // Add submenu for letterhead settings
    add_submenu_page(
        'prm-add-patient',
        'Letterhead Settings',
        'Letterhead Settings',
        'manage_options',
        'prm-letterhead-settings',
        'prm_letterhead_settings_page' // Callback function for Letterhead Settings page
    );

    // Add submenu for generating reports
    add_submenu_page(
        'prm-add-patient',
        'Generate Report',
        'Generate Report',
        'manage_options',
        'prm-generate-report',
        'prm_generate_report_page' // Callback function for Generate Report page
    );
}

// Patient Manager Page
function prm_add_patient_page() {
    global $wpdb;
    $patients_table = $wpdb->prefix . 'prm_patients';

    // Handle Add Patient Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prm_add_patient'])) {
        $name = sanitize_text_field($_POST['name']);
        $age = intval($_POST['age']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $email = sanitize_email($_POST['email']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);

        $wpdb->insert($patients_table, compact('name', 'age', 'mobile', 'email', 'date', 'time'));

        echo '<div class="notice notice-success"><p>Patient added successfully!</p></div>';
    }

    // Fetch all patients
    $patients = $wpdb->get_results("SELECT * FROM $patients_table");

    echo '<div class="wrap">';
    echo '<h1>Manage Patients</h1>';

    // Add Patient Form
    echo '<form method="POST" class="prm-form">';
    echo '<input type="hidden" name="prm_add_patient" value="1">';
    echo '<label for="name">Name:</label><br>';
    echo '<input type="text" id="name" name="name" placeholder="Name" required><br>';
    echo '<label for="age">Age:</label><br>';
    echo '<input type="number" id="age" name="age" placeholder="Age" required><br>';
    echo '<label for="mobile">Mobile:</label><br>';
    echo '<input type="text" id="mobile" name="mobile" placeholder="Mobile" required><br>';
    echo '<label for="email">Email:</label><br>';
    echo '<input type="email" id="email" name="email" placeholder="Email" required><br>';
    echo '<label for="date">Date:</label><br>';
    echo '<input type="date" id="date" name="date" required><br>';
    echo '<label for="time">Time:</label><br>';
    echo '<input type="time" id="time" name="time" required><br>';
    echo '<button type="submit" class="button button-primary">Add Patient</button>';
    echo '</form>';

    // Display Patient List
    echo '<h2>Patient List</h2>';
    if (count($patients) === 0) {
        echo '<p>No patients found.</p>';
    } else {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Mobile</th><th>Email</th><th>Date</th><th>Time</th></tr></thead><tbody>';
        foreach ($patients as $patient) {
            echo "<tr>
                <td>{$patient->id}</td>
                <td>{$patient->name}</td>
                <td>{$patient->age}</td>
                <td>{$patient->mobile}</td>
                <td>{$patient->email}</td>
                <td>{$patient->date}</td>
                <td>{$patient->time}</td>
            </tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Letterhead Settings Page
function prm_letterhead_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prm_save_letterhead_settings'])) {
        $letterhead_logo = sanitize_text_field($_POST['letterhead_logo']);
        $letterhead_header = sanitize_textarea_field($_POST['letterhead_header']);
        $letterhead_footer = sanitize_textarea_field($_POST['letterhead_footer']);

        update_option('prm_letterhead_logo', $letterhead_logo);
        update_option('prm_letterhead_header', $letterhead_header);
        update_option('prm_letterhead_footer', $letterhead_footer);

        echo '<div class="notice notice-success"><p>Letterhead settings saved successfully!</p></div>';
    }

    $letterhead_logo = get_option('prm_letterhead_logo', '');
    $letterhead_header = get_option('prm_letterhead_header', '');
    $letterhead_footer = get_option('prm_letterhead_footer', '');

    echo '<div class="wrap">';
    echo '<h1>Letterhead Settings</h1>';
    echo '<form method="POST">';
    echo '<input type="hidden" name="prm_save_letterhead_settings" value="1">';
    echo '<label for="letterhead_logo">Logo URL:</label><br>';
    echo '<input type="url" id="letterhead_logo" name="letterhead_logo" value="' . esc_attr($letterhead_logo) . '" class="large-text"><br>';
    echo '<label for="letterhead_header">Header Text:</label><br>';
    echo '<textarea id="letterhead_header" name="letterhead_header" class="large-text">' . esc_textarea($letterhead_header) . '</textarea><br>';
    echo '<label for="letterhead_footer">Footer Text:</label><br>';
    echo '<textarea id="letterhead_footer" name="letterhead_footer" class="large-text">' . esc_textarea($letterhead_footer) . '</textarea><br>';
    echo '<button type="submit" class="button button-primary">Save Settings</button>';
    echo '</form>';
    echo '</div>';
}

// Generate Report Page
function prm_generate_report_page() {
    global $wpdb;
    $patients_table = $wpdb->prefix . 'prm_patients';
    $reports_table = $wpdb->prefix . 'prm_reports';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prm_generate_report'])) {
        $patient_id = intval($_POST['patient_id']);
        $report_content = sanitize_textarea_field($_POST['report']);
        
        // Save the report
        $wpdb->insert($reports_table, ['patient_id' => $patient_id, 'report' => $report_content]);

        // Fetch letterhead settings
        $logo = get_option('prm_letterhead_logo', '');
        $header = get_option('prm_letterhead_header', '');
        $footer = get_option('prm_letterhead_footer', '');

        // Generate Report with Letterhead
        $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $patients_table WHERE id = %d", $patient_id));
        echo '<div class="wrap">';
        echo '<h1>Generated Report</h1>';

        // Letterhead
        echo '<div style="border: 1px solid #000; padding: 20px; margin-bottom: 20px;">';
        if ($logo) echo '<img src="' . esc_url($logo) . '" alt="Logo" style="max-width: 100px; display: block; margin-bottom: 20px;">';
        if ($header) echo '<h2>' . esc_html($header) . '</h2>';
        echo '</div>';

        // Report Content
        echo '<h2>Report for ' . esc_html($patient->name) . '</h2>';
        echo '<p>' . nl2br(esc_html($report_content)) . '</p>';

        // Footer
        if ($footer) {
            echo '<div style="border-top: 1px solid #000; margin-top: 20px; padding-top: 10px;">';
            echo '<p>' . esc_html($footer) . '</p>';
            echo '</div>';
        }

        echo '</div>';
        return;
    }

    // Fetch all patients
    $patients = $wpdb->get_results("SELECT * FROM $patients_table");

    echo '<div class="wrap">';
    echo '<h1>Generate Report</h1>';

    if (count($patients) === 0) {
        echo '<p>No patients found. Please add patients first.</p>';
    } else {
        echo '<form method="POST" class="prm-form">';
        echo '<input type="hidden" name="prm_generate_report" value="1">';
        echo '<label for="patient_id">Select Patient:</label><br>';
        echo '<select id="patient_id" name="patient_id" required>';
        echo '<option value="">Select Patient</option>';
        foreach ($patients as $patient) {
            echo "<option value='{$patient->id}'>{$patient->name} (Age: {$patient->age}, Mobile: {$patient->mobile})</option>";
        }
        echo '</select><br>';
        echo '<label for="report">Report Content:</label><br>';
        echo '<textarea id="report" name="report" class="large-text" placeholder="Write the report here..." required></textarea><br>';
        echo '<button type="submit" class="button button-primary">Generate Report</button>';
        echo '</form>';
    }
    echo '</div>';
}
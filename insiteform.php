<?php
/*
 * Plugin Name: Insiteform
 * Plugin URI: https://www.insiteform.com
 * Description: Track and analyse your WordPress forms to improve conversions and user experience
 * Version: 1.0.0
 * Author: Insiteform
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

function insiteform_admin_menu() {
    // Create a new top-level menu
    add_menu_page(
        'Insiteform', // Page title
        'Insiteform', // Menu title
        'manage_options', // Capability
        'insiteform', // Menu slug
        'insiteform_admin_screen', // Callback function
        'dashicons-info-outline',
        100 // Menu position
    );
}
add_action('admin_menu', 'insiteform_admin_menu');

function insiteform_admin_screen() {
    // Check if the user has permission to access this page
    if (!current_user_can('manage_options')) {
        return;
    }

      // Check if the form has been submitted
      if (isset($_POST['insiteform_key'])) {
        // Clean and escape the input
        $insiteform_key = sanitize_text_field(wp_unslash($_POST['insiteform_key']));
        $insiteform_key = esc_attr($insiteform_key);
    
        // Save the key to the database
        update_option('insiteform_key', $insiteform_key);

        // Show a success message
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>Your key has been saved.</p>';
        echo '</div>';
    }

    // Display the form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post">
            <?php
            // Check if a key is already saved
            $key = get_option('insiteform_key');
            if ($key) {
                // If a key is saved, display it truncated
                $key = substr($key, 0, 20) . '...';
                $button_text = 'Update Key';
                $instructions = 'Please test your form to ensure it\'s set up and ready for use';

            } else {
                $button_text = 'Save Key';
                $instructions = 'Enter your activation key here.';
            }
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="insiteform_key">Activation Key</label></th>
                        <td>
                            <input name="insiteform_key" type="text" id="insiteform_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
                            <p class="description" id="insiteform-key-description"><?php echo esc_attr($instructions); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr($button_text); ?>">
            </p>
        </form>
    </div>
    <?php
}

function insiteform_scripts() {
    // Get the saved activation key
    $key = get_option('insiteform_key');

    // Check if a key is saved
    if ($key) {
        // Build the API URL
        $url = 'https://api.insiteform.com/v1/user-domain/get-pages/' . $key;

        // Escape the URL
        $url = esc_url($url);

        // Call the API
        $response = wp_remote_get($url);
        $code = wp_remote_retrieve_response_code($response);

        // Check if the request was successful
        if (is_wp_error($response) || $code !== 200) {
            return;
        }

        // Get the response body
        $body = wp_remote_retrieve_body($response);

        // Decode the JSON
        $data = json_decode($body);

        // Check if the API returned any pages
        if (!empty($data)) {
            // Loop through the pages
            foreach ($data as $page) {
                if ((is_single($page) || is_page($page)) || ($page === 'home-page' && is_front_page())) {
                     // Enqueue the script for each page
                    wp_enqueue_script(
                        'insiteform-script', // Script handle
                        'https://api.insiteform.com/v1/insiteform-script', // Script URL
                        array(), // Dependencies
                        '1.0.0', // Version
                        true // Load in footer
                    );
                }
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'insiteform_scripts');

function insiteform_uninstall() {
    // Delete the saved activation key
    delete_option('insiteform_key');
}
register_uninstall_hook(__FILE__, 'insiteform_uninstall');
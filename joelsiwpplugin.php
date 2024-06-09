<?php
/*
Plugin Name: Joel’s Interstitial Plugin for WordPress
Description: Display an interstitial for external links with customization options.
Version: 1.0
Authors: Joel Gratcyk, ChatGPT (OpenAI)
Author URI: https://joel.gr
*/

// Function to check if a URL is external
function is_external_link($url) {
    $site_url = site_url(); // Get the site's URL
    $parsed_site_url = parse_url($site_url);
    $parsed_url = parse_url($url);

    // Compare the hostnames of the URLs
    return isset($parsed_url['host']) && $parsed_url['host'] !== $parsed_site_url['host'];
}

// Function to add interstitial for external links
function add_external_links_interstitial($content) {
    $options = get_option('external_links_interstitial_options');
    $delay = isset($options['delay']) ? intval($options['delay']) : 0;

    // Check if the content has any anchor tags
    if (strpos($content, '<a ') !== false) {
        // Get all anchor tags from the content
        $doc = new DOMDocument();
        $doc->loadHTML($content);
        $links = $doc->getElementsByTagName('a');

        // Loop through each anchor tag
        foreach ($links as $link) {
            $url = $link->getAttribute('href');
            
            // Check if the URL is external
            if (is_external_link($url)) {
                // Check if the URL matches any excluded domains
                $excluded_domains = isset($options['excluded_domains']) ? explode("\n", $options['excluded_domains']) : array();
                $parsed_url = parse_url($url);
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

                if (!in_array($host, $excluded_domains)) {
                    // Modify the anchor tag to include interstitial with delay
                    $link->setAttribute('onclick', 'setTimeout(function() { if (!confirm("You are leaving this site. Continue?")) { return false; } window.location.href = "' . esc_url($url) . '"; }, ' . $delay . '); return false;');
                }
            }
        }

        // Save the modified HTML
        $content = $doc->saveHTML();
    }

    return $content;
}

// Add plugin settings page
function external_links_interstitial_settings_page() {
    add_options_page('External Links Interstitial Settings', 'External Links Interstitial', 'manage_options', 'external-links-interstitial-settings', 'external_links_interstitial_settings_page_content');
}
add_action('admin_menu', 'external_links_interstitial_settings_page');

// Callback function to display settings page content
function external_links_interstitial_settings_page_content() {
    ?>
    <div class="wrap">
        <h2>External Links Interstitial Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('external_links_interstitial_options_group'); ?>
            <?php $options = get_option('external_links_interstitial_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Excluded Domains</th>
                    <td><textarea name="external_links_interstitial_options[excluded_domains]" rows="5" cols="50"><?php echo isset($options['excluded_domains']) ? esc_textarea($options['excluded_domains']) : ''; ?></textarea><br />
                    <span class="description">Enter one domain per line to exclude from showing the interstitial.</span></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Delay (milliseconds)</th>
                    <td><input type="number" name="external_links_interstitial_options[delay]" value="<?php echo isset($options['delay']) ? esc_attr($options['delay']) : '0'; ?>" min="0" step="100"><br />
                    <span class="description">Delay before showing the interstitial (0 for no delay).</span></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register plugin settings
function external_links_interstitial_register_settings() {
    register_setting('external_links_interstitial_options_group', 'external_links_interstitial_options');
}
add_action('admin_init', 'external_links_interstitial_register_settings');

// Hook into the_content filter to add interstitial for external links
add_filter('the_content', 'add_external_links_interstitial');
?>

<?php
/*
Plugin Name: Clean Discord Embed
Description: Customizes Discord link previews with Open Graph tags and admin options for image, author, and excerpt. Also filters oEmbed data for Discord.
Version: 1.0
Author: C4813
*/

defined('ABSPATH') or die('No script kiddies please!');

// Default options on activation
function clean_discord_embed_activate() {
    add_option('clean_discord_embed_image_url', 'https://example.com/default-image.png');
    add_option('clean_discord_embed_show_author', true);
    add_option('clean_discord_embed_show_excerpt', true);
}
register_activation_hook(__FILE__, 'clean_discord_embed_activate');

// Inject Open Graph meta tags
function clean_discord_embed_meta_tags() {
    if (is_singular()) {
        global $post;

        $image_url = get_option('clean_discord_embed_image_url', '');
        $show_author = (bool) get_option('clean_discord_embed_show_author', true);
        $show_excerpt = (bool) get_option('clean_discord_embed_show_excerpt', true);

        $title = get_the_title($post);
        if ($show_author) {
            $author = get_the_author_meta('display_name', $post->post_author);
            $title .= ' by ' . $author;
        }

        $description = $show_excerpt ? get_the_excerpt($post) : '';
        $url = get_permalink($post);

        echo "\n<!-- Clean Discord Embed -->\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
        echo "\n<!-- End Clean Discord Embed -->\n";
    }
}
add_action('wp_head', 'clean_discord_embed_meta_tags');

// Admin menu
function clean_discord_embed_admin_menu() {
    add_options_page(
        'Discord Embed Settings',
        'Discord Embed',
        'manage_options',
        'clean-discord-embed',
        'clean_discord_embed_settings_page'
    );
}
add_action('admin_menu', 'clean_discord_embed_admin_menu');

// Settings page
function clean_discord_embed_settings_page() {
    ?>
    <div class="wrap">
        <h1>Discord Embed Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('clean_discord_embed_settings');
            do_settings_sections('clean-discord-embed');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function clean_discord_embed_register_settings() {
    register_setting('clean_discord_embed_settings', 'clean_discord_embed_image_url');

    register_setting('clean_discord_embed_settings', 'clean_discord_embed_show_author', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true,
    ]);

    register_setting('clean_discord_embed_settings', 'clean_discord_embed_show_excerpt', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true,
    ]);

    add_settings_section(
        'clean_discord_embed_main',
        'Main Settings',
        null,
        'clean-discord-embed'
    );

    add_settings_field(
        'clean_discord_embed_image_url',
        'Default Embed Image URL',
        'clean_discord_embed_image_url_field',
        'clean-discord-embed',
        'clean_discord_embed_main'
    );

    add_settings_field(
        'clean_discord_embed_show_author',
        'Show Author Name in Title',
        'clean_discord_embed_show_author_field',
        'clean-discord-embed',
        'clean_discord_embed_main'
    );

    add_settings_field(
        'clean_discord_embed_show_excerpt',
        'Show Post/Page Excerpt',
        'clean_discord_embed_show_excerpt_field',
        'clean-discord-embed',
        'clean_discord_embed_main'
    );
}
add_action('admin_init', 'clean_discord_embed_register_settings');

// Field rendering
function clean_discord_embed_image_url_field() {
    $value = get_option('clean_discord_embed_image_url', '');
    echo '<input type="text" name="clean_discord_embed_image_url" value="' . esc_attr($value) . '" size="50" />';
    echo '<p class="description">This image will be used for all Discord embeds. Leave empty for no image.</p>';
}

function clean_discord_embed_show_author_field() {
    $value = (bool) get_option('clean_discord_embed_show_author', true);
    echo '<input type="hidden" name="clean_discord_embed_show_author" value="0" />';
    echo '<input type="checkbox" name="clean_discord_embed_show_author" value="1"' . checked(true, $value, false) . '>';
}

function clean_discord_embed_show_excerpt_field() {
    $value = (bool) get_option('clean_discord_embed_show_excerpt', true);
    echo '<input type="hidden" name="clean_discord_embed_show_excerpt" value="0" />';
    echo '<input type="checkbox" name="clean_discord_embed_show_excerpt" value="1"' . checked(true, $value, false) . '>';
}

// Filter oEmbed for Discord (removes author name from top line)
add_filter('oembed_response_data', function($data, $post, $width, $height) {
    if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'discord') !== false) {
        $data['author_name'] = '';
    }
    return $data;
}, 10, 4);

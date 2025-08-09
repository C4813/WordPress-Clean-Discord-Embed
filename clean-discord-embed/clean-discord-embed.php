<?php
/*
Plugin Name: Clean Discord Embed
Description: Customizes Discord link previews with Open Graph tags and admin options for image, author, and excerpt. Also filters oEmbed data for Discord.
Version: 1.1
Author: C4813
*/

defined('ABSPATH') || exit;

// Default options on activation
function clean_discord_embed_activate() {
    add_option('clean_discord_embed_image_url', 'https://example.com/default-image.png');
    add_option('clean_discord_embed_show_author', true);
    add_option('clean_discord_embed_show_excerpt', true);
}
register_activation_hook(__FILE__, 'clean_discord_embed_activate');

/**
 * Inject Open Graph meta tags for Discord
 */
function clean_discord_embed_meta_tags() {
    if (!is_singular()) {
        return;
    }

    global $post;
    if (!($post instanceof WP_Post)) {
        return;
    }

    $image_url   = esc_url(get_option('clean_discord_embed_image_url', ''));
    $show_author = (bool) get_option('clean_discord_embed_show_author', true);
    $show_excerpt = (bool) get_option('clean_discord_embed_show_excerpt', true);

    $title = wp_strip_all_tags(get_the_title($post));
    if ($show_author) {
        $author = get_the_author_meta('display_name', $post->post_author);
        $title .= ' by ' . $author;
    }
    $title = mb_substr($title, 0, 100); // limit length

    $description = $show_excerpt ? wp_strip_all_tags(get_the_excerpt($post)) : '';
    $description = mb_substr($description, 0, 300); // limit length

    $url = get_permalink($post);

    echo "\n<!-- Clean Discord Embed -->\n";
    printf('<meta property="og:title" content="%s" />' . "\n", esc_attr($title));
    printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($description));
    printf('<meta property="og:url" content="%s" />' . "\n", esc_url($url));
    if (!empty($image_url)) {
        printf('<meta property="og:image" content="%s" />' . "\n", esc_url($image_url));
    }
    echo "<!-- End Clean Discord Embed -->\n";
}
if (!is_admin()) {
    add_action('wp_head', 'clean_discord_embed_meta_tags');
}

/**
 * Admin menu
 */
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

/**
 * Settings page
 */
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

/**
 * Register settings
 */
function clean_discord_embed_register_settings() {
    register_setting('clean_discord_embed_settings', 'clean_discord_embed_image_url', [
        'type'              => 'string',
        'sanitize_callback' => function($value) {
            $value = esc_url_raw($value);
            return mb_substr($value, 0, 300);
        },
        'default' => '',
    ]);

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
        '__return_false',
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

/**
 * Field rendering
 */
function clean_discord_embed_image_url_field() {
    $value = esc_url(get_option('clean_discord_embed_image_url', ''));
    echo '<input type="url" name="clean_discord_embed_image_url" value="' . esc_attr($value) . '" size="50" maxlength="300" />';
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

/**
 * Filter oEmbed for Discord (removes author name)
 */
add_filter('oembed_response_data', function($data, $post, $width, $height) {
    if (!($post instanceof WP_Post)) {
        return $data;
    }
    if (!empty($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'discord') !== false) {
        $data['author_name'] = '';
    }
    return $data;
}, 10, 4);

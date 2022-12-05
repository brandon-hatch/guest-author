<?php

/**
 * Checks if Gutenberg is enabled
 *
 * @since 1.9.1
 *
 * @credit: https://wordpress.stackexchange.com/questions/321368/how-to-check-if-current-admin-page-is-gutenberg-editor
 *
 * @return boolean True if gutenberg is enabled
 */
function BS_is_gutenberg () {
    global $current_screen;
    $current_screen = get_current_screen();

    return
        (method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor())
        || ( ( function_exists('is_gutenberg_page')) && is_gutenberg_page() );

}

<?php
/*
Plugin Name: The Movie DB Import
Description: Import movies and TV shows from The Movie DB using their API
*/

// Register a new menu page for the plugin
add_action( 'admin_menu', 'themoviedb_import_menu' );
function themoviedb_import_menu() {
    add_menu_page( 'The Movie DB Import', 'The Movie DB Import', 'manage_options', 'themoviedb-import', 'themoviedb_import_page', 'dashicons-upload', 6  );
}

// Display the import page
function themoviedb_import_page() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Check if the form has been submitted
    if ( isset( $_POST['import'] ) ) {
        // API endpoint and key
        $api_endpoint = 'https://api.themoviedb.org/3/';
        $api_key = 'f091c384fc05a9cf1137f1b94b4ee90a';

        // Get the list of movies and TV shows
        $response = wp_remote_get( $api_endpoint . 'discover/movie?api_key=' . $api_key );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        // Check for errors
        if ( is_wp_error( $response ) || $data['status_code'] != 1 ) {
            echo '<p>An error occurred while trying to import movies and TV shows from The Movie DB.</p>';
            return;
        }

        // Loop through the results and display them
        echo '<h2>Movies and TV Shows</h2>';
        echo '<form method="post">';
        echo '<ul>';
        foreach ( $data['results'] as $item ) {
            echo '<li><input type="checkbox" name="import_items[]" value="' . $item['id'] . '">' . $item['title'] . '</li>';
        }
        echo '</ul>';
        echo '<input type="submit" name="import_selected" value="Import Selected">';
        echo '</form>';
    } elseif ( isset( $_POST['import_selected'] ) ) {
        // Check if any items were selected for import
        if ( empty( $_POST['import_items'] ) ) {
            echo '<p>No items were selected for import.</p>';
            return;
        }

        // Loop through the selected items
        foreach ( $_POST['import_items'] as $item_id ) {
            // Get the movie or TV show data from The Movie DB
            $response = wp_remote_get( $api_endpoint . 'movie/' . $item_id . '?api_key=' . $api_key );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            
        // Insert the data into the WordPress database
        $post_id = wp_insert_post( array(
            'post_title' => $data['title'],
            'post_content' => $data['overview'],
            'post_type' => 'movie',
            'post_status' => 'publish',
        ) );

        // Add the movie's release date as a custom field
        add_post_meta( $post_id, 'release_date', $data['release_date'] );

        // Add the movie's poster as a featured image
        $poster_url = 'https://image.tmdb.org/t/p/original' . $data['poster_path'];
        $poster = media_sideload_image( $poster_url, $post_id );
        set_post_thumbnail( $post_id, $poster );
    }

    // Display a success message
    echo '<p>The selected movies and TV shows have been imported.</p>';
}

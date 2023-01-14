<?php
/*
Plugin Name: The Movie DB Import
Description: Import movies and TV shows from The Movie DB using their API
*/

// Register a new menu page for the plugin
add_action( 'admin_menu', 'themoviedb_import_menu' );
function themoviedb_import_menu() {
    add_menu_page( 'The Movie DB Import', 'The Movie DB Import', 'manage_options', 'themoviedb-import', 'themoviedb_import_page', 'dashicons-download' );
}

// Display the plugin's admin page
function themoviedb_import_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

<h1>The Movie DB Import</h1>
<form method="post">
    <label for="movies">Select the movies and TV shows to import:</label>
    <br>
    <?php
    // Get the list of movies and TV shows
    $response = wp_remote_get( $api_endpoint . 'popular?api_key=' . $api_key );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    ?>
    <ul>
        <?php foreach ( $data['results'] as $movie ) : ?>
            <li>
                <input type="checkbox" name="movies[]" value="<?php echo $movie['id']; ?>">
                <?php echo $movie['title']; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <input type="submit" name="submit" value="Import">
</form>


    $api_endpoint = 'https://api.themoviedb.org/3/movie/';
    $api_key = 'f091c384fc05a9cf1137f1b94b4ee90a';

    // Check if the form has been submitted
    if ( isset( $_POST['submit'] ) ) {

        // Get the selected movie IDs
        $movie_ids = $_POST['movies'];

        // Loop through the selected movie IDs
        foreach ( $movie_ids as $movie_id ) {

            // Make a request to the API for the movie data
            $response = wp_remote_get( $api_endpoint . $movie_id . '?api_key=' . $api_key );

            // Decode the response body into an array
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            // Insert the data into the WordPress database
            $post_id = wp_insert_post( array(
                'post_title' => $data['title'],
                'post_content' => $data['overview'],
                'post_type' => 'movie',
                'post_status' => 'publish',
            ));

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
}

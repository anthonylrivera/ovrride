<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$content = get_post_meta( $post_id, '_wc_trip_routes', true);
$content = ( $content ?: "");
$editorSettings = array('textarea_rows' => 12);
?>
<div id="trips_routes" class="woocommerce_options_panel panel wc-metaboxes-wrapper">
    <div class="options_group">
        <h1>Beach Bus Routes</h1>
        <p>Content for bus routes tab on product page</p>
        <?php wp_editor( $content , "_wc_trip_routes", $editorSettings); ?>
    </div>
</div>
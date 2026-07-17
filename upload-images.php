<?php
require_once 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function anyora_sideload_image( $file_path, $title ) {
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['path'] . '/' . basename($file_path);
    copy($file_path, $upload_path);
    
    $attachment = array(
        'post_mime_type' => wp_check_filetype( $upload_path, null )['type'],
        'post_title'     => sanitize_file_name( $title ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    $attach_id = wp_insert_attachment( $attachment, $upload_path );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    
    return $attach_id;
}

$images = array(
    'Compact Mobility Scooter (Foldable)' => 'C:\\Users\\HP\\.gemini\\antigravity-ide\\brain\\ca38b2cf-677e-4456-bcc1-0995f7ea4585\\anyora_prod_scooter_1784283193613.png',
    'Bamboo 3-Tier Shelving Unit' => 'C:\\Users\\HP\\.gemini\\antigravity-ide\\brain\\ca38b2cf-677e-4456-bcc1-0995f7ea4585\\anyora_prod_shelves_1784283201358.png',
    'Minimalist Desk Organizer' => 'C:\\Users\\HP\\.gemini\\antigravity-ide\\brain\\ca38b2cf-677e-4456-bcc1-0995f7ea4585\\anyora_prod_organizer_1784283210303.png',
    'Ceramic Bathroom Set' => 'C:\\Users\\HP\\.gemini\\antigravity-ide\\brain\\ca38b2cf-677e-4456-bcc1-0995f7ea4585\\anyora_prod_bathroom_1784283219156.png',
);

foreach ($images as $product_title => $path) {
    if (file_exists($path)) {
        $product = get_page_by_title( $product_title, OBJECT, 'product' );
        if ($product) {
            $attach_id = anyora_sideload_image($path, $product_title);
            set_post_thumbnail($product->ID, $attach_id);
            echo "Attached image to $product_title\n";
        } else {
            echo "Skipped $product_title (not found)\n";
        }
    } else {
        echo "File not found: $path\n";
    }
}

// Layout images
$hero_path = 'C:\\Users\\HP\\.gemini\\antigravity-ide\\brain\\ca38b2cf-677e-4456-bcc1-0995f7ea4585\\anyora_hero_banner_1784283229632.png';
if (file_exists($hero_path)) {
    $attach_id = anyora_sideload_image($hero_path, 'Anyora Hero Banner');
    update_option('anyora_hero_image_id', $attach_id);
    echo "Uploaded Hero Banner\n";
}

$feature_path = 'C:\\Users\\HP\\.gemini\\antigravity-ide\\brain\\ca38b2cf-677e-4456-bcc1-0995f7ea4585\\anyora_feature_lifestyle_1784283238592.png';
if (file_exists($feature_path)) {
    $attach_id = anyora_sideload_image($feature_path, 'Anyora Feature Lifestyle');
    update_option('anyora_feature_image_id', $attach_id);
    echo "Uploaded Feature Lifestyle\n";
}

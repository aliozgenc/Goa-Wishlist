<?php
/*
Plugin Name: WooCommerce Wishlist
Description: Simple wishlist functionality for WooCommerce without user registration.
Version: 1.0
Author: Ali Ozgenc
*/


// Admin Tab
function wishlist_custom_admin_tab()
{
    add_menu_page(
        'Wishlist', // Sayfa başlığı
        'Wishlist Custom', // Menü adı
        'manage_options', // Gereken yetki düzeyi
        'wishlist_custom_admin_page', // Sayfa slug
        'wishlist_custom_admin_page_content', // İçerik fonksiyonu
        'dashicons-admin-generic', // Menü ikonu (isteğe bağlı)
        31 // Menü sırası
    );
}

// Admin Page Content
function wishlist_custom_admin_page_content()
{
?>
    <div class="wrap">
        <h2>Wishlist Short Code</h2>
        <p>Shortcode: [custom_wishlist] </p>
    </div>
<?php
}

add_action('admin_menu', 'wishlist_custom_admin_tab');

// AJAX func for wishlist
function add_to_wishlist()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id > 0) {
        // Wishlist data
        $wishlist = isset($_COOKIE['wishlist']) ? json_decode($_COOKIE['wishlist'], true) : array();

        // Add product to wishlist
        if (!in_array($product_id, $wishlist)) {
            $wishlist[] = $product_id;
        }

        // Save wishlist data cookies
        setcookie('wishlist', json_encode($wishlist), time() + 3600, '/');

        // AJAX response
        echo json_encode(array('success' => true));
    } else {
        // If error
        echo json_encode(array('error' => 'Invalid product ID'));
    }

    wp_die();
}
add_action('wp_ajax_add_to_wishlist', 'add_to_wishlist');
add_action('wp_ajax_nopriv_add_to_wishlist', 'add_to_wishlist');


// Show wishlist page
function show_custom_wishlist()
{
    // Wishlist verilerini çek
    $wishlist = isset($_COOKIE['wishlist']) ? json_decode($_COOKIE['wishlist'], true) : array();

    // Wishlist Page content
    $output = '<h2>Custom Wishlist</h2>';
    $output .= '<ul>';
    foreach ($wishlist as $product_id) {
        $product = wc_get_product($product_id);
        $output .= '<li><a href="' . get_permalink($product_id) . '">' . $product->get_name() . '</a></li>';
    }
    $output .= '</ul>';

    return $output;
}

// Wishlist short code
add_shortcode('custom_wishlist', 'show_custom_wishlist');

// Add Wishlist button to the product card
function add_wishlist_button_to_product()
{
    global $product;

    // get Wishlist
    $wishlist = isset($_COOKIE['wishlist']) ? json_decode($_COOKIE['wishlist'], true) : array();

    // Add wishlist button 
    echo '<a href="#" class="add-to-wishlist" data-product-id="' . $product->get_id() . '">Add to Wishlist</a>';
}
add_action('woocommerce_after_shop_loop_item', 'add_wishlist_button_to_product', 15);

function add_wishlist_script()
{
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var wishlistButtons = document.querySelectorAll('.add-to-wishlist');

            wishlistButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    var productId = this.getAttribute('data-product-id');

                    // AJAX request
                    var xhr = new XMLHttpRequest();


                    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);

                    // Other AJAX settings
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

                    // AJAX data
                    var data = 'action=add_to_wishlist&product_id=' + encodeURIComponent(productId);

                    // Make request
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                console.log('Product added to wishlist!');
                            } else {
                                console.error('AJAX request failed:', xhr.statusText);
                            }
                        }
                    };
                    xhr.send(data);
                });
            });
        });
    </script>
<?php
}
add_action('wp_footer', 'add_wishlist_script');

<?php
/*
Plugin Name: WooCommerce Wishlist
Description: Simple wishlist functionality for WooCommerce without user registration.
Version: 2.0
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
function wishlist_custom_ajax_handler($data)
{
    $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;

    if ($product_id > 0) {
        // Wishlist data
        $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : array();

        // Check if product is already in wishlist
        $index = array_search($product_id, $wishlist);

        // If product is in wishlist, remove it; otherwise, add it
        if ($index !== false) {
            unset($wishlist[$index]);
        } else {
            $wishlist[] = $product_id;
        }

        // Save wishlist data to localStorage
        setcookie('wishlist', json_encode($wishlist), time() + 3600 * 24 * 30, '/'); // 30 days expiration

        // AJAX response
        return json_encode(array('success' => true));
    } else {
        // If error
        return json_encode(array('error' => 'Invalid product ID'));
    }
}

add_action('rest_api_init', 'wishlist_custom_rest_api_endpoint');

function wishlist_custom_rest_api_endpoint()
{
    register_rest_route('wishlist/v1', '/add', array(
        'methods' => 'POST',
        'callback' => 'wishlist_custom_ajax_handler',
        'permission_callback' => '__return_true',
    ));
}

// Show wishlist page
function show_custom_wishlist()
{
    // Wishlist verilerini çek
    $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : array();

    // Wishlist Page content
    $output = '<h2>Custom Wishlist</h2>';
    $output .= '<ul>';

    // Check if $wishlist is an array before using foreach
    if (is_array($wishlist)) {
        foreach ($wishlist as $product_id) {
            $product = wc_get_product($product_id);
            $output .= '<li><a href="' . get_permalink($product_id) . '">' . $product->get_name() . '</a></li>';
        }
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

    // Check if $product is a valid product object
    if (!is_a($product, 'WC_Product')) {
        return;
    }

    // get Wishlist
    $wishlist = isset($_COOKIE['wishlist']) ? json_decode(stripslashes($_COOKIE['wishlist']), true) : array();

    // Check if the product is in the wishlist
    $isProductInWishlist = in_array($product->get_id(), $wishlist ?? array());

    // Add wishlist button 
    echo '<a href="#" class="add-to-wishlist" data-product-id="' . $product->get_id() . '" data-nonce="' . wp_create_nonce('add_to_wishlist_nonce') . '"><span class="heart-icon ' . ($isProductInWishlist ? 'active' : '') . '"></span> Add to Wishlist</a>';
}

add_action('woocommerce_after_shop_loop_item', 'add_wishlist_button_to_product', 15);

// Add Wishlist script
function add_wishlist_script()
{
?>
    <style>
        /* Kalp ikonu */
        .heart-icon {
            width: 20px;
            height: 20px;
            background-image: url('https://countryclassic.co.uk/wp-content/uploads/2023/11/heart.png');
            background-size: cover;
            display: inline-block;
        }

        /* Kalp ikonu - Kırmızı */
        .heart-icon.active {
            background-image: url('https://countryclassic.co.uk/wp-content/uploads/2023/11/heart_red.png');
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var wishlistButtons = document.querySelectorAll('.add-to-wishlist');

            wishlistButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    var productId = this.getAttribute('data-product-id');
                    var heartIcon = this.querySelector('.heart-icon');
                    var nonce = this.getAttribute('data-nonce');

                    // Check if the product is in the wishlist
                    var isProductInWishlist = heartIcon.classList.contains('active');

                    // AJAX request
                    var xhr = new XMLHttpRequest();

                    xhr.open('POST', '<?php echo esc_url_raw(rest_url('wishlist/v1/add')); ?>', true);

                    // Other AJAX settings
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

                    // AJAX data
                    var data = 'product_id=' + encodeURIComponent(productId) + '&nonce=' + encodeURIComponent(nonce);

                    // Make request
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                // Change heart color when product is added to or removed from wishlist
                                heartIcon.classList.toggle('active');
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

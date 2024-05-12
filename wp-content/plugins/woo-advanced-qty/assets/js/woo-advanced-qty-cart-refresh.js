jQuery(document).ready(function ($) {
	var updateCart = null;

	$(document).on('change', '.woocommerce-cart-form .cart_item .qty', function () {
        var updateCartButton = $(document).find('button[name="update_cart"]');

        if (updateCart !== null) {
			clearTimeout(updateCart);
		}

		updateCart = setTimeout(function () {
			updateCartButton.trigger('click');
		}, 1200)
	})
});
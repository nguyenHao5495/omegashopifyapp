let ot_ba_discountPrice = 0;
let ot_ba_cart;
ot_ba_cartInit();

async function ot_ba_cartInit () {
    ot_ba_cart = await ot_ba_getCart();
    ot_ba_discountPrice = await ot_ba_createDiscountPrice();
    if (ot_ba_discountPrice > 0) {
        ot_ba_displayDiscountPrice();
        let newAttribute = {
            "Source" : ot_ba_settings.order_tag
        };
        ot_ba_updateCartAttribute(newAttribute);
    } else {
        let newAttribute = {
            "Source" : ''
        };
        ot_ba_updateCartAttribute(newAttribute);
    }
}

function ot_ba_displayDiscountPrice () {
    let originalTotalPrice = Shopify.formatMoney(ot_ba_cart.total_price);
    if (ot_ba_settings.total_price_class && ot_ba_settings.total_price_class != '') {
        $(`${ot_ba_settings.total_price_class}`).html(`
            <span class="ot-combo-cart__subtotal money" style="text-decoration: line-through">
                ${originalTotalPrice}
            </span>
        `);
    } else if ($(`:contains(${originalTotalPrice})`).length > 0 || !$(`:contains(${originalTotalPrice}):last`).is(':hidden')) {
        $(`:contains(${originalTotalPrice}):last`).html(`
            <span class="ot-combo-cart__subtotal money" style="text-decoration: line-through">
                ${originalTotalPrice}
            </span>
        `);
    } else {
        $(`.cart__subtotal`).html(`
            <span class="ot-combo-cart__subtotal money" style="text-decoration: line-through">
                ${originalTotalPrice}
            </span>
        `);
    }
    
    if ($('.ot-combo-cart__subtotal').length > 0) {
        let newTotalPrice = Shopify.formatMoney(ot_ba_cart.total_price - ot_ba_discountPrice);
        $('.ot-combo-cart__subtotal')
            .parent()
            .append(`
                <span class="ot-combo-cart__discounted-price money" style="display: block;">
                    ${newTotalPrice}
                </span>
            `);
    }
    
}

$(":submit[name='checkout']").on('click', function (e) {
    if (ot_ba_discountPrice > 0) {
        e.preventDefault();
        ot_ba_createDiscountCode(ot_ba_discountPrice)
            .then(discountCode => {
                if (discountCode && discountCode.code) {
                    window.location = `/checkout.json?discount=${discountCode.code}`;
                }
            });
        return false;
    }
});

// ----------- Api ------------
function ot_ba_createDiscountPrice () {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_ba_rootLink}/bundle_advance.php`,
            type: 'POST',
            data: {
                shop        : Shopify.shop,
                action      : 'createDiscountPrice',
                cart        : ot_ba_cart,
                customerId  : __st.cid
            },
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

function ot_ba_createDiscountCode(discountPrice) {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_ba_rootLink}/bundle_advance.php`,
            type: 'POST',
            data: {
                shop            : Shopify.shop,
                action          : 'createDiscountCode',
                discountPrice   : discountPrice/100,
            },
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

function ot_ba_getCart () {
    return new Promise(resolve => {
        $.ajax({
            url: `/cart.js`,
            type: 'GET',
            dataType: 'json'
        }).done(result => {
            let data = {};
            data.total_price = result.total_price;
            data.items = result.items.map(item => {
                let reducedItem = {
                    product_id : item.product_id,
                    original_price : item.original_price,
                    line_price : item.line_price,
                    quantity : item.quantity,
                    variant_id : item.variant_id
                }
                return reducedItem;
            });
            resolve(data);
        });
    });
}

function ot_ba_updateCartAttribute(attributes) {
    $.ajax({
        type: 'POST',
        url: '/cart/update.js',
        dataType: 'json',
        data: {
            attributes: attributes
        }
    });
}

// ---------End Api -----------
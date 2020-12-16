// Main class
let ot_combo_mainClass              = 'ot-combo';
let ot_combo_msgClass               = ot_combo_mainClass + '-msg';
let ot_combo_layoutInline           = ot_combo_mainClass + '-layout-inline';
let ot_combo_layoutSeparateLine     = ot_combo_mainClass + '-layout-separate-line';
let ot_combo_listProductsClass      = ot_combo_mainClass + '-list-products';
let ot_combo_product                = ot_combo_mainClass + '-product';
let ot_combo_product_img            = ot_combo_mainClass + '-product-img';
let ot_combo_product_title          = ot_combo_mainClass + '-product-title';
let ot_combo_product_price          = ot_combo_mainClass + '-product-price';
let ot_combo_product_variants       = ot_combo_mainClass + '-product-variants';
let ot_combo_product_select_variant = ot_combo_mainClass + '-product-current-variant';
let ot_combo_product_checkbox       = ot_combo_mainClass + '-product-checkbox';
let ot_combo_product_plus           = ot_combo_mainClass + '-product-plus';
let ot_combo_add_btn                = ot_combo_mainClass + '-add-btn';
let ot_combo_product_clearBoth      = ot_combo_mainClass + '-clear-both';

// Start function
let ot_combo_bundles = [];
ot_combo_products();

async function ot_combo_products () {
    ot_combo_createParentClass();
    ot_combo_applyCss();

    ot_combo_bundles = await ot_combo_getBundles();
    let i = 0;
    ot_combo_bundles.forEach(async (bundle, index) => {
        let bundleValid = await ot_combo_checkBundleValid(bundle);
        if (bundleValid) {
            i ++;
            if (i <= ot_combo_settings.max_bundles) {
                ot_combo_displayBundle(bundle, index);
            }
        }
    });
}

function ot_combo_applyCss () {
    $('body').append(`
        <style>
            .${ot_combo_mainClass} .${ot_combo_msgClass} {
                font-size: ${ot_combo_settings.title_text_size}px;
                color: ${ot_combo_settings.title_text_color};
                background-color: ${ot_combo_settings.title_background_color};
            }
            .${ot_combo_mainClass} .${ot_combo_add_btn} button {
                font-size: ${ot_combo_settings.button_text_size}px;
                color: ${ot_combo_settings.button_text_color};
                background-color: ${ot_combo_settings.button_background_color};
            }
            ${ot_combo_settings.custom_css}
        </style>
    `);
}

function ot_combo_createParentClass() {
    if ($(`.${ot_combo_mainClass}`).length == 0) {
        if (ot_combo_settings.custom_position && ot_combo_settings.custom_position != '') {
            $(`${ot_combo_settings.custom_position}`).append(`
                <div class="${ot_combo_mainClass}"></div>
            `);
        } else {
            $(`${ot_combo_settings.position}`).append(`
                <div class="${ot_combo_mainClass}"></div>
            `);
        }
    }
}

function ot_combo_checkBundleValid (bundle) {
    return new Promise(async resolve => {
        // Check date
        let checkDate = ot_combo_checkDate();
        function ot_combo_checkDate () {
            let checkDate   = true;
            let today       = new Date().getTime();
            let startDate   = new Date(bundle.start_date).getTime();
            let endDate     = new Date(bundle.end_date).getTime();
            if (bundle.enable_start_date == 1 && bundle.enable_end_date == 1) {
                checkDate = (today >= startDate && today <= endDate);
            } else if (bundle.enable_start_date == 1) {
                checkDate = (today >= startDate);
            } else if (bundle.enable_end_date == 1) {
                checkDate = (today <= endDate);
            }
            return checkDate;
        }
        
        // Check Customer
        let checkCustomer = await ot_combo_checkCustomer();
        function ot_combo_checkCustomer() {
            return new Promise(async resolve2 => {
                let checkCustomer = true;
                if (bundle.require_logged_in == 1) {
                    checkCustomer = __st.cid ? true : false;
                }
                if (bundle.enable_customer_tags == 1 && checkCustomer) {
                    let bundleTags = bundle.customer_tags.split(',').map(e => {
                        e = e.trim();
                        return e;
                    });
                    let customerTags = await ot_combo_getCustomerTags(__st.cid);
                    customerTags = customerTags.split(',').map(e => {
                        e = e.trim();
                        return e;
                    });
                    checkCustomer = customerTags.some(cTag => {
                        return bundleTags.some(bTag => bTag == cTag);
                    });
                }
                resolve2(checkCustomer);
            });
        }
        resolve(checkDate && checkCustomer);
    }) 
}

// Start display products in bundles
function ot_combo_displayBundle (bundle, index) {
    let ot_combo_currentClass = ot_combo_mainClass + '-no-' + index;
    $(`.${ot_combo_mainClass}`).append(`
        <div class="${ot_combo_currentClass}" ot-bundle-id="${bundle.id}"></div>
    `);
    ot_combo_displayMsg(ot_combo_currentClass, bundle);
    ot_combo_displayListProducts(ot_combo_currentClass, bundle);
    ot_combo_displayAddBtn(ot_combo_currentClass, bundle);
    ot_combo_displayDiscountPrice(bundle.id);
}

// ----- Display bundle msg -----
function ot_combo_displayMsg (comboClass, bundle) {
    $(`.${comboClass}`).append(`
        <p class="${ot_combo_msgClass}">${bundle.bundle_msg}</p>
    `);
}
// --- End Display bundle msg ---

// ----- Display product -----
// Includes: image, title, variants, price and checkbox
function ot_combo_displayListProducts (comboClass, bundle) {
    $(`.${comboClass}`).append(`
        <div class="${ot_combo_listProductsClass} ${bundle.bundle_layout == 'inline' ? ot_combo_layoutInline : ot_combo_layoutSeparateLine}"></div>
    `);
    bundle.products.forEach((product, index) => {
        $(`.${comboClass} .${ot_combo_listProductsClass}`).append(`
            <div class="${ot_combo_product}" ot-product-id="${product.id}"></div>
        `);

        // Image
        $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_combo_product_img}">
                <a target="_blank" href="${product.handle}">
                    <img src="${product.image.src}"/>
                </a>
            </div>
        `);

        // Title
        $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_combo_product_title}">
                <p title="${product.title}">${product.title}</p>
            </div>
        `);

        // Variants
        $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_combo_product_variants}">
                <select class="${ot_combo_product_select_variant}" onchange="ot_combo_updateVariantPrice(${bundle.id}, ${product.id})">
                </select>
            </div>
        `);
        product.variants.forEach(variant => {
            $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"] .${ot_combo_product_select_variant}`).append(`
                <option value="${variant.id}_${variant.price}">${variant.title}</option>
            `);
        });

        // Price
        $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_combo_product_price}">
                <p>${Shopify.formatMoney(product.variants[0].price*100)}</p>
            </div>
        `);

        // Checkbox
        $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_combo_product_checkbox}">
                <input type="checkbox" onchange="ot_combo_displayDiscountPrice(${bundle.id})" checked="checked"> 
            </div>
        `);
        
        // Checkbox
        $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_combo_product_clearBoth}"></div>
        `);

        // Plus icon
        if (index != 0) {
            $(`.${comboClass} .${ot_combo_listProductsClass} [ot-product-id="${product.id}"]`).append(`
                <div class="${ot_combo_product_plus}">
                    <img src="//cdn.shopify.com/s/files/1/0002/7728/2827/t/2/assets/ba-plus_38x.png?10643061042407540549" />
                </div>
            `);
        }
    });
}
// ----- Display product -----

function ot_combo_displayAddBtn (comboClass, bundle) {
    $(`.${comboClass}`).append(`
        <div class="${ot_combo_add_btn}">
            <button onclick="ot_combo_addBundle(${bundle.id})">
                <span class="top">${ot_combo_settings.button_text}</span>
                <span class="bottom"></span>
            </button>
        </div>
    `);
}

// ----- Discount price -----
// Calculate discount price then display it
// Must passing id of bundle for reusable reasons.
// Reason: when variant of product change, it maybe change product price => discount price change too
function ot_combo_displayDiscountPrice (bundleId) {
    let bundle = ot_combo_bundles.find(e => e.id == bundleId);
    let discountedPrice = ot_combo_calculateDiscountPrice(bundle);
    let discountText = ot_combo_settings.button_discount_text;
    discountText = discountText.replace('{{discount}}', Shopify.formatMoney(discountedPrice*100));
    $(`[ot-bundle-id=${bundleId}] .${ot_combo_add_btn} .bottom`).text(discountText);
}

function ot_combo_calculateDiscountPrice (bundle) {
    let totalPrice = 0;
    let numberOfProducts = 0;
    $(`[ot-bundle-id=${bundle.id}] .${ot_combo_product_select_variant}`).each(function () {
        if ($(this).parent().parent().children(`.${ot_combo_product_checkbox}`).children('input').is(':checked')) {
            let variantId_price = $(this).val();
            let price = variantId_price.split('_')[1];
            totalPrice += Number(price);
            numberOfProducts ++;
        }
    });
    let rule = bundle.rules.find(e => {
        return e.quantity == numberOfProducts;
    });
    let discountedPrice = 0;
    if (rule) {
        if (rule.discount_type == 'percent') {
            discountedPrice = totalPrice*rule.amount/100;
        } else {
            if (totalPrice > rule.amount) {
                discountedPrice = rule.amount;
            } else {
                discountedPrice = totalPrice;
            }
        }
    }
    return discountedPrice;
}
// ---- End Discount price ----

// ----- Change Price and Update DiscountPrice -----
// update price of product when customer choose another variant, then update total discount product
function ot_combo_updateVariantPrice (bundleId, productId) {
    let variantId_price = $(`[ot-bundle-id=${bundleId}] [ot-product-id=${productId}] .${ot_combo_product_select_variant}`).val();
    let price = variantId_price.split('_')[1];
    $(`[ot-bundle-id=${bundleId}] [ot-product-id=${productId}] .${ot_combo_product_price} p`).text(Shopify.formatMoney(price*100));
    ot_combo_displayDiscountPrice(bundleId);
}
// --- End Change Price and Update DiscountPrice ---

// ------ Add bundle to cart ------
function ot_combo_addBundle (bundleId) {
    let listSelectedVariants = $(`[ot-bundle-id=${bundleId}] .${ot_combo_product_select_variant}`);
    listSelectedVariants.each((index, select) => {
        if ($(select).parent().parent().children(`.${ot_combo_product_checkbox}`).children('input').is(':checked')) {
            let variantId_price = $(select).val();
            let variantId = variantId_price.split('_')[0];
            Shopify.pustToQueue(variantId, 1);
        }
        if (index == (listSelectedVariants.length - 1)) {
            Shopify.moveAlong();
        }
    });
}
// ---- End add bundle to cart ----

// --------- API -----------

function ot_combo_getBundles() {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_combo_rootLink}/combo_products.php`,
            type: 'GET',
            data: {
                shop        : ot_combo_shopName,
                action      : 'getBundles',
                productId   : ot_combo_productId
            },
            dataType: 'json'
        }).done(result => {
            resolve(result.sort(function(a, b) {
                return a.bundle_order - b.bundle_order;
            }));
        })
    })
}

function ot_combo_getCustomerTags(customerId) {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_combo_rootLink}/combo_products.php`,
            type: 'GET',
            data: {
                shop        : ot_combo_shopName,
                action      : 'getCustomerTags',
                customerId  : customerId
            },
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

function ot_combo_getCustomerTags(customerId) {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_combo_rootLink}/combo_products.php`,
            type: 'GET',
            data: {
                shop        : ot_combo_shopName,
                action      : 'getCustomerTags',
                customerId  : customerId
            },
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

let currentUrl = window.location.href.split('/');
if (currentUrl.indexOf('cart') > -1) {
    ot_combo_showDiscountPrice();
}

async function ot_combo_showDiscountPrice () {
    let cart = await ot_combo_getCart();
    let discountPrice = await ot_combo_createDiscountePrice(cart);

    let originalTotalPrice = Shopify.formatMoney(cart.total_price);
    $(`:contains(${originalTotalPrice}):last`).html(`
        <span class="ot-bundle-advance-cart__subtotal" style="text-decoration: line-through">
            ${originalTotalPrice}
        </span>
    `);

    let newTotalPrice = Shopify.formatMoney(cart.total_price - discountPrice);
    $('.ot-bundle-advance-cart__subtotal')
        .parent()
        .append(`
        <span class="ot-bundle-advance-cart__discounted-price" style="display: block;">
            ${newTotalPrice}
        </span>
    `);


}

function ot_combo_createDiscountePrice (cart) {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_combo_rootLink}/combo_products.php`,
            type: 'POST',
            data: {
                shop        : ot_combo_shopName,
                action      : 'createDiscountePrice',
                cart        : cart
            },
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

function ot_combo_fetchMatchBundles () {
    return new Promise(resolve => {
        $.ajax({
            url: `/cart.js`,
            type: 'GET',
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

function ot_combo_getCart () {
    return new Promise(resolve => {
        $.ajax({
            url: `/cart.js`,
            type: 'GET',
            dataType: 'json'
        }).done(result => {
            resolve(result);
        });
    });
}

// ------- End API ---------
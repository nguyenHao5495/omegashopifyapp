// Main class
let ot_ba_mainClass = 'ot-ba';
let ot_ba_loader = ot_ba_mainClass + '-loader';
let ot_ba_msgClass = ot_ba_mainClass + '-msg';
let ot_ba_layoutInline = ot_ba_mainClass + '-layout-inline';
let ot_ba_layoutSeparateLine = ot_ba_mainClass + '-layout-separate-line';
let ot_ba_listProductsClass = ot_ba_mainClass + '-list-products';
let ot_ba_product = ot_ba_mainClass + '-product';
let ot_ba_product_img = ot_ba_mainClass + '-product-img';
let ot_ba_product_title = ot_ba_mainClass + '-product-title';
let ot_ba_product_price = ot_ba_mainClass + '-product-price';
let ot_ba_product_variants = ot_ba_mainClass + '-product-variants';
let ot_ba_product_select_variant = ot_ba_mainClass + '-product-current-variant';
let ot_ba_product_checkbox = ot_ba_mainClass + '-product-checkbox';
let ot_ba_product_plus = ot_ba_mainClass + '-product-plus';
let ot_ba_add_btn = ot_ba_mainClass + '-add-btn';
let ot_ba_product_clearBoth = ot_ba_mainClass + '-clear-both';

// Start function
let ot_ba_bundles = [];
ot_ba_products();
console.log('vao product')
async function ot_ba_products() {
    ot_ba_createParentClass();
    ot_ba_applyCss();
    ot_ba_showLoadingSpinner();
    ot_ba_bundles = await ot_ba_getBundles();
    ot_ba_hideLoadingSpinner();
    let i = 0;
    ot_ba_bundles.forEach((bundle, index) => {
        i++;
        if (i <= ot_ba_settings.max_bundles) {
            ot_ba_displayBundle(bundle, index);
        }
    });
}

function ot_ba_showLoadingSpinner() {
    $(`.${ot_ba_mainClass}`).append(`
        <div class="${ot_ba_loader}" style="text-align: center;">
            <div></div>
        </div>
    `);
}

function ot_ba_hideLoadingSpinner() {
    $(`.${ot_ba_mainClass} .${ot_ba_loader}`).remove();
}

function ot_ba_applyCss() {
    $('body').append(`
        <style>
            .${ot_ba_mainClass} .${ot_ba_msgClass} {
                font-size: ${ot_ba_settings.title_text_size}px;
                color: ${ot_ba_settings.title_text_color};
                background-color: ${ot_ba_settings.title_background_color};
            }
            .${ot_ba_mainClass} .${ot_ba_add_btn} p {
                font-size: ${ot_ba_settings.button_text_size}px;
                color: ${ot_ba_settings.button_text_color};
                background-color: ${ot_ba_settings.button_background_color};
            }
            .${ot_ba_mainClass} .${ot_ba_loader} div {
                border-top: 2px solid ${ot_ba_settings.button_background_color};
            }
            ${ot_ba_settings.custom_css}
        </style>
    `);
}

function ot_ba_createParentClass() {
    if ($(`.${ot_ba_mainClass}`).length == 0) {
        if (ot_ba_settings.custom_position && ot_ba_settings.custom_position != '') {
            $(`${ot_ba_settings.custom_position}`).append(`
                <div class="${ot_ba_mainClass}"></div>
            `);
        } else {
            console.log(ot_ba_settings.position);
            $(`${ot_ba_settings.position}`).append(`
                <div class="${ot_ba_mainClass}"></div>
            `);
        }
    }
}

// Start display products in bundles
function ot_ba_displayBundle(bundle, index) {
    let ot_ba_currentClass = ot_ba_mainClass + '-no-' + index;
    $(`.${ot_ba_mainClass}`).append(`
        <div class="${ot_ba_currentClass}" ot-bundle-id="${bundle.id}"></div>
    `);
    ot_ba_displayMsg(ot_ba_currentClass, bundle);
    ot_ba_displayListProducts(ot_ba_currentClass, bundle);
    ot_ba_displayAddBtn(ot_ba_currentClass, bundle);
    ot_ba_displayDiscountPrice(bundle.id);
}

// ----- Display bundle msg -----
function ot_ba_displayMsg(comboClass, bundle) {
    $(`.${comboClass}`).append(`
        <p class="${ot_ba_msgClass}">${bundle.bundle_msg}</p>
    `);
}
// --- End Display bundle msg ---

// ----- Display product -----
// Includes: image, title, variants, price and checkbox
function ot_ba_displayListProducts(comboClass, bundle) {
    $(`.${comboClass}`).append(`
        <div class="${ot_ba_listProductsClass} ${bundle.bundle_layout == 'inline' ? ot_ba_layoutInline : ot_ba_layoutSeparateLine}"></div>
    `);
    bundle.products.forEach((product, index) => {
        $(`.${comboClass} .${ot_ba_listProductsClass}`).append(`
            <div class="${ot_ba_product}" ot-product-id="${product.id}"></div>
        `);

        // Image
        $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_ba_product_img}">
                <a target="_blank" href="${product.handle}">
                    <div style="background-image: url(${product.image.src ? product.image.src : ''})"></div>
                </a>
            </div>
        `);

        // Title
        $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_ba_product_title}">
                <p title="${product.title}">${product.title}</p>
            </div>
        `);

        // Variants
        $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_ba_product_variants}">
                <select class="${ot_ba_product_select_variant}" onchange="ot_ba_updateVariantPrice(${bundle.id}, ${product.id})">
                </select>
            </div>
        `);
        product.variants.forEach(variant => {
            $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"] .${ot_ba_product_select_variant}`).append(`
                <option value="${variant.id}_${variant.price}_${product['product_quantity']}">${variant.title}</option>
            `);
        }); 
		// ----- hide selectbox if there is only one variant
		if (product.variants.length <= 1) {
			$(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"] .${ot_ba_product_select_variant}`).css("display", "none");
		}

        // Price
        $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_ba_product_price}">
				<p><span class="money">${Shopify.formatMoney(product.variants[0].price*100)}</span></p>
            </div>
        `);

        // Checkbox
        $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_ba_product_checkbox}">
                <input type="checkbox" onchange="ot_ba_displayDiscountPrice(${bundle.id})" checked="checked"> 
            </div>
        `);

        // Checkbox
        $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
            <div class="${ot_ba_product_clearBoth}"></div>
        `);

        // Plus icon
        if (index != 0) {
            $(`.${comboClass} .${ot_ba_listProductsClass} [ot-product-id="${product.id}"]`).append(`
                <div class="${ot_ba_product_plus}">
                    <img src="//cdn.shopify.com/s/files/1/0002/7728/2827/t/2/assets/ba-plus_38x.png?10643061042407540549" />
                </div>
            `);
        }
    });
}
// ----- End Display product -----

// ----- Display add bundle button -----
function ot_ba_displayAddBtn(comboClass, bundle) {
    $(`.${comboClass}`).append(`
        <div class="${ot_ba_add_btn}">
            <p onclick="ot_ba_addBundle(${bundle.id})">
                <span class="top">${ot_ba_settings.button_text}</span>
                <span class="bottom"></span>
            </p>
        </div>
    `);
}
// ----- End Display add bundle button -----

// ----- Discount price -----
// Calculate discount price then display it
// Must passing id of bundle for reusable reasons.
// Reason: when variant of product change, it maybe change product price => discount price change too
function ot_ba_displayDiscountPrice(bundleId) {
    let bundle = ot_ba_bundles.find(e => e.id == bundleId);
    let discountedPrice = ot_ba_calculateDiscountPrice(bundle);
    let discountText = ot_ba_settings.button_discount_text; 
    if(ot_ba_settings.typeRule == 0){
        discountText = discountText.replace('{{discount}}','<span class="money">'+Shopify.formatMoney(discountedPrice*100)+'</span>');
    }else{
        discountText = discountText.replace('{{discount}}','<span>'+discountedPrice+'%</span>');
    }
	
    $(`[ot-bundle-id=${bundleId}] .${ot_ba_add_btn} .bottom`).html(discountText);
}
// ------------ Format money  ---------------

Shopify.formatMoney = function (cents, format) {
    if (typeof cents === 'string') {
        cents = cents.replace('.', '');
    }
    var value = '';
    var placeholderRegex = /\{\{\s*(\w+)\s*\}\}/;
    var formatString = (format || this.money_format);

    function defaultOption(opt, def) {
        return (typeof opt == 'undefined' ? def : opt);
    }

    function formatWithDelimiters(number, precision, thousands, decimal) {
        precision = defaultOption(precision, 2);
        thousands = defaultOption(thousands, ',');
        decimal = defaultOption(decimal, '.');

        if (isNaN(number) || number == null) {
            return 0;
        }

        number = (number / 100.0).toFixed(precision);

        var parts = number.split('.'),
            dollars = parts[0].replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1' + thousands),
            cents = parts[1] ? (decimal + parts[1]) : '';

        return dollars + cents;
    }
    switch (formatString.match(placeholderRegex)[1]) {
        case 'amount':
            value = formatWithDelimiters(cents, 2);
            break;
        case 'amount_no_decimals':
            value = formatWithDelimiters(cents, 0);
            break;
        case 'amount_with_comma_separator':
            value = formatWithDelimiters(cents, 2, '.', ',');
            break;
        case 'amount_no_decimals_with_comma_separator':
            value = formatWithDelimiters(cents, 0, '.', ',');
            break;
    }
    return formatString.replace(placeholderRegex, value);
}
// ------------ End Format money  ---------------

function ot_ba_calculateDiscountPrice(bundle) {
    let totalPrice = 0;
    let numberOfProducts = 0;
    $(`[ot-bundle-id=${bundle.id}] .${ot_ba_product_select_variant}`).each(function () {
        if ($(this).parent().parent().children(`.${ot_ba_product_checkbox}`).children('input').is(':checked')) {
            let variantId_price_quantity = $(this).val();
            let data = variantId_price_quantity.split('_');
            let price = data[1]; 
            let quantity = data[2];
            totalPrice += Number(price) * Number(quantity);
            numberOfProducts += Number(quantity);
        }
    });
    let rule = bundle.rules.find(e => {
        return e.quantity == numberOfProducts;
    });
    let discountedPrice = 0;  
    if (rule) { 
         
        switch (rule.discount_type) {
            case "percent_off": 
                discountedPrice = totalPrice * rule.amount / 100;  
                break;
            case "fixed_price_off":
                if (totalPrice > rule.amount) {
                    discountedPrice = rule.amount;
                } else {
                    discountedPrice = totalPrice;
                }
                break;
            case "fixed_last_price":
                if (totalPrice > rule.amount) {
                    discountedPrice = totalPrice - rule.amount;
                } else {
                    discountedPrice = totalPrice;
                }
                break;
            default:
                break;
        }
    } 
    if(ot_ba_settings.typeRule == 1){
        discountedPrice = (discountedPrice/totalPrice).toFixed(2)*100;
    } 
    return discountedPrice;
}
// ---- End Discount price ----

// ----- Change Price and Update DiscountPrice -----
// update price of product when customer choose another variant, then update total discount product
function ot_ba_updateVariantPrice(bundleId, productId) {
    let variantId_price_quantity = $(`[ot-bundle-id=${bundleId}] [ot-product-id=${productId}] .${ot_ba_product_select_variant}`).val();
    let price = variantId_price_quantity.split('_')[1];
    $(`[ot-bundle-id=${bundleId}] [ot-product-id=${productId}] .${ot_ba_product_price} p`).text(Shopify.formatMoney(price * 100));
    ot_ba_displayDiscountPrice(bundleId);
}
// --- End Change Price and Update DiscountPrice ---

// ------ Add bundle to cart ------
function ot_ba_addBundle(bundleId) {
    let listSelectedVariants = $(`[ot-bundle-id=${bundleId}] .${ot_ba_product_select_variant}`);

    listSelectedVariants.each((index, select) => {
        if ($(select).parent().parent().children(`.${ot_ba_product_checkbox}`).children('input').is(':checked')) {
            let variantId_price_quantity = $(select).val();
			console.log(variantId_price_quantity);
            let data = variantId_price_quantity.split("_");
            let variantId = data[0];
            let quantity = data[2];
            Shopify.ot_ba_pustToQueue(variantId, quantity);
        }
        if (index == (listSelectedVariants.length - 1)) {
            Shopify.ot_ba_moveAlong();
        }
    });
}
// ---- End add bundle to cart ----

// --------- API -----------

function ot_ba_getBundles() {
    return new Promise(resolve => {
        $.ajax({
            url: `${ot_ba_rootLink}/bundle_advance.php`,
            type: 'GET',
            data: {
                shop: Shopify.shop,
                action: 'getBundles',
                productId: ot_ba_productId,
                customerId: __st.cid
            },
            dataType: 'json'
        }).done(result => {
            resolve(result);
        })
    })
}


// ------- End API ---------
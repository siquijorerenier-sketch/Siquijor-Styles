/* =========================================================
   SIQUIJOR STYLES
   MAIN JAVASCRIPT
   COMPLETE STOREFRONT VERSION

   IMPORTANT:
   - Cart data is stored in the database.
   - Add to Cart uses php/cart_process.php.
   - cart.php owns its page-specific cart controls.
   - checkout.php reads the same database cart.
   - No profile icon or header UI is created by this file.
========================================================= */


/* =========================================================
   GLOBAL STATE
========================================================= */

let cart = [];
let currentQuickProduct = null;


/* =========================================================
   PAGE / RETURN URL HELPERS
========================================================= */

function getCurrentPage() {

    const path =
        window.location.pathname || "";

    const filename =
        path.substring(
            path.lastIndexOf("/") + 1
        );

    return filename || "index.php";
}


function saveReturnPage() {

    const page =
        getCurrentPage();

    if (
        page === "login.php" ||
        page === "signup.php"
    ) {

        return;

    }

    const url =
        window.location.pathname +
        window.location.search +
        window.location.hash;

    try {

        sessionStorage.setItem(
            "siquijorReturnPage",
            url
        );

    } catch (error) {

        console.warn(
            "Unable to save return page:",
            error
        );

    }

}


function getReturnPage() {

    try {

        const saved =
            sessionStorage.getItem(
                "siquijorReturnPage"
            );

        if (
            saved &&
            saved !== "login.php" &&
            saved !== "signup.php"
        ) {

            return saved;

        }

    } catch (error) {

        console.warn(
            "Unable to read return page:",
            error
        );

    }

    return "index.php";
}


function clearReturnPage() {

    try {

        sessionStorage.removeItem(
            "siquijorReturnPage"
        );

    } catch (error) {

        console.warn(
            "Unable to clear return page:",
            error
        );

    }

}


/* =========================================================
   FORMATTERS
========================================================= */

function formatPrice(value) {

    const number =
        Number(value) || 0;

    return number.toLocaleString(
        "en-PH",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );

}


function escapeHTML(value) {

    const div =
        document.createElement(
            "div"
        );

    div.textContent =
        value == null
            ? ""
            : String(value);

    return div.innerHTML;

}


/* =========================================================
   MODALS
========================================================= */

function openModal(id) {

    const modal =
        document.getElementById(id);

    if (!modal) {

        return;

    }

    modal.classList.add(
        "active"
    );

    modal.classList.add(
        "show"
    );

}


function closeModal(id) {

    const modal =
        document.getElementById(id);

    if (!modal) {

        return;

    }

    modal.classList.remove(
        "active"
    );

    modal.classList.remove(
        "show"
    );

}


function setupModalClosing() {

    document
        .querySelectorAll(
            ".modal"
        )
        .forEach(
            function (modal) {

                if (
                    modal.dataset
                        .modalCloseReady === "1"
                ) {

                    return;

                }

                modal.dataset
                    .modalCloseReady = "1";

                modal.addEventListener(
                    "click",
                    function (event) {

                        if (
                            event.target ===
                            modal
                        ) {

                            modal.classList.remove(
                                "active"
                            );

                            modal.classList.remove(
                                "show"
                            );

                        }

                    }
                );

            }
        );

}


/* =========================================================
   CLOSE MODALS WITH ESCAPE
========================================================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key !== "Escape"
        ) {

            return;

        }

        document
            .querySelectorAll(
                ".modal"
            )
            .forEach(
                function (modal) {

                    modal.classList.remove(
                        "active"
                    );

                    modal.classList.remove(
                        "show"
                    );

                }
            );

    }
);


/* =========================================================
   CART API
========================================================= */

async function cartRequest(
    action,
    extraData = {}
) {

    const formData =
        new FormData();

    formData.append(
        "action",
        action
    );

    Object.keys(
        extraData
    ).forEach(
        function (key) {

            formData.append(
                key,
                extraData[key]
            );

        }
    );

    const response =
        await fetch(
            "php/cart_process.php",
            {
                method:
                    "POST",

                body:
                    formData,

                credentials:
                    "same-origin",

                cache:
                    "no-store",

                headers:
                    {
                        "Accept":
                            "application/json"
                    }
            }
        );

    const text =
        await response.text();

    console.log(
        "Cart API response:",
        text
    );

    let data;

    try {

        data =
            JSON.parse(
                text
            );

    } catch (error) {

        console.error(
            "Invalid cart JSON:",
            text
        );

        throw new Error(
            "The cart server returned an unexpected response.\n\nPlease check php/cart_process.php."
        );

    }

    return data;

}


/* =========================================================
   EXTRACT CART ITEMS
========================================================= */

function extractCartItems(data) {

    if (
        !data ||
        typeof data !== "object"
    ) {

        return [];

    }

    if (
        data.data &&
        Array.isArray(
            data.data.cart
        )
    ) {

        return data.data.cart;

    }

    if (
        data.data &&
        Array.isArray(
            data.data.items
        )
    ) {

        return data.data.items;

    }

    if (
        Array.isArray(
            data.cart
        )
    ) {

        return data.cart;

    }

    if (
        Array.isArray(
            data.items
        )
    ) {

        return data.items;

    }

    return [];

}


/* =========================================================
   EXTRACT CART COUNT
========================================================= */

function extractCartCount(data) {

    if (
        !data ||
        typeof data !== "object"
    ) {

        return 0;

    }

    if (
        data.data &&
        data.data.total_items !==
            undefined
    ) {

        return Number(
            data.data.total_items
        ) || 0;

    }

    if (
        data.total_items !==
            undefined
    ) {

        return Number(
            data.total_items
        ) || 0;

    }

    const items =
        extractCartItems(
            data
        );

    return items.reduce(
        function (
            total,
            item
        ) {

            return (
                total +
                (
                    Number(
                        item.quantity
                    ) || 0
                )
            );

        },
        0
    );

}


/* =========================================================
   LOAD CART
========================================================= */

async function loadCart() {

    try {

        const data =
            await cartRequest(
                "get"
            );

        if (
            !data.success
        ) {

            cart = [];

            updateCartCount(
                0
            );

            updateShopStockDisplays();

            return data;

        }

        cart =
            extractCartItems(
                data
            ).map(
                function (item) {

                    const productId =
                        Number(
                            item.product_id ??
                            item.productId ??
                            item.id ??
                            0
                        );

                    const productName =
                        item.product_name ??
                        item.productName ??
                        item.name ??
                        "Product";

                    return {

                        id:
                            productId,

                        productId:
                            productId,

                        product_id:
                            productId,

                        name:
                            productName,

                        product_name:
                            productName,

                        price:
                            Number(
                                item.price
                            ) || 0,

                        quantity:
                            Number(
                                item.quantity
                            ) || 0,

                        stock:
                            item.stock === null ||
                            item.stock === undefined
                                ? null
                                : Number(
                                    item.stock
                                ),

                        image:
                            item.image ??
                            ""

                    };

                }
            );

        updateCartCount(
            extractCartCount(
                data
            )
        );

        updateShopStockDisplays();

        return data;

    } catch (error) {

        console.error(
            "Cart loading error:",
            error
        );

        cart = [];

        updateCartCount(
            0
        );

        updateShopStockDisplays();

        return {

            success:
                false,

            server_error:
                true,

            message:
                error.message

        };

    }

}


/* =========================================================
   CART COUNT

   This only updates the existing
   cart count element.

   It DOES NOT create icons.
========================================================= */

function updateCartCount(
    count = null
) {

    const element =
        document.getElementById(
            "cartCount"
        );

    if (!element) {

        return;

    }

    if (
        count === null
    ) {

        count =
            cart.reduce(
                function (
                    total,
                    item
                ) {

                    return (
                        total +
                        (
                            Number(
                                item.quantity
                            ) || 0
                        )
                    );

                },
                0
            );

    }

    element.textContent =
        String(
            count
        );

}


/* =========================================================
   ADD TO CART
========================================================= */

async function addToCart(
    productId,
    productName = "Product",
    price = 0,
    stock = null
) {

    productId =
        Number(
            productId
        );

    price =
        Number(
            price
        ) || 0;

    if (
        !productId ||
        productId <= 0
    ) {

        alert(
            "Invalid product."
        );

        return false;

    }


    /*
     * Read the current database cart.
     */
    await loadCart();


    const existingItem =
        cart.find(
            function (item) {

                return Number(
                    item.productId ??
                    item.product_id ??
                    item.id ??
                    0
                ) === productId;

            }
        );


    const currentQuantity =
        existingItem
            ? Number(
                existingItem.quantity
            ) || 0
            : 0;


    /*
     * Check the available stock shown
     * by the Shop page.
     */
    if (
        stock !== null &&
        stock !== undefined
    ) {

        const available =
            Number(
                stock
            );

        if (
            !Number.isNaN(
                available
            ) &&
            currentQuantity >=
                available
        ) {

            alert(
                "You have already added the maximum available quantity of this product."
            );

            return false;

        }

    }


    try {

        const data =
            await cartRequest(
                "add",
                {
                    product_id:
                        productId,

                    quantity:
                        1
                }
            );


        if (
            !data.success
        ) {

            const message =
                String(
                    data.message ||
                    "Unable to add the product to your cart."
                );


            const lower =
                message.toLowerCase();


            const loginRequired =
                lower.includes(
                    "log in"
                ) ||
                lower.includes(
                    "login required"
                ) ||
                lower.includes(
                    "not logged in"
                ) ||
                lower.includes(
                    "authentication required"
                );


            if (
                loginRequired
            ) {

                saveReturnPage();

                window.location.href =
                    "login.php";

                return false;

            }


            alert(
                message
            );

            return false;

        }


        /*
         * Reload from the database after
         * the successful insert/update.
         */
        await loadCart();


        updateShopStockDisplays();


        alert(
            data.message ||
            (
                String(
                    productName
                ) +
                " has been added to your cart."
            )
        );


        return true;


    } catch (error) {

        console.error(
            "Add to cart error:",
            error
        );


        alert(
            error.message ||
            "Unable to add the product to your cart."
        );


        return false;

    }

}


/* =========================================================
   SHOP STOCK DISPLAY
========================================================= */

function updateShopStockDisplays() {

    document
        .querySelectorAll(
            ".product-card"
        )
        .forEach(
            function (card) {

                const productId =
                    Number(
                        card.dataset
                            .productId ||
                        0
                    );

                if (
                    !productId
                ) {

                    return;

                }


                let originalStock =
                    Number(
                        card.dataset
                            .originalStock
                    );


                if (
                    Number.isNaN(
                        originalStock
                    ) ||
                    originalStock < 0
                ) {

                    originalStock =
                        Number(
                            card.dataset.stock
                        );

                }


                if (
                    Number.isNaN(
                        originalStock
                    ) ||
                    originalStock < 0
                ) {

                    return;

                }


                card.dataset
                    .originalStock =
                        String(
                            originalStock
                        );


                const cartItem =
                    cart.find(
                        function (item) {

                            return Number(
                                item.productId ??
                                item.product_id ??
                                item.id ??
                                0
                            ) === productId;

                        }
                    );


                const quantityInCart =
                    cartItem
                        ? Number(
                            cartItem.quantity
                        ) || 0
                        : 0;


                const remaining =
                    Math.max(
                        0,
                        originalStock -
                        quantityInCart
                    );


                card.dataset.stock =
                    String(
                        remaining
                    );


                const stockElement =
                    card.querySelector(
                        ".product-stock"
                    );


                const addButton =
                    card.querySelector(
                        ".add-cart"
                    );


                if (
                    stockElement
                ) {

                    if (
                        remaining <=
                            0
                    ) {

                        stockElement.textContent =
                            "Out of Stock";

                        stockElement.classList.add(
                            "out-of-stock"
                        );

                    } else {

                        stockElement.textContent =
                            "Stock: " +
                            remaining;

                        stockElement.classList.remove(
                            "out-of-stock"
                        );

                    }

                }


                if (
                    addButton
                ) {

                    if (
                        remaining <=
                            0
                    ) {

                        addButton.disabled =
                            true;

                        addButton.textContent =
                            "OUT OF STOCK";

                    } else {

                        addButton.disabled =
                            false;

                        addButton.textContent =
                            "🛒 Add to Cart";

                    }

                }

            }
        );

}


/* =========================================================
   OPEN CART MODAL
========================================================= */

async function openCart() {

    await loadCart();

    /*
     * Some pages may have their own
     * renderCart() function.
     */
    if (
        typeof renderCart ===
        "function"
    ) {

        renderCart();

    }

    openModal(
        "cartModal"
    );

}


/* =========================================================
   CHECKOUT
========================================================= */

async function checkout() {

    try {

        const data =
            await cartRequest(
                "get"
            );


        if (
            !data.success
        ) {

            saveReturnPage();

            alert(
                data.message ||
                "Please log in before checking out."
            );

            window.location.href =
                "login.php";

            return;

        }


        const items =
            extractCartItems(
                data
            );


        if (
            items.length === 0
        ) {

            alert(
                "Your cart is empty."
            );

            return;

        }


        window.location.href =
            "checkout.php";


    } catch (error) {

        console.error(
            "Checkout validation error:",
            error
        );

        alert(
            error.message ||
            "Unable to verify your cart."
        );

    }

}


/* =========================================================
   QUICK VIEW
========================================================= */

function quickView(
    productId,
    productName,
    price,
    stock = null
) {

    productId =
        Number(
            productId
        );

    price =
        Number(
            price
        ) || 0;


    const card =
        document.querySelector(
            `.product-card[data-product-id="${productId}"]`
        );


    if (
        card
    ) {

        const displayedStock =
            Number(
                card.dataset.stock
            );


        if (
            !Number.isNaN(
                displayedStock
            )
        ) {

            stock =
                displayedStock;

        }

    }


    currentQuickProduct = {

        productId:
            productId,

        name:
            productName,

        price:
            price,

        stock:
            stock

    };


    const nameElement =
        document.getElementById(
            "quickProductName"
        );


    const priceElement =
        document.getElementById(
            "quickProductPrice"
        );


    const button =
        document.getElementById(
            "quickAddButton"
        );


    const imageContainer =
        document.querySelector(
            "#quickViewModal .quick-image"
        );


    if (
        nameElement
    ) {

        nameElement.textContent =
            productName;

    }


    if (
        priceElement
    ) {

        priceElement.textContent =
            "₱" +
            formatPrice(
                price
            );

    }


    if (
        imageContainer
    ) {

        let source =
            null;


        if (
            card
        ) {

            source =
                card.querySelector(
                    ".product-image img, .shop-product-image"
                );

        }


        if (
            source &&
            source.getAttribute(
                "src"
            )
        ) {

            imageContainer.innerHTML = `
                <img
                    src="${escapeHTML(
                        source.getAttribute(
                            "src"
                        )
                    )}"
                    alt="${escapeHTML(
                        productName
                    )}"
                >
            `;

        } else {

            imageContainer.innerHTML = `
                <span>
                    PRODUCT IMAGE
                </span>
            `;

        }

    }


    if (
        button
    ) {

        button.disabled =
            false;


        button.textContent =
            "ADD TO CART";


        if (
            stock !== null &&
            stock !== undefined &&
            Number(stock) <= 0
        ) {

            button.disabled =
                true;

            button.textContent =
                "OUT OF STOCK";

        }


        button.onclick =
            async function () {

                if (
                    !currentQuickProduct ||
                    button.disabled
                ) {

                    return;

                }


                await addToCart(

                    currentQuickProduct
                        .productId,

                    currentQuickProduct
                        .name,

                    currentQuickProduct
                        .price,

                    currentQuickProduct
                        .stock

                );

            };

    }


    openModal(
        "quickViewModal"
    );

}


/* =========================================================
   FAVORITES
========================================================= */

function favoriteProduct(
    button
) {

    if (
        !button
    ) {

        return;

    }


    button.classList.toggle(
        "favorite-active"
    );


    button.textContent =
        button.classList.contains(
            "favorite-active"
        )
            ? "♥"
            : "♡";

}


/* =========================================================
   SEARCH
========================================================= */

function openSearch() {

    openModal(
        "searchModal"
    );


    const input =
        document.getElementById(
            "searchInput"
        );


    if (
        input
    ) {

        input.focus();

        searchProducts();

    }

}


function searchProducts() {

    const input =
        document.getElementById(
            "searchInput"
        );


    const results =
        document.getElementById(
            "searchResults"
        );


    if (
        !input ||
        !results
    ) {

        return;

    }


    const query =
        input.value
            .trim()
            .toLowerCase();


    const products =
        document.querySelectorAll(
            ".product-card"
        );


    results.innerHTML =
        "";


    if (
        !query
    ) {

        results.innerHTML = `
            <p>
                Start typing to search products.
            </p>
        `;

        return;

    }


    let found =
        0;


    products.forEach(
        function (product) {

            const name =
                String(
                    product.dataset.name ||
                    ""
                ).toLowerCase();


            const text =
                product.textContent
                    .toLowerCase();


            if (
                !name.includes(
                    query
                ) &&
                !text.includes(
                    query
                )
            ) {

                return;

            }


            found++;


            const productId =
                Number(
                    product.dataset
                        .productId
                );


            const productName =
                product.dataset.name ||
                "Product";


            const price =
                Number(
                    product.dataset
                        .price ||
                    0
                );


            const stock =
                Number(
                    product.dataset
                        .stock ||
                    0
                );


            const result =
                document.createElement(
                    "div"
                );


            result.className =
                "search-result";


            result.innerHTML = `
                <strong>
                    ${escapeHTML(
                        productName
                    )}
                </strong>

                <span>
                    ₱${formatPrice(
                        price
                    )}
                </span>

                <button
                    type="button">
                    View
                </button>
            `;


            const viewButton =
                result.querySelector(
                    "button"
                );


            if (
                viewButton
            ) {

                viewButton.addEventListener(
                    "click",
                    function () {

                        closeModal(
                            "searchModal"
                        );


                        quickView(
                            productId,
                            productName,
                            price,
                            stock
                        );

                    }
                );

            }


            results.appendChild(
                result
            );

        }
    );


    if (
        found === 0
    ) {

        results.innerHTML = `
            <p>
                No products found for
                "<strong>
                    ${escapeHTML(
                        query
                    )}
                </strong>".
            </p>
        `;

    }

}


/* =========================================================
   SHOP FILTER
========================================================= */

function filterProducts(
    category
) {

    const selected =
        String(
            category || ""
        ).toLowerCase();


    document
        .querySelectorAll(
            ".product-card"
        )
        .forEach(
            function (card) {

                const cardCategory =
                    String(
                        card.dataset
                            .category ||
                        ""
                    ).toLowerCase();


                card.style.display =
                    cardCategory ===
                    selected
                        ? ""
                        : "none";

            }
        );


    const shopSection =
        document.getElementById(
            "shop"
        ) ||
        document.querySelector(
            ".shop-page-section"
        );


    if (
        shopSection
    ) {

        shopSection.scrollIntoView(
            {
                behavior:
                    "smooth",

                block:
                    "start"
            }
        );

    }

}


function showAllProducts() {

    document
        .querySelectorAll(
            ".product-card"
        )
        .forEach(
            function (card) {

                card.style.display =
                    "";

            }
        );


    const shopSection =
        document.getElementById(
            "shop"
        ) ||
        document.querySelector(
            ".shop-page-section"
        );


    if (
        shopSection
    ) {

        shopSection.scrollIntoView(
            {
                behavior:
                    "smooth",

                block:
                    "start"
            }
        );

    }

}


/* =========================================================
   LOGIN SUPPORT
========================================================= */

function openLogin() {

    closeModal(
        "signupModal"
    );

    closeModal(
        "profileModal"
    );


    if (
        document.getElementById(
            "loginModal"
        )
    ) {

        openModal(
            "loginModal"
        );

        return;

    }


    saveReturnPage();


    window.location.href =
        "login.php";

}


function showLoginRequired() {

    saveReturnPage();


    if (
        document.getElementById(
            "loginModal"
        )
    ) {

        openModal(
            "loginModal"
        );

        return;

    }


    window.location.href =
        "login.php";

}


/* =========================================================
   SIGNUP SUPPORT
========================================================= */

function openSignup() {

    closeModal(
        "loginModal"
    );

    closeModal(
        "profileModal"
    );


    if (
        document.getElementById(
            "signupModal"
        )
    ) {

        openModal(
            "signupModal"
        );

        return;

    }


    saveReturnPage();


    window.location.href =
        "signup.php";

}


/* =========================================================
   LOGIN FORM
========================================================= */

async function login(
    event
) {

    event.preventDefault();


    const emailElement =
        document.getElementById(
            "loginEmail"
        );


    const passwordElement =
        document.getElementById(
            "loginPassword"
        );


    if (
        !emailElement ||
        !passwordElement
    ) {

        saveReturnPage();

        window.location.href =
            "login.php";

        return;

    }


    const email =
        emailElement.value.trim();


    const password =
        passwordElement.value;


    if (
        !email ||
        !password
    ) {

        alert(
            "Please enter your email and password."
        );

        return;

    }


    try {

        const formData =
            new FormData();


        formData.append(
            "email",
            email
        );


        formData.append(
            "password",
            password
        );


        const response =
            await fetch(
                "php/login_process.php",
                {
                    method:
                        "POST",

                    body:
                        formData,

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:
                        {
                            "Accept":
                                "application/json"
                        }
                }
            );


        const text =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    text
                );

        } catch (error) {

            console.error(
                "Login response:",
                text
            );

            alert(
                "The server returned an unexpected response. Please check PHP."
            );

            return;

        }


        if (
            !data.success
        ) {

            alert(
                data.message ||
                "Login failed."
            );

            return;

        }


        closeModal(
            "loginModal"
        );


        /*
         * ADMIN
         */
        if (
            data.user &&
            data.user.role ===
                "admin"
        ) {

            clearReturnPage();


            window.location.href =
                "admin/index.php";


            return;

        }


        /*
         * CUSTOMER
         */
        const returnPage =
            getReturnPage();


        clearReturnPage();


        window.location.href =
            returnPage;


    } catch (error) {

        console.error(
            "Login error:",
            error
        );


        alert(
            "Unable to connect to the server."
        );

    }

}


/* =========================================================
   SIGNUP FORM
========================================================= */

async function signup(
    event
) {

    event.preventDefault();


    const nameElement =
        document.getElementById(
            "signupName"
        );


    const emailElement =
        document.getElementById(
            "signupEmail"
        );


    const passwordElement =
        document.getElementById(
            "signupPassword"
        );


    const confirmElement =
        document.getElementById(
            "signupConfirm"
        );


    if (
        !nameElement ||
        !emailElement ||
        !passwordElement ||
        !confirmElement
    ) {

        saveReturnPage();

        window.location.href =
            "signup.php";

        return;

    }


    const fullName =
        nameElement.value.trim();


    const email =
        emailElement.value.trim();


    const password =
        passwordElement.value;


    const confirmPassword =
        confirmElement.value;


    if (
        !fullName ||
        !email ||
        !password ||
        !confirmPassword
    ) {

        alert(
            "Please complete all fields."
        );

        return;

    }


    if (
        password !==
        confirmPassword
    ) {

        alert(
            "Passwords do not match."
        );

        return;

    }


    if (
        password.length < 6
    ) {

        alert(
            "Password must be at least 6 characters."
        );

        return;

    }


    try {

        const formData =
            new FormData();


        formData.append(
            "full_name",
            fullName
        );


        formData.append(
            "email",
            email
        );


        formData.append(
            "password",
            password
        );


        const response =
            await fetch(
                "php/signup_process.php",
                {
                    method:
                        "POST",

                    body:
                        formData,

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:
                        {
                            "Accept":
                                "application/json"
                        }
                }
            );


        const text =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    text
                );

        } catch (error) {

            console.error(
                "Signup response:",
                text
            );

            alert(
                "The server returned an unexpected response."
            );

            return;

        }


        if (
            !data.success
        ) {

            alert(
                data.message ||
                "Unable to create account."
            );

            return;

        }


        alert(
            data.message ||
            "Account created successfully."
        );


        clearReturnPage();


        window.location.href =
            "login.php";


    } catch (error) {

        console.error(
            "Signup error:",
            error
        );


        alert(
            "Unable to connect to the server."
        );

    }

}


/* =========================================================
   PROFILE FALLBACK

   IMPORTANT:
   This function does NOT create a profile icon.
   It only supports an old page if it calls
   openProfile().
========================================================= */

function openProfile() {

    if (
        document.getElementById(
            "profileModal"
        )
    ) {

        openModal(
            "profileModal"
        );

        return;

    }


    saveReturnPage();


    window.location.href =
        "orders.php";

}


/* =========================================================
   NEWSLETTER
========================================================= */

function subscribe(
    event
) {

    event.preventDefault();


    const input =
        document.getElementById(
            "newsletterEmail"
        );


    if (
        !input
    ) {

        return;

    }


    const email =
        input.value.trim();


    if (
        !email
    ) {

        alert(
            "Please enter your email address."
        );

        return;

    }


    alert(
        "Thank you for subscribing to Siquijor Styles!"
    );


    input.value =
        "";

}


/* =========================================================
   CONTACT FORM
========================================================= */

function sendMessage(
    event
) {

    event.preventDefault();


    alert(
        "Thank you for contacting Siquijor Styles!"
    );

}


/* =========================================================
   SMOOTH ANCHOR SCROLL
========================================================= */

document.addEventListener(
    "click",
    function (event) {

        const link =
            event.target.closest(
                'a[href^="#"]'
            );


        if (
            !link
        ) {

            return;

        }


        const href =
            link.getAttribute(
                "href"
            );


        if (
            !href ||
            href === "#"
        ) {

            return;

        }


        let target =
            null;


        try {

            target =
                document.querySelector(
                    href
                );

        } catch (error) {

            return;

        }


        if (
            !target
        ) {

            return;

        }


        event.preventDefault();


        target.scrollIntoView(
            {
                behavior:
                    "smooth",

                block:
                    "start"
            }
        );

    }
);


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        setupModalClosing();


        /*
         * Only use the database cart on pages
         * that actually contain a cart counter
         * or products.
         */
        if (
            document.getElementById(
                "cartCount"
            ) ||
            document.querySelector(
                ".product-card"
            )
        ) {

            await loadCart();

        }


        updateShopStockDisplays();

    }
);
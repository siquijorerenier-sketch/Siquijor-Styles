/* =========================================================
   SIQUIJOR STYLES
   MAIN JAVASCRIPT
   DATABASE / PHP VERSION
========================================================= */


/* =========================================================
   GLOBAL
========================================================= */

let cart = [];
let currentQuickProduct = null;


/* =========================================================
   RETURN PAGE
========================================================= */

function getCurrentPage() {

    const path = window.location.pathname;

    const filename =
        path.substring(path.lastIndexOf("/") + 1);

    if (!filename) {
        return "index.php";
    }

    return filename;
}


function saveReturnPage() {

    const currentPage = getCurrentPage();

    if (
        currentPage === "login.php" ||
        currentPage === "signup.php"
    ) {
        return;
    }

    const currentURL =
        window.location.pathname +
        window.location.search +
        window.location.hash;

    sessionStorage.setItem(
        "siquijorReturnPage",
        currentURL
    );
}


function getReturnPage() {

    const savedPage =
        sessionStorage.getItem(
            "siquijorReturnPage"
        );

    if (
        savedPage &&
        savedPage !== "login.php" &&
        savedPage !== "signup.php"
    ) {
        return savedPage;
    }

    return "index.php";
}


function clearReturnPage() {

    sessionStorage.removeItem(
        "siquijorReturnPage"
    );
}


/* =========================================================
   PAGE LOAD
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        setupModalClosing();

        loadCart();

    }
);


/* =========================================================
   MODALS
========================================================= */

function openModal(id) {

    const modal =
        document.getElementById(id);

    if (!modal) {
        return;
    }

    modal.classList.add("active");
    modal.classList.add("show");
}


function closeModal(id) {

    const modal =
        document.getElementById(id);

    if (!modal) {
        return;
    }

    modal.classList.remove("active");
    modal.classList.remove("show");
}


function setupModalClosing() {

    document
        .querySelectorAll(".modal")
        .forEach(function (modal) {

            modal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === modal
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

        });

}


document.addEventListener(
    "keydown",
    function (event) {

        if (event.key !== "Escape") {
            return;
        }

        document
            .querySelectorAll(".modal")
            .forEach(function (modal) {

                modal.classList.remove(
                    "active"
                );

                modal.classList.remove(
                    "show"
                );

            });

    }
);


/* =========================================================
   PRICE FORMAT
========================================================= */

function formatPrice(value) {

    return Number(
        value || 0
    ).toLocaleString(
        "en-PH",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );

}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHTML(value) {

    const div =
        document.createElement(
            "div"
        );

    div.textContent =
        value ?? "";

    return div.innerHTML;

}


/* =========================================================
   LOGIN
========================================================= */

function openLogin() {

    closeModal("signupModal");
    closeModal("profileModal");

    const loginModal =
        document.getElementById(
            "loginModal"
        );

    if (loginModal) {

        openModal(
            "loginModal"
        );

        return;

    }

    saveReturnPage();

    window.location.href =
        "login.php";

}


async function login(event) {

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
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Login server response:",
                responseText
            );


            alert(
                "The server returned an unexpected response. Please check PHP."
            );


            return;

        }


        if (!data.success) {

            alert(
                data.message ||
                "Login failed."
            );


            const message =
                String(
                    data.message || ""
                ).toLowerCase();


            if (
                message.includes(
                    "no account was found"
                )
            ) {

                const goToSignup =
                    confirm(
                        "No account was found with this email.\n\nWould you like to sign up now?"
                    );


                if (goToSignup) {

                    saveReturnPage();

                    window.location.href =
                        "signup.php";

                }

            }


            return;

        }


        alert(
            data.message ||
            "Login successful."
        );


        closeModal(
            "loginModal"
        );


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
   SIGN UP
========================================================= */

function openSignup() {

    closeModal("loginModal");
    closeModal("profileModal");


    const signupModal =
        document.getElementById(
            "signupModal"
        );


    if (signupModal) {

        openModal(
            "signupModal"
        );

        return;

    }


    saveReturnPage();


    window.location.href =
        "signup.php";

}


async function signup(event) {

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


        formData.append(
            "confirm_password",
            confirmPassword
        );


        const response =
            await fetch(
                "php/signup_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Signup server response:",
                responseText
            );


            alert(
                "The server returned an unexpected response. Please check PHP."
            );


            return;

        }


        if (!data.success) {

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


        closeModal(
            "signupModal"
        );


        const returnPage =
            getReturnPage();


        clearReturnPage();


        window.location.href =
            returnPage;


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
   SWITCH LOGIN / SIGNUP
========================================================= */

function switchToSignup() {

    closeModal("loginModal");

    openSignup();

}


function switchToLogin() {

    closeModal("signupModal");

    openLogin();

}


/* =========================================================
   PROFILE
========================================================= */

function openProfile() {

    openModal(
        "profileModal"
    );

}


/* =========================================================
   LOGOUT
========================================================= */

async function logout() {

    const confirmed =
        confirm(
            "Are you sure you want to log out?"
        );


    if (!confirmed) {
        return;
    }


    try {

        await fetch(
            "logout.php",
            {
                method: "GET",
                credentials: "same-origin"
            }
        );

    } catch (error) {

        console.error(
            "Logout error:",
            error
        );

    }


    cart = [];

    clearReturnPage();


    window.location.href =
        "index.php";

}


/* =========================================================
   LOAD CART FROM DATABASE
========================================================= */

async function loadCart() {

    try {

        const formData =
            new FormData();


        formData.append(
            "action",
            "get"
        );


        const response =
            await fetch(
                "php/cart_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Cart server response:",
                responseText
            );


            cart = [];

            updateCartCount();

            updateShopStockDisplays();


            return {
                success: false,
                server_error: true
            };

        }


        let serverCart = [];


        if (
            data.data &&
            Array.isArray(
                data.data.cart
            )
        ) {

            serverCart =
                data.data.cart;

        }

        else if (
            data.data &&
            Array.isArray(
                data.data.items
            )
        ) {

            serverCart =
                data.data.items;

        }

        else if (
            Array.isArray(
                data.cart
            )
        ) {

            serverCart =
                data.cart;

        }

        else if (
            Array.isArray(
                data.items
            )
        ) {

            serverCart =
                data.items;

        }


        cart =
            serverCart.map(
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


                    const price =
                        Number(
                            item.price ?? 0
                        );


                    const quantity =
                        Number(
                            item.quantity ?? 0
                        );


                    const stock =
                        item.stock !== undefined &&
                        item.stock !== null
                            ? Number(item.stock)
                            : null;


                    const image =
                        item.image ??
                        "";


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
                            price,

                        quantity:
                            quantity,

                        stock:
                            stock,

                        image:
                            image

                    };

                }
            );


        updateCartCount();


        /*
         * IMPORTANT:
         * Every time the cart is loaded,
         * refresh the remaining stock shown
         * on the shop page.
         */

        updateShopStockDisplays();


        return data;


    } catch (error) {

        console.error(
            "Cart loading error:",
            error
        );


        cart = [];

        updateCartCount();

        updateShopStockDisplays();


        return {
            success: false,
            server_error: true
        };

    }

}


/* =========================================================
   OPEN CART
========================================================= */

async function openCart() {

    await loadCart();

    renderCart();

    openModal(
        "cartModal"
    );

}


/* =========================================================
   CART COUNT
========================================================= */

function updateCartCount() {

    const cartCount =
        document.getElementById(
            "cartCount"
        );


    if (!cartCount) {
        return;
    }


    let total = 0;


    cart.forEach(
        function (item) {

            total +=
                Number(
                    item.quantity
                ) || 0;

        }
    );


    cartCount.textContent =
        total;

}


/* =========================================================
   UPDATE SHOP STOCK DISPLAY
========================================================= */

function updateShopStockDisplays() {

    const productCards =
        document.querySelectorAll(
            ".product-card"
        );


    if (
        !productCards ||
        productCards.length === 0
    ) {

        return;

    }


    productCards.forEach(
        function (card) {

            const productId =
                Number(
                    card.dataset.productId ||
                    0
                );


            if (!productId) {
                return;
            }


            /*
             * Get the original database stock.
             *
             * data-original-stock is preferred
             * because the visible stock number
             * changes after every cart update.
             */

            let originalStock =
                Number(
                    card.dataset.originalStock
                );


            /*
             * If original stock has not been
             * stored yet, try data-stock.
             */

            if (
                !originalStock ||
                originalStock < 0
            ) {

                originalStock =
                    Number(
                        card.dataset.stock
                    );

            }


            /*
             * If the HTML does not have data-stock,
             * get the initial number from the
             * visible stock text.
             */

            if (
                !originalStock ||
                originalStock < 0
            ) {

                const stockElement =
                    card.querySelector(
                        ".product-stock"
                    );


                if (stockElement) {

                    const match =
                        stockElement.textContent.match(
                            /(\d+)/
                        );


                    if (match) {

                        originalStock =
                            Number(
                                match[1]
                            );

                    }

                }

            }


            if (
                isNaN(originalStock) ||
                originalStock < 0
            ) {

                return;

            }


            /*
             * Store the original stock permanently
             * for this page session.
             */

            card.dataset.originalStock =
                originalStock;


            /*
             * Find quantity of this product
             * already in the logged-in user's cart.
             */

            let cartQuantity = 0;


            cart.forEach(
                function (item) {

                    const itemProductId =
                        Number(
                            item.productId ??
                            item.product_id ??
                            item.id ??
                            0
                        );


                    if (
                        itemProductId ===
                        productId
                    ) {

                        cartQuantity +=
                            Number(
                                item.quantity
                            ) || 0;

                    }

                }
            );


            /*
             * Calculate remaining stock.
             */

            const remainingStock =
                Math.max(
                    0,
                    originalStock -
                    cartQuantity
                );


            /*
             * Store the remaining stock
             * on the card.
             */

            card.dataset.stock =
                remainingStock;


            /*
             * Update visible stock text.
             */

            const stockElement =
                card.querySelector(
                    ".product-stock"
                );


            if (stockElement) {

                if (
                    remainingStock <= 0
                ) {

                    stockElement.textContent =
                        "Out of Stock";

                    stockElement.classList.add(
                        "out-of-stock"
                    );

                }

                else {

                    stockElement.textContent =
                        "Stock: " +
                        remainingStock;

                    stockElement.classList.remove(
                        "out-of-stock"
                    );

                }

            }


            /*
             * Update Add to Cart button.
             */

            const addButton =
                card.querySelector(
                    ".add-cart"
                );


            if (addButton) {

                if (
                    remainingStock <= 0
                ) {

                    addButton.disabled =
                        true;

                    addButton.textContent =
                        "OUT OF STOCK";

                }

                else {

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
   CHECK LOGIN / GUEST
========================================================= */

function showLoginRequired() {

    saveReturnPage();


    alert(
        "Please log in first before adding products to your cart."
    );


    setTimeout(
        function () {

            window.location.href =
                "login.php";

        },
        100
    );

}


/* =========================================================
   ADD TO CART
========================================================= */

async function addToCart(
    productId,
    productName,
    price,
    stock = null
) {

    productId =
        Number(productId);


    price =
        Number(price) || 0;


    if (
        !productId ||
        productId <= 0
    ) {

        alert(
            "Invalid product."
        );

        return;

    }


    /*
     * Check the amount already in this
     * user's cart before sending another
     * request to the server.
     */

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


    if (
        stock !== null &&
        stock !== undefined
    ) {

        const availableStock =
            Number(stock);


        if (
            !isNaN(availableStock) &&
            currentQuantity >=
            availableStock
        ) {

            alert(
                "You have already added the maximum available quantity of this product."
            );

            return;

        }

    }


    try {

        const formData =
            new FormData();


        formData.append(
            "action",
            "add"
        );


        formData.append(
            "product_id",
            productId
        );


        formData.append(
            "quantity",
            "1"
        );


        const response =
            await fetch(
                "php/cart_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        console.log(
            "Cart server response:",
            responseText
        );


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Invalid cart JSON response:",
                responseText
            );


            alert(
                "The cart server returned an unexpected response.\n\n" +
                "Please check php/cart_process.php."
            );


            return;

        }


        if (!data.success) {

            const message =
                String(
                    data.message ||
                    ""
                );


            const lowerMessage =
                message.toLowerCase();


            const loginRequired =
                lowerMessage.includes(
                    "please log in"
                ) ||
                lowerMessage.includes(
                    "login required"
                ) ||
                lowerMessage.includes(
                    "authentication required"
                ) ||
                lowerMessage.includes(
                    "not logged in"
                );


            if (loginRequired) {

                showLoginRequired();

                return;

            }


            alert(
                message ||
                "Unable to add product to cart."
            );


            return;

        }


        await loadCart();


        alert(
            data.message ||
            (
                (productName || "Product") +
                " has been added to your cart."
            )
        );


    } catch (error) {

        console.error(
            "Add to cart error:",
            error
        );


        alert(
            "Unable to add the product to your cart.\n\n" +
            "Please check that Apache and MySQL are running."
        );

    }

}


/* =========================================================
   RENDER CART
========================================================= */

function renderCart() {

    const cartItems =
        document.getElementById(
            "cartItems"
        );


    const cartTotal =
        document.getElementById(
            "cartTotal"
        );


    if (
        !cartItems ||
        !cartTotal
    ) {

        return;

    }


    if (
        !cart ||
        cart.length === 0
    ) {

        cartItems.innerHTML = `

            <p class="empty-cart">
                Your cart is empty.
            </p>

        `;


        cartTotal.textContent =
            "₱0.00";


        updateCartCount();

        return;

    }


    let total = 0;


    cartItems.innerHTML =
        "";


    cart.forEach(
        function (item) {

            const productId =
                Number(
                    item.productId ??
                    item.product_id ??
                    item.id ??
                    0
                );


            const price =
                Number(
                    item.price
                ) || 0;


            const quantity =
                Number(
                    item.quantity
                ) || 0;


            const stock =
                item.stock !== null &&
                item.stock !== undefined
                    ? Number(item.stock)
                    : null;


            const subtotal =
                price * quantity;


            total +=
                subtotal;


            const itemElement =
                document.createElement(
                    "div"
                );


            itemElement.className =
                "cart-item";


            let stockMessage =
                "";


            if (
                !isNaN(stock)
            ) {

                if (
                    stock <= 0
                ) {

                    stockMessage = `
                        <small class="out-of-stock">
                            Out of Stock
                        </small>
                    `;

                }

                else {

                    const remaining =
                        stock -
                        quantity;


                    if (
                        remaining <= 0
                    ) {

                        stockMessage = `
                            <small>
                                Maximum available quantity reached.
                            </small>
                        `;

                    }

                    else {

                        stockMessage = `
                            <small>
                                ${remaining} remaining
                            </small>
                        `;

                    }

                }

            }


            itemElement.innerHTML = `

                <div class="cart-item-info">

                    ${
                        item.image
                            ? `
                                <img
                                    src="images/${escapeHTML(
                                        item.image
                                    )}"
                                    alt="${escapeHTML(
                                        item.name
                                    )}"
                                    class="cart-item-image"
                                    onerror="this.style.display='none';"
                                >
                              `
                            : ""
                    }

                    <div>

                        <strong>
                            ${escapeHTML(
                                item.name
                            )}
                        </strong>

                        <small>
                            ₱${formatPrice(
                                price
                            )} each
                        </small>

                        ${stockMessage}

                    </div>

                </div>


                <div class="cart-item-controls">

                    <button
                        type="button"
                        onclick="changeCartQuantity(
                            ${productId},
                            ${quantity - 1}
                        )">

                        −

                    </button>


                    <span>
                        ${quantity}
                    </span>


                    <button
                        type="button"
                        onclick="changeCartQuantity(
                            ${productId},
                            ${quantity + 1}
                        )">

                        +

                    </button>


                    <button
                        type="button"
                        class="remove-cart-item"
                        onclick="removeFromCart(
                            ${productId}
                        )">

                        ×

                    </button>

                </div>


                <strong class="cart-item-subtotal">

                    ₱${formatPrice(
                        subtotal
                    )}

                </strong>

            `;


            cartItems.appendChild(
                itemElement
            );

        }
    );


    cartTotal.textContent =
        "₱" +
        formatPrice(
            total
        );


    updateCartCount();

}


/* =========================================================
   CHANGE CART QUANTITY
========================================================= */

async function changeCartQuantity(
    productId,
    quantity
) {

    productId =
        Number(productId);


    quantity =
        Number(quantity);


    if (
        !productId ||
        productId <= 0
    ) {

        return;

    }


    if (
        quantity <= 0
    ) {

        await removeFromCart(
            productId
        );

        return;

    }


    const item =
        cart.find(
            function (cartItem) {

                return Number(
                    cartItem.productId ??
                    cartItem.product_id ??
                    cartItem.id ??
                    0
                ) === productId;

            }
        );


    if (
        item &&
        item.stock !== null &&
        item.stock !== undefined
    ) {

        const stock =
            Number(
                item.stock
            );


        if (
            !isNaN(stock) &&
            quantity > stock
        ) {

            alert(
                "Only " +
                stock +
                " unit(s) are available."
            );

            return;

        }

    }


    try {

        const formData =
            new FormData();


        formData.append(
            "action",
            "update"
        );


        formData.append(
            "product_id",
            productId
        );


        formData.append(
            "quantity",
            quantity
        );


        const response =
            await fetch(
                "php/cart_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Update cart response:",
                responseText
            );


            alert(
                "The server returned an unexpected response."
            );


            return;

        }


        if (!data.success) {

            alert(
                data.message ||
                "Unable to update cart."
            );

            return;

        }


        await loadCart();

        renderCart();


    } catch (error) {

        console.error(
            "Quantity update error:",
            error
        );


        alert(
            "Unable to connect to the server."
        );

    }

}


/* =========================================================
   REMOVE FROM CART
========================================================= */

async function removeFromCart(
    productId
) {

    productId =
        Number(productId);


    if (
        !productId ||
        productId <= 0
    ) {

        return;

    }


    try {

        const formData =
            new FormData();


        formData.append(
            "action",
            "remove"
        );


        formData.append(
            "product_id",
            productId
        );


        const response =
            await fetch(
                "php/cart_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Remove cart response:",
                responseText
            );


            alert(
                "The server returned an unexpected response."
            );


            return;

        }


        if (!data.success) {

            alert(
                data.message ||
                "Unable to remove item."
            );

            return;

        }


        await loadCart();

        renderCart();


    } catch (error) {

        console.error(
            "Remove cart error:",
            error
        );


        alert(
            "Unable to connect to the server."
        );

    }

}


/* =========================================================
   CLEAR CART
========================================================= */

async function clearCart() {

    if (
        !cart ||
        cart.length === 0
    ) {

        return;

    }


    const confirmed =
        confirm(
            "Remove all items from your cart?"
        );


    if (!confirmed) {
        return;
    }


    try {

        const formData =
            new FormData();


        formData.append(
            "action",
            "clear"
        );


        const response =
            await fetch(
                "php/cart_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Clear cart response:",
                responseText
            );


            alert(
                "The server returned an unexpected response."
            );


            return;

        }


        if (!data.success) {

            alert(
                data.message ||
                "Unable to clear cart."
            );

            return;

        }


        cart = [];


        renderCart();

        updateCartCount();

        updateShopStockDisplays();


    } catch (error) {

        console.error(
            "Clear cart error:",
            error
        );


        alert(
            "Unable to connect to the server."
        );

    }

}


/* =========================================================
   CHECKOUT
========================================================= */

async function checkout() {

    if (
        !cart ||
        cart.length === 0
    ) {

        alert(
            "Your cart is empty."
        );

        return;

    }


    try {

        const response =
            await fetch(
                "php/cart_process.php",
                {
                    method: "POST",
                    body: new URLSearchParams({
                        action: "get"
                    }),
                    credentials: "same-origin"
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                "Checkout response:",
                responseText
            );


            alert(
                "Unable to verify your cart."
            );


            return;

        }


        if (!data.success) {

            saveReturnPage();


            alert(
                data.message ||
                "Please log in before checking out."
            );


            window.location.href =
                "login.php";


            return;

        }


    } catch (error) {

        console.error(
            "Checkout validation error:",
            error
        );


        alert(
            "Unable to verify your cart."
        );


        return;

    }


    window.location.href =
        "checkout.php";

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
        Number(productId);


    price =
        Number(price);


    stock =
        stock !== null &&
        stock !== undefined
            ? Number(stock)
            : null;


    if (
        !productId ||
        productId <= 0
    ) {

        alert(
            "Invalid product."
        );

        return;

    }


    /*
     * Use the current remaining stock
     * when the product card has been
     * updated after adding to cart.
     */

    const productCard =
        document.querySelector(
            `.product-card[data-product-id="${productId}"]`
        );


    if (productCard) {

        const displayedStock =
            Number(
                productCard.dataset.stock
            );


        if (
            !isNaN(displayedStock) &&
            displayedStock >= 0
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


    const quickImage =
        document.querySelector(
            "#quickViewModal .quick-image"
        );


    if (nameElement) {

        nameElement.textContent =
            productName;

    }


    if (priceElement) {

        priceElement.textContent =
            "₱" +
            formatPrice(
                price
            );

    }


    if (quickImage) {

        if (productCard) {

            const sourceImage =
                productCard.querySelector(
                    ".product-image img"
                );


            if (
                sourceImage &&
                sourceImage.getAttribute(
                    "src"
                )
            ) {

                quickImage.innerHTML = `

                    <img
                        src="${escapeHTML(
                            sourceImage.getAttribute(
                                "src"
                            )
                        )}"
                        alt="${escapeHTML(
                            productName
                        )}"
                    >

                `;

            }

            else {

                quickImage.innerHTML = `

                    <span>
                        PRODUCT IMAGE
                    </span>

                `;

            }

        }

        else {

            quickImage.innerHTML = `

                <span>
                    PRODUCT IMAGE
                </span>

            `;

        }

    }


    if (button) {

        button.disabled =
            false;


        button.textContent =
            "ADD TO CART";


        if (
            stock !== null &&
            !isNaN(stock) &&
            stock <= 0
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

                    currentQuickProduct.productId,

                    currentQuickProduct.name,

                    currentQuickProduct.price,

                    currentQuickProduct.stock

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

function favoriteProduct(button) {

    if (!button) {
        return;
    }


    button.classList.toggle(
        "favorite-active"
    );


    if (
        button.classList.contains(
            "favorite-active"
        )
    ) {

        button.textContent =
            "♥";

    }

    else {

        button.textContent =
            "♡";

    }

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


    if (input) {

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


    const search =
        input.value
            .trim()
            .toLowerCase();


    const products =
        document.querySelectorAll(
            ".product-card"
        );


    results.innerHTML =
        "";


    if (search === "") {

        results.innerHTML = `

            <p>
                Start typing to search products.
            </p>

        `;


        return;

    }


    let found = 0;


    products.forEach(
        function (product) {

            const name =
                (
                    product.dataset.name ||
                    ""
                ).toLowerCase();


            const productText =
                product.textContent
                    .toLowerCase();


            if (
                name.includes(search) ||
                productText.includes(search)
            ) {

                found++;


                const productId =
                    Number(
                        product.dataset.productId
                    );


                const productName =
                    product.dataset.name;


                const price =
                    Number(
                        product.dataset.price
                    );


                const stock =
                    product.dataset.stock !== undefined
                        ? Number(
                            product.dataset.stock
                        )
                        : null;


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


                if (viewButton) {

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
                        search
                    )}
                </strong>".

            </p>

        `;

    }

}


/* =========================================================
   PRODUCT FILTER
========================================================= */

function filterProducts(category) {

    const products =
        document.querySelectorAll(
            ".product-card"
        );


    products.forEach(
        function (product) {

            const productCategory =
                product.dataset.category ||
                "";


            if (
                productCategory.toLowerCase() ===
                String(
                    category
                ).toLowerCase()
            ) {

                product.style.display =
                    "";

            }

            else {

                product.style.display =
                    "none";

            }

        }
    );


    const shop =
        document.getElementById(
            "shop"
        );


    const shopSection =
        document.querySelector(
            ".shop-page-section"
        );


    if (shop) {

        shop.scrollIntoView({
            behavior: "smooth"
        });

    }

    else if (shopSection) {

        shopSection.scrollIntoView({
            behavior: "smooth"
        });

    }

}


function showAllProducts() {

    const products =
        document.querySelectorAll(
            ".product-card"
        );


    products.forEach(
        function (product) {

            product.style.display =
                "";

        }
    );


    const shop =
        document.getElementById(
            "shop"
        );


    const shopSection =
        document.querySelector(
            ".shop-page-section"
        );


    if (shop) {

        shop.scrollIntoView({
            behavior: "smooth"
        });

    }

    else if (shopSection) {

        shopSection.scrollIntoView({
            behavior: "smooth"
        });

    }

}


/* =========================================================
   NEWSLETTER
========================================================= */

function subscribe(event) {

    event.preventDefault();


    const emailInput =
        document.getElementById(
            "newsletterEmail"
        );


    if (!emailInput) {
        return;
    }


    const email =
        emailInput.value.trim();


    if (!email) {

        alert(
            "Please enter your email address."
        );

        return;

    }


    alert(
        "Thank you for subscribing to Siquijor Styles!"
    );


    emailInput.value =
        "";

}


/* =========================================================
   CONTACT FORM
========================================================= */

function sendMessage(event) {

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


        if (!link) {
            return;
        }


        const targetId =
            link.getAttribute(
                "href"
            );


        if (
            !targetId ||
            targetId === "#"
        ) {

            return;

        }


        const target =
            document.querySelector(
                targetId
            );


        if (!target) {
            return;
        }


        event.preventDefault();


        target.scrollIntoView({
            behavior: "smooth"
        });

    }
);


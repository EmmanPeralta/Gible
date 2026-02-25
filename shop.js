const cart = {};
const cartItems = document.getElementById('cart-items');
const totalPriceElement = document.getElementById('total-price');
const currencyAmountElement = document.getElementById('currencyAmount');
const buyButton = document.getElementById('buy-button');
const settingsModal = document.getElementById('settings-modal');
let purchaseMessageTimeout = null; // timeout handle for success message

const MAX_ITEM_QUANTITY = 10; // 🔒 Maximum per item

function showPurchaseMessage(message, isError = false) {
    const msgEl = document.getElementById('purchase-message');
    if (!msgEl) {
        return;
    }

    msgEl.textContent = message;
    // Toggle optional styling hook
    if (isError) {
        msgEl.classList.add('purchase-message-error');
    } else {
        msgEl.classList.remove('purchase-message-error');
    }

    if (purchaseMessageTimeout) {
        clearTimeout(purchaseMessageTimeout);
    }

    purchaseMessageTimeout = setTimeout(() => {
        msgEl.textContent = '';
        msgEl.classList.remove('purchase-message-error');
    }, 3000);
}

// Add items to the cart when an item card is clicked
document.querySelectorAll('.item-card').forEach(card => {
    card.addEventListener('click', () => {
        const itemName = card.getAttribute('data-item');
        const itemPrice = parseInt(card.getAttribute('data-price'));

        if (!cart[itemName]) {
            cart[itemName] = { quantity: 1, price: itemPrice };
        } else if (cart[itemName].quantity < MAX_ITEM_QUANTITY) {
            cart[itemName].quantity++;
        }
        updateCart();
    });
});

// Update cart display and total price
function updateCart() {
    cartItems.innerHTML = '';
    let totalPrice = 0;

    for (const [itemName, itemData] of Object.entries(cart)) {
        const li = document.createElement('li');
        
        // 👀 Only show "+" if under max
        const plusButton = itemData.quantity < MAX_ITEM_QUANTITY
            ? `<button onclick="changeQuantity('${itemName}', 1)">+</button>`
            : '';

        // Always show "-" (unless we also want to hide at 10)
        const minusButton = `<button onclick="changeQuantity('${itemName}', -1)">-</button>`;

        li.innerHTML = `
            ${itemName} - ${itemData.quantity} x ${itemData.price} KP
            ${minusButton}
            ${plusButton}
        `;
        cartItems.appendChild(li);
        totalPrice += itemData.quantity * itemData.price;
    }

    totalPriceElement.textContent = totalPrice;

    // 🔊 Update TTS label for total KP
    const totalContainer = document.getElementById("total-price-container");
    if (totalContainer) {
        totalContainer.setAttribute("data-label", `Total: ${totalPrice} KP`);
    }
}

// Change quantity of an item in cart
function changeQuantity(itemName, change) {
    if (cart[itemName]) {
        cart[itemName].quantity += change;

        if (cart[itemName].quantity <= 0) {
            delete cart[itemName];
        } else if (cart[itemName].quantity > MAX_ITEM_QUANTITY) {
            cart[itemName].quantity = MAX_ITEM_QUANTITY; // safeguard
        }

        updateCart();
    }
}

// Open settings modal
function openModal() {
    if (settingsModal) {
        settingsModal.style.display = 'block';
    }
}

// Close settings modal with animation
function closeModal() {
    if (settingsModal) {
        settingsModal.style.animation = 'scaleOut 0.1s ease-out forwards';
        setTimeout(() => {
            settingsModal.style.display = 'none';
            settingsModal.style.animation = '';
        }, 100);
    }
}

// Handle purchase button click, send cart data to buy.php
buyButton.addEventListener('click', () => {
    if (Object.keys(cart).length === 0) {
        alert('Your cart is empty!');
        return;
    }

    const totalPrice = parseInt(totalPriceElement.textContent);

    fetch('buy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'totalPrice=' + encodeURIComponent(totalPrice) +
              '&cart=' + encodeURIComponent(JSON.stringify(cart))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message below the Buy button instead of alert
            showPurchaseMessage('Purchase successful!');
            
            // Update KP amount and its TTS label
            currencyAmountElement.textContent = data.kp;
            currencyAmountElement.setAttribute("data-label", `${data.kp} KP`);

            document.getElementById('owned-potion').textContent = 'You have: ' + data.potion;
            document.getElementById('owned-scoreup').textContent = 'You have: ' + data.score_up;
            document.getElementById('owned-guardup').textContent = 'You have: ' + data.guard_up;

            // Clear cart
            for (const item in cart) {
                delete cart[item];
            }
            updateCart();
        } else {
            showPurchaseMessage(data.error || 'Purchase failed.', true);
        }
    });
});
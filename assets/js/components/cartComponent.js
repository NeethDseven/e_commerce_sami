import {
    removeFromCart,
    updateCartQuantity,
    clearCart,
    validateOrder,
    getOrders,
    generateCartHTML,
    generateSummaryHTML, 
    updateCartCounter,
    showToast
} from '../services/cartService.js';

export function cartCrud() {
    return {
        async handleUpdateQuantity(button) {
            const id = button.dataset.id;
            const input = button.parentElement.querySelector(`input[data-id="${id}"]`);
            
            if (!input) {
                showToast('Erreur: élément non trouvé', 'danger');
                return;
            }

            const newQuantity = parseInt(input.value);
            const maxStock = parseInt(input.dataset.stock);

            if (isNaN(newQuantity) || newQuantity < 1) {
                showToast('Quantité invalide', 'danger');
                return;
            }

            if (newQuantity > maxStock) {
                showToast('Stock insuffisant', 'warning');
                input.value = maxStock;
                return;
            }

            try {
                const result = await updateCartQuantity(id, newQuantity);
                if (result.success) {
                    await renderCart();
                    showToast('Quantité mise à jour', 'success');
                } else {
                    showToast(result.message || 'Erreur de mise à jour', 'danger');
                }
            } catch (error) {
                console.error('Erreur mise à jour quantité:', error);
                showToast('Erreur lors de la mise à jour', 'danger');
            }
        },

        async handleClearCart() {
            if (confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
                try {
                    await clearCart();
                    await renderCart();
                    showToast('Le panier a été vidé', 'success');
                } catch (error) {
                    console.error('Erreur vidage panier:', error);
                    showToast('Erreur lors du vidage du panier', 'danger');
                }
            }
        },

        async handleValidateCart() {
            try {
                const cartData = await checkCartContent();
                if (!cartData?.items?.length) {
                    showToast('Le panier est vide', 'warning');
                    return;
                }

                showToast('Traitement de votre commande...', 'info');
                
                const paymentData = await showPaymentModal();
                if (!paymentData) {
                    throw new Error('Informations de paiement non fournies');
                }

                const result = await validateOrder({
                    paymentInfo: {
                        nom: paymentData.nom,
                        prenom: paymentData.prenom,
                        email: paymentData.email,
                        adresse: paymentData.adresse
                    }
                });
                
                if (result.success) {
                    showToast('Commande validée avec succès!', 'success');
                    await clearCart();
                    setTimeout(() => window.location.href = 'index.php', 1500);
                } else {
                    throw new Error(result.error || 'Erreur lors de la validation');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast(error.message, 'danger');
            }
        },

        handleQuantityButton(button) {
            const id = button.dataset.id;
            const container = button.closest('.quantity-controls');
            const input = container.querySelector(`input[data-id="${id}"]`);
            const currentValue = parseInt(input.value);
            const maxStock = parseInt(input.dataset.stock);
            const isIncrease = button.classList.contains('increase-quantity');

            if (isIncrease && currentValue < maxStock) {
                input.value = currentValue + 1;
            } else if (!isIncrease && currentValue > 1) {
                input.value = currentValue - 1;
            }

            this.updateButtonStates(container, input.value, maxStock);
        },

        updateButtonStates(container, value, maxStock) {
            container.querySelector('.decrease-quantity').disabled = (value <= 1);
            container.querySelector('.increase-quantity').disabled = (value >= maxStock);
        },

        handleQuantityInput(input) {
            const newValue = parseInt(input.value);
            const maxStock = parseInt(input.dataset.stock);

            if (newValue < 1) {
                input.value = 1;
                showToast('La quantité minimum est 1', 'warning');
            } else if (newValue > maxStock) {
                input.value = maxStock;
                showToast('Stock maximum atteint', 'warning');
            }

            const container = input.closest('.quantity-controls');
            if (container) {
                this.updateButtonStates(container, input.value, maxStock);
            }
        }
    };
}

export async function showCart() {
    await renderCart();
    const crud = cartCrud();

    const handleValidateClick = async (event) => {
        if (event.target.classList.contains('validate-cart')) {
            event.preventDefault();
            await crud.handleValidateCart();
        }
    };

    document.removeEventListener('click', handleValidateClick);
    document.addEventListener('click', handleValidateClick);

    const clearCartButton = document.getElementById('clear-cart');
    if (clearCartButton) {
        const newButton = clearCartButton.cloneNode(true);
        clearCartButton.parentNode.replaceChild(newButton, clearCartButton);
        newButton.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();
            await crud.handleClearCart();
        });
    }

    const handleClick = async (event) => {
        const target = event.target;

        if (target.id === 'clear-cart' || target.closest('#clear-cart')) {
            return;
        }

        const actions = {
            'remove-item': async (element) => {
                const id = element.dataset.id;
                try {
                    await removeFromCart(id);
                    await renderCart();
                    showToast('Article supprimé du panier', 'success');
                } catch (error) {
                    showToast('Erreur lors de la suppression: ' + error.message, 'danger');
                }
            },
            'increase-quantity': (element) => {
                const input = element.parentElement.querySelector('input[type="number"]');
                const currentValue = parseInt(input.value);
                const maxStock = parseInt(input.dataset.stock);
                if (currentValue < maxStock) {
                    input.value = currentValue + 1;
                }
            },
            'decrease-quantity': (element) => {
                const input = element.parentElement.querySelector('input[type="number"]');
                const currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            },
            'update-quantity': async (element) => {
                const id = element.dataset.id;
                const input = element.parentElement.querySelector(`input[data-id="${id}"]`);
                const newQuantity = parseInt(input.value);
                
                try {
                    await updateCartQuantity(id, newQuantity);
                    await renderCart();
                    showToast('Quantité mise à jour', 'success');
                } catch (error) {
                    showToast('Erreur lors de la mise à jour: ' + error.message, 'danger');
                }
            },
            'validate-cart': () => crud.handleValidateCart()
        };

        for (const [className, handler] of Object.entries(actions)) {
            if (target.classList.contains(className)) {
                event.preventDefault();
                event.stopPropagation();
                await handler(target);
                break;
            }
        }
    };

    document.removeEventListener('click', handleClick);
    document.addEventListener('click', handleClick);

    document.addEventListener('change', (event) => {
        if (event.target.matches('input[type="number"]')) {
            crud.handleQuantityInput(event.target);
        }
    });
}

async function renderCart() {
    const cartContainer = document.querySelector('#cart-container');
    const summaryContainer = document.querySelector('#cart-summary');
    
    if (!cartContainer) {
        console.error('Container du panier non trouvé');
        return;
    }

    try {
        const response = await fetch('index.php?controller=cartOrder&action=show', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        let data;
        try {
            data = await response.text();
            const cartData = JSON.parse(data);
            
            if (summaryContainer) {
                summaryContainer.innerHTML = generateSummaryHTML(cartData);
            }

            cartContainer.innerHTML = cartData.items?.length > 0 
                ? generateCartHTML(cartData.items)
                : '<p class="text-center">Votre panier est vide.</p>';

            updateCartCounter(cartData.items || []);
            return cartData;
        } catch (parseError) {
            console.error('Parse error:', parseError);
            throw new Error(`Erreur de parsing: ${data}`);
        }
    } catch (error) {
        console.error('Erreur détaillée du rendu du panier:', error);
        cartContainer.innerHTML = `<p class="text-danger">Erreur lors du chargement du panier: ${error.message}</p>`;
        if (summaryContainer) {
            summaryContainer.innerHTML = '<p class="text-danger">Erreur de chargement</p>';
        }
        throw error;
    }
}

async function showPaymentModal() {
    return new Promise((resolve) => {
        const modalHTML = `
            <div class="modal fade" id="paymentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Informations de paiement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="paymentForm">
                                <div class="mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" class="form-control" name="nom" required maxlength="50">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" class="form-control" name="prenom" required maxlength="50">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required maxlength="100">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Adresse</label>
                                    <textarea class="form-control" name="adresse" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Numéro de carte</label>
                                    <input type="text" class="form-control" name="carte" required pattern="[0-9]{16}">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date d'expiration</label>
                                        <input type="text" class="form-control" name="expiration" required pattern="[0-9]{2}/[0-9]{2}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">CVV</label>
                                        <input type="text" class="form-control" name="cvv" required pattern="[0-9]{3}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="confirmPayment">Confirmer</button>
                        </div>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        const form = document.getElementById('paymentForm');

        document.getElementById('confirmPayment').addEventListener('click', () => {
            if (form.checkValidity()) {
                const formData = new FormData(form);
                const paymentData = Object.fromEntries(formData.entries());
                modal.hide();
                document.getElementById('paymentModal').remove();
                resolve(paymentData);
            } else {
                form.reportValidity();
            }
        });

        modal.show();
    });
}

async function checkCartContent() {
    try {
        const response = await fetch('index.php?controller=cartOrder&action=show', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        });
        return await response.json();
    } catch (error) {
        console.error('Error checking cart:', error);
        throw new Error('Erreur lors de la vérification du panier');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    showCart();
});
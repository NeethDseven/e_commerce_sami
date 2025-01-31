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
            const newQuantity = parseInt(input.value);

            try {
                await updateCartQuantity(id, newQuantity);
                await renderCart();
                showToast('Quantité mise à jour', 'success');
            } catch (error) {
                showToast('Erreur lors de la mise à jour', 'danger');
            }
        },

        async handleClearCart() {
            const confirmed = await showConfirmModal('Êtes-vous sûr de vouloir vider votre panier ?');
            if (confirmed) {
                try {
                    await clearCart();
                    await renderCart();
                    showToast('Le panier a été vidé', 'success');
                } catch (error) {
                    showToast('Erreur lors du vidage du panier', 'danger');
                }
            }
        },

        async handleValidateCart() {
            try {
                const orderData = {
                    userId: document.querySelector('#cart-container').dataset.userId,
                };

                const result = await validateOrder(orderData);
                if (result.success) {
                    showToast('Commande validée avec succès!', 'success');
                    await displayOrderDetails(orderData.userId);
                } else {
                    showToast('Erreur lors de la validation de la commande', 'danger');
                }
            } catch (error) {
                console.error('Error validating order:', error);
                showToast('Erreur lors de la validation de la commande', 'danger');
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

// Mise à jour de la fonction showCart pour utiliser cartCrud
export async function showCart() {
    await renderCart();
    const crud = cartCrud();

    document.addEventListener('click', async (event) => {
        const target = event.target;

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
            'decrease-quantity, increase-quantity': (element) => {
                crud.handleQuantityButton(element);
            },
            'update-quantity': (element) => {
                crud.handleUpdateQuantity(element);
            },
            'clear-cart': () => {
                crud.handleClearCart();
            },
            'validate-cart': () => {
                crud.handleValidateCart();
            }
        };

        for (const [selector, handler] of Object.entries(actions)) {
            const element = target.closest(`.${selector}`);
            if (element) {
                await handler(element);
                break;
            }
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('input[type="number"]')) {
            crud.handleQuantityInput(event.target);
        }
    });
}

async function renderCart() {
    const cartContainer = document.querySelector('#cart-container');
    const summaryContainer = document.querySelector('#cart-summary');
    
    if (!cartContainer) return;

    try {
        const response = await fetch(`index.php?controller=cartOrder&action=show`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Erreur serveur');
        }

        const cartData = await response.json();
        
        if (summaryContainer) {
            summaryContainer.innerHTML = generateSummaryHTML(cartData);
        }

        if (cartData.items && Array.isArray(cartData.items)) {
            cartContainer.innerHTML = cartData.items.length > 0 
                ? generateCartHTML(cartData.items) 
                : '<p class="text-center">Votre panier est vide.</p>';
            // Suppression de la ligne avec attachEventListeners car nous utilisons la délégation d'événements
        } else {
            cartContainer.innerHTML = '<p class="text-center">Votre panier est vide.</p>';
        }

        updateCartCounter(cartData.items || []);
        return cartData;

    } catch (error) {
        console.error('Error rendering cart:', error);
        cartContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement du panier</p>';
        if (summaryContainer) {
            summaryContainer.innerHTML = '<p class="text-danger">Erreur de chargement</p>';
        }
        throw error;
    }
}

function showModal({ title, content, buttons }) {
    return new Promise((resolve) => {
        const modal = `
            <div class="modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">${content}</div>
                        <div class="modal-footer">${buttons}</div>
                    </div>
                </div>
            </div>
        `;

        const modalElement = document.createElement('div');
        modalElement.innerHTML = modal;
        document.body.appendChild(modalElement.firstElementChild);

        const modalInstance = new bootstrap.Modal(document.querySelector('.modal'));

        // Gestionnaire pour les boutons
        document.querySelector('.modal').addEventListener('click', (e) => {
            if (e.target.matches('[data-modal-action]')) {
                modalInstance.hide();
                resolve(e.target.dataset.modalAction === 'confirm');
            }
        });

        document.querySelector('.modal').addEventListener('hidden.bs.modal', function () {
            this.remove();
            resolve(false);
        });

        modalInstance.show();
    });
}

async function displayOrderDetails(userId) {
    try {
        const orders = await getOrders(userId);
        const lastOrder = orders[orders.length - 1];

        await showModal({
            title: 'Détails de la commande',
            content: `
                <p>Commande N° : ${lastOrder.id}</p>
                <p>Date : ${new Date(lastOrder.date).toLocaleString()}</p>
                <p>Total : ${lastOrder.total}€</p>
            `,
            buttons: `<button type="button" class="btn btn-secondary" data-modal-action="close">Fermer</button>`
        });

        await clearCart();
        await renderCart();
    } catch (error) {
        console.error('Error displaying order details:', error);
        showToast('Erreur lors de l\'affichage des détails de la commande', 'danger');
    }
}

function showConfirmModal(message) {
    return showModal({
        title: 'Confirmation',
        content: `<p>${message}</p>`,
        buttons: `
            <button type="button" class="btn btn-secondary" data-modal-action="cancel">Annuler</button>
            <button type="button" class="btn btn-primary" data-modal-action="confirm">Confirmer</button>
        `
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    showCart();
});
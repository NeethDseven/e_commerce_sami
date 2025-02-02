<<<<<<< HEAD
import { updateOrderStatus, getOrderDetails } from '../services/orderDetailsService.js';
=======
import { updateOrderStatus, getOrderDetails, refreshOrderData } from '../services/orderDetailsService.js';
>>>>>>> origin/develop

let orderId;
let statusBadge;
let statusButtons;
let printButton;
let orderDetailsContainer;

function initializeElements() {
    statusBadge = document.querySelector('.status-badge');
    statusButtons = document.querySelectorAll('.status-btn');
    printButton = document.getElementById('printButton');
    orderDetailsContainer = document.querySelector('#orderDetailsContent');
<<<<<<< HEAD
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', handleViewDetailsClick);
    });
=======
>>>>>>> origin/develop
}

function getOrderIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('order_id');
}

function attachEventListeners() {
    statusButtons.forEach(button => {
        button.addEventListener('click', handleStatusChange);
    });

    if (printButton) {
        printButton.addEventListener('click', () => window.print());
    }
}

async function loadOrderDetails() {
    try {
        const data = await getOrderDetails(orderId);
        updateUI(data);
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

async function handleStatusChange(event) {
    const button = event.currentTarget;
    const newStatus = button.dataset.status;

    try {
        const result = await updateOrderStatus(orderId, newStatus);
        if (result.success) {
<<<<<<< HEAD
            updateStatusDisplay(result.order.statut);
            await loadOrderDetails();
            showNotification('Statut mis à jour avec succès');
            
            const url = new URL(window.location.href);
            url.searchParams.set('status', newStatus);
            window.history.pushState({}, '', url);
            
            sessionStorage.setItem(`orderStatus_${orderId}`, newStatus);
        } else {
            throw new Error(result.message || 'Erreur lors de la mise à jour');
        }
    } catch (error) {
        console.error('Error:', error);
=======
            updateStatusDisplay(newStatus);
            showNotification(`Statut mis à jour: ${newStatus}`);
            const newData = await refreshOrderData(orderId);
            updateUI(newData);
        }
    } catch (error) {
>>>>>>> origin/develop
        showNotification(error.message, 'error');
    }
}

function updateUI(data) {
<<<<<<< HEAD
    if (!data || !data.order) {
        console.error('Invalid data received:', data);
        return;
    }

    updateStatusDisplay(data.order.statut);

    if (orderDetailsContainer) {
        const detailsHtml = generateOrderDetailsHtml(data);
        orderDetailsContainer.innerHTML = detailsHtml;
    }

    sessionStorage.setItem(`orderStatus_${orderId}`, data.order.statut);
}

function updateStatusDisplay(newStatus) {
    if (!newStatus) {
        console.error('Statut non défini');
        return;
    }

=======
    if (data.order) {
        updateStatusDisplay(data.order.statut);
        updateOrderDetails(data);
    }
}

function updateStatusDisplay(newStatus) {
>>>>>>> origin/develop
    if (statusBadge) {
        statusBadge.textContent = newStatus;
        
        statusButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === newStatus) {
                btn.classList.add('active');
                btn.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-danger');
                switch(newStatus) {
                    case 'en cours':
                        btn.classList.add('btn-outline-primary');
                        break;
                    case 'validée':
                        btn.classList.add('btn-outline-success');
                        break;
                    case 'annulée':
                        btn.classList.add('btn-outline-danger');
                        break;
                }
            }
        });
    }
}

<<<<<<< HEAD

function generateOrderDetailsHtml(data) {
    if (!data || !data.order) {
        return '<div class="alert alert-warning">Données de commande non disponibles</div>';
    }

    return `
        <div class="order-info">
            <p><strong>Client:</strong> ${data.order.nom || data.order.nom_utilisateur || 'Non renseigné'}</p>
            <p><strong>Date:</strong> ${data.order.date_commande || 'Non renseignée'}</p>
            <p><strong>Total:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(data.total || 0)}</p>
=======
function updateOrderDetails(data) {
    if (orderDetailsContainer) {
        const detailsHtml = generateOrderDetailsHtml(data);
        orderDetailsContainer.innerHTML = detailsHtml;
    }
}

function generateOrderDetailsHtml(data) {
    return `
        <div class="order-info">
            <p><strong>Client:</strong> ${data.order.nom_utilisateur}</p>
            <p><strong>Date:</strong> ${data.order.date_commande}</p>
            <p><strong>Total:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(data.total)}</p>
>>>>>>> origin/develop
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
<<<<<<< HEAD
                ${Array.isArray(data.details) ? data.details.map(item => `
=======
                ${data.details.map(item => `
>>>>>>> origin/develop
                    <tr>
                        <td>${item.nom}</td>
                        <td>${item.quantite}</td>
                        <td>${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(item.prix)}</td>
                        <td>${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(item.prix * item.quantite)}</td>
                    </tr>
<<<<<<< HEAD
                `).join('') : '<tr><td colspan="4">Aucun article trouvé</td></tr>'}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total de la commande:</strong></td>
                    <td><strong>${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(data.total || 0)}</strong></td>
                </tr>
            </tfoot>
=======
                `).join('')}
            </tbody>
>>>>>>> origin/develop
        </table>`;
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

<<<<<<< HEAD
function handleViewDetailsClick(event) {
    const orderId = event.currentTarget.dataset.orderId;
    window.location.href = `orderDetailsView.php?order_id=${orderId}`;
}

document.addEventListener('DOMContentLoaded', () => {
    orderId = getOrderIdFromUrl();
    if (!orderId) {
        console.error('No order ID found in URL');
        return;
    }
    
    initializeElements();
    attachEventListeners();
    initializeFromStorage();
    loadOrderDetails();

    const statusSelect = document.querySelector('.status-select');
    if (statusSelect && orderId) {
        statusSelect.addEventListener('change', (e) => {
            updateOrderStatus(orderId, e.target.value);
        });
    }
});

function initializeFromStorage() {
    const storedStatus = sessionStorage.getItem(`orderStatus_${orderId}`);
    if (storedStatus) {
        updateStatusDisplay(storedStatus);
    }
}
=======
// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    orderId = getOrderIdFromUrl();
    initializeElements();
    attachEventListeners();
    loadOrderDetails();
});
>>>>>>> origin/develop

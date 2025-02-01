import { getOrderList } from '../services/orderListService.js';

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

async function loadOrders() {
    const orderListContainer = document.querySelector('#orderList');
    if (!orderListContainer) return;

    try {
        const response = await getOrderList();
        if (response.success) {
            orderListContainer.innerHTML = generateOrderListHtml(response.orders);
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

function generateOrderListHtml(orders) {
    return `
        <table class="table">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${orders.map(order => `
                    <tr>
                        <td>${order.id_commande}</td>
                        <td>${order.nom_utilisateur}</td>
                        <td>${order.date_commande}</td>
                        <td><span class="badge bg-${getStatusBadgeColor(order.statut)}">${order.statut}</span></td>
                        <td>
                            <a href="index.php?page=orderDetailsView&order_id=${order.id_commande}" 
                               class="btn btn-sm btn-primary">
                                Détails
                            </a>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>`;
}

function getStatusBadgeColor(status) {
    switch(status) {
        case 'en cours': return 'primary';
        case 'validée': return 'success';
        case 'annulée': return 'danger';
        default: return 'secondary';
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', loadOrders);

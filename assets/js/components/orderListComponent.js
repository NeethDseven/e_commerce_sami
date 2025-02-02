<<<<<<< HEAD
import { getOrderList, updateOrderStatus } from '../services/orderListService.js';

const orderListContainer = document.querySelector('#orderList');

async function initialize() {
    console.log('Initialisation...');
    await loadOrders(1);
}

async function loadOrders(page = 1) {
    const table = document.querySelector('#ordersTable tbody');
    const spinner = document.querySelector('.loading-spinner');
    
    if (!table) {
        console.error('Table des commandes non trouvée');
        return;
    }
    
    try {
        if (spinner) spinner.style.display = 'block';
        const response = await getOrderList(page);
        
        if (response.success) {
            table.innerHTML = generateOrderRows(response.orders);
            if (response.pagination) {
                generatePagination(response.pagination);
            }
            attachEventListeners();
        }
    } catch (error) {
        console.error('Erreur lors du chargement:', error);
        if (table) {
            table.innerHTML = `<tr><td colspan="6" class="text-center text-danger">
                Erreur lors du chargement des commandes: ${error.message}
            </td></tr>`;
        }
    } finally {
        if (spinner) spinner.style.display = 'none';
    }
}

function attachEventListeners() {
    orderListContainer.addEventListener('change', async (e) => {
        if (e.target.classList.contains('status-select')) {
            const orderId = e.target.dataset.orderId;
            const newStatus = e.target.value;
            try {
                await updateOrderStatus(orderId, newStatus);
                showNotification('Statut mis à jour avec succès');
            } catch (error) {
                showNotification(error.message, 'danger');
                await loadOrders();
            }
        }
    });

    const detailButtons = document.querySelectorAll('button.view-details-btn');
    console.log('Nombre de boutons trouvés:', detailButtons.length);

    detailButtons.forEach(button => {
        button.replaceWith(button.cloneNode(true));
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.orderId;
            console.log('Clic détecté sur le bouton:', {
                orderId: orderId,
                element: this,
                classes: this.classList.toString()
            });
            
            if (orderId) {
                window.location.href = `orderDetailsView.php?order_id=${orderId}`;
            } else {
                console.error('Pas d\'ID de commande trouvé sur le bouton');
            }
        });
    });

    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const orderId = this.dataset.orderId;
            
            try {
                const response = await fetch(`index.php?page=orderdetails&order_id=${orderId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = `index.php?page=orderdetails&order_id=${orderId}`;
                    } else {
                        console.error('Erreur:', result.error);
                        showNotification(result.error, 'danger');
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la récupération des détails', 'danger');
            }
        });
    });
}

function generateOrderRows(orders) {
    if (!orders || orders.length === 0) {
        return `<tr><td colspan="6" class="text-center">Aucune commande trouvée</td></tr>`;
    }
    
    return orders.map(order => `
        <tr>
            <td>${order.id}</td>
            <td>${order.date_commande}</td>
            <td>${order.nom_client || 'Client inconnu'}</td>
            <td>
                <select class="form-select form-select-sm status-select" data-order-id="${order.id}">
                    ${generateStatusOptions(order.status)}
                </select>
            </td>
            <td>${Number(order.total || 0).toFixed(2)} €</td>
            <td>
                <a href="index.php?page=orderdetails&order_id=${order.id}" 
                   class="btn btn-sm btn-primary">
                    Voir détails
                </a>
            </td>
        </tr>
    `).join('');
}

function generateStatusOptions(currentStatus) {
    const statuses = ['En attente', 'En cours', 'Expédiée', 'Livrée'];
    return statuses.map(status => 
        `<option value="${status}" ${status === currentStatus ? 'selected' : ''}>${status}</option>`
    ).join('');
}

function showNotification(message, type = 'success') {
    const toast = document.querySelector('#cartToast');
    const toastBody = toast.querySelector('.toast-body');
    
    toastBody.textContent = message;
    toast.classList.add(`bg-${type}`);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    setTimeout(() => {
        toast.classList.remove(`bg-${type}`);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM chargé, démarrage de l\'initialisation');
    initialize();
});

function generatePagination(pagination) {
    const paginationContainer = document.querySelector('#pagination');
    if (!paginationContainer) return;

    const { currentPage, pages } = pagination;
    if (!pages || pages < 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    const html = `
        <nav aria-label="Navigation des pages">
            <ul class="pagination justify-content-center">
                <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
                </li>
                ${Array.from({length: pages}, (_, i) => i + 1)
                    .map(i => `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `).join('')}
                <li class="page-item ${currentPage >= pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
                </li>
            </ul>
        </nav>
    `;
    
    paginationContainer.innerHTML = html;

    const newPaginationContainer = paginationContainer.cloneNode(true);
    paginationContainer.parentNode.replaceChild(newPaginationContainer, paginationContainer);

    newPaginationContainer.addEventListener('click', async (e) => {
        e.preventDefault();
        const pageLink = e.target.closest('.page-link');
        if (!pageLink) return;

        const page = parseInt(pageLink.dataset.page);
        if (!isNaN(page) && page !== currentPage) {
            await loadOrders(page);
        }
    });
}
=======
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
>>>>>>> origin/develop

import { updateOrderStatus, getOrderDetails, refreshOrderData } from '../services/orderDetailsService.js';

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
            updateStatusDisplay(newStatus);
            showNotification(`Statut mis à jour: ${newStatus}`);
            const newData = await refreshOrderData(orderId);
            updateUI(newData);
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

function updateUI(data) {
    if (data.order) {
        updateStatusDisplay(data.order.statut);
        updateOrderDetails(data);
    }
}

function updateStatusDisplay(newStatus) {
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
                ${data.details.map(item => `
                    <tr>
                        <td>${item.nom}</td>
                        <td>${item.quantite}</td>
                        <td>${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(item.prix)}</td>
                        <td>${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(item.prix * item.quantite)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>`;
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    orderId = getOrderIdFromUrl();
    initializeElements();
    attachEventListeners();
    loadOrderDetails();
});

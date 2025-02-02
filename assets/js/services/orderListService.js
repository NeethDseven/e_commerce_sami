<<<<<<< HEAD
const BASE_URL = '/projet/e_commerce_sami-develop';

export async function getOrderList(page = 1) {
    try {
        const response = await fetch(`${BASE_URL}/index.php?controller=order&action=getList&page=${page}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        const cleanJson = text.substring(text.indexOf('{'));
        
        try {
            return JSON.parse(cleanJson);
        } catch (e) {
            console.error('Invalid JSON received:', text);
            throw new Error('Invalid JSON response from server');
        }
    } catch (error) {
        console.error('Error fetching orders:', error);
        throw error;
    }
}

export async function updateOrderStatus(orderId, status) {
    try {
        const response = await fetch(`${BASE_URL}/index.php?controller=order&action=updateStatus`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ orderId, status })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error updating order status:', error);
        throw new Error('Failed to update order status');
    }
}
=======
const baseUrl = '/Projet/ecommercesami/index.php';

export async function getOrderList() {
    try {
        const response = await fetch(`${baseUrl}?controller=order&action=getList`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        return await response.json();
    } catch (error) {
        console.error('Erreur lors de la récupération des commandes:', error);
        throw new Error('Impossible de charger la liste des commandes');
    }
}
>>>>>>> origin/develop

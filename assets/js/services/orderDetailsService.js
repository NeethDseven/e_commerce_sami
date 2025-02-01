const baseUrl = '/Projet/ecommercesami/index.php';

export async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch(`${baseUrl}?controller=order&action=updateStatus`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ orderId, status: newStatus })
        });
        const data = await response.json();
        if (!data.success) {
            console.error('Erreur de mise à jour du statut:', data.message);
        }
        return data;
    } catch (error) {
        console.error('Erreur technique lors de la mise à jour:', error);
        throw error;
    }
}

export async function getOrderDetails(orderId) {
    try {
        const response = await fetch(`${baseUrl}?controller=order&action=getDetails&order_id=${orderId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        return await response.json();
    } catch (error) {
        console.error('Erreur récupération détails:', error);
        throw new Error('Erreur lors de la récupération des détails');
    }
}

export async function refreshOrderData(orderId) {
    try {
        const response = await fetch(`${baseUrl}?controller=order&action=getDetails&order_id=${orderId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        if (!text) {
            throw new Error('Réponse vide du serveur');
        }
        
        try {
            const data = JSON.parse(text);
            if (!data.success) {
                throw new Error(data.message || 'Erreur serveur');
            }
            return data;
        } catch (e) {
            console.error('Réponse brute:', text);
            throw new Error('Réponse invalide du serveur');
        }
    } catch (error) {
        console.error('Erreur rafraîchissement données:', error);
        throw error;
    }
}

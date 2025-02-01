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

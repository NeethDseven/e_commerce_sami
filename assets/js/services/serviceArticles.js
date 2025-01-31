// Fonctions de récupération des articles
export async function fetchArticles(categoryId = null, page = 1, search = '') {
    try {
        const url = new URL(`${window.location.origin}/Projet/ecommercesami/controller/articleController.php`);
        url.searchParams.set('page', Math.max(1, parseInt(page)));
        if (categoryId) url.searchParams.set('category', parseInt(categoryId));
        if (search) url.searchParams.set('search', search.trim());

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Une erreur est survenue');
        }

        return data;
    } catch (error) {
        throw new Error(`Erreur lors du chargement des articles: ${error.message}`);
    }
}

export async function fetchArticlesByCategory(categoryId) {
    try {
        const url = new URL(`${window.location.origin}/Projet/ecommercesami/controller/articleController.php`);
        url.searchParams.append('category', categoryId);
        
        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Error fetching articles');
        }
        return result.data;
    } catch (error) {
        console.error('Error fetching articles:', error);
        throw error;
    }
}

export async function searchArticles(searchTerm = '', category = null, page = 1) {
    const loader = document.getElementById('loader');
    try {
        loader?.classList.remove('d-none');
        
        const url = new URL(`${window.location.origin}/Projet/ecommercesami/controller/articleController.php`);
        if (searchTerm) url.searchParams.append('search', searchTerm);
        if (category) url.searchParams.append('category', category);
        url.searchParams.append('page', page);

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) throw new Error('Erreur réseau');
        return await response.json();
    } catch (error) {
        throw new Error(`Erreur lors de la recherche: ${error.message}`);
    } finally {
        loader?.classList.add('d-none');
    }
}

// Fonctions de gestion des quantités pour l'ajout au panier
export function handleQuantityInput(input) {
    const newValue = parseInt(input.value);
    const maxStock = parseInt(input.dataset.stock);

    if (newValue < 1) {
        input.value = 1;
        return { success: false, message: 'La quantité minimum est 1' };
    } else if (newValue > maxStock) {
        input.value = maxStock;
        return { success: false, message: 'Stock maximum atteint' };
    }
    return { success: true };
}

// Modifier l'export de addToCart pour utiliser cartService
import { addToCart as cartServiceAddToCart } from './cartService.js';

export async function handleAddToCartClick(event) {
    event.preventDefault();
    const button = event.target.closest('.add-to-cart');
    if (!button) return;
    
    try {
        const articleId = button.dataset.articleId;
        const quantityInput = document.querySelector(`#quantity-${articleId}`);
        
        if (!articleId) {
            throw new Error('Article ID is missing');
        }
        
        const articleData = {
            articleId: parseInt(articleId),
            quantite: parseInt(quantityInput?.value || 1)
        };
        
        const result = await cartServiceAddToCart(articleData);
        showToast('Article ajouté au panier', 'success');
        return { success: true, data: result };
    } catch (error) {
        showToast('Erreur lors de l\'ajout au panier', 'danger');
        return { success: false, error: error.message };
    }
}

export function setupAddToCartListeners() {
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.removeEventListener('click', handleAddToCartClick);
        button.addEventListener('click', handleAddToCartClick);
    });

    // Gérer les inputs de quantité
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', (event) => {
            const result = handleQuantityInput(event.target);
            if (!result.success) {
                showToast(result.message, 'warning');
            }
        });
    });
}

// Notifications UI spécifiques aux articles
export function showToast(message, type = 'success') {
    const toast = document.getElementById('cartToast');
    if (!toast) return;

    toast.classList.remove('bg-success', 'bg-danger', 'bg-warning');
    toast.classList.add(`bg-${type}`);
    toast.querySelector('.toast-body').textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

export async function addToCart(articleData) {
    try {
        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=add`, {
            method: 'POST',
            headers: defaultHeaders,
            body: JSON.stringify({
                id_article: articleData.articleId,
                quantite: articleData.quantite
            })
        });

        const data = await response.json();
        if (!response.ok || data.error) {
            throw new Error(data.error || 'Erreur lors de l\'ajout au panier');
        }

        return data;
    } catch (error) {
        throw error;
    }
}
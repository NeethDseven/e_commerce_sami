<<<<<<< HEAD
const BASE_URL = '/projet/e_commerce_sami-develop';

=======
const BASE_URL = '/projet/ecommercesami/';
>>>>>>> origin/develop
const defaultHeaders = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json'
};

<<<<<<< HEAD
export async function fetchArticles(page = 1, category = null, search = '') {
    try {
        const url = new URL(`${window.location.origin}${BASE_URL}/controller/articleController.php`);
        const params = new URLSearchParams();
        
        params.set('action', 'list');
        params.set('page', page);
        if (category) params.set('category', category);
        if (search && search.trim() !== '') params.set('search', search.trim());
        
        url.search = params.toString();

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error('La réponse n\'est pas au format JSON');
        }

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();
        
        if (!data || typeof data !== 'object') {
            throw new Error('Format de réponse invalide');
        }

        return {
            success: true,
            articles: data.articles || [],
            pagination: data.pagination || null
        };

    } catch (error) {
        return {
            success: false,
            error: error.message,
            articles: [],
            pagination: null
        };
    }
}

export function handleQuantityInput(input) {
    const value = parseInt(input.value);
    const max = parseInt(input.dataset.stock);
    
    if (isNaN(value) || value < 1) {
        input.value = 1;
        return { success: false, message: 'La quantité doit être supérieure à 0' };
    }
    
    if (value > max) {
        input.value = max;
        return { success: false, message: 'Quantité ajustée au stock disponible' };
    }
    
    return { success: true };
}

=======
// Articles fetching functions
export async function fetchArticles(categoryId = null, page = 1, search = '') {
    try {
        const url = new URL(`${window.location.origin}${BASE_URL}controller/articleController.php`);
        const params = new URLSearchParams({
            page: Math.max(1, parseInt(page))
        });
        
        if (categoryId) params.set('category', parseInt(categoryId));
        if (search) params.set('search', search.trim());
        url.search = params.toString();

        const response = await fetch(url.toString(), { headers: defaultHeaders });
        const data = await response.json();
        
        if (!data.success) throw new Error(data.error || 'Une erreur est survenue');
        return data;
    } catch (error) {
        throw new Error(`Erreur lors du chargement des articles: ${error.message}`);
    }
}

// Cart handling functions
export function handleQuantityInput(input) {
    const newValue = parseInt(input.value);
    const maxStock = parseInt(input.dataset.stock);

    if (newValue < 1) {
        input.value = 1;
        return { success: false, message: 'La quantité minimum est 1' };
    }
    if (newValue > maxStock) {
        input.value = maxStock;
        return { success: false, message: 'Stock maximum atteint' };
    }
    return { success: true };
}

export async function addToCart(articleData) {
    try {
        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=add`, {
            method: 'POST',
            headers: defaultHeaders,
            body: JSON.stringify({
                id_article: parseInt(articleData.articleId),
                quantite: parseInt(articleData.quantite)
            })
        });

        const data = await response.json();
        if (!response.ok || data.error) {
            throw new Error(data.error || 'Erreur lors de l\'ajout au panier');
        }
        return { success: true, data };
    } catch (error) {
        console.error('Erreur addToCart:', error);
        return { success: false, error: error.message };
    }
}

>>>>>>> origin/develop
export function showToast(message, type = 'success') {
    const toast = document.getElementById('cartToast');
    if (!toast) return;

    toast.classList.remove('bg-success', 'bg-danger', 'bg-warning');
    toast.classList.add(`bg-${type}`);
    toast.querySelector('.toast-body').textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

export function setupAddToCartListeners() {
<<<<<<< HEAD
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.removeEventListener('click', handleAddToCartClick);
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();
            
            const articleId = event.currentTarget.dataset.articleId;
            
            const quantityInput = document.querySelector(`#quantity-${articleId}`);
            if (!quantityInput) {
                showToast('Erreur: impossible de trouver la quantité', 'danger');
                return;
            }

            try {
                const data = {
                    articleId: articleId,
                    quantite: parseInt(quantityInput.value)
                };

                const response = await addToCart(data);

                if (response.success) {
                    showToast('Article ajouté au panier', 'success');
                } else {
                    throw new Error(response.error || 'Erreur lors de l\'ajout au panier');
                }
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
=======
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.removeEventListener('click', handleAddToCartClick);
        button.addEventListener('click', handleAddToCartClick);
>>>>>>> origin/develop
    });

    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', (event) => {
            const result = handleQuantityInput(event.target);
            if (!result.success) showToast(result.message, 'warning');
        });
    });
}
const BASE_URL = (() => {
    const metaUrl = document.querySelector('meta[name="base-url"]')?.content;
    const defaultUrl = '/projet/e_commerce_sami-develop/';
    const url = metaUrl || defaultUrl;
    return url.endsWith('/') ? url : url + '/';
})();

const defaultHeaders = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json'
};

async function makeRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        credentials: 'include',
        headers: {
            ...defaultHeaders,
            'Origin': window.location.origin,
            ...(options.headers || {})
        }
    });
    
    if (!response.ok) {
        const text = await response.text();
        try {
            const json = JSON.parse(text);
            throw new Error(json.error || 'Server error');
        } catch (e) {
            throw new Error(`Server error: ${text}`);
        }
    }
    
    return response.json();
}

export async function removeFromCart(idArticle) {
    try {
        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=remove`, {
            method: 'POST',
            headers: defaultHeaders,
            credentials: 'include',
            body: JSON.stringify({ id_article: idArticle })
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur lors de la suppression');
        }

        return result;
    } catch (error) {
        console.error('Error removing from cart:', error);
        throw error;
    }
}

export async function updateCartQuantity(idArticle, newQuantity) {
    try {
        if (!idArticle || !newQuantity) {
            throw new Error('ID article et quantité requis');
        }

        const url = `${BASE_URL}index.php?controller=cartOrder&action=update`;
        const response = await fetch(url, {
            method: 'POST',
            headers: defaultHeaders,
            body: JSON.stringify({
                id_article: parseInt(idArticle),
                quantite: parseInt(newQuantity)
            })
        });

        const data = await response.json().catch(() => null);
        
        if (!response.ok) {
            throw new Error(data?.message || 'Erreur de mise à jour du panier');
        }
        
        return data;
    } catch (error) {
        throw error;
    }
}

export async function clearCart() {
    try {
        const url = `${BASE_URL}index.php?controller=cartOrder&action=clear`;
        const response = await fetch(url, {
            method: 'POST',
            headers: defaultHeaders,
            credentials: 'include'
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur lors du vidage du panier');
        }

        updateCartCounter([]);
        
        return result;
    } catch (error) {
        console.error('Error clearing cart:', error);
        throw new Error('Erreur lors du vidage du panier');
    }
}

export async function calculatePromotion(item) {
    const prixOriginal = parseFloat(item.prix);
    const prixFinal = parseFloat(item.prix_final);
    const hasPromo = prixFinal && prixFinal < prixOriginal;
    
    if (!hasPromo) return {
        hasPromo: false,
        prixOriginal,
        prixFinal: prixOriginal,
        reduction: 0,
        economie: 0
    };

    const prixMinimum = prixOriginal * 0.1;
    const prixPromo = Math.max(prixFinal, prixMinimum);
    const reduction = ((prixOriginal - prixPromo) / prixOriginal) * 100;
    const economie = prixOriginal - prixPromo;

    return {
        hasPromo: true,
        prixOriginal,
        prixFinal: prixPromo,
        reduction: Math.round(reduction),
        economie
    };
}

export function generateCartHTML(items) {
    return items.map(item => {
        const quantity = parseInt(item.quantite) || 0;
        const stock = parseInt(item.stock) || 0;
        const prixInitial = parseFloat(item.prix) || 0;
        const prixFinal = parseFloat(item.prix_final) || prixInitial;
        const name = item.nom || 'Article sans nom';
        const image = item.image || 'default-image.jpg';
        const id = item.id_article || '';

        const priceToDisplay = prixFinal < prixInitial ? prixFinal : prixInitial;

        return `
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <img src="${image}" class="img-fluid" alt="${name}">
                    </div>
                    <div class="col-md-4">
                        <h5 class="card-title">${name}</h5>
                        <p class="card-text">
                            ${prixFinal < prixInitial ? `
                                <del class="text-muted">${prixInitial.toFixed(2)}€</del>
                                <span class="text-danger fw-bold">${prixFinal.toFixed(2)}€</span>
                            ` : `
                                <span>${priceToDisplay.toFixed(2)}€</span>
                            `}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="quantity-controls">
                            <button class="btn btn-sm btn-secondary decrease-quantity" data-id="${id}">-</button>
                            <input type="number" value="${quantity}" min="1" max="${stock}" 
                                   data-id="${id}" data-stock="${stock}" class="form-control d-inline-block w-25">
                            <button class="btn btn-sm btn-secondary increase-quantity" data-id="${id}">+</button>
                            <button class="btn btn-sm btn-primary update-quantity" data-id="${id}">Mettre à jour</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-danger remove-item" data-id="${id}">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    }).join('');
}

export function generateSummaryHTML(cartData) {
    let total = 0;
    const items = cartData.items || [];

    items.forEach(item => {
        const quantity = parseInt(item.quantite) || 0;
        const prixInitial = parseFloat(item.prix) || 0;
        const prixFinal = parseFloat(item.prix_final) || prixInitial;
        const priceToUse = prixFinal < prixInitial ? prixFinal : prixInitial;
        total += priceToUse * quantity;
    });

    const uniqueItemCount = items.length;
    const totalQuantity = items.reduce((sum, item) => sum + parseInt(item.quantite || 0), 0);

    return `
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Résumé</h5>
                <p>Articles différents : ${uniqueItemCount}</p>
                <p>Quantité totale : ${totalQuantity} article(s)</p>
                <p class="h4">Total : ${total.toFixed(2)} €</p>
                ${uniqueItemCount > 0 ? '<button class="btn btn-primary validate-cart">Valider la commande</button>' : ''}
            </div>
        </div>
    `;
}

export async function validateOrder(orderData) {
    try {
        const response = await makeRequest(`${BASE_URL}index.php?controller=cartOrder&action=validate`, {
            method: 'POST',
            body: JSON.stringify(orderData)
        });

        if (!response.success) {
            throw new Error(response.error || 'Erreur lors de la validation de la commande');
        }

        return response;
    } catch (error) {
        console.error('Validate order error:', error);
        throw new Error(error.message || 'Erreur lors de la validation de la commande');
    }
}

export async function getOrders(userId) {
    const response = await fetch(`index.php?controller=order&action=list&id_user=${userId}`, {
        method: 'GET',
    });
    if (!response.ok) {
        throw new Error('Failed to fetch orders');
    }
    return response.json();
}

export function updateCartCounter(items) {
    const counter = document.querySelector('#cart-counter');
    if (counter) {
        const totalItems = items.reduce((sum, item) => sum + (parseInt(item.quantite) || 0), 0);
        counter.textContent = totalItems;
        counter.style.display = totalItems > 0 ? 'inline' : 'none';
    }
}

export function showToast(message, type = 'success') {
    const toast = document.getElementById('cartToast');
    if (!toast) return;

    toast.classList.remove('bg-success', 'bg-danger');
    toast.classList.add(`bg-${type}`);
    toast.querySelector('.toast-body').textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

export async function addToCart(articleData) {
    try {
        if (!articleData || typeof articleData !== 'object') {
            throw new Error('Données article invalides');
        }

        const articleId = parseInt(articleData.articleId);
        const quantite = parseInt(articleData.quantite);

        if (!articleId || isNaN(articleId) || articleId <= 0) {
            throw new Error('ID article invalide');
        }

        if (!quantite || isNaN(quantite) || quantite <= 0) {
            throw new Error('Quantité invalide');
        }

        const payload = {
            id_article: articleId,
            quantite: quantite
        };

        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=add`, {
            method: 'POST',
            headers: defaultHeaders,
            credentials: 'include',
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur lors de l\'ajout au panier');
        }

        return result;
    } catch (error) {
        console.error('Error in addToCart:', error);
        throw error;
    }
}

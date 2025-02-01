const BASE_URL = '/projet/ecommercesami/';  // Correction du chemin de base
const defaultHeaders = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json'
};


export async function removeFromCart(idArticle) {
    const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=remove`, {  // Modifié de 'cart' à 'cartOrder'
        method: 'POST',
        headers: defaultHeaders,
        body: JSON.stringify({ id_article: idArticle })
    });
    
    const data = await response.text();
    try {
        return JSON.parse(data);
    } catch (e) {
        throw new Error(`Erreur serveur: ${data}`);
    }
}

export async function updateCartQuantity(idArticle, newQuantity) {
    try {
        // Vérification et conversion des valeurs
        if (!idArticle || !newQuantity) {
            throw new Error('ID article et quantité requis');
        }

        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=update`, {
            method: 'POST',
            headers: defaultHeaders,
            body: JSON.stringify({
                id_article: parseInt(idArticle),
                quantite: parseInt(newQuantity)
            })
        });

        const data = await response.json().catch(() => null);
        
        if (!response.ok) {
            console.log('Réponse serveur:', data); // Pour déboguer
            throw new Error(data?.message || 'Erreur de mise à jour du panier');
        }
        
        return data;
    } catch (error) {
        console.error('Détails de l\'erreur updateCartQuantity:', {
            idArticle,
            newQuantity,
            error: error.message
        });
        throw error;
    }
}

export async function clearCart() {
    const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=clear`, {
        method: 'POST',
        headers: defaultHeaders
    });
    
    if (!response.ok) {
        throw new Error('Erreur lors du vidage du panier');
    }
    return response.json();
}

// Fonctions de calcul et de génération HTML
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
        // Correction des noms des propriétés pour correspondre à ceux du backend
        const quantity = parseInt(item.quantite) || 0;
        const stock = parseInt(item.stock) || 0;
        const prixInitial = parseFloat(item.prix) || 0;
        const prixFinal = parseFloat(item.prix_final) || prixInitial;
        const name = item.nom || 'Article sans nom';
        const image = item.image || 'default-image.jpg';
        const id = item.id_article || '';

        // Calcul du prix à afficher
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
    
    // Calcul correct du total
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

// Gestion des commandes
export async function validateOrder(orderData) {
    try {
        console.log('Sending order data:', orderData); // Debug

        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=validate`, {
            method: 'POST',
            headers: {
                ...defaultHeaders
            },
            credentials: 'include', // Important pour les sessions
            body: JSON.stringify(orderData)
        });

        const data = await response.json();
        console.log('Server response:', data); // Debug

        if (!response.ok) {
            if (response.status === 401) {
                // Stocker l'URL actuelle avant la redirection
                const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = `${BASE_URL}index.php?page=login&return=${returnUrl}`;
                throw new Error('Session expirée, veuillez vous reconnecter');
            }
            throw new Error(data.error || 'Erreur lors de la validation de la commande');
        }

        return data;
    } catch (error) {
        console.error('Erreur validateOrder:', error);
        throw error;
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

// UI helpers pour le panier
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

export async function addToCart({ articleId, quantite }) {
    try {
        const response = await fetch(`${BASE_URL}index.php?controller=cartOrder&action=add`, {
            method: 'POST',
            headers: defaultHeaders,
            body: JSON.stringify({
                id_article: articleId,
                quantite: quantite
            })
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de l\'ajout au panier');
        }

        if (data.success) {
            showToast('Article ajouté au panier', 'success');
            updateCartCounter(data.items || []);
        }

        return data;
    } catch (error) {
        console.error('Erreur addToCart:', error);
        throw error;
    }
}

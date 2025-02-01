import { 
    fetchArticles, 
    handleQuantityInput 
} from '../services/serviceArticles.js';
import { showToast, addToCart } from '../services/cartService.js';

// Regroupement des constantes globales
const articlesContainer = document.getElementById('article');
const urlParams = new URLSearchParams(window.location.search);
let isUpdating = false;
let currentCategory = null; // Ajoutez cette variable globale en haut du fichier pour suivre la catégorie active

// Fonction utilitaire de debounce
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

// Fonction principale d'affichage d'un article
export function displayArticle(article) {
    let html = '<div class="col-md-3 mb-4"><div class="card h-100">';
    
    // Image avec badge promo si applicable
    html += '<div class="position-relative">';
    html += `<img src="./assets/images/articles/${article.image}" class="card-img-top" alt="${article.nom}">`;
    if (article.pourcentage_reduction > 0) {
        html += `<div class="position-absolute top-0 end-0 badge bg-danger m-2">-${article.pourcentage_reduction}%</div>`;
    }
    html += '</div>';
    
    // Début du corps de la carte
    html += '<div class="card-body">';
    
    // Titre et description
    html += `<h5 class="card-title">${article.nom}</h5>`;
    html += `<p class="card-text text-truncate">${article.description}</p>`;
    
    // Prix et badge promo
    html += '<div class="d-flex justify-content-between align-items-center"><div class="price-container">';
    if (article.pourcentage_reduction > 0 && article.prix_promotionnel) {
        const prixInitial = parseFloat(article.prix).toFixed(2);
        const prixPromo = parseFloat(article.prix_promotionnel).toFixed(2);
        html += `<p class="card-text text-decoration-line-through">${prixInitial}€</p>`;
        html += `<p class="card-text text-danger fw-bold">${prixPromo}€</p>`;
        html += `</div><span class="badge bg-danger">-${article.pourcentage_reduction}%</span>`;
    } else {
        const prix = parseFloat(article.prix).toFixed(2);
        html += `<p class="card-text fw-bold">${prix}€</p></div>`;
    }
    html += '</div>';
    
    // Contrôles de stock et quantité
    html += '<div class="quantity-control mt-2">';
    html += `<small class="text-muted">Stock: ${article.stock} unité${article.stock > 1 ? 's' : ''}</small>`;
    
    if (article.stock > 0) {
        html += `
            <div class="d-flex align-items-center mt-2 gap-2">
                <div class="input-group input-group-sm" style="width: 120px;">
                    <button class="btn btn-outline-secondary quantity-btn" data-action="decrease">-</button>
                    <input type="number" 
                        class="form-control text-center quantity-input" 
                        id="quantity-${article.id_article}"
                        min="1" 
                        max="${article.stock}" 
                        value="1" 
                        data-stock="${article.stock}">
                    <button class="btn btn-outline-secondary quantity-btn" data-action="increase">+</button>
                </div>
                <button class="btn btn-primary flex-grow-1 add-to-cart" data-article-id="${article.id_article}">
                    Ajouter
                </button>
            </div>`;
    } else {
        html += '<button class="btn btn-secondary mt-2 w-100" disabled>Rupture de stock</button>';
    }
    
    // Fermeture des divs
    html += '</div></div></div></div>';
    
    return html;
}

function displayArticles(articles) {
    const articlesContainer = document.getElementById('articles-container');
    if (!articlesContainer) return;

    let html = '<div class="row">';
    
    articles.forEach(article => {
        const prixInitial = parseFloat(article.prix);
        const promotion = parseFloat(article.promotion) || 0;
        const prixFinal = promotion > 0 ? prixInitial * (1 - promotion / 100) : prixInitial;

        html += `
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    // ...existing code...
                    <div class="card-body">
                        <h5 class="card-title">${article.nom}</h5>
                        <p class="card-text">${article.description}</p>
                        <div class="price-section">
                            ${promotion > 0 ? 
                                `<p class="original-price text-muted"><del>${prixInitial.toFixed(2)}€</del></p>
                                <p class="final-price text-danger">${prixFinal.toFixed(2)}€</p>` :
                                `<p class="price">${prixFinal.toFixed(2)}€</p>`
                            }
                        </div>
                        // ...existing code...
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    articlesContainer.innerHTML = html;
}

async function handleNavigation(params = {}, pushState = true) {
    if (isUpdating) return;
    isUpdating = true;
    
    try {
        const categoryToUse = params.category !== undefined ? params.category : currentCategory;
        const response = await fetchArticles(
            categoryToUse,
            params.page || 1,
            params.search || ''
        );
        
        if (response.success) {
            currentCategory = categoryToUse;
            updateArticlesDisplay(response.data);
            updatePagination(response.pagination);
            updateActiveCategory(categoryToUse);

            if (pushState) {
                const url = new URL(window.location);
                Object.entries({
                    ...params,
                    category: categoryToUse
                }).forEach(([key, value]) => {
                    if (value) {
                        url.searchParams.set(key, value);
                    } else {
                        url.searchParams.delete(key);
                    }
                });
                history.pushState({ ...params, category: categoryToUse }, '', url);
            }
        }
    } catch (error) {
        if (articlesContainer) {
            articlesContainer.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des articles</div>';
        }
    } finally {
        isUpdating = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la catégorie courante depuis l'URL
    currentCategory = urlParams.get('category');

    handleNavigation({
        page: parseInt(urlParams.get('page')) || 1,
        category: urlParams.get('category'),
        search: urlParams.get('search')
    }, false);

    // Configuration du formulaire de recherche
    setupSearchForm();

    // Gestion de la pagination
    document.querySelector('#pagination-container')?.addEventListener('click', e => {
        const pageLink = e.target.closest('.page-link');
        if (!pageLink) return;
        
        e.preventDefault();
        const page = pageLink.dataset.page;
        if (!page) return;
        
        handleNavigation({
            page: parseInt(page),
            category: currentCategory, // Utiliser la catégorie active
            search: urlParams.get('search')
        });
    });

    // Gestion du retour/avant du navigateur
    window.addEventListener('popstate', (event) => {
        handleNavigation(event.state || {}, false);
    });

    // Ajouter l'écouteur pour le changement de catégorie
    window.addEventListener('categoryChange', async (event) => {
        currentCategory = event.detail.categoryId;
        await handleNavigation({
            page: 1,
            category: currentCategory,
            search: urlParams.get('search')
        });
    });
});

function setupSearchForm() {
    const searchForm = document.querySelector('form[role="search"]');
    if (!searchForm) return;

    const searchInput = searchForm.querySelector('input[name="search"]');
    const categorySelect = searchForm.querySelector('select[name="category"]');

    const debouncedSearch = debounce(() => {
        handleNavigation({
            page: 1,
            category: categorySelect?.value || null,
            search: searchInput?.value?.trim() || ''
        });
    }, 300);

    if (searchInput) searchInput.addEventListener('input', debouncedSearch);
    if (categorySelect) categorySelect.addEventListener('change', debouncedSearch);

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        debouncedSearch();
    });
}

function updatePagination(paginationData) {
    const container = document.querySelector('#pagination-container .pagination');
    if (!container || !paginationData) return;

    if (paginationData.totalItems === 0) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    if (paginationData.hasPreviousPage) {
        html += `<li class="page-item"><button class="page-link" data-page="${paginationData.previousPage}">Précédent</button></li>`;
    }

    for (let i = 1; i <= paginationData.totalPages; i++) {
        if (i === 1 || i === paginationData.totalPages || 
            (i >= paginationData.currentPage - 2 && i <= paginationData.currentPage + 2)) {
            html += `<li class="page-item ${i === paginationData.currentPage ? 'active' : ''}">
                        <button class="page-link" data-page="${i}">${i}</button>
                    </li>`;
        } else if (i === paginationData.currentPage - 3 || i === paginationData.currentPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    if (paginationData.hasNextPage) {
        html += `<li class="page-item"><button class="page-link" data-page="${paginationData.nextPage}">Suivant</button></li>`;
    }

    container.innerHTML = html;
}

function updateActiveCategory(categoryId) {
    const buttons = document.querySelectorAll('#category-buttons .category-btn');
    const select = document.querySelector('select[name="category"]');
    
    if (buttons) {
        buttons.forEach(button => {
            button.classList.remove('active');
            if (categoryId === null && button.dataset.category === '') {
                button.classList.add('active');
            } else if (button.dataset.category === categoryId?.toString()) {
                button.classList.add('active');
            }
        });
    }
    
    if (select) {
        select.value = categoryId || '';
    }
}

function updateArticlesDisplay(articles) {
    const container = document.getElementById('article');
    if (!container) return;

    if (!articles || articles.length === 0) {
        container.innerHTML = '<div class="col-12"><p class="alert alert-info">Aucun article ne correspond à votre recherche</p></div>';
        const paginationContainer = document.querySelector('#pagination-container .pagination');
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
        return;
    }

    let html = '';
    for (let i = 0; i < articles.length; i += 3) {
        html += '<div class="row mb-4 justify-content-start">';
        for (let j = 0; j < 3 && (i + j) < articles.length; j++) {
            html += displayArticle(articles[i + j]);
        }
        html += '</div>';
    }
    
    container.innerHTML = html;
    setupQuantityControls(container);
    setupAddToCartButtons();
}

function setupQuantityControls(container) {
    // Gestion des boutons +/- 
    container.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            const input = event.target.closest('.input-group').querySelector('.quantity-input');
            const action = event.target.dataset.action;
            const currentValue = parseInt(input.value);
            const maxStock = parseInt(input.dataset.stock);

            if (action === 'decrease' && currentValue > 1) {
                input.value = currentValue - 1;
            } else if (action === 'increase' && currentValue < maxStock) {
                input.value = currentValue + 1;
            }

            const result = handleQuantityInput(input);
            if (!result.success) {
                showToast(result.message, 'warning');
            }
        });
    });

    // Gestion de l'input direct
    container.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', (event) => {
            const result = handleQuantityInput(event.target);
            if (!result.success) {
                showToast(result.message, 'warning');
            }
        });
    });
}

function setupAddToCartButtons() {
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const articleId = event.target.dataset.articleId;
            const quantityInput = document.querySelector(`#quantity-${articleId}`);
            
            if (!quantityInput) {
                showToast('Erreur: impossible de trouver la quantité', 'danger');
                return;
            }

            const result = handleQuantityInput(quantityInput);
            if (!result.success) {
                showToast(result.message, 'warning');
                return;
            }
            
            try {
                await addToCart({
                    articleId: parseInt(articleId),
                    quantite: parseInt(quantityInput.value)
                });
            } catch (error) {
                console.error('Erreur ajout panier:', error);
                showToast('Erreur lors de l\'ajout au panier', 'danger');
            }
        });
    });
}
import { fetchArticles, handleQuantityInput } from '../services/serviceArticles.js';
import { addToCart, showToast } from '../services/cartService.js';

const state = {
    isUpdating: false,
    currentCategory: null,
    currentPage: 1,
    loadedImages: new Set(),
    lastRequest: null,
};

const urlParams = new URLSearchParams(window.location.search);

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function getImageUrl(image) {
    if (!image) return './assets/images/default-product.jpg';
    
    if (image.includes('placeholder.com')) {
        return image;
    }
    
    return image.startsWith('http') 
        ? image 
        : `/projet/e_commerce_sami-develop/assets/images/articles/${image}`;
}

export function displayArticle(article) {
    if (!article) return '';

    const imageUrl = getImageUrl(article.image);
    
    const imageKey = btoa(imageUrl);
    if (!state.loadedImages.has(imageKey)) {
        const img = new Image();
        img.src = imageUrl;
        state.loadedImages.add(imageKey);
    }

    const prixOriginal = parseFloat(article.prix);
    const prixPromotionnel = article.prix_promotionnel ? parseFloat(article.prix_promotionnel) : null;
    const pourcentageReduction = article.pourcentage_reduction ? Math.abs(parseInt(article.pourcentage_reduction)) : 0;
    const hasPromotion = prixPromotionnel && prixPromotionnel < prixOriginal && pourcentageReduction > 0;

    let priceHtml = '';
    if (hasPromotion) {
        priceHtml = `
            <div class="price-container">
                <del class="text-muted">${prixOriginal.toFixed(2)}€</del>
                <span class="text-danger fw-bold ms-2">${prixPromotionnel.toFixed(2)}€</span>
                <div class="badge bg-danger mt-1">-${pourcentageReduction}%</div>
            </div>
        `;
    } else {
        priceHtml = `
            <div class="price-container">
                <span class="fw-bold">${prixOriginal.toFixed(2)}€</span>
            </div>
        `;
    }

    return `
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="position-relative">
                    <img src="${imageUrl}" 
                         class="card-img-top" 
                         alt="${article.nom}"
                         loading="lazy"
                         style="height: 200px; object-fit: contain;">
                    ${hasPromotion ? `
                        <div class="position-absolute top-0 end-0 badge bg-danger m-2">
                            -${pourcentageReduction}%
                        </div>
                    ` : ''}
                </div>
                
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${article.nom}</h5>
                    <p class="card-text text-truncate">${article.description}</p>
                    
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center">
                            ${priceHtml}
                            ${article.categorie_nom ? `
                                <span class="badge bg-secondary">${article.categorie_nom}</span>
                            ` : ''}
                        </div>
                        
                        <div class="quantity-control mt-2">
                            <small class="text-muted d-block mb-2">
                                Stock: ${article.stock} unité${article.stock > 1 ? 's' : ''}
                            </small>
                            ${article.stock > 0 ? `
                                <div class="d-flex gap-2">
                                    <div class="input-group input-group-sm flex-nowrap">
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
                                    <button class="btn btn-primary add-to-cart-btn" data-article-id="${article.id_article}">
                                        Ajouter
                                    </button>
                                </div>
                            ` : `
                                <button class="btn btn-secondary w-100" disabled>Rupture de stock</button>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

let fetchInProgress = false;

async function fetchArticlesWithDebounce(page, category) {
    if (fetchInProgress) {
        return;
    }
    
    try {
        fetchInProgress = true;
        const data = await fetchArticles(page, category);
    } catch (error) {
        console.error('Erreur critique:', error);
    } finally {
        fetchInProgress = false;
    }
}

async function handleNavigation(params = {}, pushState = true) {
    const searchTerm = params.search || '';
    const requestKey = `${params.page}-${params.category}-${searchTerm}`;
    
    if (state.isUpdating && state.lastRequest === requestKey) {
        return;
    }
    
    state.isUpdating = true;
    state.lastRequest = requestKey;
    
    const container = document.getElementById('article');
    if (!container) return;
    
    try {
        container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
        
        const response = await fetchArticles(
            params.page || state.currentPage,
            params.category !== undefined ? params.category : state.currentCategory,
            searchTerm
        );
        
        if (!response.success) {
            throw new Error(response.error || 'Erreur lors du chargement des articles');
        }

        if (pushState) {
            const url = new URL(window.location);
            url.searchParams.set('page', params.page || 1);
            
            if (params.category) {
                url.searchParams.set('category', params.category);
            } else {
                url.searchParams.delete('category');
            }
            
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            
            history.pushState({ ...params }, '', url);
        }

        updateArticlesDisplay(response);
        if (response.pagination) {
            updatePagination(response.pagination);
        }
        updateActiveCategory(params.category);
        
    } catch (error) {
        console.error('Navigation error:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <h4 class="alert-heading">Erreur de chargement</h4>
                <p>${error.message || 'Une erreur est survenue lors du chargement des articles.'}</p>
                <hr>
                <button class="btn btn-outline-danger" onclick="window.location.reload()">
                    Actualiser la page
                </button>
            </div>`;
    } finally {
        state.isUpdating = false;
        state.lastRequest = null;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    state.currentCategory = urlParams.get('category');

    handleNavigation({
        page: parseInt(urlParams.get('page')) || 1,
        category: urlParams.get('category'),
        search: urlParams.get('search')
    }, false);

    setupSearchForm();

    document.querySelector('#pagination-container')?.addEventListener('click', e => {
        const pageLink = e.target.closest('.page-link');
        if (!pageLink) return;
        
        e.preventDefault();
        const page = pageLink.dataset.page;
        if (!page) return;
        
        handleNavigation({
            page: parseInt(page),
            category: state.currentCategory,
            search: urlParams.get('search')
        });
    });

    window.addEventListener('popstate', (event) => {
        handleNavigation(event.state || {}, false);
    });

    window.addEventListener('categoryChange', async (event) => {
        state.currentCategory = event.detail.categoryId;
        await handleNavigation({
            page: 1,
            category: state.currentCategory,
            search: urlParams.get('search')
        });
    });
});

function setupSearchForm() {
    const searchForm = document.querySelector('form[role="search"]');
    if (!searchForm) return;

    const searchInput = searchForm.querySelector('input[name="search"]');
    const categorySelect = searchForm.querySelector('select[name="category"]');

    const performSearch = async () => {
        const searchTerm = searchInput?.value?.trim() || '';
        const categoryId = categorySelect?.value || null;
        
        console.log('Searching with:', { searchTerm, categoryId });
        
        await handleNavigation({
            page: 1,
            category: categoryId,
            search: searchTerm
        });
    };

    const debouncedSearch = debounce(performSearch, 300);

    if (searchInput) {
        searchInput.addEventListener('input', debouncedSearch);
        
        const initialSearch = urlParams.get('search');
        if (initialSearch) {
            searchInput.value = initialSearch;
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', performSearch);
    }

    searchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await performSearch();
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

function updateArticlesDisplay(response) {
    const container = document.getElementById('article');
    if (!container) return;

    if (!response?.articles || !Array.isArray(response.articles)) {
        container.innerHTML = '<div class="col-12"><p class="alert alert-danger">Erreur de format de données</p></div>';
        return;
    }

    const articles = Array.isArray(response.articles[0]) ? response.articles[0] : response.articles;

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
            const article = articles[i + j];
            if (article) {
                html += displayArticle(article);
            }
        }
        html += '</div>';
    }

    container.innerHTML = html;
    setupQuantityControls(container);
    setupAddToCartButtons();
}

function setupQuantityControls(container) {
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

    container.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', (event) => {
            const result = handleQuantityInput(event.target);
            if (!result.success) {
                showToast(result.message, 'warning');
            }
        });
    });
}

async function handleAddToCartClick(event) {
    event.preventDefault();
    const button = event.currentTarget;
    button.disabled = true;
    
    try {
        const articleId = button.dataset.articleId;
        const quantityInput = document.querySelector(`#quantity-${articleId}`);
        
        if (!quantityInput) {
            throw new Error('Impossible de trouver la quantité');
        }

        const result = handleQuantityInput(quantityInput);
        if (!result.success) {
            throw new Error(result.message);
        }
        
        const response = await addToCart({
            articleId: parseInt(articleId),
            quantite: parseInt(quantityInput.value)
        });
        
        if (response.success) {
            showToast('Article ajouté au panier', 'success');
            if (response.cartCount) {
                updateCartCounter(response.cartCount);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast(error.message || 'Erreur lors de l\'ajout au panier', 'danger');
    } finally {
        button.disabled = false;
    }
}

function setupAddToCartButtons() {
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.removeEventListener('click', handleAddToCartClick);
        button.addEventListener('click', async function(event) {
            event.preventDefault();
            const button = event.currentTarget;
            const articleId = button.dataset.articleId;
            const quantityInput = document.querySelector(`#quantity-${articleId}`);
            
            if (!quantityInput) {
                showToast('Erreur: impossible de trouver la quantité', 'danger');
                return;
            }

            try {
                button.disabled = true;
                const response = await addToCart({
                    articleId: parseInt(articleId),
                    quantite: parseInt(quantityInput.value)
                });
                
                if (response.success) {
                    showToast('Article ajouté au panier', 'success');
                    document.dispatchEvent(new CustomEvent('cartUpdated'));
                }
            } catch (error) {
                showToast(error.message || 'Erreur lors de l\'ajout au panier', 'danger');
            } finally {
                button.disabled = false;
            }
        });
    });
}

function initArticleComponent() {
    const articleContainer = document.getElementById('article');
    if (!articleContainer) {
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    state.currentPage = parseInt(urlParams.get('page')) || 1;
    state.currentCategory = urlParams.get('category') || null;

    handleNavigation({
        page: state.currentPage,
        category: state.currentCategory
    }, false);

    initEventListeners();
}

function initEventListeners() {
    window.removeEventListener('categoryChange', handleCategoryChange);
    window.addEventListener('categoryChange', handleCategoryChange);
    
    const paginationContainer = document.querySelector('#pagination-container');
    if (paginationContainer) {
        paginationContainer.removeEventListener('click', handlePaginationClick);
        paginationContainer.addEventListener('click', handlePaginationClick);
    }
}

function handleCategoryChange(event) {
    const categoryId = event.detail.categoryId;
    if (categoryId !== state.currentCategory) {
        handleNavigation({ page: 1, category: categoryId });
    }
}

function handlePaginationClick(e) {
    const pageLink = e.target.closest('.page-link');
    if (!pageLink || pageLink.closest('.disabled') || pageLink.closest('.active')) return;
    
    e.preventDefault();
    const page = parseInt(pageLink.dataset.page);
    if (!page) return;
    
    document.querySelectorAll('.page-link').forEach(link => {
        link.classList.add('disabled');
    });
    
    handleNavigation({ 
        page, 
        category: state.currentCategory 
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initArticleComponent);
} else {
    initArticleComponent();
}

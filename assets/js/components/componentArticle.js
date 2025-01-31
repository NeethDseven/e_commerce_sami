import { 
    fetchArticles, 
    handleQuantityInput, 
    setupAddToCartListeners, 
    showToast 
} from '../services/serviceArticles.js';
import { addToCart } from '../services/serviceArticles.js';

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    let isUpdating = false;

    // Gestionnaire unifié pour la navigation
    async function handleNavigation(params = {}, pushState = true) {
        if (isUpdating) return;
        isUpdating = true;
        
        const loader = document.getElementById('loader');
        loader?.classList.remove('d-none');

        try {
            let { page = 1, category = null, search = '' } = params;
            
            // Vérifier que les paramètres sont du bon type
            page = parseInt(page) || 1;
            category = category ? parseInt(category) : null;
            search = typeof search === 'string' ? search : '';

            const response = await fetchArticles(category, page, search);

            if (response.success) {
                // Si aucun résultat, on n'affiche pas la pagination
                if (!response.data || response.data.length === 0) {
                    updateArticlesDisplay([]);
                    // On force la pagination à être vide
                    const paginationContainer = document.querySelector('#pagination-container .pagination');
                    if (paginationContainer) {
                        paginationContainer.innerHTML = '';
                    }
                } else {
                    updateArticlesDisplay(response.data);
                    updatePagination(response.pagination);
                }

                updateActiveCategory(category);

                // Mettre à jour les champs de recherche de la navbar
                if (document.getElementById('navSearchInput')) {
                    document.getElementById('navSearchInput').value = search;
                }
                if (document.getElementById('navSearchCategory')) {
                    document.getElementById('navSearchCategory').value = category || '';
                }

                if (pushState) {
                    const url = new URL(window.location);
                    if (category) url.searchParams.set('category', category);
                    else url.searchParams.delete('category');
                    if (search) url.searchParams.set('search', search);
                    else url.searchParams.delete('search');
                    url.searchParams.set('page', page);
                    history.pushState(params, '', url);
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } catch (error) {
            console.error('Erreur de navigation:', error);
            showToast('Erreur lors de la navigation', 'danger');
        } finally {
            loader?.classList.add('d-none');
            isUpdating = false;
        }
    }

    // Gestionnaire de pagination simplifié
    const paginationContainer = document.getElementById('pagination-container');
    if (paginationContainer) {
        paginationContainer.addEventListener('click', async (e) => {
            const button = e.target.closest('.page-link');
            if (!button || button.hasAttribute('disabled') || isUpdating) return;
            
            e.preventDefault();
            const page = parseInt(button.dataset.page);
            const urlParams = new URLSearchParams(window.location.search);
            
            await handleNavigation({
                page: page,
                category: urlParams.get('category'),
                search: urlParams.get('search')
            });
        });
    }

    // Gestionnaire de catégories - mise à jour
    const categoryButtons = document.getElementById('category-buttons');
    if (categoryButtons) {
        categoryButtons.addEventListener('click', async (e) => {
            e.preventDefault();
            const link = e.target.closest('[data-category]');
            if (!link || isUpdating) return;
            
            const category = link.dataset.category;
            try {
                await handleNavigation({ 
                    page: 1,
                    category: category,
                    search: ''  // Réinitialiser la recherche lors du changement de catégorie
                });
            } catch (error) {
                console.error('Erreur lors du changement de catégorie:', error);
                showToast('Erreur lors du chargement de la catégorie', 'danger');
            }
        });
    }

    // Gestion du bouton retour/avant du navigateur
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        handleNavigation({
            page: parseInt(url.searchParams.get('page') || 1),
            category: url.searchParams.get('category')
        }, false);
    });

    // Modifier le gestionnaire de recherche
    const searchForm = document.querySelector('form[role="search"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const currentPage = new URLSearchParams(window.location.search).get('page');
            // Si nous sommes sur une page spéciale, laisser le comportement par défaut
            if (['userlist', 'orderView', 'login', 'register'].includes(currentPage)) {
                return;
            }
            
            e.preventDefault();
            const formData = new FormData(this);
            const selectedCategory = formData.get('category') || null;
            
            handleNavigation({
                page: 1,
                category: selectedCategory, // Utiliser la catégorie sélectionnée
                search: formData.get('search')
            });
        });
    }

    // Gestionnaire pour les liens de catégories et pagination
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href*="category"], a[href*="page"], .page-link');
        if (!link || !link.href) return;
        
        // Liste des liens à exclure du comportement AJAX
        const excludedPages = ['login', 'register', 'logout', 'userlist', 'orderView'];
        
        try {
            const url = new URL(link.href);
            const pageParam = url.searchParams.get('page');
            
            // Vérifier si le lien doit être géré de manière traditionnelle
            if (pageParam && excludedPages.includes(pageParam)) {
                return; // Laisser le comportement par défaut
            }
            
            e.preventDefault();
            handleNavigation({
                page: url.searchParams.get('page'),
                category: url.searchParams.get('category'),
                search: url.searchParams.get('search')
            });
        } catch (error) {
            console.error('Erreur lors du parsing de l\'URL:', error);
        }
    });
    
    function updatePagination(paginationData) {
        const container = document.querySelector('#pagination-container .pagination');
        if (!container || !paginationData) return;
    
        // Si aucun article trouvé, on cache la pagination
        if (paginationData.totalItems === 0) {
            container.innerHTML = '';
            return;
        }
    
        let html = '';
        
        // Bouton précédent
        if (paginationData.hasPreviousPage) {
            html += `
                <li class="page-item">
                    <button class="page-link" data-page="${paginationData.previousPage}">Précédent</button>
                </li>`;
        }
    
        // Numéros de page
        for (let i = 1; i <= paginationData.totalPages; i++) {
            if (i === 1 || i === paginationData.totalPages || 
                (i >= paginationData.currentPage - 2 && i <= paginationData.currentPage + 2)) {
                html += `
                    <li class="page-item ${i === paginationData.currentPage ? 'active' : ''}">
                        <button class="page-link" data-page="${i}">${i}</button>
                    </li>`;
            } else if (i === paginationData.currentPage - 3 || i === paginationData.currentPage + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
    
        // Bouton suivant
        if (paginationData.hasNextPage) {
            html += `
                <li class="page-item">
                    <button class="page-link" data-page="${paginationData.nextPage}">Suivant</button>
                </li>`;
        }
    
        container.innerHTML = html;
    
        // Ajout des écouteurs d'événements
        container.querySelectorAll('.page-link').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(button.dataset.page);
                if (!isNaN(page)) {
                    handleNavigation({ page });
                }
            });
        });
    }
    
    // Fonctions utilitaires
    // Modifier la fonction updateActiveCategory pour synchroniser avec le select
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
        
        // Mise à jour du select
        if (select) {
            select.value = categoryId || '';
        }
    }

    function updateArticlesDisplay(articles) {
        const container = document.getElementById('article');
        if (!container) {
            console.error('Container #article not found');
            return;
        }

        if (!articles || articles.length === 0) {
            container.innerHTML = '<div class="col-12"><p class="alert alert-info">Aucun article ne correspond à votre recherche</p></div>';
            // On force la pagination à être vide ici aussi
            const paginationContainer = document.querySelector('#pagination-container .pagination');
            if (paginationContainer) {
                paginationContainer.innerHTML = '';
            }
            return;
        }

        let html = '';
        // Afficher tous les articles reçus en grille de 3
        for (let i = 0; i < articles.length; i += 3) {
            html += '<div class="row mb-4 justify-content-start">';
            for (let j = 0; j < 3 && (i + j) < articles.length; j++) {
                const article = articles[i + j];
                const hasPromo = article.prix_final !== null && parseFloat(article.prix_final) < parseFloat(article.prix);
                const prixFinal = hasPromo ? parseFloat(article.prix_final) : parseFloat(article.prix);
                const reduction = hasPromo ? parseInt(article.pourcentage_reduction) : 0;

                html += `
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            ${hasPromo ? `
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger">-${reduction}%</span>
                                </div>
                            ` : ''}
                            <img src="${article.image || ''}" class="card-img-top" alt="${article.nom}" 
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">${article.nom}</h5>
                                <p class="card-text flex-grow-1">${article.description}</p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="price-container">
                                            ${hasPromo ? `
                                                <del class="text-muted me-2">${article.prix}€</del>
                                                <span class="text-danger fw-bold fs-5">${prixFinal.toFixed(2)}€</span>
                                            ` : `
                                                <span class="fs-5">${parseFloat(article.prix).toFixed(2)}€</span>
                                            `}
                                        </div>
                                        <span class="text-muted">Stock: ${article.stock}</span>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <input type="number" 
                                               id="quantity-${article.id_article}"
                                               class="form-control" 
                                               min="1" 
                                               max="${article.stock}"
                                               value="1"
                                               style="width: 80px;">
                                        <button class="btn btn-primary flex-grow-1 add-to-cart" 
                                                data-article-id="${article.id_article}">
                                            Ajouter au panier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';
        }
        
        container.innerHTML = html;
        setupAddToCartListeners(); // S'assurer que cette ligne est présente
    }

    // Supprimer les fonctions qui ont été déplacées :
    // - handleQuantityInput
    // - setupCartButtons
    // - handleAddToCart
    // - showToast

    // Validation des quantités
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', (event) => {
            const max = parseInt(event.target.max);
            const value = parseInt(event.target.value);
            if (value > max) {
                event.target.value = max;
            }
            if (value < 1) {
                event.target.value = 1;
            }
        });
    });

    // Appel initial pour charger la première page
    const urlParams = new URLSearchParams(window.location.search);
    handleNavigation({
        page: parseInt(urlParams.get('page')) || 1,
        category: urlParams.get('category'),
        search: urlParams.get('search')
    }, false);
    
    // Gestionnaire pour les liens
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href*="category"], a[href*="page"]');
        if (!link || !link.href) return;
        
        // Liste des pages à exclure du comportement AJAX
        const excludedPages = ['login', 'register', 'logout', 'userlist', 'orderView'];
        
        try {
            const url = new URL(link.href);
            const pageParam = url.searchParams.get('page');
            
            // Vérifier si le lien doit être géré de manière traditionnelle
            if (pageParam && excludedPages.includes(pageParam)) {
                return; // Laisser le comportement par défaut pour ces pages
            }
            
            // Vérifier si c'est un lien de connexion ou de commande
            if (link.getAttribute('data-nav') === 'auth' || link.getAttribute('data-nav') === 'order') {
                return; // Laisser le comportement par défaut
            }
            
            e.preventDefault();
            handleNavigation({
                page: url.searchParams.get('page'),
                category: url.searchParams.get('category'),
                search: url.searchParams.get('search')
            });
        } catch (error) {
            console.error('Erreur lors du parsing de l\'URL:', error);
        }
    });

    // Modification de la fonction handleNavigation
    async function handleNavigation(params = {}, pushState = true) {
        if (isUpdating) return;
        isUpdating = true;
        
        try {
            // Vérifier si params.page est un nombre valide
            if (params.page && !isNaN(params.page)) {
                // Si c'est juste un numéro de page sans autre contexte, ajuster l'URL
                if (Object.keys(params).length === 1 && params.page) {
                    params = {
                        ...params,
                        search: new URLSearchParams(window.location.search).get('search') || '',
                        category: new URLSearchParams(window.location.search).get('category') || null
                    };
                }
                
                const response = await fetchArticles(params.category, params.page, params.search);
                
                if (response.success) {
                    // Si aucun résultat, on n'affiche pas la pagination
                    if (!response.data || response.data.length === 0) {
                        updateArticlesDisplay([]);
                        // On force la pagination à être vide
                        const paginationContainer = document.querySelector('#pagination-container .pagination');
                        if (paginationContainer) {
                            paginationContainer.innerHTML = '';
                        }
                    } else {
                        updateArticlesDisplay(response.data);
                        updatePagination(response.pagination);
                    }
                    
                    if (pushState) {
                        const url = new URL(window.location.href);
                        // Mise à jour uniquement des paramètres nécessaires
                        url.searchParams.set('page', params.page);
                        if (params.category) {
                            url.searchParams.set('category', params.category);
                        } else {
                            url.searchParams.delete('category');
                        }
                        if (params.search) {
                            url.searchParams.set('search', params.search);
                        } else {
                            url.searchParams.delete('search');
                        }
                        history.pushState(params, '', url);
                    }
                }
            }
        } catch (error) {
            console.error('Erreur de navigation:', error);
            showToast('Erreur lors de la navigation', 'danger');
        } finally {
            isUpdating = false;
        }
    }

    // Modification de la gestion des clics sur les liens de pagination
    document.addEventListener('click', function(e) {
        const pageLink = e.target.closest('.page-link');
        if (!pageLink) return;
        
        e.preventDefault();
        
        const page = pageLink.dataset.page;
        if (!page) return;
        
        handleNavigation({
            page: parseInt(page),
            category: new URLSearchParams(window.location.search).get('category'),
            search: new URLSearchParams(window.location.search).get('search')
        });
    });

    // Ajouter la gestion du formulaire de recherche de la navbar
    const navSearchForm = document.getElementById('navSearchForm');
    if (navSearchForm) {
        const navSearchInput = document.getElementById('navSearchInput');
        const navSearchCategory = document.getElementById('navSearchCategory');

        // Créer une version debounced de la fonction de recherche
        const debouncedNavSearch = debounce(() => {
            const selectedCategory = navSearchCategory.value || null;
            handleNavigation({
                page: 1,
                category: selectedCategory, // Conserver la catégorie sélectionnée
                search: navSearchInput.value.trim()
            });
        }, 300);

        // Appliquer le debounce sur l'input
        navSearchInput.addEventListener('input', debouncedNavSearch);

        // Appliquer le debounce sur le changement de catégorie
        navSearchCategory.addEventListener('change', debouncedNavSearch);

        // Garder le comportement immédiat sur la soumission du formulaire
        navSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedCategory = navSearchCategory.value || null;
            handleNavigation({
                page: 1,
                category: selectedCategory, // Conserver la catégorie sélectionnée
                search: navSearchInput.value.trim()
            });
        });
    }
});
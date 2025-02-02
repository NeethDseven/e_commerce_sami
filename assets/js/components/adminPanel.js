import { 
    debounce, 
    fetchArticles,  
    saveArticle, 
    deleteArticle, 
    updateTableContent,
    updatePagination,
} from '../services/adminPanelService.js';

const state = {
    searchTerm: '',
    category: '',
    isLoading: false,
    showOnlyPromos: false
};

const showToast = (message, type = 'success') => {
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.getElementById('toast-container').appendChild(toastEl);
    
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
};

const handlePromotionFields = (show) => {
    const promotionFields = document.getElementById('promotion_fields');
    promotionFields.style.display = show ? 'block' : 'none';
    
    promotionFields.querySelectorAll('input').forEach(field => {
        if (show) {
            field.setAttribute('required', 'required');
        } else {
            field.removeAttribute('required');
        }
    });
};

const calculatePromotionalPrice = (prix, reduction) => {
    const prixNormal = parseFloat(prix);
    const reductionExacte = parseFloat(reduction);
    const prixPromo = prixNormal * (1 - (reductionExacte / 100));
    const prixCalculeDiv = document.getElementById('prix_calcule');
    if (prixCalculeDiv) {
        prixCalculeDiv.textContent = `Prix promotionnel calculé : ${prixPromo.toFixed(2)}€ (-${reductionExacte.toFixed(2)}%)`;
    }
};

document.addEventListener('DOMContentLoaded', async () => {
    const elements = {
        form: document.getElementById('articleForm'),
        modal: document.getElementById('articleModal'),
        search: document.getElementById('searchInput'),
        category: document.getElementById('searchCategory'),
        promoCheckbox: document.getElementById('has_promotion'),
        reductionInput: document.getElementById('reduction_percent'),
        priceInput: document.getElementById('article_prix'),
        filterPromosBtn: document.getElementById('filterPromos'),
        pagination: document.querySelector('#pagination'),
        articleTable: document.querySelector('.table-articles'), 
    
    };

    elements.promoCheckbox?.addEventListener('change', (e) => {
        handlePromotionFields(e.target.checked);
        if (!e.target.checked) {
            const prixCalculeDiv = document.getElementById('prix_calcule');
            if (prixCalculeDiv) {
                prixCalculeDiv.textContent = '';
            }
        }
    });

    elements.reductionInput?.addEventListener('input', (e) => {
        let reduction = parseFloat(e.target.value);
        const prix = parseFloat(elements.priceInput.value);
        
        if (!prix || isNaN(prix) || prix <= 0) {
            showToast('Veuillez d\'abord saisir un prix normal valide', 'warning');
            e.target.value = '';
            return;
        }
        
        if (!isNaN(reduction)) {
            reduction = Math.min(60, Math.max(0, reduction));
            e.target.value = reduction.toString();
            calculatePromotionalPrice(prix, reduction);
        }
    });

    elements.modal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        elements.form.reset();
        
        document.getElementById('id_article').value = '';
        
        if (!button?.dataset.bsArticle) {
            elements.promoCheckbox.checked = false;
            handlePromotionFields(false);
            return;
        }
        
<<<<<<< HEAD
=======
        // Mode édition
>>>>>>> origin/develop
        const article = JSON.parse(button.dataset.bsArticle);
        if (article) {
            Object.entries({
                'id_article': article.id_article,
                'article_nom': article.nom,
                'article_description': article.description,
                'article_prix': article.prix,
                'article_stock': article.stock,
                'categorie': article.id_categorie
            }).forEach(([id, value]) => {
                document.getElementById(id).value = value;
            });

<<<<<<< HEAD
=======
            // Gestion promotion
>>>>>>> origin/develop
            const hasPromotion = article.prix_promotionnel !== null;
            elements.promoCheckbox.checked = hasPromotion;
            handlePromotionFields(hasPromotion);

            if (hasPromotion && article.pourcentage_reduction) {
                const reductionPercent = parseFloat(article.pourcentage_reduction);
                document.getElementById('reduction_percent').value = reductionPercent;
                document.getElementById('date_debut').value = article.date_debut?.split(' ')[0] || '';
                document.getElementById('date_fin').value = article.date_fin?.split(' ')[0] || '';
                calculatePromotionalPrice(article.prix, reductionPercent);
            }
        }
    });

<<<<<<< HEAD
=======
    // Ajouter un gestionnaire pour le modal qui se ferme
>>>>>>> origin/develop
    elements.modal.addEventListener('hidden.bs.modal', () => {
        elements.form.reset();
        document.getElementById('id_article').value = '';
        document.getElementById('prix_calcule').textContent = '';
        handlePromotionFields(false);
    });

<<<<<<< HEAD
=======
    // Search handler
>>>>>>> origin/develop
    const handleSearch = async (page = 1) => {
        if (state.isLoading) return;
        state.isLoading = true;
        
        try {
            const data = await fetchArticles(page, {
                search: state.searchTerm,
                category: state.category,
                promos_only: state.showOnlyPromos
            });
            
            if (data.success && data.data) {
<<<<<<< HEAD
=======
                // Mise à jour du contenu et de la pagination
>>>>>>> origin/develop
                updateTableContent(data.data.articles);
                if (data.data.pagination) {
                    updatePagination(data.data.pagination);
                }
            } else {
                throw new Error('Données invalides reçues du serveur');
            }
        } catch (error) {
            showToast(error.message || 'Erreur lors de la recherche', 'danger');
        } finally {
            state.isLoading = false;
        }
    };

<<<<<<< HEAD
=======
    // Event listeners
>>>>>>> origin/develop
    elements.search.addEventListener('input', debounce(() => {
        state.searchTerm = elements.search.value.trim();
        handleSearch(1);
    }, 500));

    elements.category.addEventListener('change', () => {
        state.category = elements.category.value;
        handleSearch(1);
    });

    elements.filterPromosBtn?.addEventListener('click', () => {
        state.showOnlyPromos = !state.showOnlyPromos;
        elements.filterPromosBtn.classList.toggle('active');
        handleSearch(1);
    });

    elements.form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = e.submitter;
        if (submitButton) submitButton.disabled = true;

        try {
            const formData = new FormData(elements.form);
            const hasPromotion = elements.promoCheckbox.checked;
<<<<<<< HEAD
            formData.set('has_promotion', hasPromotion ? 'on' : 'off');
            
            const result = await saveArticle(formData);
            
            if (result.success) {
                if (result.article) {
                    const articleRow = document.querySelector(`tr[data-article-id="${result.article.id_article}"]`);
                    if (articleRow) {
                        const newRow = createArticleRow(result.article);
                        articleRow.replaceWith(newRow);
                    } else {
                        const tbody = document.querySelector('#articlesTable');
                        tbody.insertAdjacentHTML('afterbegin', createArticleRow(result.article));
                    }

                    if (hasPromotion) {
                        updatePromotionsSection();
                    }
                }
                
                bootstrap.Modal.getInstance(elements.modal)?.hide();
                showToast(result.message || 'Article sauvegardé avec succès', 'success');
            } else {
                throw new Error(result.error || 'Erreur lors de la sauvegarde');
            }
=======
            
            // Ajouter explicitement has_promotion
            formData.set('has_promotion', hasPromotion ? 'on' : 'off');
            
            if (!hasPromotion) {
                // Si pas de promotion, supprimer les champs liés
                formData.delete('reduction_percent');
                formData.delete('date_debut');
                formData.delete('date_fin');
            } else {
                // Vérifier les champs requis pour la promotion
                const reduction = formData.get('reduction_percent');
                const dateDebut = formData.get('date_debut');
                const dateFin = formData.get('date_fin');
                
                if (!reduction || !dateDebut || !dateFin) {
                    showToast('Tous les champs de promotion sont requis', 'warning');
                    if (submitButton) submitButton.disabled = false;
                    return;
                }
            }

            const result = await saveArticle(formData);
            bootstrap.Modal.getInstance(elements.modal)?.hide();
            await handleSearch(1);
            showToast(result.message || 'Article sauvegardé avec succès', 'success');
>>>>>>> origin/develop
        } catch (error) {
            showToast(error.message, 'danger');
        } finally {
            if (submitButton) submitButton.disabled = false;
            elements.form.reset();
        }
    });

<<<<<<< HEAD
    async function updatePromotionsSection() {
        try {
            const response = await fetch('index.php?controller=article&action=promotions', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success && data.promotions) {
                const promotionsContainer = document.querySelector('#promotions-container');
                if (promotionsContainer) {
                    refreshPromotionsDisplay(promotionsContainer, data.promotions);
                }
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour des promotions:', error);
        }
    }

    function refreshPromotionsDisplay(container, promotions) {
        container.innerHTML = promotions
            .filter(promo => 
                promo.prix_promotionnel < promo.prix && 
                parseFloat(promo.pourcentage_reduction) > 0
            )
            .map(promo => {
                const reduction = Math.abs(parseFloat(promo.pourcentage_reduction));
                return `
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="assets/images/articles/${promo.image}" 
                                     class="card-img-top" 
                                     alt="${promo.nom}"
                                     style="height: 200px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0 p-2">
                                    <span class="badge bg-danger">-${reduction.toFixed(1)}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
    }

    function createArticleRow(article) {
        const reduction = article.pourcentage_reduction ? Math.abs(parseFloat(article.pourcentage_reduction)) : null;
        return `
            <tr data-article-id="${article.id_article}" ${article.prix_promotionnel ? 'class="table-warning"' : ''}>
                <td class="text-center">${article.id_article}</td>
                <td class="text-center">
                    <img src="assets/images/articles/${article.image || ''}" 
                         alt="${article.nom}" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td>${article.nom}</td>
                <td class="text-end">${parseFloat(article.prix).toFixed(2)}€</td>
                <td class="text-end">
                    ${article.prix_promotionnel 
                        ? `<span class="text-danger fw-bold">${parseFloat(article.prix_promotionnel).toFixed(2)}€</span>`
                        : '-'}
                </td>
                <td class="text-center">
                    ${reduction 
                        ? `<span class="badge bg-danger">-${reduction.toFixed(1)}%</span>`
                        : '-'}
                </td>
                <td class="text-center">
                    ${article.promo_date_debut && article.promo_date_fin
                        ? `Du ${new Date(article.promo_date_debut).toLocaleDateString()}<br>
                           au ${new Date(article.promo_date_fin).toLocaleDateString()}`
                        : '-'}
                </td>
                <td class="text-center">${article.stock}</td>
                <td>${article.categorie_nom || 'Non catégorisé'}</td>
                <td class="text-end">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal"
                                data-bs-target="#articleModal"
                                data-bs-article='${JSON.stringify(article)}'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-article" 
                                data-id="${article.id_article}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

=======
    // Ajouter l'écouteur d'événements pour la pagination
>>>>>>> origin/develop
    elements.pagination?.addEventListener('click', async (e) => {
        e.preventDefault();
        const pageLink = e.target.closest('.page-link');
        if (!pageLink) return;
        
        const pageNum = parseInt(pageLink.getAttribute('data-page'));
        if (!pageNum) return;

        try {
            await handleSearch(pageNum);
        } catch (error) {
            showToast('Erreur lors du changement de page: ' + error.message, 'danger');
        }
    });

<<<<<<< HEAD
=======
    // Remplacer l'écouteur d'événements pour la suppression d'articles
>>>>>>> origin/develop
    document.addEventListener('click', async (e) => {
        const deleteButton = e.target.closest('.delete-article');
        if (!deleteButton) return;
        
        e.preventDefault();
        if (deleteButton.disabled) return;
        
        const articleId = deleteButton.dataset.id;
        if (!articleId) {
            showToast('ID d\'article manquant', 'danger');
            return;
        }

        if (!confirm('Voulez-vous vraiment supprimer cet article ?')) return;
        
        deleteButton.disabled = true;
        try {
            await deleteArticle(articleId);
            await handleSearch(1);
            showToast('Article supprimé avec succès', 'success');
        } catch (error) {
            showToast(error.message, 'danger');
        } finally {
            deleteButton.disabled = false;
        }
    });

<<<<<<< HEAD
=======
    // Initial load
>>>>>>> origin/develop
    try {
        const data = await fetchArticles(1);
        if (data.data?.articles) {
            updateTableContent(data.data.articles);
            updatePagination(data.data.pagination);
        }
    } catch (error) {
        showToast('Erreur lors du chargement initial : ' + error.message, 'danger');
    }
});

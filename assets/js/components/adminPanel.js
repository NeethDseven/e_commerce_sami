import { 
    debounce, 
    fetchArticles,  
    saveArticle, 
    deleteArticle, 
    updateTableContent,
    updatePagination,
} from '../services/adminPanelService.js';

// Variables globales
const state = {
    searchTerm: '',
    category: '',
    isLoading: false,
    showOnlyPromos: false
};

// Utilitaires
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
    
    // Modifier la gestion des champs required
    promotionFields.querySelectorAll('input').forEach(field => {
        if (show) {
            field.setAttribute('required', 'required');
        } else {
            field.removeAttribute('required');
            // Ne pas vider les valeurs ici
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

// Gestionnaire principal
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

    // Gestionnaires d'événements
    elements.promoCheckbox?.addEventListener('change', (e) => {
        handlePromotionFields(e.target.checked);
        // Réinitialiser le message de prix calculé si on désactive les promotions
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
            // Garder la précision décimale
            reduction = Math.min(60, Math.max(0, reduction));
            e.target.value = reduction.toString(); // Garder la valeur exacte
            calculatePromotionalPrice(prix, reduction);
        }
    });

    // Modal handler
    elements.modal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        elements.form.reset(); // Reset le formulaire d'abord
        
        // Réinitialiser explicitement l'ID
        document.getElementById('id_article').value = '';
        
        if (!button?.dataset.bsArticle) {
            // Mode création
            elements.promoCheckbox.checked = false;
            handlePromotionFields(false);
            return;
        }
        
        // Mode édition
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

            // Gestion promotion
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

    // Ajouter un gestionnaire pour le modal qui se ferme
    elements.modal.addEventListener('hidden.bs.modal', () => {
        elements.form.reset();
        document.getElementById('id_article').value = '';
        document.getElementById('prix_calcule').textContent = '';
        handlePromotionFields(false);
    });

    // Search handler
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
                // Mise à jour du contenu et de la pagination
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

    // Event listeners
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
        } catch (error) {
            showToast(error.message, 'danger');
        } finally {
            if (submitButton) submitButton.disabled = false;
            elements.form.reset();
        }
    });

    // Ajouter l'écouteur d'événements pour la pagination
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

    // Remplacer l'écouteur d'événements pour la suppression d'articles
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

    // Initial load
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

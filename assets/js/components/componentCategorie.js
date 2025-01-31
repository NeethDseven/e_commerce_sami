import { fetchCategories } from '../services/serviceCategorie.js';

const initCategories = async () => {
    try {
        const result = await fetchCategories();
        if (!result.success) throw new Error('Erreur lors du chargement des catégories');

        const categories = result.data;
        const categoryContainer = document.getElementById('category-buttons');
        const urlParams = new URLSearchParams(window.location.search);
        const currentCategory = urlParams.get('category');
        
        const html = `
            <button class="btn btn-outline-primary category-btn ${!currentCategory ? 'active' : ''}" 
                    data-category="">
                Tous les articles
            </button>
            ${categories.map(category => `
                <button class="btn btn-outline-primary category-btn ${currentCategory === category.id_categorie.toString() ? 'active' : ''}"
                        data-category="${category.id_categorie}">
                    ${category.nom}
                </button>
            `).join('')}
        `;
        
        categoryContainer.innerHTML = html;

        // Gestionnaire d'événements amélioré pour les boutons de catégorie
        categoryContainer.addEventListener('click', async (e) => {
            const button = e.target.closest('.category-btn');
            if (!button) return;
            
            e.preventDefault();
            
            // Mettre à jour visuellement les boutons immédiatement
            categoryContainer.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            const categoryId = button.dataset.category;
            const url = new URL(window.location);
            
            if (categoryId) {
                url.searchParams.set('category', categoryId);
            } else {
                url.searchParams.delete('category');
            }
            url.searchParams.set('page', '1');
            
            // Mettre à jour l'URL sans recharger la page
            window.history.pushState({}, '', url);
            
            // Déclencher l'événement de changement de catégorie
            window.dispatchEvent(new CustomEvent('categoryChange', { 
                detail: { categoryId } 
            }));
        });
    } catch (error) {
        console.error('Error initializing categories:', error);
        document.getElementById('category-buttons').innerHTML = 
            '<div class="alert alert-danger">Erreur lors du chargement des catégories</div>';
    }
};

document.addEventListener('DOMContentLoaded', initCategories);

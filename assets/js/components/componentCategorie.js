import { fetchCategories } from '../services/serviceCategorie.js';

async function initCategories() {
    try {
        const categoryButtons = document.getElementById('category-buttons');
        if (!categoryButtons) return;

        const categories = await fetchCategories();
        let html = `
            <button class="btn btn-outline-primary category-btn active" data-category="">
                Toutes les catégories
            </button>
        `;

        if (Array.isArray(categories)) {
            categories.forEach(category => {
                html += `
                    <button class="btn btn-outline-primary category-btn" 
                            data-category="${category.id_categorie}">
                        ${category.nom}
                    </button>
                `;
            });
        }

        categoryButtons.innerHTML = html;

        // Ajouter les écouteurs d'événements après avoir inséré les boutons
        categoryButtons.addEventListener('click', handleCategoryClick);
    } catch (error) {
        const categoryButtons = document.getElementById('category-buttons');
        if (categoryButtons) {
            categoryButtons.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des catégories</div>';
        }
    }
}

function handleCategoryClick(event) {
    const button = event.target.closest('.category-btn');
    if (!button) return;

    // Retirer la classe active de tous les boutons
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Ajouter la classe active au bouton cliqué
    button.classList.add('active');

    // Récupérer l'ID de la catégorie et déclencher l'événement
    const categoryId = button.dataset.category || null;
    
    // Créer et dispatcher l'événement
    const customEvent = new CustomEvent('categoryChange', {
        detail: { categoryId }
    });
    window.dispatchEvent(customEvent);
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', initCategories);

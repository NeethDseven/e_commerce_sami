import { fetchCategories } from '../services/serviceCategorie.js';

async function initCategories() {
    try {
        const categoryButtons = document.getElementById('category-buttons');
        if (!categoryButtons) return;

        const categories = await fetchCategories();
<<<<<<< HEAD

=======
>>>>>>> origin/develop
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
<<<<<<< HEAD
        categoryButtons.addEventListener('click', handleCategoryClick);
    } catch (error) {
        console.error('Error initializing categories:', error);
        const categoryButtons = document.getElementById('category-buttons');
        if (categoryButtons) {
            categoryButtons.innerHTML = `<div class="alert alert-danger">
                Erreur lors du chargement des catégories: ${error.message}
            </div>`;
=======

        // Ajouter les écouteurs d'événements après avoir inséré les boutons
        categoryButtons.addEventListener('click', handleCategoryClick);
    } catch (error) {
        const categoryButtons = document.getElementById('category-buttons');
        if (categoryButtons) {
            categoryButtons.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des catégories</div>';
>>>>>>> origin/develop
        }
    }
}

function handleCategoryClick(event) {
    const button = event.target.closest('.category-btn');
    if (!button) return;

<<<<<<< HEAD
    if (button.classList.contains('active')) return;

=======
    // Retirer la classe active de tous les boutons
>>>>>>> origin/develop
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });

<<<<<<< HEAD
    button.classList.add('active');

    const categoryId = button.dataset.category || null;
    
=======
    // Ajouter la classe active au bouton cliqué
    button.classList.add('active');

    // Récupérer l'ID de la catégorie et déclencher l'événement
    const categoryId = button.dataset.category || null;
    
    // Créer et dispatcher l'événement
>>>>>>> origin/develop
    const customEvent = new CustomEvent('categoryChange', {
        detail: { categoryId }
    });
    window.dispatchEvent(customEvent);
}

<<<<<<< HEAD
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCategories);
} else {
    initCategories();
}
=======
// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', initCategories);
>>>>>>> origin/develop

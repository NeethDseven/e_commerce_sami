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
        categoryButtons.addEventListener('click', handleCategoryClick);
    } catch (error) {
        console.error('Error initializing categories:', error);
        const categoryButtons = document.getElementById('category-buttons');
        if (categoryButtons) {
            categoryButtons.innerHTML = `<div class="alert alert-danger">
                Erreur lors du chargement des catégories: ${error.message}
            </div>`;
        }
    }
}

function handleCategoryClick(event) {
    const button = event.target.closest('.category-btn');
    if (!button) return;

    if (button.classList.contains('active')) return;

    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    button.classList.add('active');

    const categoryId = button.dataset.category || null;
    
    const customEvent = new CustomEvent('categoryChange', {
        detail: { categoryId }
    });
    window.dispatchEvent(customEvent);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCategories);
} else {
    initCategories();
}

import { showToast } from "./shared/toast.js";
import { fetchCategoriesForAdmin } from '../services/serviceCategorie.js';

<<<<<<< HEAD
const BASE_URL = '/projet/e_commerce_sami-develop';

let isSortMode = false;

=======
// Ajouter ces variables au début du fichier
let isSortMode = false;

// Modifier initSortable
>>>>>>> origin/develop
function initSortable(enabled = false) {
    const tbody = document.getElementById('categoriesTable');
    if (!tbody) return;

    try {
        if (window.sortableInstance) {
            window.sortableInstance.destroy();
            window.sortableInstance = null;
        }
    } catch (error) {
<<<<<<< HEAD
        console.warn('Error destroying Sortable instance:', error);
=======
        console.warn('Erreur lors de la destruction de l\'instance Sortable:', error);
>>>>>>> origin/develop
    }

    if (enabled) {
        window.sortableInstance = new Sortable(tbody, {
            animation: 150,
            handle: '.handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
        });
    }
}

<<<<<<< HEAD
=======
// Déplacer loadCategories en dehors pour la rendre accessible globalement
>>>>>>> origin/develop
async function loadCategories() {
    try {
        const result = await fetchCategoriesForAdmin();
        if (result.success) {
            displayCategories(result.categories);
        } else {
<<<<<<< HEAD
            showToast('Error loading categories', 'bg-danger');
        }
    } catch (error) {
        console.error('Error loadCategories:', error);
        showToast('Error loading categories', 'bg-danger');
    }
}

=======
            showToast('Erreur lors du chargement des catégories', 'bg-danger');
        }
    } catch (error) {
        console.error('Erreur loadCategories:', error);
        showToast('Erreur lors du chargement des catégories', 'bg-danger');
    }
}

// Déplacer displayCategories en dehors aussi
// Modifier displayCategories pour prendre en compte le mode de tri
>>>>>>> origin/develop
function displayCategories(categories) {
    const tbody = document.getElementById('categoriesTable');
    if (!tbody) return;

<<<<<<< HEAD
=======
    // Désactiver Sortable avant de modifier le contenu
>>>>>>> origin/develop
    if (window.sortableInstance) {
        try {
            window.sortableInstance.destroy();
            window.sortableInstance = null;
        } catch (error) {
<<<<<<< HEAD
            console.warn('Error destroying Sortable:', error);
        }
    }

=======
            console.warn('Erreur lors de la destruction de Sortable:', error);
        }
    }

    // Assurons-nous que les catégories sont triées par ordre
>>>>>>> origin/develop
    categories.sort((a, b) => a.ordre - b.ordre);
    
    tbody.innerHTML = categories.map(category => `
        <tr data-category-id="${category.id_categorie}" data-order="${category.ordre}">
            <td class="handle">
                <i class="fas fa-bars drag-icon"></i>
                ${category.id_categorie}
            </td>
            <td>${category.nom}</td>
            <td class="ordre-cell">${category.ordre}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-2" 
                        onclick="editCategory(${category.id_categorie})">
<<<<<<< HEAD
                    Edit
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteCategory(${category.id_categorie})">
                    Delete
=======
                    Modifier
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteCategory(${category.id_categorie})">
                    Supprimer
>>>>>>> origin/develop
                </button>
            </td>
        </tr>
    `).join('');

    if (isSortMode) {
        initSortable(true);
        tbody.classList.add('sorting-mode');
    } else {
        tbody.classList.remove('sorting-mode');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('categoryModal');
    const categoryModal = new bootstrap.Modal(modalElement);
    const categoryForm = document.getElementById('categoryForm');
    const saveButton = document.getElementById('saveCategory');
    const addButton = document.querySelector('[data-bs-target="#categoryModal"]');
<<<<<<< HEAD

    loadCategories();
    
=======
    const categoriesTable = document.getElementById('categoriesTable');
    let sortable;

    loadCategories(); // Premier chargement
    
    // Réinitialiser le formulaire quand on clique sur "Ajouter une Catégorie"
>>>>>>> origin/develop
    addButton.addEventListener('click', () => {
        categoryForm.reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('category_nom').value = '';
        document.getElementById('category_ordre').value = '0';
    });
    
<<<<<<< HEAD
=======
    // Gestionnaire pour le bouton de sauvegarde
>>>>>>> origin/develop
    saveButton.addEventListener('click', async () => {
        if (!categoryForm.checkValidity()) {
            categoryForm.reportValidity();
            return;
        }
        
        const formData = new FormData(categoryForm);
        const categoryId = formData.get('id_categorie');
        const isEditing = categoryId && categoryId.trim() !== '';
        
        try {
            if (!isEditing) {
                formData.delete('id_categorie');
            }
            
<<<<<<< HEAD
            const response = await fetch(`${BASE_URL}/index.php?controller=category&action=${isEditing ? 'update' : 'create'}`, {
=======
            const response = await fetch('/Projet/ecommercesami/index.php?controller=category&action=' + (isEditing ? 'update' : 'create'), {
>>>>>>> origin/develop
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
<<<<<<< HEAD
                showToast(isEditing ? 'Category updated' : 'Category created', 'bg-success');
                categoryModal.hide();
=======
                showToast(isEditing ? 'Catégorie mise à jour' : 'Catégorie créée', 'bg-success');
                categoryModal.hide();
                // Nettoyer la modal et l'arrière-plan
>>>>>>> origin/develop
                const modalBackdrop = document.querySelector('.modal-backdrop');
                if (modalBackdrop) {
                    modalBackdrop.remove();
                }
                document.body.classList.remove('modal-open');
                await loadCategories();
                categoryForm.reset();
<<<<<<< HEAD
                modalElement.style.display = 'none';
            } else {
                showToast(result.error || 'An error occurred', 'bg-danger');
            }
        } catch (error) {
            showToast('Error saving', 'bg-danger');
=======
                modalElement.style.display = 'none'; // Forcer la fermeture
            } else {
                showToast(result.error || 'Une erreur est survenue', 'bg-danger');
            }
        } catch (error) {
            showToast('Erreur lors de la sauvegarde', 'bg-danger');
>>>>>>> origin/develop
        }
    });

    const toggleSortModeBtn = document.getElementById('toggleSortMode');
    const saveSortOrderBtn = document.getElementById('saveSortOrder');

    if (toggleSortModeBtn) {
        toggleSortModeBtn.addEventListener('click', () => {
            isSortMode = !isSortMode;
            toggleSortModeBtn.classList.toggle('btn-warning');
            toggleSortModeBtn.classList.toggle('btn-danger');
            saveSortOrderBtn.style.display = isSortMode ? 'inline-block' : 'none';
            
<<<<<<< HEAD
            toggleSortModeBtn.innerHTML = isSortMode ? 
                '<i class="fas fa-times"></i> Cancel sorting' : 
                '<i class="fas fa-sort"></i> Sort mode';
                
            initSortable(isSortMode);
            
=======
            // Mettre à jour le texte du bouton
            toggleSortModeBtn.innerHTML = isSortMode ? 
                '<i class="fas fa-times"></i> Annuler le tri' : 
                '<i class="fas fa-sort"></i> Mode tri';
                
            // Activer/désactiver le tri
            initSortable(isSortMode);
            
            // Mettre à jour l'apparence du tableau
>>>>>>> origin/develop
            const tbody = document.getElementById('categoriesTable');
            if (tbody) {
                tbody.style.cursor = isSortMode ? 'move' : 'default';
                tbody.classList.toggle('sorting-mode', isSortMode);
                initSortable(isSortMode);
            }
        });
    }

    saveSortOrderBtn.addEventListener('click', async () => {
        const tbody = document.getElementById('categoriesTable');
        const newOrder = Array.from(tbody.getElementsByTagName('tr')).map(
            row => row.dataset.categoryId
        );
        
        try {
            const orderData = newOrder.map((categoryId, index) => ({
                id_categorie: parseInt(categoryId),
                ordre: index + 1
            }));

<<<<<<< HEAD
            const response = await fetch(`${BASE_URL}/index.php?controller=category&action=updateOrder`, {
=======
            const response = await fetch('/Projet/ecommercesami/index.php?controller=category&action=updateOrder', {
>>>>>>> origin/develop
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ categories: orderData })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();

            if (data.success) {
                const result = await fetchCategoriesForAdmin();
                if (result.success) {
<<<<<<< HEAD
                    isSortMode = false;
                    toggleSortModeBtn.classList.remove('btn-danger');
                    toggleSortModeBtn.classList.add('btn-warning');
                    toggleSortModeBtn.innerHTML = '<i class="fas fa-sort"></i> Sort mode';
                    saveSortOrderBtn.style.display = 'none';

                    tbody.classList.remove('sorting-mode');
                    displayCategories(result.categories);
                    
                    showToast('Category order updated', 'bg-success');
                }
            } else {
                throw new Error(data.error || 'Error updating order');
            }
        } catch (error) {
            showToast('Error updating order', 'bg-danger');
        }
    });

    initSortable(false);
});

window.loadCategories = loadCategories;

window.editCategory = async (id) => {
    try {
        const response = await fetch(`${BASE_URL}/index.php?controller=category&action=get&id=${id}`, {
=======
                    // Désactiver le mode tri
                    isSortMode = false;
                    toggleSortModeBtn.classList.remove('btn-danger');
                    toggleSortModeBtn.classList.add('btn-warning');
                    toggleSortModeBtn.innerHTML = '<i class="fas fa-sort"></i> Mode tri';
                    saveSortOrderBtn.style.display = 'none';

                    // Retirer la classe sorting-mode et réinitialiser l'affichage
                    tbody.classList.remove('sorting-mode');
                    displayCategories(result.categories);
                    
                    showToast('Ordre des catégories mis à jour', 'bg-success');
                }
            } else {
                throw new Error(data.error || 'Erreur lors de la mise à jour');
            }
        } catch (error) {
            showToast('Erreur lors de la mise à jour de l\'ordre', 'bg-danger');
        }
    });

    // Initialiser sans le tri activé
    initSortable(false);
});

// Rendre loadCategories accessible globalement
window.loadCategories = loadCategories;

// Fonctions globales pour l'édition et la suppression
window.editCategory = async (id) => {
    try {
        const response = await fetch(`/Projet/ecommercesami/index.php?controller=category&action=get&id=${id}`, {
>>>>>>> origin/develop
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const modalElement = document.getElementById('categoryModal');
            const form = document.getElementById('categoryForm');
            form.querySelector('#categoryId').value = data.category.id_categorie;
            form.querySelector('#category_nom').value = data.category.nom;
            form.querySelector('#category_ordre').value = data.category.ordre;
            
            const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modal.show();
        } else {
<<<<<<< HEAD
            throw new Error(data.error || 'Error loading category');
        }
    } catch (error) {
        showToast('Error loading category', 'bg-danger');
=======
            throw new Error(data.error || 'Erreur lors du chargement de la catégorie');
        }
    } catch (error) {
        showToast('Erreur lors du chargement de la catégorie', 'bg-danger');
>>>>>>> origin/develop
    }
};

window.deleteCategory = async (id) => {
<<<<<<< HEAD
    if (!confirm('Warning: Deleting this category will also delete all associated items. Do you want to continue?')) {
=======
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
>>>>>>> origin/develop
        return;
    }
    
    try {
<<<<<<< HEAD
        const response = await fetch(`${BASE_URL}/index.php?controller=category&action=delete`, {
=======
        const response = await fetch('/Projet/ecommercesami/index.php?controller=category&action=delete', {
>>>>>>> origin/develop
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id_categorie: id })
        });
        
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || `HTTP error: ${response.status}`);
        }
        
        if (result.success) {
<<<<<<< HEAD
            showToast('Category and associated items successfully deleted', 'bg-success');
            await window.loadCategories();
        } else {
            throw new Error(result.error || 'Error deleting');
        }
    } catch (error) {
        showToast('Error deleting: ' + error.message, 'bg-danger');
    }
};
=======
            showToast(result.message || 'Catégorie supprimée avec succès', 'bg-success');
            await window.loadCategories();
        } else {
            throw new Error(result.error || 'Erreur lors de la suppression');
        }
    } catch (error) {
        showToast('Erreur lors de la suppression: ' + error.message, 'bg-danger');
    }
};

// Fonction pour mettre à jour l'ordre des catégories
async function updateCategoriesOrder(newOrder) {
    try {
        // Préparer les données pour l'ordre
        const orderData = newOrder.map((categoryId, index) => ({
            id_categorie: parseInt(categoryId),
            ordre: index + 1
        }));

        // Envoyer la mise à jour au serveur
        const response = await fetch('index.php?controller=category&action=updateOrder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ categories: orderData })
        });
        
        const data = await response.json();
        if (data.success) {
            // Mettre à jour l'affichage immédiatement après la réponse du serveur
            const result = await fetchCategoriesForAdmin();
            if (result.success) {
                displayCategories(result.categories);
                // Afficher le toast seulement après la mise à jour réussie de l'affichage
                showToast('Ordre des catégories mis à jour', 'bg-success');
            }
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        showToast('Erreur lors de la mise à jour de l\'ordre', 'bg-danger');
        await loadCategories(); // Recharger en cas d'erreur
    }
}
>>>>>>> origin/develop

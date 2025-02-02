import { showToast } from "./shared/toast.js";
import { fetchCategoriesForAdmin } from '../services/serviceCategorie.js';

const BASE_URL = '/projet/e_commerce_sami-develop';

let isSortMode = false;

function initSortable(enabled = false) {
    const tbody = document.getElementById('categoriesTable');
    if (!tbody) return;

    try {
        if (window.sortableInstance) {
            window.sortableInstance.destroy();
            window.sortableInstance = null;
        }
    } catch (error) {
        console.warn('Error destroying Sortable instance:', error);
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

async function loadCategories() {
    try {
        const result = await fetchCategoriesForAdmin();
        if (result.success) {
            displayCategories(result.categories);
        } else {
            showToast('Error loading categories', 'bg-danger');
        }
    } catch (error) {
        console.error('Error loadCategories:', error);
        showToast('Error loading categories', 'bg-danger');
    }
}

function displayCategories(categories) {
    const tbody = document.getElementById('categoriesTable');
    if (!tbody) return;

    if (window.sortableInstance) {
        try {
            window.sortableInstance.destroy();
            window.sortableInstance = null;
        } catch (error) {
            console.warn('Error destroying Sortable:', error);
        }
    }

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
                    Edit
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteCategory(${category.id_categorie})">
                    Delete
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

    loadCategories();
    
    addButton.addEventListener('click', () => {
        categoryForm.reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('category_nom').value = '';
        document.getElementById('category_ordre').value = '0';
    });
    
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
            
            const response = await fetch(`${BASE_URL}/index.php?controller=category&action=${isEditing ? 'update' : 'create'}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(isEditing ? 'Category updated' : 'Category created', 'bg-success');
                categoryModal.hide();
                const modalBackdrop = document.querySelector('.modal-backdrop');
                if (modalBackdrop) {
                    modalBackdrop.remove();
                }
                document.body.classList.remove('modal-open');
                await loadCategories();
                categoryForm.reset();
                modalElement.style.display = 'none';
            } else {
                showToast(result.error || 'An error occurred', 'bg-danger');
            }
        } catch (error) {
            showToast('Error saving', 'bg-danger');
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
            
            toggleSortModeBtn.innerHTML = isSortMode ? 
                '<i class="fas fa-times"></i> Cancel sorting' : 
                '<i class="fas fa-sort"></i> Sort mode';
                
            initSortable(isSortMode);
            
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

            const response = await fetch(`${BASE_URL}/index.php?controller=category&action=updateOrder`, {
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
            throw new Error(data.error || 'Error loading category');
        }
    } catch (error) {
        showToast('Error loading category', 'bg-danger');
    }
};

window.deleteCategory = async (id) => {
    if (!confirm('Warning: Deleting this category will also delete all associated items. Do you want to continue?')) {
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/index.php?controller=category&action=delete`, {
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
            showToast('Category and associated items successfully deleted', 'bg-success');
            await window.loadCategories();
        } else {
            throw new Error(result.error || 'Error deleting');
        }
    } catch (error) {
        showToast('Error deleting: ' + error.message, 'bg-danger');
    }
};

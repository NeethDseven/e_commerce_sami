import { getUtilisateurs, updateUtilisateur, deleteUtilisateur, createUtilisateur } from '../services/person.js';
import { showToast } from "./shared/toast.js";

document.addEventListener('DOMContentLoaded', async () => {
    const userListContainer = document.getElementById('user-list-container');
    const paginationContainer = document.getElementById('pagination-container');
    let currentSearch = '';
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');

    if (!userListContainer || !paginationContainer) {
        console.error('Conteneurs nécessaires non trouvés dans le DOM');
        return;
    }

    // Ajouter le spinner
    const spinnerHTML = `
        <div id="spinner" class="spinner-border text-primary d-none" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
    `;
    userListContainer.insertAdjacentHTML('beforebegin', spinnerHTML);
    const spinner = document.querySelector('#spinner');

    let currentPage = 1;

    // Déplacer refreshUserList en dehors du DOMContentLoaded pour le rendre accessible globalement
    // Fonction debounce pour la recherche
    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // Fonction pour mettre à jour l'URL
    const updateURL = (page, search = '') => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'userlist');
        url.searchParams.set('currentPage', page);
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        window.history.pushState({}, '', url);
    };

    // Fonction pour lire les paramètres de l'URL au chargement
    const loadURLParams = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const pageFromURL = parseInt(urlParams.get('currentPage')) || 1;
        const searchFromURL = urlParams.get('search') || '';
        
        if (searchFromURL) {
            searchInput.value = searchFromURL;
            currentSearch = searchFromURL;
        }
        
        return { page: pageFromURL, search: searchFromURL };
    };

    // Modifier refreshUserList pour inclure la mise à jour de l'URL
    window.refreshUserList = async (page = 1) => {
        if (spinner) spinner.classList.remove('d-none');
        try {
            const data = await getUtilisateurs(page, currentSearch);
            updateURL(page, currentSearch);
            
            const tableHtml = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.users.map(user => `
                            <tr>
                                <td>${user.id_utilisateur}</td>
                                <td>${user.nom}</td>
                                <td>${user.email}</td>
                                <td>${user.role}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary me-2 edit-user" data-id="${user.id_utilisateur}">
                                        <i class="fa fa-edit"></i> Modifier
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id_utilisateur}">
                                        <i class="fa fa-trash"></i> Supprimer
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            userListContainer.innerHTML = tableHtml;

            attachEventListeners();
            updatePagination(data.currentPage, data.totalPages);
        } catch (error) {
            showToast(error.message, 'bg-danger');
        } finally {
            if (spinner) spinner.classList.add('d-none');
        }
    };

    const attachEventListeners = () => {
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', handleEdit);
        });

        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', handleDelete);
        });
    };

    const updatePagination = (currentPage, totalPages) => {
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        const pages = [];
        for (let i = 1; i <= totalPages; i++) {
            pages.push(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" data-page="${i}">${i}</button>
                </li>
            `);
        }

        const paginationHtml = `
            <nav aria-label="Navigation des pages">
                <ul class="pagination justify-content-center">
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <button class="page-link" data-page="${currentPage - 1}">&laquo; Précédent</button>
                    </li>
                    ${pages.join('')}
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <button class="page-link" data-page="${currentPage + 1}">Suivant &raquo;</button>
                    </li>
                </ul>
            </nav>
        `;

        paginationContainer.innerHTML = paginationHtml;

        paginationContainer.querySelectorAll('.page-link').forEach(button => {
            button.addEventListener('click', async (e) => {
                const newPage = parseInt(e.target.dataset.page);
                if (!isNaN(newPage) && newPage > 0 && newPage <= totalPages) {
                    currentPage = newPage;
                    await refreshUserList(currentPage);
                }
            });
        });
    };

    const handleEdit = (e) => {
        const userId = e.target.dataset.id;
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        
        const row = e.target.closest('tr');
        const nom = row.cells[1].textContent;
        const email = row.cells[2].textContent;
        const role = row.cells[3].textContent;

        document.getElementById('edit_nom').value = nom;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        document.getElementById('editUserForm').dataset.userId = userId;

        editModal.show();
    };

    const handleDelete = async (e) => {
        const button = e.target;
        const userId = button.dataset.id;
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
            button.disabled = true;
            try {
                const result = await deleteUtilisateur(userId);
                if (result.success) {
                    await refreshUserList(currentPage);
                    showToast('Utilisateur supprimé avec succès', 'bg-success');
                } else if (!result.cancelled) {
                    showToast(result.message || 'Erreur lors de la suppression', 'bg-danger');
                }
            } catch (error) {
                showToast(error.message, 'bg-danger');
            } finally {
                button.disabled = false;
            }
        }
    };

    // Initialisation avec les paramètres de l'URL
    const { page, search } = loadURLParams();
    await refreshUserList(page);

    document.getElementById('editUserForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const userId = form.dataset.userId;
            const result = await updateUtilisateur(form, userId);
            
            if (result.success) {
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                editModal.hide();
                
                if (result.redirectUrl) {
                    window.location.href = result.redirectUrl;
                    return;
                }
                
                await refreshUserList(currentPage);
                showToast('Utilisateur modifié avec succès', 'bg-success');
            } else if (result.errors) {
                showToast(`Erreurs : ${result.errors.join(', ')}`, 'bg-danger');
            }
        } catch (error) {
            showToast(error.message, 'bg-danger');
        } finally {
            submitButton.disabled = false;
        }
    });

    // Remplacer le double gestionnaire d'événements par un seul
    const createUserForm = document.getElementById('createUserForm');
    if (createUserForm) {
        createUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const result = await createUtilisateur(form);
                if (result && result.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createUserModal'));
                    modal.hide();
                    form.reset();
                    await window.refreshUserList(1);
                    showToast('Utilisateur créé avec succès', 'bg-success');
                }
            } catch (error) {
                showToast(error.message || 'Erreur lors de la création', 'bg-danger');
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    // Gestionnaire de recherche
    const handleSearch = debounce(async () => {
        currentSearch = searchInput.value.trim();
        await refreshUserList(1); // Retour à la première page lors d'une recherche
    }, 300);

    // Événements de recherche
    searchInput.addEventListener('input', handleSearch);
    searchButton.addEventListener('click', handleSearch);

    // Ajout de la recherche par touche Entrée
    searchInput.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            await handleSearch();
        }
    });
});

// Supprimer cette fonction car elle fait double emploi
// async function handleCreateUser(event) { ... }

// Dans la fonction de validation du formulaire
const validateNom = (nom) => {
    // Vérification simple de la longueur
    if (nom.length < 2 || nom.length > 50) {
        return false;
    }
    // Vérification des caractères autorisés
    return /^[a-zA-Z0-9\s\-']+$/.test(nom);
};

// Dans l'événement submit du formulaire
document.getElementById('createUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    try {
        const nom = form.querySelector('[name="nom"]').value.trim();
        
        if (!validateNom(nom)) {
            throw new Error('Le nom contient des caractères non autorisés ou est trop court/long');
        }
        // ... reste du code ...
    } catch (error) {
        showToast(error.message, 'bg-danger');
    } finally {
        submitButton.disabled = false;
    }
});
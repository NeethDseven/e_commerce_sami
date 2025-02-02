import { getUtilisateurs, updateUtilisateur, deleteUtilisateur, createUtilisateur } from '../services/person.js';
import { showToast } from "./shared/toast.js";

const loadURLParams = () => ({
    page: parseInt(new URLSearchParams(window.location.search).get('currentPage')) || 1,
    search: new URLSearchParams(window.location.search).get('search') || ''
});

const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
};

const updateURL = (page, search = '') => {
    const url = new URL(window.location.href);
    url.searchParams.set('currentPage', page);
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    window.history.pushState({}, '', url);
};

document.addEventListener('DOMContentLoaded', async () => {
    const elements = {
        userListContainer: document.getElementById('user-list-container'),
        paginationContainer: document.getElementById('pagination-container'),
        searchInput: document.getElementById('searchInput'),
        searchButton: document.getElementById('searchButton'),
        editUserForm: document.getElementById('editUserForm'),
        createUserForm: document.getElementById('createUserForm'),
        spinner: null
    };

    let state = { currentPage: 1, currentSearch: '' };

    if (!elements.userListContainer || !elements.paginationContainer) return;

    elements.userListContainer.insertAdjacentHTML('beforebegin',
        '<div id="spinner" class="spinner-border text-primary d-none" role="status">' +
        '<span class="visually-hidden">Chargement...</span></div>'
    );
    elements.spinner = document.querySelector('#spinner');

    const handleSearch = debounce(async () => {
        state.currentSearch = elements.searchInput.value.trim();
        await refreshUserList(1);
    }, 300);

    const initEditModal = () => ({
        editModal: new bootstrap.Modal(document.getElementById('editUserModal')),
        editForm: document.getElementById('editUserForm'),
        inputs: {
            nom: document.getElementById('edit_nom'),
            email: document.getElementById('edit_email'),
            role: document.getElementById('edit_role'),
            password: document.getElementById('edit_password')
        }
    });

    const handleEdit = (() => {
        const modal = initEditModal();
        return (e) => {
            const target = e.target.closest('[data-id]');
            if (!target) return;

            const row = target.closest('tr');
            if (!row) return;

            const data = {
                nom: row.cells[1].textContent.trim(),
                email: row.cells[2].textContent.trim(),
                role: row.cells[3].textContent.trim()
            };

            Object.entries(data).forEach(([key, value]) => modal.inputs[key] && (modal.inputs[key].value = value));
            modal.inputs.password.value = '';
            modal.editForm.dataset.userId = target.dataset.id;
            modal.editModal.show();
        };
    })();

    const handleDelete = (() => {
        let isProcessing = false;
        return async (e) => {
            if (isProcessing) return;
            const target = e.target.closest('[data-id]');
            if (!target || !confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) return;

            isProcessing = true;
            target.disabled = true;

            try {
                const result = await deleteUtilisateur(target.dataset.id);
                if (result.success) {
                    const row = target.closest('tr');
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        const remainingRows = elements.userListContainer.querySelectorAll('tbody tr').length;
                        if (remainingRows === 0) refreshUserList(Math.max(1, state.currentPage - 1));
                    }, 300);
                    showToast('Utilisateur supprimé avec succès', 'bg-success');
                }
            } catch (error) {
                showToast(error.message, 'bg-danger');
            } finally {
                isProcessing = false;
                target.disabled = false;
            }
        };
    })();

    const renderUserList = data => {
        if (!elements.userListContainer) return;
        
        let html = '<table class="table"><thead><tr>' +
            '<th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Actions</th>' +
            '</tr></thead><tbody>';

        data.users.forEach(user => {
            html += '<tr><td>' + user.id_utilisateur + '</td><td>' + user.nom + 
                '</td><td>' + user.email + '</td><td>' + user.role + '</td><td class="text-end">' +
                '<button class="btn btn-sm btn-outline-primary me-2 edit-user" data-id="' + user.id_utilisateur + 
                '"><i class="fa fa-edit"></i> Modifier</button>' +
                '<button class="btn btn-sm btn-outline-danger delete-user" data-id="' + user.id_utilisateur + 
                '"><i class="fa fa-trash"></i> Supprimer</button></td></tr>';
        });

        elements.userListContainer.innerHTML = html + '</tbody></table>';

        const handleTableClick = debounce(e => {
            const editButton = e.target.closest('.edit-user');
            const deleteButton = e.target.closest('.delete-user');
            if (editButton) { e.preventDefault(); handleEdit(e); }
            if (deleteButton) { e.preventDefault(); handleDelete(e); }
        }, 100);

        elements.userListContainer.removeEventListener('click', handleTableClick);
        elements.userListContainer.addEventListener('click', handleTableClick);
    };

    const updatePagination = (currentPage, totalPages) => {
        if (totalPages <= 1) return elements.paginationContainer.innerHTML = '';

        let html = '<nav><ul class="pagination justify-content-center">' +
            '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + 
            '"><button class="page-link" data-page="' + (currentPage - 1) + '">&laquo;</button></li>';

        for (let i = 1; i <= totalPages; i++) {
            html += '<li class="page-item ' + (i === currentPage ? 'active' : '') + 
                '"><button class="page-link" data-page="' + i + '">' + i + '</button></li>';
        }

        html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + 
            '"><button class="page-link" data-page="' + (currentPage + 1) + '">&raquo;</button></li></ul></nav>';

        elements.paginationContainer.innerHTML = html;

        elements.paginationContainer.querySelectorAll('.page-link').forEach(button => {
            button.addEventListener('click', async e => {
                const newPage = parseInt(e.target.dataset.page);
                if (!isNaN(newPage) && newPage > 0 && newPage <= totalPages) {
                    state.currentPage = newPage;
                    await refreshUserList(newPage);
                }
            });
        });
    };

    window.refreshUserList = async (page = 1) => {
        elements.spinner?.classList.remove('d-none');
        try {
            const data = await getUtilisateurs(page, state.currentSearch);
            updateURL(page, state.currentSearch);
            renderUserList(data);
            updatePagination(data.currentPage, data.totalPages);
        } catch (error) {
            showToast(error.message, 'bg-danger');
        } finally {
            elements.spinner?.classList.add('d-none');
        }
    };

    [['input', handleSearch], ['click', handleSearch]].forEach(([event, handler]) => 
        elements.searchInput.addEventListener(event, event === 'input' ? handler : e => e.key === 'Enter' && (e.preventDefault(), handler()))
    );

    if (elements.editUserForm) {
        elements.editUserForm.addEventListener('submit', async e => {
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
                if (!userId) {
                    throw new Error('ID utilisateur manquant');
                }

                const result = await updateUtilisateur(form, userId);
                
                if (result.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                    modal.hide();
                    await refreshUserList(state.currentPage);
                    showToast('Utilisateur modifié avec succès', 'bg-success');
                } else {
                    throw new Error(result.message || 'Erreur lors de la mise à jour');
                }
            } catch (error) {
                showToast(error.message || 'Erreur lors de la mise à jour', 'bg-danger');
                console.error('Update error:', error);
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    if (elements.createUserForm) {
        elements.createUserForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const result = await createUtilisateur(e.target);
                if (result?.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                    e.target.reset();
                    await refreshUserList(1);
                    showToast('Utilisateur créé avec succès', 'bg-success');
                }
            } catch (error) {
                showToast(error.message || 'Erreur lors de la création', 'bg-danger');
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    const { page, search } = loadURLParams();
    if (search) {
        elements.searchInput.value = search;
        state.currentSearch = search;
    }
    await refreshUserList(page);
});
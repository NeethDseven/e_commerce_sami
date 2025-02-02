<<<<<<< HEAD
const BASE_URL = '/projet/e_commerce_sami-develop/';
let isSubmitting = false;
=======
const BASE_URL = '/Projet/ecommercesami/controller/user.php';
let isSubmitting = false; // Ajout de la variable de contrôle
>>>>>>> origin/develop

export const getUtilisateurs = async (currentPage = 1, search = '') => {
    try {
        const searchParams = new URLSearchParams({
<<<<<<< HEAD
            controller: 'user',
=======
>>>>>>> origin/develop
            action: 'list',
            currentPage: currentPage.toString()
        });

        if (search) {
            searchParams.append('search', search);
        }

<<<<<<< HEAD
        const response = await fetch(`${BASE_URL}index.php?${searchParams}`, {
=======
        const response = await fetch(`${BASE_URL}?${searchParams}`, {
>>>>>>> origin/develop
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
<<<<<<< HEAD
            },
            credentials: 'same-origin'
        });

        let data;
        const contentType = response.headers.get('content-type');
        const responseText = await response.text();

        try {
            data = JSON.parse(responseText);
        } catch (e) {
            throw new Error('La réponse du serveur n\'est pas au format JSON valide');
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || `Erreur HTTP: ${response.status}`);
        }

=======
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de la récupération des utilisateurs');
        }
        
>>>>>>> origin/develop
        return {
            users: data.users || [],
            total: data.total || 0,
            totalPages: data.totalPages || 0,
            currentPage: data.currentPage || 1,
            success: true
        };
    } catch (error) {
<<<<<<< HEAD
=======
        console.error('Error fetching users:', error);
>>>>>>> origin/develop
        throw error;
    }
}

<<<<<<< HEAD
=======

>>>>>>> origin/develop
export async function createUtilisateur(form) {
    if (isSubmitting) return;

    try {
        isSubmitting = true;
        const formData = new FormData(form);
        
<<<<<<< HEAD
        const response = await fetch(`${BASE_URL}index.php?controller=user&action=add`, {
=======
        const response = await fetch(`${BASE_URL}?action=add`, {
>>>>>>> origin/develop
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            credentials: 'include'
        });

        let responseData;
        try {
            responseData = await response.json();
        } catch (e) {
            throw new Error('Réponse invalide du serveur');
        }

        if (!response.ok) {
            throw new Error(responseData.message || `Erreur HTTP: ${response.status}`);
        }

        if (!responseData.success) {
            throw new Error(responseData.message || 'Erreur inconnue');
        }

        return responseData;
    } catch (error) {
<<<<<<< HEAD
=======
        console.error('Erreur création utilisateur:', error);
>>>>>>> origin/develop
        throw error;
    } finally {
        isSubmitting = false;
    }
}

export const updateUtilisateur = async (form, id) => {
<<<<<<< HEAD
    try {
        const formData = new FormData(form);
        
        if (!formData.get('password')?.trim()) {
            formData.delete('password');
        }

        const response = await fetch(`${BASE_URL}index.php?controller=user&action=update&id_utilisateur=${id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            credentials: 'include'
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Réponse du serveur invalide');
        }

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `Erreur HTTP: ${response.status}`);
        }

        return data;
    } catch (error) {
        throw error;
=======
    const formData = new FormData(form);
    
    // Si le mot de passe est vide, le supprimer du FormData
    if (!formData.get('password').trim()) {
        formData.delete('password');
    }

    const response = await fetch(`${BASE_URL}?action=update&id_utilisateur=${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        method: 'POST',
        body: formData
    });

    const text = await response.text();
    try {
        return JSON.parse(text);
    } catch (error) {
        console.error('Response is not valid JSON:', text);
        throw new Error('Invalid JSON response');
>>>>>>> origin/develop
    }
};

export const deleteUtilisateur = async (id) => {
    try {
<<<<<<< HEAD
        const response = await fetch(`${BASE_URL}index.php?controller=user&action=delete&id_utilisateur=${id}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'include'
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Réponse du serveur invalide');
=======
        const response = await fetch(`${BASE_URL}?action=delete&id_utilisateur=${id}`, {
            method: 'GET', // Changé de DELETE à GET car PHP ne gère pas bien DELETE
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
>>>>>>> origin/develop
        }

        const result = await response.json();

<<<<<<< HEAD
        if (!response.ok) {
            throw new Error(result.message || `Erreur HTTP: ${response.status}`);
        }

=======
        // Si l'utilisateur a des commandes, demander confirmation
>>>>>>> origin/develop
        if (!result.success && result.hasCommandes) {
            const confirmDelete = confirm(
                "Attention : Cet utilisateur possède des commandes associées. " +
                "La suppression entraînera la suppression de toutes ses commandes. " +
                "Voulez-vous continuer ?"
            );

            if (confirmDelete) {
<<<<<<< HEAD
                const forceResponse = await fetch(
                    `${BASE_URL}index.php?controller=user&action=delete&id_utilisateur=${id}&force=true`,
                    {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'include'
                    }
                );

                const forceResult = await forceResponse.json();
                if (!forceResponse.ok) {
                    throw new Error(forceResult.message || `Erreur HTTP: ${forceResponse.status}`);
                }
                return forceResult;
=======
                const forceResponse = await fetch(`${BASE_URL}?action=delete&id_utilisateur=${id}&force=true`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                return await forceResponse.json();
>>>>>>> origin/develop
            }
            return { success: false, cancelled: true };
        }

        return result;
    } catch (error) {
<<<<<<< HEAD
=======
        console.error('Error deleting user:', error);
>>>>>>> origin/develop
        throw error;
    }
};

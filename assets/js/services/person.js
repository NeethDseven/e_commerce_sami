const BASE_URL = '/Projet/ecommercesami/controller/user.php';
let isSubmitting = false; // Ajout de la variable de contrôle

export const getUtilisateurs = async (currentPage = 1, search = '') => {
    try {
        const searchParams = new URLSearchParams({
            action: 'list',
            currentPage: currentPage.toString()
        });

        if (search) {
            searchParams.append('search', search);
        }

        const response = await fetch(`${BASE_URL}?${searchParams}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
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
        
        return {
            users: data.users || [],
            total: data.total || 0,
            totalPages: data.totalPages || 0,
            currentPage: data.currentPage || 1,
            success: true
        };
    } catch (error) {
        console.error('Error fetching users:', error);
        throw error;
    }
}


export async function createUtilisateur(form) {
    if (isSubmitting) return;

    try {
        isSubmitting = true;
        const formData = new FormData(form);
        
        const response = await fetch(`${BASE_URL}?action=add`, {
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
        console.error('Erreur création utilisateur:', error);
        throw error;
    } finally {
        isSubmitting = false;
    }
}

export const updateUtilisateur = async (form, id) => {
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
    }
};

export const deleteUtilisateur = async (id) => {
    try {
        const response = await fetch(`${BASE_URL}?action=delete&id_utilisateur=${id}`, {
            method: 'GET', // Changé de DELETE à GET car PHP ne gère pas bien DELETE
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        // Si l'utilisateur a des commandes, demander confirmation
        if (!result.success && result.hasCommandes) {
            const confirmDelete = confirm(
                "Attention : Cet utilisateur possède des commandes associées. " +
                "La suppression entraînera la suppression de toutes ses commandes. " +
                "Voulez-vous continuer ?"
            );

            if (confirmDelete) {
                const forceResponse = await fetch(`${BASE_URL}?action=delete&id_utilisateur=${id}&force=true`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                return await forceResponse.json();
            }
            return { success: false, cancelled: true };
        }

        return result;
    } catch (error) {
        console.error('Error deleting user:', error);
        throw error;
    }
};

const BASE_URL = '/projet/e_commerce_sami-develop/';
let isSubmitting = false;

export const getUtilisateurs = async (currentPage = 1, search = '') => {
    try {
        const searchParams = new URLSearchParams({
            controller: 'user',
            action: 'list',
            currentPage: currentPage.toString()
        });

        if (search) {
            searchParams.append('search', search);
        }

        const response = await fetch(`${BASE_URL}index.php?${searchParams}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
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

        return {
            users: data.users || [],
            total: data.total || 0,
            totalPages: data.totalPages || 0,
            currentPage: data.currentPage || 1,
            success: true
        };
    } catch (error) {
        throw error;
    }
}

export async function createUtilisateur(form) {
    if (isSubmitting) return;

    try {
        isSubmitting = true;
        const formData = new FormData(form);
        
        const response = await fetch(`${BASE_URL}index.php?controller=user&action=add`, {
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
        throw error;
    } finally {
        isSubmitting = false;
    }
}

export const updateUtilisateur = async (form, id) => {
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
    }
};

export const deleteUtilisateur = async (id) => {
    try {
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
        }

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || `Erreur HTTP: ${response.status}`);
        }

        if (!result.success && result.hasCommandes) {
            const confirmDelete = confirm(
                "Attention : Cet utilisateur possède des commandes associées. " +
                "La suppression entraînera la suppression de toutes ses commandes. " +
                "Voulez-vous continuer ?"
            );

            if (confirmDelete) {
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
            }
            return { success: false, cancelled: true };
        }

        return result;
    } catch (error) {
        throw error;
    }
};

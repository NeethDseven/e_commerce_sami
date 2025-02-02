export async function fetchCategories() {
    try {
        const response = await fetch('/projet/e_commerce_sami-develop/index.php?controller=category&action=list', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Erreur serveur');
        }

        return data.categories;
    } catch (error) {
        console.error('Error fetching categories:', error);
        throw error;
    }
}

export async function fetchCategoriesForAdmin() {
    try {
        const response = await fetch('/projet/e_commerce_sami-develop/index.php?controller=category&action=list', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            credentials: 'same-origin'
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La réponse n\'est pas au format JSON');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Erreur serveur');
        }

        return data;
    } catch (error) {
        console.error('Error fetching categories for admin:', error);
        throw error;
    }
}


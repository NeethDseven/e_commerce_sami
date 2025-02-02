export async function fetchCategories() {
    try {
<<<<<<< HEAD
        const response = await fetch('/projet/e_commerce_sami-develop/index.php?controller=category&action=list', {
=======
        const response = await fetch('/Projet/ecommercesami/index.php?controller=category&action=list', {
>>>>>>> origin/develop
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

<<<<<<< HEAD
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Erreur serveur');
=======
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Erreur lors de la récupération des catégories');
>>>>>>> origin/develop
        }

        return data.categories;
    } catch (error) {
<<<<<<< HEAD
        console.error('Error fetching categories:', error);
=======
>>>>>>> origin/develop
        throw error;
    }
}

<<<<<<< HEAD
export async function fetchCategoriesForAdmin() {
    try {
        const response = await fetch('/projet/e_commerce_sami-develop/index.php?controller=category&action=list', {
=======
// Pour la gestion des catégories (admin)
export async function fetchCategoriesForAdmin() {
    try {
        const response = await fetch('/Projet/ecommercesami/index.php?controller=category&action=list', {
>>>>>>> origin/develop
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


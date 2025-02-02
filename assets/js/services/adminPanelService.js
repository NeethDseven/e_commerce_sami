// Garder uniquement les fonctions essentielles
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

export async function fetchArticles(pageNum = 1, options = {}) {
    const params = new URLSearchParams({
        page: pageNum.toString()  // Changé de 'pageNum' à 'page'
    });

    if (options.search) {
        params.append('search', options.search);
    }
    if (options.category) {
        params.append('category', options.category);
    }
    if (options.promos_only) {
        params.append('promos_only', '1');
    }

    const response = await fetch(`index.php?controller=adminPanel&${params.toString()}`, {
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.error || 'Erreur serveur');
    }

    return data;
}


export async function saveArticle(formData) {
    try {
        const articleId = formData.get('id_article');
        const action = articleId ? 'update' : 'create';
        
<<<<<<< HEAD
        // Debug des données envoyées
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

=======
>>>>>>> origin/develop
        const response = await fetch(`index.php?controller=adminPanel&action=${action}`, {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

<<<<<<< HEAD
=======
        // Vérifier le type de contenu
>>>>>>> origin/develop
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Réponse non-JSON reçue:', text);
            throw new Error('Le serveur n\'a pas renvoyé une réponse JSON valide');
        }

        const result = await response.json();
        if (!result.success) {
<<<<<<< HEAD
            console.error('Erreur serveur:', result);
=======
>>>>>>> origin/develop
            throw new Error(result.error || 'Erreur lors de l\'opération');
        }
        return result;
    } catch (error) {
        console.error('Erreur complète:', error);
        throw error;
    }
}

export const deleteArticle = async (articleId) => {
    try {
        const response = await fetch(`index.php?controller=adminPanel&action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: `id=${encodeURIComponent(articleId)}`
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Réponse non-JSON reçue:', await response.text());
            throw new Error('Le serveur n\'a pas renvoyé une réponse JSON valide');
        }

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Échec de la suppression');
        }

        return data;
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        throw new Error('Erreur lors de la suppression de l\'article: ' + error.message);
    }
};

export function updateTableContent(articles) {
    const tbody = document.querySelector('#articlesTable');
    if (!tbody) return;
    
    tbody.innerHTML = articles.map(article => `
        <tr data-article-id="${article.id_article}" ${article.prix_promotionnel ? 'class="table-warning"' : ''}>
            <td class="text-center">${article.id_article}</td>
            <td class="text-center">
                <img src="assets/images/articles/${article.image || ''}" 
                     alt="${article.nom}" 
                     style="width: 50px; height: 50px; object-fit: cover;">
            </td>
            <td>${article.nom}</td>
            <td class="text-end">${parseFloat(article.prix).toFixed(2)}€</td>
            <td class="text-end">
                ${article.prix_promotionnel 
                    ? `<span class="text-danger fw-bold">${parseFloat(article.prix_promotionnel).toFixed(2)}€</span>`
                    : '-'}
            </td>
            <td class="text-center">
                ${article.pourcentage_reduction 
                    ? `<span class="badge bg-danger">-${article.pourcentage_reduction}%</span>`
                    : '-'}
            </td>
            <td class="text-center">
                ${article.date_debut && article.date_fin
                    ? `Du ${new Date(article.date_debut).toLocaleDateString()}<br>
                       au ${new Date(article.date_fin).toLocaleDateString()}`
                    : '-'}
            </td>
            <td class="text-center">${article.stock}</td>
            <td>${article.categorie_nom || 'Non catégorisé'}</td>
            <td class="text-end">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="modal"
                            data-bs-target="#articleModal"
                            data-bs-article='${JSON.stringify({
                                ...article,
                                has_promotion: !!article.prix_promotionnel,
                                id_categorie: article.id_categorie
                            })}'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-article" 
                            data-id="${article.id_article}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

export function updatePagination(paginationData) {
    const paginationElement = document.querySelector('#pagination');
    if (!paginationElement || !paginationData) return;

    let html = '';
    const totalPages = parseInt(paginationData.totalPages);
    const currentPage = parseInt(paginationData.currentPage);

<<<<<<< HEAD
=======
    // Bouton précédent
>>>>>>> origin/develop
    if (currentPage > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a>
        </li>`;
    }

<<<<<<< HEAD
=======
    // Pages numérotées
>>>>>>> origin/develop
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

<<<<<<< HEAD
=======
    // Bouton suivant
>>>>>>> origin/develop
    if (currentPage < totalPages) {
        html += `<li class="page-item">
            <a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a>
        </li>`;
    }

    paginationElement.innerHTML = html;
}

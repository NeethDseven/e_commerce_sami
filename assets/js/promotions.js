document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('promotions-container');
    try {
        const articles = await fetchPromotions();
        
        if (articles && articles.length > 0) {
            displayPromotions(articles);
        } else {
            container.innerHTML = '<p class="text-center">Aucune promotion en cours</p>';
        }
    } catch (error) {
        if (container) {
            container.innerHTML = `<div class="alert alert-danger">
                Erreur lors du chargement des promotions: ${error.message}
            </div>`;
        }
    }
});

async function fetchPromotions() {
    const response = await fetch('/Projet/ecommercesami/controller/adminPanelController.php?action=getPromotions', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.error || 'Erreur serveur');
    }

    return data.articles || [];
}

function displayPromotions(promotions) {
    const container = document.getElementById('promotions-container');
    if (promotions.length === 0) {
        container.innerHTML = '<p class="text-center">Aucune promotion en cours</p>';
        return;
    }

    let html = `
        <div id="carouselPromotions" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="row">
    `;

    // Afficher 3 articles par slide
    for (let i = 0; i < promotions.length; i += 3) {
        html += `
            <div class="carousel-item ${i === 0 ? 'active' : ''}">
                <div class="row justify-content-center">
        `;

        // Boucle pour les 3 articles du slide courant
        for (let j = i; j < Math.min(i + 3, promotions.length); j++) {
            const promo = promotions[j];
            html += `
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="assets/images/articles/${promo.image}" 
                             class="card-img-top" 
                             alt="${promo.nom}">
                        <div class="card-body">
                            <h5 class="card-title">${promo.nom}</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="card-text text-decoration-line-through">${promo.prix}€</p>
                                    <p class="card-text text-danger fw-bold">${promo.prix_promotionnel}€</p>
                                </div>
                                <span class="badge bg-danger savings-badge">-${promo.pourcentage_reduction}%</span>
                            </div>
                            <button class="btn btn-primary mt-2" onclick="addToCart(${promo.id_article})">
                                Ajouter au panier
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;
    }

    html += `
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselPromotions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselPromotions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Suivant</span>
            </button>
        </div>
    `;

    container.innerHTML = html;

    // Initialiser le carousel
    new bootstrap.Carousel(document.getElementById('carouselPromotions'), {
        interval: 3000,
        ride: true
    });
}

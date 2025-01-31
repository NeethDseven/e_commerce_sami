document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('promotions-container');
    if (!container) {
        return;
    }
    loadPromotions(container);
});

async function loadPromotions(container) {
    try {
        container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
        
        const response = await fetch('index.php?controller=promotion&action=getPromotions', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success && data.articles) {
            displayPromotions(data.articles, container);
        } else {
            throw new Error('Erreur lors du chargement des promotions');
        }
    } catch (error) {
        container.innerHTML = `<div class="col-12"><div class="alert alert-danger">
            Erreur lors du chargement des promotions
        </div></div>`;
    }
}

function displayPromotions(articles, container) {
    if (!articles || articles.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="alert alert-info">Aucune promotion en cours</div></div>';
        return;
    }

    // Regrouper les articles par groupes de 3
    const articleGroups = [];
    for (let i = 0; i < articles.length; i += 3) {
        articleGroups.push(articles.slice(i, i + 3));
    }

    const carouselHtml = `
        <div id="carouselExampleCaptions" class="carousel slide">
            <div class="carousel-indicators">
                ${articleGroups.map((_, index) => `
                    <button type="button" 
                            data-bs-target="#carouselExampleCaptions" 
                            data-bs-slide-to="${index}" 
                            class="${index === 0 ? 'active' : ''}"
                            aria-current="${index === 0 ? 'true' : 'false'}"
                            aria-label="Slide ${index + 1}">
                    </button>
                `).join('')}
            </div>
            <div class="carousel-inner">
                ${articleGroups.map((group, groupIndex) => `
                    <div class="carousel-item ${groupIndex === 0 ? 'active' : ''}">
                        <div class="row justify-content-center">
                            ${group.map(article => {
                                const prixOriginal = parseFloat(article.prix);
                                const prixPromo = parseFloat(article.prix_promotionnel);
                                const reduction = ((prixOriginal - prixPromo) / prixOriginal) * 100;
                                const economie = prixOriginal - prixPromo;
                                
                                return `
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <img src="${article.image || 'path/to/default/image.jpg'}" 
                                                class="card-img-top" 
                                                alt="${article.nom}"
                                                style="height: 200px; object-fit: cover;">
                                            <div class="card-body">
                                                <h5 class="card-title">${article.nom}</h5>
                                                <div class="price-info">
                                                    <div class="mb-2">
                                                        <del class="text-muted">${prixOriginal.toFixed(2)}€</del>
                                                        <span class="text-danger fw-bold fs-5 ms-2">${prixPromo.toFixed(2)}€</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge bg-danger">-${Math.round(reduction)}%</span>
                                                        <span class="text-success">
                                                            Économie: ${economie.toFixed(2)}€
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Suivant</span>
            </button>
        </div>
    `;

    container.innerHTML = carouselHtml;

    // Initialiser le carousel
    new bootstrap.Carousel(document.getElementById('carouselExampleCaptions'), {
        interval: 5000,
        wrap: true,
        touch: true
    });
}

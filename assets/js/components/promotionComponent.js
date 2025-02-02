import { addToCart, showToast, updateCartCounter } from '../services/cartService.js';
import { fetchPromotions } from '../services/promotionService.js';

export function generatePromotionHTML(promotion) {
    console.log('Generating HTML for promotion:', promotion);
    return `
        <div class="col-md-4">
            <div class="card promotion-card h-100">
                <div class="row g-0">
                    <div class="col-12">
                        <img src="${promotion.image}" class="img-fluid rounded-start" alt="${promotion.nom}">
                    </div>
                    <div class="col-12">
                        <div class="card-body">
                            <h5 class="card-title">${promotion.nom}</h5>
                            <p class="card-text">${promotion.description}</p>
                            <p class="card-text">
                                <del class="text-muted">${promotion.prix}€</del>
                                <span class="text-danger fw-bold">${promotion.prix_promotionnel}€</span>
                                <span class="badge bg-danger">-${promotion.pourcentage_reduction}%</span>
                            </p>
                            <div class="quantity-control mt-2">
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" 
                                           class="form-control quantity-input" 
                                           value="1" 
                                           min="1" 
                                           max="${promotion.stock}"
                                           data-article-id="${promotion.id_article}">
                                </div>
                                <button class="btn btn-primary add-to-promo-cart w-100" 
                                        data-article-id="${promotion.id_article}">
                                    Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

function getImageUrl(image) {
    if (!image) return './assets/images/default-product.jpg';
    return image.startsWith('http') 
        ? image 
        : `/projet/e_commerce_sami-develop/assets/images/articles/${image}`;
}

export function displayPromotions(promotions) {
    const container = document.querySelector('#promotions-container .carousel-inner');
    const indicators = document.querySelector('#promotions-container .carousel-indicators');
    
    if (!container || !indicators) return;

    if (!promotions || promotions.length === 0) {
        container.closest('#promotions-container').style.display = 'none';
        return;
    }

    let indicatorsHtml = '';
    let carouselHtml = '';

    for (let i = 0; i < promotions.length; i += 3) {
        const slidePromotions = promotions.slice(i, i + 3);
        const slideIndex = i / 3;

        indicatorsHtml += `
            <button type="button" 
                    data-bs-target="#promotions-container" 
                    data-bs-slide-to="${slideIndex}" 
                    class="${slideIndex === 0 ? 'active' : ''}"
                    aria-current="${slideIndex === 0 ? 'true' : 'false'}"
                    aria-label="Slide ${slideIndex + 1}">
            </button>`;

        carouselHtml += `
            <div class="carousel-item ${slideIndex === 0 ? 'active' : ''}" data-bs-interval="5000">
                <div class="row">
                    ${slidePromotions.map(promotion => {
                        const imageUrl = getImageUrl(promotion.image);
                        return `
                            <div class="col-md-4">
                                <div class="card promotion-card h-100">
                                    <div class="row g-0">
                                        <div class="col-12">
                                            <img src="${imageUrl}" 
                                                 class="img-fluid rounded-start" 
                                                 alt="${promotion.nom}"
                                                 style="object-fit: contain; height: 200px; width: 100%;">
                                        </div>
                                        <div class="col-12">
                                            <div class="card-body">
                                                <h5 class="card-title">${promotion.nom}</h5>
                                                <p class="card-text">${promotion.description}</p>
                                                <p class="card-text">
                                                    <del class="text-muted">${promotion.prix}€</del>
                                                    <span class="text-danger fw-bold ms-2">${promotion.prix_promotionnel}€</span>
                                                    <span class="badge bg-danger">-${promotion.pourcentage_reduction}%</span>
                                                </p>
                                                <div class="quantity-control mt-2">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <input type="number" 
                                                               class="form-control quantity-input" 
                                                               value="1" 
                                                               min="1" 
                                                               max="${promotion.stock}"
                                                               data-article-id="${promotion.id_article}">
                                                    </div>
                                                    <button class="btn btn-primary add-to-promo-cart w-100" 
                                                            data-article-id="${promotion.id_article}">
                                                        Ajouter au panier
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    }).join('')}
                </div>
            </div>`;
    }

    indicators.innerHTML = indicatorsHtml;
    container.innerHTML = carouselHtml;

    const promotionsContainer = container.closest('#promotions-container');
    promotionsContainer.style.display = 'block';
    promotionsContainer.style.marginBottom = '2rem';
    
    if (typeof bootstrap !== 'undefined') {
        new bootstrap.Carousel(promotionsContainer, {
            interval: 5000,
            wrap: true
        });
    }

    initializePromotionEvents();
}

export function initializePromotionEvents() {
    document.querySelectorAll('#promotions-container .add-to-promo-cart').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                const articleId = parseInt(this.dataset.articleId, 10);
                const quantityInput = document.querySelector(`.quantity-input[data-article-id="${articleId}"]`);
                const quantity = Math.max(1, parseInt(quantityInput?.value, 10) || 1);
                
                this.disabled = true;
                const response = await addToCart({ articleId, quantite: quantity });

                if (response.success) {
                    showToast('Article ajouté au panier !', 'success');
                    if (response.cartCount !== undefined) {
                        updateCartCounter(response.cartCount);
                    }
                    document.dispatchEvent(new CustomEvent('cartUpdated'));
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                showToast(error.message || 'Erreur lors de l\'ajout au panier', 'danger');
            } finally {
                this.disabled = false;
            }
        });
    });
}

export function validatePromotionForm(form) {
    const reduction = form.querySelector('#reduction_percent');
    if (reduction) {
        const value = parseInt(reduction.value);
        if (isNaN(value) || value < 0 || value > 60) {
            reduction.setCustomValidity('La réduction doit être entre 0 et 60%');
            return false;
        }
        reduction.setCustomValidity('');
    }
    return form.checkValidity();
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!validatePromotionForm(form)) {
                e.preventDefault();
                return false;
            }
        });

        const reductionInput = form.querySelector('#reduction_percent');
        if (reductionInput) {
            reductionInput.addEventListener('input', () => {
                validatePromotionForm(form);
            });
        }
    }
});


let isInitialized = false;

function initializePromotions() {
    if (isInitialized) return;
    isInitialized = true;

    const container = document.getElementById('promotions-container');
    if (!container) {
        console.warn('Promotions container not found');
        return;
    }

    fetchPromotions()
        .then(promotions => {
            displayPromotions(promotions);
            initializePromotionEvents();
        })
        .catch(error => {
            console.error('Failed to initialize promotions:', error);
            container.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des promotions</div>';
        });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePromotions);
} else {
    initializePromotions();
}
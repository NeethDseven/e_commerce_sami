const BASE_URL = (() => {
    const metaUrl = document.querySelector('meta[name="base-url"]')?.content;
    const defaultUrl = '/projet/e_commerce_sami-develop/';
    const url = metaUrl || defaultUrl;
    return url.endsWith('/') ? url : url + '/';
})();

export async function fetchPromotions() {
    try {
        const url = `${BASE_URL}index.php?controller=promotion&action=getPromotions`;
        const response = await fetch(url, {
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

        return data.promotions;
    } catch (error) {
        throw error;
    }
}

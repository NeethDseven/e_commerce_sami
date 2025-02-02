export async function fetchCategories() {
    try {
        const response = await fetch('index.php?controller=category&action=list');
        if (!response.ok) {
            throw new Error('Erreur réseau');
        }
        return await response.json();
    } catch (error) {

        if (error.status !== 404) {
            console.error('Erreur critique:', error);
        }
        throw error;
    }
}


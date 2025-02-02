export async function login(username, password) {
    try {
        const response = await fetch('/controller/loginController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ username, password })
        });

        if (!response.ok) {
            throw new Error('Failed to login');
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error during login:', error);
        return { errors: [error.message] };
    }
}
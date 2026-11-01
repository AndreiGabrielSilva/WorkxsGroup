document.addEventListener('DOMContentLoaded', () => {
    const loginButton = document.querySelector('.btn-login');

    if (loginButton) {
        // Ouve o movimento do mouse sobre o contêiner de login
        loginButton.closest('.login-container').addEventListener('mousemove', (e) => {
            const rect = loginButton.getBoundingClientRect();
            
            // Verifica se as coordenadas do mouse estão dentro do botão
            if (e.clientX >= rect.left && e.clientX <= rect.right &&
                e.clientY >= rect.top && e.clientY <= rect.bottom) {
                
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;

                const deltaX = e.clientX - centerX;
                const deltaY = e.clientY - centerY;

                // Sensibilidade: Quanto maior o número, mais suave (menos movimento)
                const sensibility = 25; 
                const moveX = deltaX / sensibility;
                const moveY = deltaY / sensibility;

                // Aplica a transformação sutil
                loginButton.style.transform = `translate(${moveX}px, ${moveY}px)`;
            } else {
                // Se o mouse sair do botão, volta à posição original
                loginButton.style.transform = `translate(0, 0)`;
            }
        });

        // Garante que ele volte ao normal ao sair do container de login
        loginButton.closest('.login-container').addEventListener('mouseleave', () => {
             loginButton.style.transform = `translate(0, 0)`;
        });
    }
});
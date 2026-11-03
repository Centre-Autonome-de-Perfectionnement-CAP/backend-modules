const buttonId = 'cap-student-history-export';
const endpoint = '/api/inscription/students/export/cap-history';

function installExportButton() {
    const title = [...document.querySelectorAll('span.fw-bold')]
        .find((element) => element.textContent.trim() === 'Liste des Étudiants');
    const actions = title?.parentElement?.querySelector(':scope > div');

    if (!actions || actions.querySelector(`#${buttonId}`)) {
        return;
    }

    const button = document.createElement('button');
    button.id = buttonId;
    button.type = 'button';
    button.className = 'btn btn-outline-success btn-sm me-2';
    button.textContent = 'Télécharger tous (PDF)';
    button.title = 'Télécharger les étudiants de première et deuxième année depuis 2023, par année et filière';
    button.addEventListener('click', async () => {
        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Génération du PDF...';

        try {
            const token = localStorage.getItem('token');
            const response = await fetch(endpoint, {
                headers: {
                    Accept: 'application/pdf',
                    ...(token ? { Authorization: `Bearer ${token}` } : {}),
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error(`Le serveur a répondu avec le statut ${response.status}.`);
            }

            if (!response.headers.get('content-type')?.includes('application/pdf')) {
                throw new Error('La réponse du serveur ne contient pas un PDF.');
            }

            const pdf = await response.blob();
            const url = URL.createObjectURL(pdf);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'liste_etudiants_cap_depuis_2023.pdf';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (error) {
            console.error('Erreur lors du téléchargement de la liste CAP :', error);
            window.alert('Impossible de télécharger la liste des étudiants. Veuillez réessayer.');
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });

    actions.appendChild(button);
}

const observer = new MutationObserver(installExportButton);
observer.observe(document.documentElement, { childList: true, subtree: true });
installExportButton();

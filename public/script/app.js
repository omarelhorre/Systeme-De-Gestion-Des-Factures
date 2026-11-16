// Fatourti — petits comportements communs aux pages connectées
// Pagination des listes et tableaux (pas de barre de défilement interne)

// ---------- Pagination ----------
// Sur un <tbody> ou un <ul> : data-pagine="8" = 8 lignes par page.
// Les boutons Précédent / Suivant sont ajoutés après la liste, et cachés si tout tient sur une page.
document.querySelectorAll("[data-pagine]").forEach((liste) => {
    const parPage = Number(liste.dataset.pagine);
    const lignes = Array.from(liste.children).filter((ligne) => !ligne.classList.contains("vide"));
    const nbPages = Math.ceil(lignes.length / parPage);
    if (nbPages <= 1) return;

    let page = 1;
    const zone = document.createElement("div");
    zone.className = "pagination";
    zone.innerHTML =
        '<button class="bouton accent petit" type="button">Précédent</button>' +
        '<span class="pagination-numero"></span>' +
        '<button class="bouton accent petit" type="button">Suivant</button>';
    const [precedent, numero, suivant] = zone.children;

    function afficher() {
        lignes.forEach((ligne, i) => { ligne.hidden = i < (page - 1) * parPage || i >= page * parPage; });
        numero.textContent = page + " / " + nbPages;
        precedent.disabled = page <= 1;
        suivant.disabled = page >= nbPages;
    }
    precedent.addEventListener("click", () => { page -= 1; afficher(); });
    suivant.addEventListener("click", () => { page += 1; afficher(); });

    // après le tableau entier si la liste est un <tbody>, sinon après la liste
    (liste.closest("table") || liste).after(zone);
    afficher();
});


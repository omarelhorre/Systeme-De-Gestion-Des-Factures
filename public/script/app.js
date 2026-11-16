// Fatourti — petits comportements communs aux pages connectées
// 1. Pagination des listes et tableaux (pas de barre de défilement interne)
// 2. Avatars générés : chaque client a le sien, dessiné à partir de son identifiant

// ---------- 1. Pagination ----------
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

// ---------- 2. Avatars ----------
// <span class="avatar" data-avatar="17"></span> : formes et couleurs choisies à partir du nombre.
const COULEURS_AVATAR = ["#006233", "#F2B33D", "#1F4E8C", "#C1272D", "#0E1116"];

document.querySelectorAll("[data-avatar]").forEach((avatar) => {
    // petit générateur pseudo-aléatoire : la même graine donne toujours le même dessin
    let graine = Number(avatar.dataset.avatar) * 2654435761 % 4294967296;
    const hasard = (max) => { graine = (graine * 1664525 + 1013904223) % 4294967296; return graine % max; };

    const fond = COULEURS_AVATAR[hasard(5)];
    let formes = "";
    for (let i = 0; i < 3; i++) {
        let couleur = COULEURS_AVATAR[hasard(5)];
        if (couleur === fond) couleur = "#F6F4EF";
        const x = hasard(28) + 4, y = hasard(28) + 4, t = hasard(12) + 8;
        formes += hasard(2)
            ? `<circle cx="${x}" cy="${y}" r="${t / 2}" fill="${couleur}"/>`
            : `<rect x="${x - t / 2}" y="${y - t / 2}" width="${t}" height="${t}" fill="${couleur}"/>`;
    }
    avatar.innerHTML = `<svg viewBox="0 0 36 36" aria-hidden="true"><rect width="36" height="36" fill="${fond}"/>${formes}</svg>`;
});

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


// ---------- Notifications (cloche) ----------
// La liste vient du serveur en AJAX (notification/all, en JSON) et se rafraîchit toute seule :
// pas besoin de recharger la page pour voir une nouvelle notification.
(function () {
    const cloche = document.getElementById("cloche");
    if (!cloche) return;

    const SITE = cloche.dataset.site;            // adresse du site, ex. http://localhost:8080/
    const RAFRAICHIR_MS = 10000;                 // on redemande la liste toutes les 10 secondes
    const PAR_PAGE = 4;

    const fenetre = document.getElementById("notifs");
    const vueListe = document.getElementById("notifs-liste-vue");
    const liste = document.getElementById("notifs-liste");
    const nombre = document.getElementById("cloche-nombre");
    const pagination = document.getElementById("notifs-pagination");
    const formulaire = document.getElementById("notifs-reponse");
    const onglets = Array.from(fenetre.querySelectorAll("[data-onglet]"));

    let donnees = { notifications: [], nonLues: 0, csrf: "", csrfReclamations: null, maintenant: "" };
    let onglet = "toutes";
    let page = 1;

    // Icône et texte du bouton selon le type de notification
    const TYPES = {
        bienvenue:           { icone: "fa-key",                  action: "Changer mon mot de passe" },
        facture:             { icone: "fa-file-invoice",         action: "Voir la facture" },
        facture_payee:       { icone: "fa-circle-check",         action: "Ouvrir" },
        anomalie:            { icone: "fa-triangle-exclamation", action: "Voir mon relevé" },
        anomalie_corrigee:   { icone: "fa-file-invoice",         action: "Voir la facture" },
        anomalie_a_corriger: { icone: "fa-triangle-exclamation", action: "Examiner" },
        reclamation_nouvelle:{ icone: "fa-comment-dots",         action: "Répondre" },
        reclamation_reponse: { icone: "fa-comment-dots",         action: "Voir la réponse" },
        annuel:              { icone: "fa-calendar-check",       action: "Ouvrir le bilan" },
        contrat_resilie:     { icone: "fa-file-circle-xmark",    action: "Ouvrir" },
    };

    // Petits outils pour créer des éléments sans jamais insérer de HTML venant des données
    function creer(balise, classe, texte) {
        const element = document.createElement(balise);
        if (classe) element.className = classe;
        if (texte !== undefined) element.textContent = texte;
        return element;
    }
    function icone(nom) {
        return creer("i", "fa-solid " + nom);
    }

    // "il y a 5 min", calculé avec l'heure du serveur
    function depuis(date) {
        const secondes = (new Date(donnees.maintenant.replace(" ", "T")) - new Date(date.replace(" ", "T"))) / 1000;
        if (secondes < 60) return "à l'instant";
        if (secondes < 3600) return "il y a " + Math.floor(secondes / 60) + " min";
        if (secondes < 86400) return "il y a " + Math.floor(secondes / 3600) + " h";
        return date.slice(8, 10) + "/" + date.slice(5, 7);
    }

    // Une demande "à traiter" reste dans cet onglet tant qu'elle n'est pas résolue
    function attend(n) { return n.aTraiter == 1 && !n.dateResolution; }

    function filtrees() {
        if (onglet === "traiter") return donnees.notifications.filter(attend);
        if (onglet === "infos") return donnees.notifications.filter((n) => !attend(n));
        return donnees.notifications;
    }

    // ----- Affichage -----
    function afficher() {
        // pastille sur la cloche
        nombre.hidden = donnees.nonLues === 0;
        nombre.textContent = donnees.nonLues > 9 ? "9+" : donnees.nonLues;

        // compteurs des onglets
        const total = donnees.notifications.length;
        const aTraiter = donnees.notifications.filter(attend).length;
        const comptes = { toutes: total, traiter: aTraiter, infos: total - aTraiter };
        onglets.forEach((bouton) => {
            bouton.classList.toggle("actif", bouton.dataset.onglet === onglet);
            bouton.querySelector("span").textContent = comptes[bouton.dataset.onglet];
        });

        // liste, par pages (pas de barre de défilement)
        const lignes = filtrees();
        const nbPages = Math.max(1, Math.ceil(lignes.length / PAR_PAGE));
        if (page > nbPages) page = nbPages;
        liste.replaceChildren();
        if (lignes.length === 0) {
            liste.appendChild(creer("li", "vide", onglet === "traiter" ? "Rien à traiter pour le moment." : "Aucune notification."));
        }
        lignes.slice((page - 1) * PAR_PAGE, page * PAR_PAGE).forEach((n) => liste.appendChild(ligne(n)));

        pagination.hidden = nbPages <= 1;
        pagination.children[0].disabled = page <= 1;
        pagination.children[1].textContent = page + " / " + nbPages;
        pagination.children[2].disabled = page >= nbPages;
    }

    // Une ligne de la liste
    function ligne(n) {
        const type = TYPES[n.type] || { icone: "fa-bell", action: "Ouvrir" };
        const element = creer("li", "notif" + (n.dateLecture ? "" : " non-lue"));

        const rond = creer("span", "notif-icone" + (n.dateResolution ? " traitee" : ""));
        rond.appendChild(icone(n.dateResolution ? "fa-check" : type.icone));
        element.appendChild(rond);

        const texte = creer("div", "notif-texte");
        texte.appendChild(creer("p", "notif-titre", n.titre));
        if (n.detail) texte.appendChild(creer("p", "notif-detail", n.detail));

        // bouton d'action, sauf si la demande est déjà traitée
        if (!n.dateResolution && n.lien) {
            const bouton = creer("button", "bouton contour petit");
            bouton.type = "button";
            bouton.append(icone("fa-arrow-up-right-from-square"), " " + type.action);
            bouton.addEventListener("click", () => agir(n));
            texte.appendChild(bouton);
        }
        element.appendChild(texte);

        const cote = creer("div", "notif-cote");
        cote.appendChild(creer("span", "notif-heure", depuis(n.dateCreation)));
        if (n.dateLecture) {
            const vu = icone("fa-check-double");     // icône "vu"
            vu.title = "Vu";
            cote.appendChild(vu);
        }
        element.appendChild(cote);
        return element;
    }

    // ----- Échanges avec le serveur (AJAX) -----
    async function charger() {
        try {
            const reponse = await fetch(SITE + "notification/all", { credentials: "same-origin" });
            if (!reponse.ok) return;
            donnees = await reponse.json();
            afficher();
        } catch (erreur) {
            // réseau coupé : on réessaiera au prochain tour
        }
    }

    async function envoyer(action) {
        const corps = new URLSearchParams({ csrf: donnees.csrf });
        const reponse = await fetch(SITE + "notification/" + action, { method: "POST", body: corps, credentials: "same-origin" });
        if (reponse.ok) {
            donnees = await reponse.json();
            afficher();
        }
    }

    // Clic sur le bouton d'une notification : elle est marquée vue, puis on agit
    async function agir(n) {
        if (!n.dateLecture) await envoyer("lire/" + n.id);
        if (n.type === "reclamation_nouvelle" && donnees.csrfReclamations) {
            ouvrirReponse(n);
        } else {
            window.location.href = SITE + n.lien;
        }
    }

    // Répondre à une réclamation dans la fenêtre : le formulaire est envoyé à la page de traitement existante
    function ouvrirReponse(n) {
        formulaire.action = SITE + "reclamation/traiter/" + n.idObjet;
        formulaire.elements.csrf.value = donnees.csrfReclamations;
        document.getElementById("notifs-reponse-titre").textContent = n.titre;
        document.getElementById("notifs-reponse-detail").textContent = n.detail || "";
        vueListe.hidden = true;
        formulaire.hidden = false;
        formulaire.elements.reponse.focus();
    }

    function fermerReponse() {
        formulaire.hidden = true;
        vueListe.hidden = false;
    }

    // ----- Ouverture et fermeture de la fenêtre -----
    function ouvrir(etat) {
        fenetre.hidden = !etat;
        cloche.setAttribute("aria-expanded", etat ? "true" : "false");
        if (etat) { fermerReponse(); charger(); }
    }

    cloche.addEventListener("click", () => ouvrir(fenetre.hidden));
    document.addEventListener("click", (evenement) => {
        if (!fenetre.hidden && !fenetre.contains(evenement.target) && !cloche.contains(evenement.target)) ouvrir(false);
    });
    document.addEventListener("keydown", (evenement) => {
        if (evenement.key === "Escape" && !fenetre.hidden) { ouvrir(false); cloche.focus(); }
    });

    onglets.forEach((bouton) => bouton.addEventListener("click", () => { onglet = bouton.dataset.onglet; page = 1; afficher(); }));
    pagination.children[0].addEventListener("click", () => { page -= 1; afficher(); });
    pagination.children[2].addEventListener("click", () => { page += 1; afficher(); });
    document.getElementById("notifs-tout-lire").addEventListener("click", () => envoyer("toutLire"));
    document.getElementById("notifs-retour").addEventListener("click", fermerReponse);

    // Premier chargement, puis rafraîchissement régulier (en pause quand l'onglet du navigateur est caché,
    // et pendant qu'on écrit une réponse pour ne pas perdre le texte)
    charger();
    setInterval(() => {
        if (!document.hidden && formulaire.hidden) charger();
    }, RAFRAICHIR_MS);
})();

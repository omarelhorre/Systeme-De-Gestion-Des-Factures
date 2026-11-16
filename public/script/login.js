// Page de connexion Fatourti
// 1. Dessine le motif "guilloche" (lignes de sécurité des documents officiels) et la rosace du sceau
// 2. Fait tourner les deux compteurs : électricité (saut net), eau (tambour qui roule)
// 3. Bouton œil : afficher / masquer le mot de passe

const SVG_NS = "http://www.w3.org/2000/svg";
const reduireMouvement = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

function ajouterChemin(parent, d) {
    const p = document.createElementNS(SVG_NS, "path");
    p.setAttribute("d", d);
    parent.appendChild(p);
}

// ----- Rosace du sceau (courbe de spirographe) -----
const rosace = document.querySelector(".rosace");
if (rosace) {
    let d = "";
    for (let i = 0; i <= 2160; i++) {
        const t = (i / 720) * Math.PI * 2;
        const x = 50 + 19 * Math.cos(t) + 15 * Math.cos((19 / 8) * t);
        const y = 50 + 19 * Math.sin(t) - 15 * Math.sin((19 / 8) * t);
        d += (i === 0 ? "M" : "L") + x.toFixed(2) + " " + y.toFixed(2);
    }
    ajouterChemin(rosace, d);
}

// ----- Guilloche du visuel : vagues entrelacées -----
const guilloche = document.querySelector(".guilloche");
if (guilloche) {
    for (let k = 0; k < 22; k++) {
        let d = "";
        for (let x = 0; x <= 400; x += 2) {
            const y = 200
                + Math.sin(x / 34 + k * 0.3) * 120
                + Math.sin(x / 14 - k * 0.55) * 14;
            d += (x === 0 ? "M" : "L") + x + " " + y.toFixed(2);
        }
        ajouterChemin(guilloche, d);
    }
}

// ----- Électricité : le dernier chiffre saute, sans transition -----
const lcd = document.querySelector(".lcd-valeur");
let kwh = lcd ? Number(lcd.dataset.depart) : 0;
function avancerElec() {
    kwh = (kwh + 1) % 100000;
    lcd.textContent = String(kwh).padStart(5, "0");
}

// ----- Eau : 4 tambours entiers + 1 décimal qui roulent -----
const HAUTEUR = "3.5rem";   // = hauteur d'un tambour dans login.css
const tambours = document.querySelector(".tambours");
let dixiemes = tambours ? Number(tambours.dataset.depart) : 0;
const bandes = [];

if (tambours) {
    for (let i = 0; i < 5; i++) {
        if (i === 4) {
            const virgule = document.createElement("span");
            virgule.className = "virgule";
            virgule.textContent = ",";
            tambours.appendChild(virgule);
        }
        const tambour = document.createElement("span");
        tambour.className = "tambour" + (i === 4 ? " decimal" : "");
        const bande = document.createElement("span");
        bande.className = "bande";
        // 0 à 9 puis encore 0, pour passer de 9 à 0 sans saut visible
        for (let n = 0; n <= 10; n++) {
            const chiffre = document.createElement("span");
            chiffre.textContent = n % 10;
            bande.appendChild(chiffre);
        }
        tambour.appendChild(bande);
        tambours.appendChild(tambour);
        bandes.push(bande);
    }
}

function placer(bande, position, anime) {
    if (!anime) bande.style.transition = "none";
    bande.style.transform = `translateY(calc(-${position} * ${HAUTEUR}))`;
    if (!anime) { bande.offsetHeight; bande.style.transition = ""; }
}

function afficherEau(anime) {
    const chiffres = String(dixiemes).padStart(5, "0").slice(-5);
    bandes.forEach((bande, i) => {
        const n = Number(chiffres[i]);
        const avant = Number(bande.dataset.n ?? n);
        if (anime && avant === 9 && n === 0) {
            // roule jusqu'au "0" du bas, puis revient en haut sans animation
            placer(bande, 10, true);
            bande.addEventListener("transitionend", () => placer(bande, 0, false), { once: true });
        } else {
            placer(bande, n, anime);
        }
        bande.dataset.n = n;
    });
}

if (tambours) afficherEau(false);

if (!reduireMouvement) {
    if (lcd) setInterval(avancerElec, 2400);
    if (tambours) setInterval(() => { dixiemes += 1; afficherEau(true); }, 1600);
}

// ----- Afficher / masquer le mot de passe -----
document.querySelectorAll(".auth-oeil").forEach((bouton) => {
    bouton.addEventListener("click", () => {
        const champ = bouton.parentElement.querySelector("input");
        const visible = champ.type === "password";
        champ.type = visible ? "text" : "password";
        bouton.classList.toggle("visible", visible);
        bouton.setAttribute("aria-label", visible ? "Masquer le mot de passe" : "Afficher le mot de passe");
    });
});

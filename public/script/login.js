// Page de connexion Fatourti
// 1. Dessine les lignes "guilloche" (comme sur les documents officiels)
// 2. Fait tourner les deux compteurs : électricité (saut net), eau (tambour qui roule)

const SVG_NS = "http://www.w3.org/2000/svg";
const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

// Une courbe "spirographe" : la base des motifs de sécurité des billets
function guillochePath(cx, cy, R, r, d, turns) {
    let path = "";
    const steps = 720 * turns;
    for (let i = 0; i <= steps; i++) {
        const t = (i / 720) * Math.PI * 2;
        const x = cx + (R - r) * Math.cos(t) + d * Math.cos(((R - r) / r) * t);
        const y = cy + (R - r) * Math.sin(t) - d * Math.sin(((R - r) / r) * t);
        path += (i === 0 ? "M" : "L") + x.toFixed(2) + " " + y.toFixed(2);
    }
    return path;
}

function addPath(parent, d) {
    const p = document.createElementNS(SVG_NS, "path");
    p.setAttribute("d", d);
    parent.appendChild(p);
}

// Rosace du sceau
document.querySelectorAll(".rosette").forEach((g) => {
    const size = Number(g.dataset.r);
    addPath(g, guillochePath(50, 50, size, size * 0.3, size * 0.55, 3));
});

// Fond de la plaque des compteurs : vagues entrelacées, comme l'impression de sécurité d'un billet
const plate = document.querySelector(".plate-guilloche");
if (plate) {
    for (let k = 0; k < 18; k++) {
        let d = "";
        for (let x = 0; x <= 400; x += 2) {
            const y = 100
                + Math.sin(x / 31 + k * 0.35) * 38
                + Math.sin(x / 13 - k * 0.6) * 9;
            d += (x === 0 ? "M" : "L") + x + " " + y.toFixed(2);
        }
        addPath(plate, d);
    }
}

// ----- Compteur d'électricité : le dernier chiffre saute, sans transition -----
const lcd = document.querySelector(".lcd-value");
let kwh = lcd ? Number(lcd.dataset.start) : 0;
function tickElec() {
    kwh = (kwh + 1) % 100000;
    lcd.textContent = String(kwh).padStart(5, "0");
}

// ----- Compteur d'eau : 4 tambours entiers + 1 décimal qui roulent -----
const drums = document.querySelector(".drums");
let liters = drums ? Number(drums.dataset.start) : 0; // en dixièmes de m³
const strips = [];

if (drums) {
    for (let i = 0; i < 5; i++) {
        if (i === 4) {
            const comma = document.createElement("span");
            comma.className = "drum-comma";
            comma.textContent = ",";
            drums.appendChild(comma);
        }
        const drum = document.createElement("span");
        drum.className = "drum" + (i === 4 ? " decimal" : "");
        const strip = document.createElement("span");
        strip.className = "drum-strip";
        // 0 à 9 puis encore 0 pour boucler sans saut visible
        for (let n = 0; n <= 10; n++) {
            const digit = document.createElement("span");
            digit.textContent = n % 10;
            strip.appendChild(digit);
        }
        drum.appendChild(strip);
        drums.appendChild(drum);
        strips.push(strip);
    }
}

function showWater(animate) {
    const digits = String(liters).padStart(5, "0").slice(-5);
    strips.forEach((strip, i) => {
        const n = Number(digits[i]);
        const previous = Number(strip.dataset.n ?? n);
        // passage de 9 à 0 : on roule jusqu'au "0" du bas, puis on revient en haut sans animation
        if (animate && previous === 9 && n === 0) {
            strip.style.transform = `translateY(calc(-10 * 3.25rem))`;
            strip.addEventListener("transitionend", () => {
                strip.style.transition = "none";
                strip.style.transform = "translateY(0)";
                strip.offsetHeight; // force le navigateur à appliquer avant de remettre la transition
                strip.style.transition = "";
            }, { once: true });
        } else {
            if (!animate) strip.style.transition = "none";
            strip.style.transform = `translateY(calc(-${n} * 3.25rem))`;
            if (!animate) { strip.offsetHeight; strip.style.transition = ""; }
        }
        strip.dataset.n = n;
    });
}

if (drums) showWater(false);

if (!reduceMotion) {
    if (lcd) setInterval(tickElec, 2400);
    if (drums) setInterval(() => { liters += 1; showWater(true); }, 1600);
}

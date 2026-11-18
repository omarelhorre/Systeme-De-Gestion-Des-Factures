document.querySelectorAll(".auth-oeil").forEach((bouton) => {
    bouton.addEventListener("click", () => {
        const champ = bouton.parentElement.querySelector("input");
        const visible = champ.type === "password";
        champ.type = visible ? "text" : "password";
        bouton.classList.toggle("visible", visible);
        bouton.querySelector("i").className = visible ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
        bouton.setAttribute("aria-label", visible ? "Masquer le mot de passe" : "Afficher le mot de passe");
    });
});

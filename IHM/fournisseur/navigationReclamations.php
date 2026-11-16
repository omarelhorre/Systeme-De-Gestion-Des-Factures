<?php
// Compléter le lien du menu sans modifier les vues existantes.
?>
<script>
document.querySelectorAll('.menu a.section-txt').forEach(function (lien) {
    var texte = lien.textContent.toLowerCase();
    if (texte.includes('reclamations') || texte.includes('réclamations')) {
        lien.href = <?= json_encode(link.'reclamation/all', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    }
});
</script>

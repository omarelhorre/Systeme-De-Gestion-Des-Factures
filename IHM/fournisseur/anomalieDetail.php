<?php ?>
<a class="lien-retour" href="<?= link ?>declaration/anomalies"><i class="fa-solid fa-arrow-left"></i> Retour aux anomalies</a>
<header class="entete ligne">
    <div>
        <h1 class="titre">Déclaration N° <?= (int)$anomalie['id'] ?></h1>
        <p class="sous-titre"><?= texteAnomalie($anomalie['prenom'] . ' ' . $anomalie['nom']) ?> · relevé du <?= $jourAnomalie($anomalie['dateSaisie']) ?> · <?= texteAnomalie($anomalie['adresse']) ?></p>
    </div>
</header>

<p class="erreur-bloc"><i class="fa-solid fa-triangle-exclamation"></i><span><?= texteAnomalie($anomalie['statut']) ?></span></p>
<?php if ($erreur !== ''): ?>
    <p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= texteAnomalie($erreur) ?></span></p>
<?php endif; ?>

<div class="grille">
    <section class="carte large">
        <h2 class="titre-carte">Photos des compteurs</h2>
        <div class="photos" style="margin-top: 1rem">
            <figure><img src="<?= link ?>declaration/photoEau/<?= (int)$anomalie['id'] ?>" alt="Photo du compteur d’eau"><figcaption><i class="fa-solid fa-droplet" style="color: var(--eau)"></i> Compteur d’eau</figcaption></figure>
            <figure><img src="<?= link ?>declaration/photoElectricite/<?= (int)$anomalie['id'] ?>" alt="Photo du compteur d’électricité"><figcaption><i class="fa-solid fa-bolt" style="color: var(--elec)"></i> Compteur d’électricité</figcaption></figure>
        </div>
    </section>

    <section class="carte large">
        <h2 class="titre-carte">Correction</h2>
        <?php if ($precedente): ?>
            <p class="legende" style="margin: 0.75rem 0 1rem">Dernier relevé valide du <?= $jourAnomalie($precedente['dateSaisie']) ?> : eau <strong><?= texteAnomalie($precedente['indexEau']) ?> m³</strong>, électricité <strong><?= texteAnomalie($precedente['indexElectricite']) ?> kWh</strong>.</p>
            <form class="formulaire" method="post" action="<?= link ?>declaration/corriger/<?= (int)$anomalie['id'] ?>">
                <input type="hidden" name="csrf" value="<?= texteAnomalie($_SESSION['csrf_anomalies']) ?>">
                <label class="champ"><span class="libelle">Index eau corrigé (m³)</span>
                    <input type="text" inputmode="decimal" name="indexEau" value="<?= texteAnomalie($_POST['indexEau'] ?? $anomalie['indexEau']) ?>" required>
                </label>
                <label class="champ"><span class="libelle">Index électricité corrigé (kWh)</span>
                    <input type="text" inputmode="decimal" name="indexElectricite" value="<?= texteAnomalie($_POST['indexElectricite'] ?? $anomalie['indexElectricite']) ?>" required>
                </label>
                <label class="case entier"><input type="checkbox" name="confirmation" value="oui" required> <span>J’ai vérifié les photos et les index corrigés. La facture sera calculée avec ces valeurs.</span></label>
                <div class="entier actions fin">
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-check"></i> Valider la correction</button>
                </div>
            </form>
        <?php else: ?>
            <p class="vide">Aucun relevé précédent valide. La correction ne peut pas être facturée.</p>
        <?php endif; ?>
    </section>
</div>

/**
 * erreur_formulaire.js
 *
 * Ce script gère la validation du formulaire de recherche de ville.
 * Il vérifie que le champ "ville" n'est pas vide avant de permettre l'envoi du formulaire.
 * En cas d'erreur, il empêche l'envoi, ajoute une classe d'erreur au champ et affiche une alerte.
 */

// Sélectionne le premier formulaire trouvé sur la page
document.querySelector('form').addEventListener('submit', function(e) {
    // Sélectionne l'input nommé "ville"
    var ville = document.querySelector('input[name="ville"]');

    // Vérifie si le champ "ville" est vide après avoir supprimé les espaces
    if (ville.value.trim() === '') {
        e.preventDefault();  // Empêche l'envoi du formulaire
        ville.classList.add('error'); // Ajoute une classe CSS pour styliser l'erreur
        alert('Veuillez entrer un nom de ville.'); // Affiche une alerte à l'utilisateur
    }
});

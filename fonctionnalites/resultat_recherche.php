<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700&display=swap" rel="stylesheet">
    <title>Résultats de la recherche - PureOxy</title>
    <!-- Inclure Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../script/erreur_formulaire.js"></script>
</head>
<body>
<div class="content-wrapper">
    <?php include 'includes/header.php'; ?>

    <main>
        <section id="resultat_recherche">
            <h2>Résultats de la recherche</h2>
            <?php
            include 'bd/bd.php';

            // Récupérer la ville saisie
            if (isset($_POST['ville'])) {
                $ville = $_POST['ville'];

                // Préparer la requête SQL pour calculer la moyenne par polluant pour la ville sélectionnée
                $stmt = $conn->prepare("
                        SELECT Pollutant, AVG(value) AS moyenne_pollution
                        FROM pollution_villes 
                        WHERE City = ? 
                        GROUP BY Pollutant
                    ");
                $stmt->bind_param("s", $ville);
                $stmt->execute();
                $result = $stmt->get_result();

                // Afficher les résultats dans un tableau
                echo "<table>";
                echo "<tr><th>Polluant</th><th>Moyenne de Pollution (µg/m³)</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>" . htmlspecialchars($row['Pollutant']) . "</td><td>" . round($row['moyenne_pollution'], 2) . " µg/m³</td></tr>";
                }
                echo "</table>";

                $stmt->close();
            } else {
                echo "<p>Veuillez entrer un nom de ville.</p>";
            }
            ?>
        </section>

        <!-- Section pour le graphique -->
        <section id="graphique_resultats">
            <h2>Graphique des résultats</h2>
            <canvas id="pollutionChart" width="400" height="200"></canvas>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</div>

<script>
    // Vérifier si des données existent pour créer le graphique
    var labels = <?php echo json_encode($labels); ?>;
    var data = <?php echo json_encode($data); ?>;

    if (labels.length > 0 && data.length > 0) {
        // Créer le graphique avec Chart.js
        var ctx = document.getElementById('pollutionChart').getContext('2d');
        var pollutionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Moyenne de pollution (µg/m³)',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>

</body>
</html>

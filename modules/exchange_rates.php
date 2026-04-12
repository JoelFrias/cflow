<?php

// modules/exchange_rates.php - Módulo para configurar las tasas de cambio de monedas a DOP

// Solo accesible para admin o usuario
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rates'])) {
    foreach($_POST['rates'] as $code => $rate) {
        $stmt = $pdo->prepare("UPDATE currencies SET exchange_rate_to_dop = ? WHERE code = ?");
        $stmt->execute([$rate, $code]);
    }
    echo "<script>Swal.fire('Tasas actualizadas','','success');</script>";
}

$stmt = $pdo->query("SELECT * FROM currencies ORDER BY code");
$currencies = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h5>Configuración de Tasas de Cambio</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="alert alert-warning">
                <strong>Importante:</strong> Actualiza las tasas de cambio diariamente para mantener la precisión de tus conversiones.
            </div>
            <table class="table">
                <thead>
                    <tr><th>Moneda</th><th>Código</th><th>Tasa a DOP (1 unidad = ? RD$)</th><th>Última actualización</th></tr>
                </thead>
                <tbody>
                    <?php foreach($currencies as $cur): ?>
                    <tr>
                        <td><?= $cur['name'] ?></td>
                        <td><?= $cur['code'] ?></td>
                        <td>
                            <input type="number" step="0.0001" name="rates[<?= $cur['code'] ?>]" 
                                   class="form-control" value="<?= $cur['exchange_rate_to_dop'] ?>" 
                                   <?= $cur['code'] == 'DOP' ? 'readonly' : '' ?>>
                        </td>
                        <td><?= $cur['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="update_rates" class="btn btn-primary">Actualizar Tasas</button>
        </form>
    </div>
</div>
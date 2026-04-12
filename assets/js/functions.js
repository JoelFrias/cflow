// assets/js/functions.js - Funciones JavaScript globales para el sistema de finanzas personales

$(document).ready(function() {
    console.log('JavaScript cargado correctamente');
    
    // Inicializar tooltips de Bootstrap si existen
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-cerrar mensajes flash después de 5 segundos
    setTimeout(function() {
        $('.alert-dismissible, .alert-auto').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Manejar el cambio de tipo de transacción en el modal
    $(document).on('change', '#transType', function() {
        if($(this).val() == 'transfer') {
            $('#transferToDiv').show();
        } else {
            $('#transferToDiv').hide();
        }
    });
    
    // Manejar checkbox de recordatorio recurrente
    $(document).on('change', '#recurringCheck', function() {
        $('#recurrenceDiv').toggle(this.checked);
    });
    
    // Vista previa de conversión de moneda
    $(document).on('change keyup', '#paymentCurrency, #accountSelect, #amountInput', function() {
        const accountCurrency = $('#accountSelect option:selected').data('currency');
        const paymentCurrency = $('#paymentCurrency').val();
        const amount = $('#amountInput').val();
        
        if(amount && paymentCurrency && paymentCurrency !== accountCurrency && paymentCurrency !== '') {
            $('#conversionPreview').html(`<small class="text-info">💱 Se convertirá de ${paymentCurrency} a ${accountCurrency} automáticamente</small>`);
        } else if(amount && paymentCurrency === '') {
            $('#conversionPreview').html('');
        } else {
            $('#conversionPreview').html('');
        }
    });
});

// Función global de confirmación
function confirmAction(message, callback) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed && callback) {
            callback();
        }
    });
    return false;
}

// Función para mostrar notificaciones
function showNotification(title, message, type = 'success') {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        confirmButtonText: 'OK',
        timer: 3000,
        timerProgressBar: true
    });
}

// Función para mostrar loading
function showLoading() {
    Swal.fire({
        title: 'Cargando...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Función para cerrar loading
function hideLoading() {
    Swal.close();
}

// Función para ver transacciones de tarjeta (se usa en módulo cards)
function viewCardTransactions(cardId, currency) {
    // Esta función se puede implementar con AJAX o modal
    Swal.fire({
        title: `Movimientos de tarjeta (${currency})`,
        html: '<div class="text-center p-4">Cargando transacciones...</div>',
        width: '800px',
        showConfirmButton: true,
        confirmButtonText: 'Cerrar'
    });
    
    // Aquí puedes cargar las transacciones vía AJAX
    $.ajax({
        url: 'ajax/get_card_transactions.php',
        method: 'GET',
        data: { card_id: cardId },
        success: function(response) {
            // Actualizar el contenido del modal
            Swal.update({
                html: response
            });
        },
        error: function() {
            Swal.update({
                html: '<div class="text-danger">Error al cargar transacciones</div>'
            });
        }
    });
}

// Función para marcar recordatorio como completado
function markReminderComplete(reminderId) {
    confirmAction('¿Marcar este recordatorio como completado?', function() {
        window.location.href = '?module=reminders&complete_reminder=' + reminderId;
    });
}

// Función para actualizar dashboard (opcional)
function refreshDashboard() {
    if(window.location.href.indexOf('module=dashboard') > -1) {
        location.reload();
    }
}

// Auto-refresh cada 5 minutos solo en dashboard
setInterval(function() {
    if(window.location.href.indexOf('module=dashboard') > -1 && !window.location.href.indexOf('module=')) {
        refreshDashboard();
    }
}, 300000); // 5 minutos
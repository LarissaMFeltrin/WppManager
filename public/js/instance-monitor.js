/**
 * Monitor de Status das Instâncias WhatsApp
 * Verifica periodicamente e notifica quando uma instância desconecta
 */
(function() {
    'use strict';

    // Configurações
    var CHECK_INTERVAL = 60000; // Verificar a cada 60 segundos
    var notificationPermission = false;

    // Solicitar permissão de notificação
    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('Este navegador não suporta notificações');
            return;
        }

        if (Notification.permission === 'granted') {
            notificationPermission = true;
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(function(permission) {
                notificationPermission = (permission === 'granted');
            });
        }
    }

    // Mostrar notificação no navegador
    function showNotification(title, body, icon) {
        if (!notificationPermission) {
            // Fallback: mostrar toast
            showToastAlert(title, body);
            return;
        }

        var notification = new Notification(title, {
            body: body,
            icon: icon || '/vendor/adminlte/dist/img/AdminLTELogo.png',
            tag: 'whatsapp-disconnect',
            requireInteraction: true
        });

        notification.onclick = function() {
            window.focus();
            window.location.href = '/admin/whatsapp';
            notification.close();
        };

        // Auto-fechar após 30 segundos
        setTimeout(function() {
            notification.close();
        }, 30000);
    }

    // Toast de alerta (fallback)
    function showToastAlert(title, body) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: body,
                icon: 'warning',
                toast: true,
                position: 'top-end',
                showConfirmButton: true,
                confirmButtonText: 'Ver Instâncias',
                timer: 10000,
                timerProgressBar: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = '/admin/whatsapp';
                }
            });
        } else {
            alert(title + '\n' + body);
        }
    }

    // Verificar status das instâncias
    function checkInstanceStatus() {
        fetch('/admin/whatsapp/status/check', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(function(data) {
            if (data.instances && data.instances.length > 0) {
                data.instances.forEach(function(instance) {
                    if (instance.just_disconnected) {
                        showNotification(
                            'WhatsApp Desconectado!',
                            'A instância "' + instance.session_name + '" foi desconectada. Clique para reconectar.',
                            '/vendor/adminlte/dist/img/AdminLTELogo.png'
                        );

                        // Atualizar badge na sidebar se existir
                        updateSidebarBadge(instance.session_name, false);
                    }
                });

                // Atualizar indicador visual na página se existir
                updateStatusIndicators(data.instances);
            }
        })
        .catch(function(error) {
            console.log('Erro ao verificar status:', error);
        });
    }

    // Atualizar indicadores visuais na página
    function updateStatusIndicators(instances) {
        instances.forEach(function(instance) {
            var statusBadge = document.querySelector('[data-instance-id="' + instance.id + '"] .status-badge');
            if (statusBadge) {
                if (instance.is_connected) {
                    statusBadge.className = 'badge badge-success status-badge';
                    statusBadge.innerHTML = '<i class="fas fa-check"></i> Conectado';
                } else {
                    statusBadge.className = 'badge badge-danger status-badge';
                    statusBadge.innerHTML = '<i class="fas fa-times"></i> Desconectado';
                }
            }
        });
    }

    // Atualizar badge na sidebar
    function updateSidebarBadge(sessionName, isConnected) {
        var badge = document.querySelector('.whatsapp-status-badge');
        if (badge) {
            badge.className = isConnected ? 'badge badge-success' : 'badge badge-danger';
            badge.textContent = isConnected ? 'Online' : 'Offline';
        }
    }

    // Inicialização
    function init() {
        // Solicitar permissão de notificação após um breve delay
        setTimeout(requestNotificationPermission, 3000);

        // Verificar status imediatamente
        setTimeout(checkInstanceStatus, 5000);

        // Verificar periodicamente
        setInterval(checkInstanceStatus, CHECK_INTERVAL);

        console.log('Monitor de instâncias WhatsApp iniciado');
    }

    // Iniciar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

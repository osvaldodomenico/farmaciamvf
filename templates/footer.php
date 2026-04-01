</div>
<!-- End Main Content -->

<!-- Aviso de Sessão Expirando -->
<div id="session-warning-toast" role="alert" aria-live="polite">
    <i class="bi bi-clock-history"></i>
    <div>
        <div class="toast-title">Sessão expirando</div>
        <div id="session-countdown">Sua sessão expira em breve.</div>
        <div class="toast-actions">
            <button class="toast-btn toast-btn-primary" onclick="renovarSessao()">Continuar logado</button>
            <button class="toast-btn toast-btn-secondary" onclick="document.getElementById('session-warning-toast').classList.remove('visible')">Ignorar</button>
        </div>
    </div>
</div>

<footer class="app-footer">
    <div class="d-flex justify-content-between align-items-center">
        <span>&copy; <?php echo date('Y'); ?> Shiftworks Tecnologia e Marketing do Brasil. Todos os direitos reservados.</span>
        <span>Versão <?php echo APP_VERSION; ?></span>
    </div>
</footer>

<!-- jQuery (sem defer — bibliotecas dependentes precisam dele síncrono) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer crossorigin="anonymous"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js" defer></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js" defer></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer crossorigin="anonymous"></script>

<!-- Input Mask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" defer crossorigin="anonymous"></script>

<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer crossorigin="anonymous"></script>

<!-- Chart.js — carregado apenas quando necessário via atributo data-chartjs -->
<script>
if (document.querySelector('[data-needs-chart]') || document.getElementById('chartTopMedicamentos')) {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    s.crossOrigin = 'anonymous';
    document.head.appendChild(s);
}
</script>

<!-- SweetAlert2 — carregado sob demanda -->
<script>
window.confirmarExclusaoLazy = function(url, msg) {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    s.onload = () => confirmarExclusao(url, msg);
    document.head.appendChild(s);
    window.confirmarExclusaoLazy = (u, m) => confirmarExclusao(u, m);
};
</script>
<!-- SweetAlert2 carregado diretamente para páginas que o usam inline -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer crossorigin="anonymous"></script>

<!-- Custom Scripts -->
<script>
// ===================================================================
// BLOCO 1: SIDEBAR — roda imediatamente (só precisa de jQuery)
// ===================================================================
(function initSidebar() {
    // Fechar todos os grupos inicialmente
    $('.menu-items').addClass('collapsed');
    $('.menu-label').addClass('collapsed');

    // Detectar item ativo e expandir apenas seu grupo
    var currentPath = window.location.pathname;
    $('.sidebar-menu a').each(function () {
        var href = $(this).attr('href');
        if (!href) return;
        var linkPath = href.split('?')[0];
        if (linkPath && currentPath.length > 1 && currentPath.includes(linkPath)) {
            $(this).addClass('active');
            var $group = $(this).closest('.menu-group');
            $group.find('.menu-items').removeClass('collapsed');
            $group.find('.menu-label').removeClass('collapsed');
            return false; // break each
        }
    });

    // Toggle accordion — fecha outros, abre este
    $(document).on('click', '.menu-label[data-toggle="collapse"]', function () {
        var $label = $(this);
        var $items = $label.next('.menu-items');
        var isCollapsed = $items.hasClass('collapsed');

        // Fechar todos
        $('.menu-items').addClass('collapsed');
        $('.menu-label').addClass('collapsed');

        // Abrir este se estava fechado
        if (isCollapsed) {
            $label.removeClass('collapsed');
            $items.removeClass('collapsed');
        }
    });

    // Toggle menu mobile
    $(document).on('click', '#mobileMenuToggle, .mobile-menu-toggle', function (e) {
        e.stopPropagation();
        $('.sidebar').toggleClass('show');
        $('#sidebarOverlay, .sidebar-overlay').toggleClass('show');
    });

    // Fechar ao clicar no overlay
    $(document).on('click', '#sidebarOverlay, .sidebar-overlay', function () {
        $('.sidebar').removeClass('show');
        $(this).removeClass('show');
    });

    // Fechar ao clicar em link (mobile)
    $(document).on('click', '.sidebar-menu a', function () {
        if ($(window).width() < 768) {
            setTimeout(function () {
                $('.sidebar').removeClass('show');
                $('#sidebarOverlay, .sidebar-overlay').removeClass('show');
            }, 200);
        }
    });

    // Fechar com ESC
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.sidebar').removeClass('show');
            $('#sidebarOverlay, .sidebar-overlay').removeClass('show');
        }
    });
})();

// ===================================================================
// BLOCO 2: CONFIG DE LIBS — roda após DOMContentLoaded
// (scripts com defer já foram executados antes deste evento)
// ===================================================================
$(document).ready(function () {

    // --- Toastr ---
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton:   true,
            progressBar:   true,
            positionClass: 'toast-top-right',
            timeOut:       3000
        };
    }

    // --- DataTables pt-BR ---
    if ($.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            processing: true,
            language: {
                processing:    '',
                search:        'Buscar:',
                lengthMenu:    'Mostrar _MENU_ registros',
                info:          'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty:     'Mostrando 0 a 0 de 0 registros',
                infoFiltered:  '(filtrado de _MAX_ registros no total)',
                loadingRecords:'',
                zeroRecords:   'Nenhum registro encontrado',
                emptyTable:    'Nenhum dado disponível na tabela',
                paginate: {
                    first:    'Primeiro',
                    previous: 'Anterior',
                    next:     'Próximo',
                    last:     'Último'
                }
            },
            pageLength: 25,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
        });
    }

    // --- Select2 ---
    if ($.fn.select2) {
        $.fn.select2.defaults.set('theme',    'bootstrap-5');
        $.fn.select2.defaults.set('language', 'pt-BR');
    }

    // --- Máscaras ---
    if ($.fn.mask) {
        $('.cpf').mask('000.000.000-00');
        $('.cnpj').mask('00.000.000/0000-00');
        $('.telefone').mask('(00) 0000-0000');
        $('.celular').mask('(00) 00000-0000');
        $('.cep').mask('00000-000');
        $('.data').mask('00/00/0000');
        $('.hora').mask('00:00');
        $('.moeda').mask('#.##0,00', { reverse: true });
    }

    // --- Auto-hide alerts após 5s ---
    setTimeout(function () { $('.alert').fadeOut('slow'); }, 5000);

    // --- Transformação de texto ---
    $('.input-uppercase').each(function () {
        if ($(this).val()) $(this).val($(this).val().toUpperCase());
    });
    $('.input-lowercase').each(function () {
        if ($(this).val()) $(this).val($(this).val().toLowerCase());
    });

    // --- Tooltips ---
    window.initTooltips = function () {
        if (typeof bootstrap === 'undefined') return;
        document.querySelectorAll('[data-tooltip]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el, {
                    title:     el.getAttribute('data-tooltip'),
                    container: 'body',
                    placement: 'auto',
                    trigger:   'hover focus'
                });
            }
        });
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) new bootstrap.Tooltip(el);
        });
    };
    window.initTooltips();
});

// ===================================================================
// BLOCO 3: FUNÇÕES GLOBAIS (disponíveis para chamada inline)
// ===================================================================
function confirmarExclusao(url, mensagem) {
    mensagem = mensagem || 'Tem certeza que deseja excluir este registro?';
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Confirmar Exclusão',
            text: mensagem,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Sim, excluir!',
            cancelButtonText:   'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) window.location.href = url;
        });
    } else {
        if (confirm(mensagem)) window.location.href = url;
    }
}

function showSuccess(msg) { if (window.toastr) toastr.success(msg); }
function showError(msg)   { if (window.toastr) toastr.error(msg);   }
function showWarning(msg) { if (window.toastr) toastr.warning(msg); }
function showInfo(msg)    { if (window.toastr) toastr.info(msg);    }

// ===================================================================
// BLOCO 4: CEP (event delegation — funciona com libs deferred)
// ===================================================================
$(document).on('blur', '#cep, .cep-lookup', function () {
    var $input = $(this);
    var cep = $input.val().replace(/\D/g, '');
    if (cep.length !== 8) return;

    var fields = {
        logradouro: $input.data('logradouro') || '#logradouro',
        bairro:     $input.data('bairro')     || '#bairro',
        cidade:     $input.data('cidade')     || '#cidade',
        estado:     $input.data('estado')     || '#estado',
        numero:     $input.data('numero')     || 'input[name="numero"]'
    };

    $(Object.values(fields).join(', ')).prop('readonly', true);

    $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function (data) {
        if (!data.erro) {
            $(fields.logradouro).val(data.logradouro ? data.logradouro.toUpperCase() : '').trigger('input');
            $(fields.bairro).val(data.bairro ? data.bairro.toUpperCase() : '').trigger('input');
            $(fields.cidade).val(data.localidade ? data.localidade.toUpperCase() : '').trigger('input');
            $(fields.estado).val(data.uf).trigger('change');
            $(fields.numero).focus();
            if (window.toastr) toastr.success('Endereço localizado com sucesso!');
        } else {
            if (window.toastr) toastr.warning('CEP não encontrado.');
        }
    }).fail(function () {
        if (window.toastr) toastr.error('Erro ao consultar o CEP.');
    }).always(function () {
        $(Object.values(fields).join(', ')).prop('readonly', false);
    });
});

// Transformação de texto global
$(document).on('input', '.input-uppercase', function () {
    var s = this.selectionStart, e = this.selectionEnd;
    $(this).val($(this).val().toUpperCase());
    this.setSelectionRange(s, e);
});
$(document).on('input', '.input-lowercase', function () {
    var s = this.selectionStart, e = this.selectionEnd;
    $(this).val($(this).val().toLowerCase());
    this.setSelectionRange(s, e);
});
</script>

<?php
// Exibir mensagens da sessão via toastr (dentro de document.ready pois toastr é deferred)
$session_msgs = [];
foreach (['success','error','warning','info'] as $type) {
    if (isset($_SESSION[$type])) {
        $session_msgs[] = ['type' => $type, 'msg' => addslashes($_SESSION[$type])];
        unset($_SESSION[$type]);
    }
}
if (!empty($session_msgs)):
    echo '<script>$(document).ready(function(){';
    foreach ($session_msgs as $m) {
        echo "if(window.toastr)toastr.{$m['type']}('{$m['msg']}');";
    }
    echo '});</script>';
endif;
?>

<script>
// ===================================================================
// ALERTA DE SESSÃO EXPIRANDO
// ===================================================================
(function() {
    const SESSION_LIFETIME = <?php echo SESSION_LIFETIME; ?>; // segundos
    const WARN_BEFORE     = 300; // avisar 5 min antes
    const warnAt          = (SESSION_LIFETIME - WARN_BEFORE) * 1000;
    const toast           = document.getElementById('session-warning-toast');
    const countdownEl     = document.getElementById('session-countdown');
    let countdownInterval;

    function startCountdown() {
        let remaining = WARN_BEFORE;
        toast.classList.add('visible');

        countdownInterval = setInterval(() => {
            remaining--;
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            countdownEl.textContent = `Sua sessão expira em ${m > 0 ? m + 'm ' : ''}${s}s.`;
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '<?php echo $base_path; ?>logout.php';
            }
        }, 1000);
    }

    setTimeout(startCountdown, warnAt);

    window.renovarSessao = function() {
        clearInterval(countdownInterval);
        toast.classList.remove('visible');
        // Faz ping silencioso para renovar sessão
        fetch('<?php echo $base_path; ?>dashboard.php', { method: 'HEAD', credentials: 'same-origin' })
            .then(() => { setTimeout(startCountdown, warnAt); })
            .catch(() => {});
    };
})();

// ===================================================================
// ATALHOS DE TECLADO GLOBAIS
// ===================================================================
document.addEventListener('keydown', function(e) {
    // Alt+N — Nova Dispensação
    if (e.altKey && e.key === 'n') {
        e.preventDefault();
        const link = document.querySelector('a[href*="dispensacoes/nova"]');
        if (link) window.location.href = link.href;
    }
    // Alt+D — Dashboard
    if (e.altKey && e.key === 'd') {
        e.preventDefault();
        window.location.href = '<?php echo $base_path; ?>dashboard.php';
    }
});

</script>

</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f4c75">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Consulta de Estoque | Farmácia Mão Amiga</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f4c75 0%, #1a2b3c 100%);
            min-height: 100vh;
            min-height: 100dvh;
            color: #333;
            -webkit-font-smoothing: antialiased;
        }
        
        .app-container {
            max-width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Premium */
        .app-header {
            background: linear-gradient(135deg, #0f4c75 0%, #1a2b3c 100%);
            color: white;
            padding: 24px 20px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .app-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }
        
        .app-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }
        
        .app-header p {
            font-size: 0.875rem;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }
        
        .header-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
            position: relative;
            z-index: 1;
        }
        
        /* Search Section */
        .search-section {
            background: white;
            margin: -20px 16px 0;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            position: relative;
            z-index: 10;
        }
        
        .search-wrapper {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            font-size: 1rem;
            border: 2px solid #e8ecf0;
            border-radius: 12px;
            outline: none;
            transition: all 0.3s ease;
            font-family: inherit;
            background: #f8fafc;
        }
        
        .search-input:focus {
            border-color: #0f4c75;
            background: white;
            box-shadow: 0 0 0 4px rgba(15, 76, 117, 0.1);
        }
        
        .search-input::placeholder {
            color: #9ca3af;
        }
        
        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.25rem;
            transition: color 0.3s;
        }
        
        .search-input:focus + .search-icon {
            color: #0f4c75;
        }
        
        .clear-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: #e8ecf0;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .clear-btn.visible {
            display: flex;
        }
        
        .clear-btn:active {
            transform: translateY(-50%) scale(0.9);
            background: #d1d5db;
        }
        
        /* Results Section */
        .results-section {
            flex: 1;
            padding: 20px 16px;
            padding-bottom: 100px;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            color: white;
        }
        
        .results-count {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        /* Medicine Cards */
        .medicine-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            animation: slideUp 0.4s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        
        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .medicine-card:nth-child(1) { animation-delay: 0.05s; }
        .medicine-card:nth-child(2) { animation-delay: 0.1s; }
        .medicine-card:nth-child(3) { animation-delay: 0.15s; }
        .medicine-card:nth-child(4) { animation-delay: 0.2s; }
        .medicine-card:nth-child(5) { animation-delay: 0.25s; }
        
        .medicine-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .medicine-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a2b3c;
            line-height: 1.3;
            flex: 1;
            padding-right: 12px;
        }
        
        .stock-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }
        
        .stock-badge.available {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .stock-badge.low {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .stock-badge.empty {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .medicine-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .detail-item {
            background: #f8fafc;
            padding: 10px 12px;
            border-radius: 10px;
        }
        
        .detail-label {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .detail-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a2b3c;
        }
        
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        
        .medicine-tags {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .tag.controlled {
            background: #fef3c7;
            color: #92400e;
        }
        
        .tag.refrigerated {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .tag.expiring {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: white;
        }
        
        .empty-state i {
            font-size: 4rem;
            opacity: 0.5;
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .empty-state p {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: white;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Initial State */
        .initial-state {
            text-align: center;
            padding: 40px 20px;
            color: white;
        }
        
        .initial-state i {
            font-size: 3.5rem;
            opacity: 0.6;
            margin-bottom: 16px;
            display: block;
        }
        
        .initial-state h3 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .initial-state p {
            font-size: 0.875rem;
            opacity: 0.7;
            line-height: 1.5;
        }
        
        /* Quantity Highlight */
        .quantity-highlight {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 16px;
            border-radius: 12px;
            margin-top: 12px;
        }
        
        .quantity-number {
            font-size: 2rem;
            font-weight: 800;
            color: #0f4c75;
        }
        
        .quantity-label {
            font-size: 0.8rem;
            color: #64748b;
            text-align: left;
            line-height: 1.3;
        }
        
        /* Safe area for iOS */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .results-section {
                padding-bottom: calc(100px + env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <i class="bi bi-capsule-pill header-icon"></i>
            <h1>Consulta de Estoque</h1>
            <p>Farmácia Mão Amiga</p>
        </header>
        
        <!-- Search -->
        <div class="search-section">
            <h4 style="font-size: 1rem; font-weight: 600; color: #1a2b3c; margin-bottom: 12px; text-align: center;">Pesquise o medicamento</h4>
            <div class="search-wrapper">
                <input
                    type="text"
                    class="search-input"
                    id="searchInput"
                    placeholder="Digite o nome ou princípio ativo para verificar disponibilidade"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                >
                <i class="bi bi-search search-icon"></i>
                <button class="clear-btn" id="clearBtn" type="button">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <!-- Filtros e Ordenação -->
            <div id="filterBar" style="display:none; margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 0.78rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Filtrar:</span>
                <button class="filter-chip active" data-filter="todos" onclick="setFilter('todos', this)">Todos</button>
                <button class="filter-chip" data-filter="disponivel" onclick="setFilter('disponivel', this)">Disponíveis</button>
                <button class="filter-chip" data-filter="controlado" onclick="setFilter('controlado', this)"><i class="bi bi-shield-exclamation"></i> Controlados</button>
                <div style="margin-left: auto; display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 0.78rem; color: #64748b; font-weight: 600;">Ordenar:</span>
                    <select id="sortSelect" onchange="renderCurrentResults()" style="font-size: 0.8rem; border: 1px solid #e8ecf0; border-radius: 8px; padding: 5px 10px; background: white; color: #1a2b3c; cursor: pointer; outline: none;">
                        <option value="relevancia">Relevância</option>
                        <option value="nome">Nome A-Z</option>
                        <option value="estoque_desc">Maior estoque</option>
                        <option value="estoque_asc">Menor estoque</option>
                    </select>
                </div>
            </div>
        </div>
        <style>
        .filter-chip {
            padding: 6px 14px;
            border-radius: 20px;
            border: 1.5px solid #e2e8f0;
            background: white;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .filter-chip:hover { border-color: #0f4c75; color: #0f4c75; }
        .filter-chip.active { background: #0f4c75; color: white; border-color: #0f4c75; }
        </style>
        
        <!-- Results -->
        <section class="results-section">
            <div id="resultsContainer">
                <div class="initial-state">
                    <i class="bi bi-search"></i>
                    <h3>Pesquise um medicamento</h3>
                    <p>Digite o nome ou princípio ativo<br>para verificar a disponibilidade</p>
                </div>
            </div>
        </section>
    </div>
    
    <script>
        const searchInput = document.getElementById('searchInput');
        const clearBtn    = document.getElementById('clearBtn');
        const filterBar   = document.getElementById('filterBar');
        const resultsContainer = document.getElementById('resultsContainer');

        const LIMIT = 20;
        let searchTimeout;
        let allResultados = [];  // cache dos resultados brutos da API
        let activeFilter  = 'todos';

        // Focus no input ao carregar
        setTimeout(() => searchInput.focus(), 300);

        // Atualiza botão de limpar
        function updateClearButton() {
            clearBtn.classList.toggle('visible', searchInput.value.length > 0);
        }

        // Limpa busca
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            updateClearButton();
            searchInput.focus();
            allResultados = [];
            filterBar.style.display = 'none';
            showInitialState();
        });

        // Busca com debounce
        searchInput.addEventListener('input', function() {
            updateClearButton();
            clearTimeout(searchTimeout);
            const termo = this.value.trim();
            if (termo.length === 0) {
                allResultados = [];
                filterBar.style.display = 'none';
                showInitialState();
                return;
            }
            if (termo.length < 2) return;
            searchTimeout = setTimeout(() => buscar(termo), 300);
        });

        function setFilter(filter, btn) {
            activeFilter = filter;
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            renderCurrentResults();
        }

        function applyFilterAndSort(resultados) {
            let filtered = resultados;

            if (activeFilter === 'disponivel') {
                filtered = resultados.filter(m => parseInt(m.quantidade_estoque) > 0);
            } else if (activeFilter === 'controlado') {
                filtered = resultados.filter(m => m.controlado == 1);
            }

            const sort = document.getElementById('sortSelect')?.value || 'relevancia';
            if (sort === 'nome') {
                filtered = [...filtered].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
            } else if (sort === 'estoque_desc') {
                filtered = [...filtered].sort((a, b) => parseInt(b.quantidade_estoque) - parseInt(a.quantidade_estoque));
            } else if (sort === 'estoque_asc') {
                filtered = [...filtered].sort((a, b) => parseInt(a.quantidade_estoque) - parseInt(b.quantidade_estoque));
            }

            return filtered;
        }

        function renderCurrentResults() {
            if (!allResultados.length) return;
            const processed = applyFilterAndSort(allResultados);
            renderResults(processed, allResultados.length);
        }

        function showInitialState() {
            resultsContainer.innerHTML = `
                <div class="initial-state">
                    <i class="bi bi-search"></i>
                    <h3>Pesquise um medicamento</h3>
                    <p>Digite o nome ou princípio ativo<br>para verificar a disponibilidade</p>
                </div>
            `;
        }

        function showLoading() {
            resultsContainer.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Buscando...</p>
                </div>
            `;
        }

        function showEmpty(termo) {
            resultsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Nenhum resultado</h3>
                    <p>Não encontramos medicamentos para "${escapeHtml(termo)}"</p>
                </div>
            `;
        }

        async function buscar(termo) {
            showLoading();
            try {
                const response = await fetch(`consulta_estoque_api.php?q=${encodeURIComponent(termo)}`);
                const data = await response.json();

                if (!data.success || !data.resultados || data.resultados.length === 0) {
                    filterBar.style.display = 'none';
                    showEmpty(termo);
                    return;
                }

                allResultados = data.resultados;
                filterBar.style.display = 'flex';
                renderCurrentResults();
            } catch (error) {
                console.error('Erro na busca:', error);
                resultsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <h3>Erro de conexão</h3>
                        <p>Verifique sua internet e tente novamente</p>
                    </div>
                `;
            }
        }

        function renderResults(resultados, totalBruto) {
            const atingiuLimite = totalBruto >= LIMIT;

            let html = `
                <div class="results-header">
                    <span class="results-count">
                        ${resultados.length} resultado${resultados.length !== 1 ? 's' : ''}
                        ${activeFilter !== 'todos' ? ' (filtrado)' : ''}
                    </span>
                </div>
            `;

            if (atingiuLimite) {
                html += `
                    <div style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.82rem; color: rgba(255,255,255,0.9); display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-info-circle" style="flex-shrink:0;"></i>
                        Exibindo os ${LIMIT} primeiros resultados. Refine sua busca para resultados mais específicos.
                    </div>
                `;
            }
            
            resultados.forEach(med => {
                const qtd = parseInt(med.quantidade_estoque) || 0;
                let stockClass = 'available';
                let stockText = 'Disponível';
                
                if (qtd === 0) {
                    stockClass = 'empty';
                    stockText = 'Indisponível';
                } else if (qtd <= 10) {
                    stockClass = 'low';
                    stockText = 'Estoque baixo';
                }
                
                // Tags
                let tags = '';
                if (med.controlado == 1) {
                    tags += '<span class="tag controlled"><i class="bi bi-shield-exclamation"></i> Controlado</span>';
                }
                if (med.refrigerado && med.refrigerado !== 'ambiente') {
                    tags += '<span class="tag refrigerated"><i class="bi bi-snow"></i> Refrigerado</span>';
                }
                
                // Validade
                let validadeInfo = '';
                if (med.proxima_validade) {
                    const hoje = new Date();
                    const validade = new Date(med.proxima_validade);
                    const diffDias = Math.ceil((validade - hoje) / (1000 * 60 * 60 * 24));
                    
                    if (diffDias <= 30 && diffDias > 0) {
                        tags += `<span class="tag expiring"><i class="bi bi-clock"></i> Vence em ${diffDias} dias</span>`;
                    } else if (diffDias <= 0) {
                        tags += '<span class="tag expiring"><i class="bi bi-exclamation-circle"></i> Lote vencido</span>';
                    }
                    
                    validadeInfo = `
                        <div class="detail-item">
                            <div class="detail-label">Próx. Validade</div>
                            <div class="detail-value">${formatDate(med.proxima_validade)}</div>
                        </div>
                    `;
                }
                
                html += `
                    <div class="medicine-card">
                        <div class="medicine-header">
                            <div class="medicine-name">${escapeHtml(med.nome)}</div>
                            <span class="stock-badge ${stockClass}">${stockText}</span>
                        </div>
                        
                        <div class="medicine-details">
                            <div class="detail-item full-width">
                                <div class="detail-label">Princípio Ativo</div>
                                <div class="detail-value">${escapeHtml(med.principio_ativo || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Dosagem</div>
                                <div class="detail-value">${escapeHtml(med.dosagem_concentracao || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Forma</div>
                                <div class="detail-value">${escapeHtml(med.forma_farmaceutica || '-')}</div>
                            </div>
                            ${validadeInfo}
                            <div class="detail-item">
                                <div class="detail-label">Lotes</div>
                                <div class="detail-value">${med.total_lotes || 0}</div>
                            </div>
                        </div>
                        
                        <div class="quantity-highlight">
                            <span class="quantity-number">${qtd}</span>
                            <span class="quantity-label">unidades<br>em estoque</span>
                        </div>
                        
                        ${tags ? `<div class="medicine-tags">${tags}</div>` : ''}
                    </div>
                `;
            });
            
            resultsContainer.innerHTML = html;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    
    <!-- Footer -->
    <footer style="background: linear-gradient(135deg, rgba(15, 76, 117, 0.9) 0%, rgba(26, 43, 60, 0.95) 100%); color: rgba(255,255,255,0.8); text-align: center; padding: 16px 20px; font-size: 0.8rem;">
        <div>&copy; 2026 <strong>Shiftworks Tecnologia e Marketing do Brasil</strong></div>
        <div style="margin-top: 4px; opacity: 0.7;">Todos os direitos reservados.</div>
    </footer>
</body>
</html>

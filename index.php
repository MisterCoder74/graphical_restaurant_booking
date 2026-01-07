<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Gestione Ristorante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }
        
        .page-card {
            text-align: center;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: white;
            margin-bottom: 1rem;
        }
        
        .page-card:hover {
            transform: scale(1.05);
        }
        
        .page-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        
        .page-card:hover .page-icon {
            transform: scale(1.1);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .page-description {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .features-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 3rem 0;
        }
        
        .feature-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-bottom: 1rem;
            color: white;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #ffc107;
        }
        
        .feature-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .feature-description {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            opacity: 0.8;
        }
        
        .current-time {
            color: white;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .footer {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 1.5rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="bi bi-shop"></i>
                    Sistema Gestione Ristorante
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="graphical_reservation.html">
                                <i class="bi bi-grid-3x3"></i> Planimetria
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reservation_history.html">
                                <i class="bi bi-clock-history"></i> Storico
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="statistics.html">
                                <i class="bi bi-bar-chart"></i> Statistiche
                            </a>
                        </li>
                    </ul>
                    <div class="current-time">
                        <div class="status-indicator"></div>
                        <span id="currentTime"></span>
                        <span id="timezoneDisplay"></span>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card dashboard-card">
                            <div class="card-body p-5">
                                <div class="text-center mb-5">
                                    <h1 class="display-4 mb-3" style="color: #495057;">
                                        <i class="bi bi-shop-window text-primary"></i>
                                        Benvenuto nel Sistema di Gestione Ristorante
                                    </h1>
                                    <p class="lead text-muted">
                                        Gestisci prenotazioni, pianifica tavoli e analizza le performance del tuo ristorante
                                    </p>
                                </div>
                                
                                <!-- Quick Stats -->
                                <div class="quick-stats" id="quickStats">
                                    <div class="stat-card">
                                        <div class="stat-number" id="activeReservations">0</div>
                                        <div class="stat-label">Prenotazioni Attive</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number" id="occupiedTables">0</div>
                                        <div class="stat-label">Tavoli Occupati</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number" id="todayGuests">0</div>
                                        <div class="stat-label">Ospiti Oggi</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number" id="totalTables">0</div>
                                        <div class="stat-label">Tavoli Totali</div>
                                    </div>
                                </div>
                                
                                <!-- Main Navigation Cards -->
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="page-card" onclick="window.location.href='graphical_reservation.html'">
                                            <div class="page-icon text-primary">
                                                <i class="bi bi-grid-3x3"></i>
                                            </div>
                                            <div class="page-title">Planimetria Interattiva</div>
                                            <div class="page-description">
                                                Progetta e gestisci la disposizione dei tavoli con il sistema canvas-based. 
                                                Trascina i tavoli, crea prenotazioni e monitora lo stato in tempo reale.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="page-card" onclick="window.location.href='reservation_history.html'">
                                            <div class="page-icon text-success">
                                                <i class="bi bi-clock-history"></i>
                                            </div>
                                            <div class="page-title">Monitor & Storico</div>
                                            <div class="page-description">
                                                Visualizza lo stato attuale di tutti i tavoli e gestisci l'archivio delle prenotazioni 
                                                con filtri avanzati e aggiornamenti automatici.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="page-card" onclick="window.location.href='statistics.html'">
                                            <div class="page-icon text-info">
                                                <i class="bi bi-bar-chart"></i>
                                            </div>
                                            <div class="page-title">Analytics & Statistiche</div>
                                            <div class="page-description">
                                                Analizza le performance del ristorante con grafici interattivi, 
                                                tassi di utilizzo e metriche dettagliate.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="text-white mb-3">Funzionalità Principali</h2>
                    <p class="text-white-50">Tutto quello che serve per gestire il tuo ristorante in modo efficiente</p>
                </div>
                
                <div class="row">
                    <div class="col-lg-4">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-cursor"></i>
                            </div>
                            <div class="feature-title">Drag & Drop</div>
                            <div class="feature-description">
                                Riposiziona i tavoli trascinandoli sul canvas con snap-to-grid automatico
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="feature-title">Tempo Reale</div>
                            <div class="feature-description">
                                Stato tavoli e prenotazioni si aggiornano automaticamente ogni 30 secondi
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="feature-title">Anti-Confitto</div>
                            <div class="feature-description">
                                Sistema di rilevamento conflitti per evitare sovrapposizioni di prenotazioni
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">
                            <i class="bi bi-c-circle"></i>
                            2024 Sistema Gestione Ristorante. Tutti i diritti riservati.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">
                            <i class="bi bi-code-square"></i>
                            Sviluppato con HTML5, Bootstrap 5, Vanilla JS e PHP
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentTime();
            loadQuickStats();
            
            // Update time every second
            setInterval(updateCurrentTime, 1000);
            
            // Update stats every 30 seconds
            setInterval(loadQuickStats, 30000);
        });
        
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('it-IT', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('timezoneDisplay').textContent = `(${timezone})`;
        }
        
        async function loadQuickStats() {
            // Initialize all stat variables with default values
            let totalTables = 0;
            let activeReservations = 0;
            let todayGuests = 0;
            let occupiedTables = 0;

            try {
                // Load layout for total tables
                const layoutResponse = await fetch('./php/api_layout.php?action=load&v=' + Date.now());
                const layoutResult = await layoutResponse.json();

                if (layoutResult.success && layoutResult.data && layoutResult.data.tables) {
                    totalTables = layoutResult.data.tables.length;
                    document.getElementById('totalTables').textContent = totalTables;
                } else {
                    document.getElementById('totalTables').textContent = '0';
                }

                // Load reservations for active stats
                const reservationsResponse = await fetch('./php/api_reservations.php?action=list&v=' + Date.now());
                const reservationsResult = await reservationsResponse.json();

                if (reservationsResult.success && Array.isArray(reservationsResult.data)) {
                    const reservations = reservationsResult.data || [];
                    const now = new Date();
                    const today = now.toISOString().split('T')[0];
                    const currentHour = now.getHours();
                    const currentMinute = now.getMinutes();
                    const currentMinutes = currentHour * 60 + currentMinute;

                    // Active reservations (upcoming or current today)
                    activeReservations = reservations.filter(r =>
                        r.status === 'upcoming' ||
                        (r.date === today && r.status !== 'cancelled')
                    ).length;

                    // Today's guests
                    const todayReservations = reservations.filter(r => r.date === today && r.status !== 'cancelled');
                    todayGuests = todayReservations.reduce((sum, r) => sum + (parseInt(r.numberOfGuests) || 0), 0);

                    // Calculate occupied tables based on actual current time
                    occupiedTables = 0;
                    const occupiedTableIds = new Set();

                    for (const reservation of reservations) {
                        // Skip cancelled reservations
                        if (reservation.status === 'cancelled') {
                            continue;
                        }

                        // Check if reservation is for today
                        if (reservation.date !== today) {
                            continue;
                        }

                        // Parse reservation time
                        const startTimeParts = (reservation.startTime || '').split(':');
                        if (startTimeParts.length !== 2) {
                            continue;
                        }

                        const reservationHour = parseInt(startTimeParts[0]) || 0;
                        const reservationMinute = parseInt(startTimeParts[1]) || 0;
                        const reservationStartMinutes = reservationHour * 60 + reservationMinute;
                        const duration = parseInt(reservation.duration) || 60; // Default 60 minutes
                        const reservationEndMinutes = reservationStartMinutes + duration;

                        // Check if reservation is currently active
                        if (currentMinutes >= reservationStartMinutes && currentMinutes < reservationEndMinutes) {
                            const tableId = parseInt(reservation.tableId);
                            if (!isNaN(tableId)) {
                                occupiedTableIds.add(tableId);
                            }
                        }
                    }

                    occupiedTables = occupiedTableIds.size;

                    document.getElementById('activeReservations').textContent = activeReservations;
                    document.getElementById('occupiedTables').textContent = occupiedTables;
                    document.getElementById('todayGuests').textContent = todayGuests;
                } else {
                    // Set default values if no reservations data
                    document.getElementById('activeReservations').textContent = '0';
                    document.getElementById('occupiedTables').textContent = '0';
                    document.getElementById('todayGuests').textContent = '0';
                }

            } catch (error) {
                console.error('Error loading quick stats:', error);
                // Set fallback values on error
                document.getElementById('totalTables').textContent = '0';
                document.getElementById('activeReservations').textContent = '0';
                document.getElementById('occupiedTables').textContent = '0';
                document.getElementById('todayGuests').textContent = '0';
            }
        }
    </script>
</body>
</html>
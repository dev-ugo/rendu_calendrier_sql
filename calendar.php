<?php
require_once 'calendar_db.php';

// Identifiant utilisateur anonyme via cookie (durée 2 ans)
if (!isset($_COOKIE['calendar_user_id'])) {
    $userId = mt_rand(1000000000, 9999999999);
    setcookie('calendar_user_id', $userId, time() + 86400 * 365 * 2, '/');
} else {
    $userId = (int) $_COOKIE['calendar_user_id'];
}

$month = (int) ($_GET['month'] ?? date('n'));
$year  = (int) ($_GET['year']  ?? date('Y'));

// Suppression
if (isset($_GET['delete_event'])) {
    $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
    $stmt->execute([(int) $_GET['delete_event'], $userId]);
    header("Location: calendar.php?month=$month&year=$year");
    exit;
}

// Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $eventId    = (int) $_POST['event_id'];
    $eventDate  = $_POST['event_date'];
    $eventTitle = trim($_POST['event_title']);
    $eventDesc  = trim($_POST['event_description']) ?: null;

    if ($eventId && $eventDate && $eventTitle) {
        $stmt = $pdo->prepare(
            "UPDATE calendar_events SET date = ?, title = ?, description = ? WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$eventDate, $eventTitle, $eventDesc, $eventId, $userId]);
    }
    header("Location: calendar.php?month=$month&year=$year");
    exit;
}

// Chargement de l'événement à modifier
$editEvent = null;
if (isset($_GET['edit_event'])) {
    $stmt = $pdo->prepare("SELECT id, date, title, description FROM calendar_events WHERE id = ? AND user_id = ?");
    $stmt->execute([(int) $_GET['edit_event'], $userId]);
    $editEvent = $stmt->fetch();
}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $eventDate  = $_POST['event_date'];
    $eventTitle = trim($_POST['event_title']);
    $eventDesc  = trim($_POST['event_description']) ?: null;

    if ($eventDate && $eventTitle) {
        $stmt = $pdo->prepare(
            "INSERT INTO calendar_events (user_id, date, title, description) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $eventDate, $eventTitle, $eventDesc]);
    }
    header("Location: calendar.php?month=$month&year=$year");
    exit;
}

// Événements du mois (pour tous les utilisateurs)
$stmt = $pdo->prepare(
    "SELECT id, user_id, date, title, description
     FROM calendar_events
     WHERE MONTH(date) = ? AND YEAR(date) = ?
     ORDER BY date ASC"
);
$stmt->execute([$month, $year]);
$events = $stmt->fetchAll();

// Navigation mois précédent / suivant
$prevMonth = $month === 1  ? 12 : $month - 1;
$prevYear  = $month === 1  ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1  : $month + 1;
$nextYear  = $month === 12 ? $year + 1 : $year;

// Données du calendrier
$firstStamp      = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth     = (int) date('t', $firstStamp);
$firstDayOfMonth = (int) date('N', $firstStamp);
$monthName       = strtoupper(date('F Y', $firstStamp));
$today           = (int) date('j');
$currentMonth    = (int) date('n');
$currentYear     = (int) date('Y');

// Index des jours avec événements pour la grille
$eventDays = [];
foreach ($events as $event) {
    $eventDays[(int) date('j', strtotime($event['date']))][] = $event;
}
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Calendar</title>
</head>

<body>
    <div class="wrapp">
        <div class="flex-calendar">
            <div class="month">
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="arrow visible"></a>
                <div class="label">
                    <?php echo $monthName; ?>
                </div>
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="arrow visible"></a>
            </div>

            <div class="week">
                <div class="day">M</div>
                <div class="day">T</div>
                <div class="day">W</div>
                <div class="day">T</div>
                <div class="day">F</div>
                <div class="day">S</div>
                <div class="day">S</div>
            </div>

            <div class="days">
                <?php
                for ($i = 1; $i < $firstDayOfMonth; $i++) {
                    echo '<div class="day out"><div class="number"></div></div>';
                }

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $classes = ['day'];
                    if ($day === $today && $month === $currentMonth && $year === $currentYear) {
                        $classes[] = 'selected';
                    }
                    if (date('N', mktime(0, 0, 0, $month, $day, $year)) == 7) {
                        $classes[] = 'disabled';
                    }
                    if (isset($eventDays[$day])) {
                        $classes[] = 'event';
                    }
                    printf('<div class="%s"><div class="number">%d</div></div>', implode(' ', $classes), $day);
                }

                $lastDayOfWeek = (int) date('N', mktime(0, 0, 0, $month, $daysInMonth, $year));
                for ($i = $lastDayOfWeek + 1; $i <= 7; $i++) {
                    echo '<div class="day out"><div class="number"></div></div>';
                }
                ?>
            </div>
        </div>
        
        <div class="event-form-container<?php echo $editEvent ? ' editing' : ''; ?>">
            <h2><?php echo $editEvent ? 'Modifier l\'événement' : 'Ajouter un événement'; ?></h2>
            <form method="POST" action="?month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="event-form">
                <?php if ($editEvent): ?>
                    <input type="hidden" name="event_id" value="<?php echo $editEvent['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="event_date">Date de l'événement *</label>
                    <input type="date" id="event_date" name="event_date"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['date']) : date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="event_title">Titre de l'événement *</label>
                    <input type="text" id="event_title" name="event_title"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['title']) : ''; ?>"
                           placeholder="Ex: Réunion d'équipe" required>
                </div>

                <div class="form-group">
                    <label for="event_description">Description (optionnelle)</label>
                    <textarea id="event_description" name="event_description"
                              placeholder="Détails de l'événement..."><?php echo $editEvent ? htmlspecialchars($editEvent['description']) : ''; ?></textarea>
                </div>

                <div class="form-actions">
                    <?php if ($editEvent): ?>
                        <button type="submit" name="update_event" class="btn-submit">Enregistrer les modifications</button>
                        <a href="?month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn-cancel">Annuler</a>
                    <?php else: ?>
                        <button type="submit" name="add_event" class="btn-submit">Ajouter l'événement</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="events-list">
            <h2>Événements planifiés</h2>
            <?php if (empty($events)): ?>
                <div class="no-events">Aucun événement planifié pour ce mois.</div>
            <?php else: ?>
                <?php foreach ($events as $event):
                    $formattedDate = date('d/m/Y', strtotime($event['date']));
                ?>
                    <div class="event-item">
                        <div class="event-content">
                            <div class="event-date"><?php echo $formattedDate; ?></div>
                            <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                            <?php if (!empty($event['description'])): ?>
                                <div class="event-description"><?php echo htmlspecialchars($event['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['user_id'] == $userId): ?>
                        <div class="event-actions">
                            <a href="?edit_event=<?php echo $event['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>"
                               class="btn-edit">Modifier</a>
                            <a href="?delete_event=<?php echo $event['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>"
                               class="btn-delete"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">Supprimer</a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
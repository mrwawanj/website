<?php
/**
 * Mini PHP Adminer - Lightweight Database Manager
 * Simple and compact database administration tool
 */

session_start();
error_reporting(0);
ini_set('display_errors', 0);

// Security: Access control with password
define('ACCESS_PASSWORD', 'changeme123'); // GANTI PASSWORD INI!

// Check access
if (!isset($_SESSION['authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_pass'])) {
        if ($_POST['access_pass'] === ACCESS_PASSWORD) {
            $_SESSION['authenticated'] = true;
        } else {
            $accessError = 'Invalid password';
        }
    }
    
    if (!isset($_SESSION['authenticated'])) {
        ?>
        <!DOCTYPE html>
        <html><head><meta charset="UTF-8"><title>Access Required</title>
        <style>
            body { font-family: Arial; background: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .access-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 300px; }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
            button { width: 100%; padding: 10px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
            .error { color: #e74c3c; margin: 10px 0; }
        </style>
        </head><body>
        <div class="access-box">
            <h2>Access Required</h2>
            <?php if (isset($accessError)): ?>
                <div class="error"><?= $accessError ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="access_pass" placeholder="Enter password" required autofocus>
                <button type="submit">Access</button>
            </form>
        </div>
        </body></html>
        <?php
        exit;
    }
}

// Configuration
$config = [
    'title' => 'Mini DB Manager',
    'version' => '1.0',
];

// Security functions
function sanitizeInput($input) {
    return trim(strip_tags($input));
}

function validateTableName($table) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $table);
}

function validateColumnName($column) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $column);
}

// Database connection
$db = null;
$error = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $host = sanitizeInput($_POST['host'] ?? 'localhost');
    $user = sanitizeInput($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';
    $dbname = sanitizeInput($_POST['dbname'] ?? '');
    
    // Rate limiting
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt'] = time();
    }
    
    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt']) < 300) {
        $error = "Too many login attempts. Wait 5 minutes.";
    } else {
        try {
            $dsn = "mysql:host=$host" . ($dbname ? ";dbname=$dbname" : "");
            $db = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            $_SESSION['db_host'] = $host;
            $_SESSION['db_user'] = $user;
            $_SESSION['db_pass'] = $pass;
            $_SESSION['db_name'] = $dbname;
            $_SESSION['logged_in'] = true;
            $_SESSION['login_attempts'] = 0;
        } catch (PDOException $e) {
            $error = "Connection failed";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
        }
    }
}

// Restore connection from session
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    try {
        $dsn = "mysql:host={$_SESSION['db_host']}" . ($_SESSION['db_name'] ? ";dbname={$_SESSION['db_name']}" : "");
        $db = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e) {
        $error = "Connection failed";
        session_destroy();
    }
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF validation failed');
        }
    }
}

// Handle database selection
if (isset($_GET['db']) && $db) {
    $dbname = sanitizeInput($_GET['db']);
    $_SESSION['db_name'] = $dbname;
    $db->exec("USE `$dbname`");
}

// Handle SQL query execution
$queryResult = null;
$queryError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query']) && $db) {
    validateCSRF();
    $sql = trim($_POST['sql']);
    
    // Basic SQL injection prevention - block dangerous keywords in certain contexts
    $dangerousPatterns = [
        '/\bINTO\s+OUTFILE\b/i',
        '/\bLOAD_FILE\b/i',
        '/\bINTO\s+DUMPFILE\b/i'
    ];
    
    $isDangerous = false;
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $sql)) {
            $isDangerous = true;
            break;
        }
    }
    
    if ($isDangerous) {
        $queryError = "Query contains restricted operations";
    } else {
        try {
            $stmt = $db->query($sql);
            if ($stmt) {
                $queryResult = $stmt->fetchAll();
            } else {
                $queryResult = ['success' => true];
            }
        } catch (PDOException $e) {
            $queryError = "Query error";
        }
    }
}

// Handle table operations
if (isset($_GET['action']) && $db) {
    validateCSRF();
    $table = sanitizeInput($_GET['table'] ?? '');
    
    if (!validateTableName($table)) {
        $error = "Invalid table name";
    } else {
        switch ($_GET['action']) {
            case 'truncate':
                try {
                    $db->exec("TRUNCATE TABLE `$table`");
                    header("Location: ?db={$_SESSION['db_name']}&table=$table");
                    exit;
                } catch (PDOException $e) {
                    $error = "Operation failed";
                }
                break;
                
            case 'drop':
                try {
                    $db->exec("DROP TABLE `$table`");
                    header("Location: ?db={$_SESSION['db_name']}");
                    exit;
                } catch (PDOException $e) {
                    $error = "Operation failed";
                }
                break;
        }
    }
}

// Handle record delete
if (isset($_GET['delete']) && isset($_GET['table']) && $db) {
    validateCSRF();
    $table = sanitizeInput($_GET['table']);
    $id = sanitizeInput($_GET['delete']);
    
    if (!validateTableName($table)) {
        $error = "Invalid table name";
    } else {
        try {
            $stmt = $db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            $pk = $stmt->fetch();
            if ($pk) {
                $stmt = $db->prepare("DELETE FROM `$table` WHERE `{$pk['Column_name']}` = ?");
                $stmt->execute([$id]);
            }
            header("Location: ?db={$_SESSION['db_name']}&table=$table");
            exit;
        } catch (PDOException $e) {
            $error = "Delete failed";
        }
    }
}

// Handle insert/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && $db) {
    validateCSRF();
    $table = sanitizeInput($_POST['table']);
    $data = $_POST['data'] ?? [];
    
    if (!validateTableName($table)) {
        $error = "Invalid table name";
    } else {
        try {
            // Validate all column names
            foreach (array_keys($data) as $col) {
                if (!validateColumnName($col)) {
                    throw new Exception("Invalid column name");
                }
            }
            
            if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
                // Update existing record
                $id = sanitizeInput($_POST['edit_id']);
                $setParts = [];
                $values = [];
                
                foreach ($data as $col => $val) {
                    $setParts[] = "`$col` = ?";
                    $values[] = $val;
                }
                
                $sql = "UPDATE `$table` SET " . implode(', ', $setParts);
                
                // Get primary key for WHERE clause
                $stmt = $db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
                $pk = $stmt->fetch();
                if ($pk) {
                    $sql .= " WHERE `{$pk['Column_name']}` = ?";
                    $values[] = $id;
                }
                
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            } else {
                // Insert new record
                $columns = array_keys($data);
                $values = array_values($data);
                $placeholders = array_fill(0, count($values), '?');
                
                $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . 
                       implode(', ', $placeholders) . ")";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
            header("Location: ?db={$_SESSION['db_name']}&table=$table");
            exit;
        } catch (Exception $e) {
            $error = "Save failed";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B4YYp4s|GHOST HAXOR<?= $config['title'] ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { font-size: 24px; }
        header a { color: #3498db; text-decoration: none; }
        .login-box {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .login-box h2 { margin-bottom: 20px; color: #2c3e50; }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="password"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea { min-height: 150px; font-family: monospace; }
        button, .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }
        button:hover, .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-small { padding: 5px 10px; font-size: 12px; }
        .error {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .sidebar h3 { margin-bottom: 15px; color: #2c3e50; }
        .sidebar ul { list-style: none; }
        .sidebar li {
            padding: 8px;
            margin: 5px 0;
            background: #ecf0f1;
            border-radius: 4px;
        }
        .sidebar li:hover { background: #bdc3c7; }
        .sidebar a {
            text-decoration: none;
            color: #2c3e50;
            display: block;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #34495e;
            color: white;
            font-weight: bold;
        }
        tr:hover { background: #f5f5f5; }
        .grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
        .query-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .form-edit {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
        }
        .form-edit input[type="text"], 
        .form-edit textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .actions { white-space: nowrap; }
    </style>
</head>
<body>
    <header>
        <h1><?= $config['title'] ?> <small>v<?= $config['version'] ?></small></h1>
        <?php if ($db): ?>
            <div>
                <span>Connected: <?= htmlspecialchars($_SESSION['db_user']) ?>@<?= htmlspecialchars($_SESSION['db_host']) ?></span>
                <a href="?logout=1">Logout</a>
            </div>
        <?php endif; ?>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!$db): ?>
            <!-- Login Form -->
            <div class="login-box">
                <h2>Database Login</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label>Host:</label>
                        <input type="text" name="host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="user" required>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="pass">
                    </div>
                    <div class="form-group">
                        <label>Database (optional):</label>
                        <input type="text" name="dbname">
                    </div>
                    <button type="submit" name="login">Connect</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Main Interface -->
            <div class="grid">
                <!-- Sidebar -->
                <div class="sidebar">
                    <h3>Databases</h3>
                    <ul>
                        <?php
                        $dbs = $db->query("SHOW DATABASES")->fetchAll();
                        foreach ($dbs as $database):
                            $dbName = $database['Database'];
                            $active = ($_SESSION['db_name'] ?? '') === $dbName;
                        ?>
                            <li style="<?= $active ? 'background:#3498db;' : '' ?>">
                                <a href="?db=<?= urlencode($dbName) ?>" 
                                   style="<?= $active ? 'color:white;' : '' ?>">
                                    <?= htmlspecialchars($dbName) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!empty($_SESSION['db_name'])): ?>
                        <h3 style="margin-top: 30px;">Tables</h3>
                        <ul>
                            <?php
                            $tables = $db->query("SHOW TABLES")->fetchAll();
                            foreach ($tables as $tbl):
                                $tableName = array_values($tbl)[0];
                                $active = ($_GET['table'] ?? '') === $tableName;
                            ?>
                                <li style="<?= $active ? 'background:#3498db;' : '' ?>">
                                    <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($tableName) ?>"
                                       style="<?= $active ? 'color:white;' : '' ?>">
                                        <?= htmlspecialchars($tableName) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Main Content -->
                <div class="content">
                    <?php if (isset($_GET['table']) && !empty($_SESSION['db_name'])): ?>
                        <!-- Table View -->
                        <?php
                        $table = $_GET['table'];
                        
                        // Handle edit/new form
                        if (isset($_GET['edit']) || isset($_GET['new'])):
                            $editId = $_GET['edit'] ?? null;
                            $editData = [];
                            
                            // Get table structure
                            $columns = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll();
                            
                            // Get existing data if editing
                            if ($editId !== null) {
                                $stmt = $db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
                                $pk = $stmt->fetch();
                                if ($pk) {
                                    $stmt = $db->prepare("SELECT * FROM `$table` WHERE `{$pk['Column_name']}` = ?");
                                    $stmt->execute([$editId]);
                                    $editData = $stmt->fetch() ?: [];
                                }
                            }
                        ?>
                            <h2><?= $editId ? 'Edit' : 'New' ?> Record: <?= htmlspecialchars($table) ?></h2>
                            
                            <div class="form-edit">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                    <?php if ($editId): ?>
                                        <input type="hidden" name="edit_id" value="<?= htmlspecialchars($editId) ?>">
                                    <?php endif; ?>
                                    
                                    <?php foreach ($columns as $col): 
                                        $colName = $col['Field'];
                                        $colType = $col['Type'];
                                        $value = $editData[$colName] ?? '';
                                        $isAuto = strpos($col['Extra'], 'auto_increment') !== false;
                                    ?>
                                        <div class="form-group">
                                            <label><?= htmlspecialchars($colName) ?> 
                                                <small style="color: #777;">(<?= htmlspecialchars($colType) ?>)</small>
                                            </label>
                                            
                                            <?php if ($isAuto && !$editId): ?>
                                                <input type="text" value="Auto" disabled>
                                            <?php elseif (strpos($colType, 'text') !== false || strpos($colType, 'blob') !== false): ?>
                                                <textarea name="data[<?= htmlspecialchars($colName) ?>]" rows="3"><?= htmlspecialchars($value) ?></textarea>
                                            <?php else: ?>
                                                <input type="text" 
                                                       name="data[<?= htmlspecialchars($colName) ?>]" 
                                                       value="<?= htmlspecialchars($value) ?>"
                                                       <?= $isAuto && $editId ? 'readonly' : '' ?>>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div style="margin-top: 20px;">
                                        <button type="submit" name="save" class="btn btn-success">Save</button>
                                        <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($table) ?>" class="btn">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        
                        <?php else:
                            // Normal table view
                            $stmt = $db->query("SELECT * FROM `$table` LIMIT 100");
                            $rows = $stmt->fetchAll();
                            $columns = $rows ? array_keys($rows[0]) : [];
                            
                            // Get primary key
                            $stmt = $db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
                            $pkInfo = $stmt->fetch();
                            $pkColumn = $pkInfo ? $pkInfo['Column_name'] : ($columns[0] ?? null);
                        ?>
                        
                        <h2>Table: <?= htmlspecialchars($table) ?></h2>
                        
                        <div style="margin: 15px 0;">
                            <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($table) ?>&new=1" 
                               class="btn btn-success">New Record</a>
                            <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($table) ?>&action=truncate" 
                               class="btn btn-small btn-danger"
                               onclick="return confirm('Truncate table?')">Truncate</a>
                            <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($table) ?>&action=drop" 
                               class="btn btn-small btn-danger"
                               onclick="return confirm('Drop table?')">Drop</a>
                        </div>

                        <?php if ($rows): ?>
                            <div style="overflow-x: auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php foreach ($columns as $col): ?>
                                                <th><?= htmlspecialchars($col) ?></th>
                                            <?php endforeach; ?>
                                            <th class="actions">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td><?= htmlspecialchars(substr($value ?? '', 0, 100)) ?></td>
                                                <?php endforeach; ?>
                                                <td class="actions">
                                                    <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($table) ?>&edit=<?= urlencode($row[$pkColumn]) ?>" 
                                                       class="btn btn-small">Edit</a>
                                                    <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($table) ?>&delete=<?= urlencode($row[$pkColumn]) ?>" 
                                                       class="btn btn-small btn-danger"
                                                       onclick="return confirm('Delete this record?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No records found.</p>
                        <?php endif; ?>
                        <?php endif; ?>

                    <?php elseif (!empty($_SESSION['db_name'])): ?>
                        <!-- Database Overview -->
                        <h2>Database: <?= htmlspecialchars($_SESSION['db_name']) ?></h2>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>Engine</th>
                                    <th>Collation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tables = $db->query("SHOW TABLE STATUS")->fetchAll();
                                foreach ($tables as $tbl):
                                ?>
                                    <tr>
                                        <td>
                                            <a href="?db=<?= urlencode($_SESSION['db_name']) ?>&table=<?= urlencode($tbl['Name']) ?>">
                                                <?= htmlspecialchars($tbl['Name']) ?>
                                            </a>
                                        </td>
                                        <td><?= number_format($tbl['Rows']) ?></td>
                                        <td><?= htmlspecialchars($tbl['Engine']) ?></td>
                                        <td><?= htmlspecialchars($tbl['Collation']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <h2>Welcome</h2>
                        <p>Select a database from the sidebar to get started.</p>
                    <?php endif; ?>

                    <!-- SQL Query Box -->
                    <?php if (!empty($_SESSION['db_name'])): ?>
                        <div class="query-box">
                            <h3>Execute SQL Query</h3>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="form-group">
                                    <textarea name="sql" placeholder="Enter SQL query..."><?= htmlspecialchars($_POST['sql'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" name="query">Execute</button>
                            </form>

                            <?php if ($queryError): ?>
                                <div class="error" style="margin-top: 15px;">
                                    <?= htmlspecialchars($queryError) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($queryResult && is_array($queryResult) && count($queryResult) > 0): ?>
                                <h4 style="margin-top: 20px;">Query Result:</h4>
                                <table>
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($queryResult[0]) as $col): ?>
                                                <th><?= htmlspecialchars($col) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($queryResult as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td><?= htmlspecialchars($value ?? '') ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif (isset($queryResult['success'])): ?>
                                <div class="success" style="margin-top: 15px;">Query executed successfully!</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
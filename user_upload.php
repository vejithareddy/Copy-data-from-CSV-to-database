<?php 

function showHelp() {
    echo "Usage: php user_upload.php [options]\n";
    echo "--file [csv file name] - CSV file to be parsed\n";
    echo "--create_table - Create the MySQL 'users' table and exit\n";
    echo "--dry_run - Run the script without inserting data into the database\n";
    echo "-u [MySQL username] - MySQL username\n";
    echo "-p [MySQL password] - MySQL password\n";
    echo "-h [MySQL host] - MySQL host\n";
    echo "--help - Display this help message\n";
}

// Parse command line options
$options = getopt("", ["file:", "create_table", "dry_run", "help"]);
if (empty($options) || isset($options['help'])) {
    showHelp();
    exit(0);
}

$csvFile = $options['file'];
$createTable = isset($options['create_table']);
$dryRun = isset($options['dry_run']);
$username = isset($options['u']) ? $options['u'] : "your_mysql_username";
$password = isset($options['p']) ? $options['p'] : "your_mysql_password";
$host = isset($options['h']) ? $options['h'] : "localhost";

// Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=mydatabase;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create or rebuild the 'users' table
if ($createTable) {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        surname VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE
    )";

    try {
        $pdo->exec($createTableSQL);
        echo "Table 'users' created or rebuilt.\n";
    } catch (PDOException $e) {
        die("Table creation failed: " . $e->getMessage());
    }

    exit(0);
}

// Read and process the CSV file
if (($handle = fopen($csvFile, "r")) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        $name = ucfirst(strtolower(trim($data[0])));
        $surname = ucfirst(strtolower(trim($data[1])));
        $email = strtolower(trim($data[2]));

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email format: $email\n";
            continue;
        }

        // Insert data into the database
        if (!$dryRun) {
            $insertSQL = "INSERT INTO users (name, surname, email) VALUES (:name, :surname, :email)";
            $stmt = $pdo->prepare($insertSQL);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':surname', $surname);
            $stmt->bindParam(':email', $email);

            try {
                $stmt->execute();
            } catch (PDOException $e) {
                echo "Error inserting record: $name, $surname, $email\n";
            }
        } else {
            echo "Dry run: Record would be inserted: $name, $surname, $email\n";
        }
    }
    fclose($handle);
}

echo "Script execution completed.\n";
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query
$sql = "INSERT INTO 
    entries (
        cardNumber, 
        product,
        cardType,
        availmentDate,
        surName,
        givenName,
        middleInitial,
        birthday,
        age,
        address,
        mobileNumber,
        emailAddress,
        beneficiary,
        relation,
        beneficiaryBirthday,
        coverageClause,
        effectiveDateClause,
        dataPrivacyClause,
        paymentMode,
        BPIA,
        SO,
        BUH,
        BH,
        SD
    ) 
    VALUES (
        'DC 0000 0000 0000 0002',
        'Student',
        'Digital',
        '2025-03-19',
        'SILOG',
        'TAP',
        '',
        '2000-05-15',
        24,
        '123 Main St, City, Country',
        '09123456789',
        'TAPSILOG@example.com',
        'HOT SILOG',
        'REL',
        '2005-06-20',
        1,
        1,
        1,
        'Monthly',
        1,
        0,
        1,
        0,
        1
    )";

if ($conn->query($sql) === TRUE) {
    echo "PASOK NA PASOK";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close connection
$conn->close();
?>

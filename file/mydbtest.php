<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>User List</title>
  <style>
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ddd;
    }
  </style>
</head>
<body>
  <h1>User List</h1>

  <?php
  require 'vendor/autoload.php';

  use Aws\DynamoDb\Exception\DynamoDbException;
  use Aws\Credentials\CredentialProvider;
  use Aws\DynamoDb\DynamoDbClient;

  $provider = CredentialProvider::ecsCredentials();
  // Be sure to memoize the credentials
  $memoizedProvider = CredentialProvider::memoize($provider);

  $dynamodb = new DynamoDbClient([
    'region' => 'ap-northeast-1',
    'version' => 'latest',
    'credentials' => $memoizedProvider
  ]);

  $Table_name = 'user_table';

  // Scan the entire table to retrieve all users (adjust for pagination if needed)
  try {
    $result = $dynamodb->scan([
      'TableName' => $Table_name
    ]);
  } catch (DynamoDbException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    exit;
  }

  // Check if there are any users in the table
  if (empty($result['Items'])) {
    echo "<p>No users found in the table.</p>";
  } else {
    // Display user data in a table
    echo "<table>";
    echo "<tr>";
    echo "<th>Email</th>";
    echo "<th>Name</th>";
    echo "<th>ID</th>";
    // Add more table headers for other desired user attributes
    echo "</tr>";

    foreach ($result['Items'] as $item) {
      echo "<tr>";
      echo "<td>" . $item['email']['S'] . "</td>";
      echo "<td>" . $item['name']['S'] . "</td>";
      echo "<td>" . $item['id']['S'] . "</td>";
      // Add more table cells for other user attributes
      echo "</tr>";
    }

    echo "</table>";
  }
  ?>
</body>
</html>